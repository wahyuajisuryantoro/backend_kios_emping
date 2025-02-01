<?php

namespace App\Http\Controllers;

use App\Models\Harga;
use App\Models\Stok;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function penjualan(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.kode_barang' => 'required|exists:tbl_barang,kode_barang',
            'items.*.quantity' => 'required|integer|min:1',
            'no_referensi' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate nomor transaksi
            $today = Carbon::now();
            $noTransaksi = 'PJ-' . $today->format('Ymd') . '-' . 
                          str_pad(Transaksi::whereDate('created_at', $today)->count() + 1, 3, '0', STR_PAD_LEFT);

            // Inisialisasi total
            $totalHarga = 0;
            $totalDiskon = 0;

            // Proses setiap item
            $items = collect($request->items)->map(function ($item) use (&$totalHarga, &$totalDiskon) {
                // Ambil harga terkini
                $harga = Harga::where('kode_barang', $item['kode_barang'])
                    ->latest('tanggal_perubahan_harga')
                    ->first();

                // Tentukan harga dan diskon berdasarkan quantity
                $hargaSatuan = $item['quantity'] >= $harga->min_qty_grosir 
                    ? $harga->harga_jual_grosir 
                    : $harga->harga_jual_eceran;
                
                $diskon = $item['quantity'] >= $harga->min_qty_grosir 
                    ? $harga->diskon_grosir 
                    : $harga->diskon_eceran;

                $subtotal = $hargaSatuan * $item['quantity'];
                $diskonNominal = ($diskon ? ($subtotal * $diskon / 100) : 0);

                $totalHarga += $subtotal;
                $totalDiskon += $diskonNominal;

                return [
                    'kode_barang' => $item['kode_barang'],
                    'quantity' => $item['quantity'],
                    'harga_satuan' => $hargaSatuan,
                    'diskon' => $diskon,
                    'subtotal' => $subtotal - $diskonNominal
                ];
            });

            // Buat transaksi
            $transaksi = Transaksi::create([
                'no_transaksi' => $noTransaksi,
                'tanggal_transaksi' => $today,
                'jenis_transaksi' => 'Penjualan',
                'no_referensi' => $request->no_referensi,
                'total_harga' => $totalHarga,
                'total_diskon' => $totalDiskon,
                'grand_total' => $totalHarga - $totalDiskon,
                'keterangan' => $request->keterangan
            ]);

            // Simpan detail transaksi
            $transaksi->details()->createMany($items);

            // Update stok
            foreach ($items as $item) {
                $stok = Stok::firstOrCreate(
                    [
                        'kode_barang' => $item['kode_barang'],
                        'tanggal_stok' => $today->toDateString()
                    ],
                    [
                        'stok_awal' => Stok::where('kode_barang', $item['kode_barang'])
                            ->where('tanggal_stok', '<', $today->toDateString())
                            ->latest('tanggal_stok')
                            ->value('stok_akhir') ?? 0,
                        'stok_masuk' => 0,
                        'stok_keluar' => 0,
                        'stok_akhir' => 0
                    ]
                );

                $stok->stok_keluar += $item['quantity'];
                $stok->stok_akhir = $stok->stok_awal + $stok->stok_masuk - $stok->stok_keluar;
                
                // Update status stok
                $stok->status_barang = $this->getStatusStok($stok->stok_akhir, $stok->batas_minimum_stok);
                $stok->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'data' => [
                    'transaksi' => $transaksi->load('details.barang'),
                    'struk' => $this->generateStruk($transaksi)
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getStatusStok($stokAkhir, $batasMinimum)
    {
        if ($stokAkhir <= 0) {
            return 'Habis';
        } elseif ($batasMinimum && $stokAkhir <= $batasMinimum) {
            return 'Hampir Habis';
        }
        return 'Tersedia';
    }


    private function generateStruk($transaksi)
    {
        // Implementasi generate struk
        // Bisa menggunakan package seperti barryvdh/laravel-dompdf
        return null;
    }

    public function show($noTransaksi)
    {
        $transaksi = Transaksi::with('details.barang')
            ->findOrFail($noTransaksi);

        return response()->json([
            'success' => true,
            'data' => $transaksi
        ]);
    }

    public function getLaporanHarian(Request $request)
    {
        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());

        $laporan = Transaksi::with('details.barang')
            ->whereDate('tanggal_transaksi', $tanggal)
            ->where('jenis_transaksi', 'Penjualan')
            ->get()
            ->pipe(function ($transaksi) {
                return [
                    'total_transaksi' => $transaksi->count(),
                    'total_penjualan' => $transaksi->sum('grand_total'),
                    'total_diskon' => $transaksi->sum('total_diskon'),
                    'detail_transaksi' => $transaksi
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $laporan
        ]);
    }
}
