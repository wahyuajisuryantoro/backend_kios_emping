<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Harga;
use App\Models\Satuan;
use App\Models\Stok;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\TransaksiDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TransaksiController extends Controller
{
    /**
     * Mengambil data barang dengan harga dan stok
     */
    public function getBarang(Request $request)
    {
        try {
            $barang = Barang::with(['harga', 'stok', 'satuanDasar'])
                ->whereHas('stok', function($q) {
                    $q->where('stok_akhir', '>', 0);
                })
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $barang
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Membuat transaksi penjualan baru
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array',
                'items.*.kode_barang' => 'required|exists:tbl_barang,kode_barang',
                'items.*.quantity' => 'required|numeric|min:0',
                'items.*.satuan_id' => 'required|exists:tbl_satuan,id',
                'items.*.harga_satuan' => 'required|numeric|min:0',
                'items.*.is_grosir' => 'required|boolean',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }
    
            // Generate nomor transaksi
            $noTransaksi = 'TRX-' . date('YmdHis');
            
            // Simpan transaksi TERLEBIH DAHULU
            $transaksi = new Transaksi();
            $transaksi->no_transaksi = $noTransaksi;
            $transaksi->toko_id = auth()->user()->toko->id;
            $transaksi->user_id = auth()->id();
            $transaksi->tanggal_transaksi = now();
            $transaksi->jenis_transaksi = 'Penjualan';
            
            // Inisialisasi total
            $totalHarga = 0;
            $totalDiskon = 0;
    
            // Proses setiap item
            foreach ($request->items as $item) {
                $barang = Barang::with(['harga', 'stok', 'satuanDasar'])->find($item['kode_barang']);
                
                // Konversi quantity ke satuan dasar
                $qtyDasar = $this->konversiKeSatuanDasar(
                    $item['quantity'], 
                    $item['satuan_id'], 
                    $barang->satuan_dasar_id
                );
                
                // Cek stok
                if ($barang->stok->stok_akhir < $qtyDasar) {
                    throw new \Exception("Stok {$barang->nama_barang} tidak mencukupi");
                }
                
                // Hitung subtotal
                $hargaSatuan = $item['harga_satuan'];
                $subtotal = $hargaSatuan * $item['quantity'];
                
                // Hitung diskon jika ada
                $diskon = 0;
                if ($item['is_grosir'] && $item['quantity'] >= $barang->harga->min_qty_grosir) {
                    $diskon = $barang->harga->diskon_grosir ?? 0;
                } else {
                    $diskon = $barang->harga->diskon_eceran ?? 0;
                }
                
                $totalDiskon += ($subtotal * $diskon / 100);
                $totalHarga += $subtotal;
            }
    
            // Set total transaksi
            $transaksi->total_harga = $totalHarga;
            $transaksi->total_diskon = $totalDiskon;
            $transaksi->grand_total = $totalHarga - $totalDiskon;
            
            // SIMPAN TRANSAKSI SEBELUM DETAIL
            $transaksi->save();
    
            // Proses detail transaksi SETELAH transaksi disimpan
            foreach ($request->items as $item) {
                $barang = Barang::with(['harga', 'stok', 'satuanDasar'])->find($item['kode_barang']);
                
                $qtyDasar = $this->konversiKeSatuanDasar(
                    $item['quantity'], 
                    $item['satuan_id'], 
                    $barang->satuan_dasar_id
                );
                
                $hargaSatuan = $item['harga_satuan'];
                $subtotal = $hargaSatuan * $item['quantity'];
                
                $diskon = 0;
                if ($item['is_grosir'] && $item['quantity'] >= $barang->harga->min_qty_grosir) {
                    $diskon = $barang->harga->diskon_grosir ?? 0;
                } else {
                    $diskon = $barang->harga->diskon_eceran ?? 0;
                }
                
                // Simpan detail transaksi
                $detail = new TransaksiDetails([
                    'no_transaksi' => $transaksi->no_transaksi, // Gunakan no_transaksi dari model yang tersimpan
                    'kode_barang' => $item['kode_barang'],
                    'quantity' => $item['quantity'],
                    'satuan_transaksi_id' => $item['satuan_id'],
                    'harga_satuan' => $hargaSatuan,
                    'diskon' => $diskon,
                    'subtotal' => $subtotal - ($subtotal * $diskon / 100)
                ]);
                
                // Update stok
                $barang->stok->stok_keluar += $qtyDasar;
                $barang->stok->stok_akhir -= $qtyDasar;
                $barang->stok->save();
                
                $transaksi->detail()->save($detail);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'data' => $transaksi->load('detail')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Konversi quantity antar satuan
     */
    private function konversiKeSatuanDasar($qty, $satuanAwalId, $satuanTujuanId)
    {
        $satuanAwal = Satuan::find($satuanAwalId);
        $satuanTujuan = Satuan::find($satuanTujuanId);
        
        if ($satuanAwal->tipe_satuan != $satuanTujuan->tipe_satuan) {
            throw new \Exception('Tipe satuan tidak sesuai');
        }
        
        return $qty * ($satuanAwal->nilai_konversi / $satuanTujuan->nilai_konversi);
    }

    /**
     * Mendapatkan harga berdasarkan quantity dan tipe penjualan
     */
    public function getHarga(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'kode_barang' => 'required|exists:tbl_barang,kode_barang',
                'quantity' => 'required|numeric|min:0',
                'is_grosir' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            $harga = Harga::where('kode_barang', $request->kode_barang)->first();
            
            $hargaJual = $request->is_grosir && $request->quantity >= $harga->min_qty_grosir 
                ? $harga->harga_jual_grosir 
                : $harga->harga_jual_eceran;
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'harga' => $hargaJual,
                    'min_qty_grosir' => $harga->min_qty_grosir
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
