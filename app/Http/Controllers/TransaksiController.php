<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\Barang;
use App\Models\Stok;
use App\Models\Harga;
use App\Models\TransaksiDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransaksiController extends Controller
{
    public function penjualan(Request $request)
    {
        try {
            $user = Auth::user();
            $toko = $user->toko;

            if (!$toko) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data toko tidak ditemukan'
                ], 404);
            }

            // Validasi request
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.kode_barang' => 'required|exists:tbl_barang,kode_barang',
                'items.*.quantity' => 'required|integer|min:1',
                'total' => 'required|numeric|min:0',
            ]);


            DB::beginTransaction();
            try {
                // Generate nomor transaksi
                $noTransaksi = 'TRX-' . date('YmdHis') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

                $itemNames = [];
                foreach ($request->items as $item) {
                    $barang = Barang::where('kode_barang', $item['kode_barang'])->first();
                    $itemNames[] = "{$barang->nama_barang} ({$item['quantity']})";
                }

                $keterangan = "Penjualan " . implode(", ", $itemNames) . " pada " . now()->format('d/m/Y H:i');

                // Buat header transaksi
                $transaksi = Transaksi::create([
                    'no_transaksi' => $noTransaksi,
                    'tanggal_transaksi' => now(),
                    'jenis_transaksi' => 'Penjualan',
                    'total_harga' => $request->total,
                    'total_diskon' => 0,
                    'grand_total' => $request->total,
                    'keterangan' => $keterangan
                ]);

                // Proses setiap item
                foreach ($request->items as $item) {
                    // Ambil data barang dan harga
                    $barang = Barang::where('kode_barang', $item['kode_barang'])
                        ->where('toko_id', $toko->id)
                        ->firstOrFail();

                    $harga = Harga::where('kode_barang', $item['kode_barang'])
                        ->latest('tanggal_perubahan_harga')
                        ->firstOrFail();

                    // Cek stok
                    $stok = Stok::where('kode_barang', $item['kode_barang'])
                        ->where('toko_id', $toko->id)
                        ->latest('tanggal_stok')
                        ->firstOrFail();

                    if ($stok->stok_akhir < $item['quantity']) {
                        throw new \Exception("Stok tidak mencukupi untuk {$barang->nama_barang}");
                    }

                    // Hitung harga berdasarkan quantity
                    $hargaSatuan = $item['quantity'] >= $harga->min_qty_grosir
                        ? $harga->harga_jual_grosir
                        : $harga->harga_jual_eceran;

                    // Buat detail transaksi
                    TransaksiDetails::create([
                        'no_transaksi' => $noTransaksi,
                        'kode_barang' => $item['kode_barang'],
                        'quantity' => $item['quantity'],
                        'harga_satuan' => $hargaSatuan,
                        'diskon' => 0,
                        'subtotal' => $hargaSatuan * $item['quantity']
                    ]);

                    // Update stok
                    Stok::create([
                        'toko_id' => $toko->id,
                        'kode_barang' => $item['kode_barang'],
                        'stok_awal' => $stok->stok_akhir,
                        'stok_keluar' => $item['quantity'],
                        'stok_akhir' => $stok->stok_akhir - $item['quantity'],
                        'tanggal_stok' => now(),
                        'status_barang' => $this->hitungStatusStok(
                            $stok->stok_akhir - $item['quantity'],
                            $stok->batas_minimum_stok
                        )
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi berhasil',
                    'data' => [
                        'no_transaksi' => $noTransaksi,
                        'total' => $request->total
                    ]
                ], 201);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    private function hitungStatusStok($stokAkhir, $batasMinimum)
    {
        if ($stokAkhir <= 0) {
            return 'Habis';
        }
        if ($batasMinimum && $stokAkhir <= $batasMinimum) {
            return 'Hampir Habis';
        }
        return 'Tersedia';
    }

    // Endpoint untuk mendapatkan detail transaksi
    public function show($noTransaksi)
    {
        try {
            $transaksi = Transaksi::with(['details.barang'])
                ->where('no_transaksi', $noTransaksi)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $transaksi
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }
    }
}
