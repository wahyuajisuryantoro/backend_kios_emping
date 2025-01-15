<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Harga;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BarangController extends Controller
{
    public function index()
    {
        try {
            $barang = Barang::with(['kategori', 'stok', 'harga'])
                            ->orderBy('created_at', 'desc')
                            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data barang berhasil diambil',
                'data' => $barang->map(function ($item) {
                    return [
                        'kode_barang' => $item->kode_barang,
                        'nama_barang' => $item->nama_barang,
                        'kategori' => [
                            'id' => $item->kategori->id,
                            'nama_kategori' => $item->kategori->nama_kategori
                        ],
                        'merk_barang' => $item->merk_barang,
                        'deskripsi_barang' => $item->deskripsi_barang,
                        'berat_barang' => $item->berat_barang,
                        'volume_barang' => $item->volume_barang,
                        'satuan_barang' => $item->satuan_barang,
                        'gambar_barang' => $item->gambar_barang,
                        'tanggal_kadaluarsa' => $item->tanggal_kadaluarsa,
                        'stok' => [
                            'stok_awal' => $item->stok->stok_awal,
                            'stok_akhir' => $item->stok->stok_akhir,
                            'status_barang' => $item->stok->status_barang
                        ],
                        'harga' => [
                            'harga_beli' => $item->harga->harga_beli,
                            'harga_jual_eceran' => $item->harga->harga_jual_eceran,
                            'harga_jual_grosir' => $item->harga->harga_jual_grosir,
                            'diskon_eceran' => $item->harga->diskon_eceran,
                            'diskon_grosir' => $item->harga->diskon_grosir
                        ]
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data barang: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'kode_barang' => 'required|string|max:20|unique:tbl_barang,kode_barang',
            'nama_barang' => 'required|string|max:100',
            'kategori_id' => 'required|exists:tbl_kategori,id',
            'merk_barang' => 'nullable|string|max:50',
            'satuan_barang' => 'required|string|max:20',
            'stok_awal' => 'required|integer|min:0',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual_eceran' => 'required|numeric|min:0',
            'harga_jual_grosir' => 'required|numeric|min:0',
            'gambar_barang' => 'nullable|image|max:2048'
        ]);

        DB::beginTransaction();
        try {
            // Upload gambar jika ada
            $gambarPath = null;
            if ($request->hasFile('gambar_barang')) {
                $gambarPath = $request->file('gambar_barang')->store('barang');
            }

            // Create Barang
            $barang = Barang::create([
                'kode_barang' => $request->kode_barang,
                'nama_barang' => $request->nama_barang,
                'kategori_id' => $request->kategori_id,
                'merk_barang' => $request->merk_barang,
                'deskripsi_barang' => $request->deskripsi_barang,
                'berat_barang' => $request->berat_barang,
                'volume_barang' => $request->volume_barang,
                'satuan_barang' => $request->satuan_barang,
                'gambar_barang' => $gambarPath,
                'tanggal_kadaluarsa' => $request->tanggal_kadaluarsa
            ]);

            // Create Stok
            $stokAwal = $request->stok_awal;
            $stok = Stok::create([
                'kode_barang' => $barang->kode_barang,
                'stok_awal' => $stokAwal,
                'stok_akhir' => $stokAwal,
                'batas_minimum_stok' => $request->batas_minimum_stok,
                'lokasi_penyimpanan' => $request->lokasi_penyimpanan,
                'tanggal_stok' => now(),
                'status_barang' => $stokAwal <= 0 ? 'Habis' : ($request->batas_minimum_stok && $stokAwal <= $request->batas_minimum_stok ? 'Hampir Habis' : 'Tersedia')
            ]);

            // Create Harga
            $harga = Harga::create([
                'kode_barang' => $barang->kode_barang,
                'harga_beli' => $request->harga_beli,
                'harga_jual_eceran' => $request->harga_jual_eceran,
                'harga_jual_grosir' => $request->harga_jual_grosir,
                'min_qty_grosir' => $request->min_qty_grosir ?? 1,
                'diskon_eceran' => $request->diskon_eceran,
                'diskon_grosir' => $request->diskon_grosir,
                'tanggal_perubahan_harga' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data barang berhasil ditambahkan',
                'data' => [
                    'barang' => $barang,
                    'stok' => $stok,
                    'harga' => $harga
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data barang: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $kodeBarang)
    {
        $request->validate([
            'nama_barang' => 'required|string|max:100',
            'kategori_id' => 'required|exists:tbl_kategori,id',
            'merk_barang' => 'nullable|string|max:50',
            'satuan_barang' => 'required|string|max:20',
            'stok_awal' => 'required|integer|min:0',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual_eceran' => 'required|numeric|min:0',
            'harga_jual_grosir' => 'required|numeric|min:0',
            'gambar_barang' => 'nullable|image|max:2048'
        ]);

        DB::beginTransaction();
        try {
            $barang = Barang::findOrFail($kodeBarang);

            // Update gambar jika ada
            if ($request->hasFile('gambar_barang')) {
                // Hapus gambar lama
                if ($barang->gambar_barang) {
                    Storage::delete($barang->gambar_barang);
                }
                $gambarPath = $request->file('gambar_barang')->store('barang');
                $barang->gambar_barang = $gambarPath;
            }

            // Update Barang
            $barang->update([
                'nama_barang' => $request->nama_barang,
                'kategori_id' => $request->kategori_id,
                'merk_barang' => $request->merk_barang,
                'deskripsi_barang' => $request->deskripsi_barang,
                'berat_barang' => $request->berat_barang,
                'volume_barang' => $request->volume_barang,
                'satuan_barang' => $request->satuan_barang,
                'tanggal_kadaluarsa' => $request->tanggal_kadaluarsa
            ]);

            // Update Stok
            $stok = Stok::where('kode_barang', $kodeBarang)->first();
            if ($stok) {
                $stok->update([
                    'stok_awal' => $request->stok_awal,
                    'stok_akhir' => $request->stok_awal,
                    'batas_minimum_stok' => $request->batas_minimum_stok,
                    'lokasi_penyimpanan' => $request->lokasi_penyimpanan,
                    'status_barang' => $request->stok_awal <= 0 ? 'Habis' : ($request->batas_minimum_stok && $request->stok_awal <= $request->batas_minimum_stok ? 'Hampir Habis' : 'Tersedia')
                ]);
            }

            // Update Harga
            $harga = Harga::where('kode_barang', $kodeBarang)->first();
            if ($harga) {
                $harga->update([
                    'harga_beli' => $request->harga_beli,
                    'harga_jual_eceran' => $request->harga_jual_eceran,
                    'harga_jual_grosir' => $request->harga_jual_grosir,
                    'min_qty_grosir' => $request->min_qty_grosir ?? 1,
                    'diskon_eceran' => $request->diskon_eceran,
                    'diskon_grosir' => $request->diskon_grosir,
                    'tanggal_perubahan_harga' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data barang berhasil diperbarui',
                'data' => [
                    'barang' => $barang,
                    'stok' => $stok,
                    'harga' => $harga
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data barang: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($kodeBarang)
    {
        DB::beginTransaction();
        try {
            $barang = Barang::findOrFail($kodeBarang);

            // Hapus gambar jika ada
            if ($barang->gambar_barang) {
                Storage::delete($barang->gambar_barang);
            }

            // Hapus data terkait
            Stok::where('kode_barang', $kodeBarang)->delete();
            Harga::where('kode_barang', $kodeBarang)->delete();
            $barang->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data barang berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data barang: ' . $e->getMessage()
            ], 500);
        }
    }
}
