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

    public function index(Request $request)
    {
        try {
            $query = Barang::with([
                'kategori',
                'stok' => function ($query) {
                    $query->select('kode_barang', 'stok_awal', 'status_barang');
                },
                'harga' => function ($query) {
                    $query->select('kode_barang', 'harga_beli');
                }
            ])->select([
                'kode_barang',
                'nama_barang',
                'kategori_id',
                'gambar_barang',
                'tanggal_kadaluarsa'
            ]);


            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                });
            }


            if ($request->has('kategori_id')) {
                $query->where('kategori_id', $request->kategori_id);
            }


            $perPage = $request->per_page ?? 10;
            $barang = $query->latest('created_at')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $barang->items(),
                'current_page' => $barang->currentPage(),
                'last_page' => $barang->lastPage(),
                'total' => $barang->total(),
                'per_page' => $barang->perPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan dalam memuat data barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($kodeBarang)
    {
        try {
            $barang = Barang::with([
                'kategori',
                'stok' => function ($query) {
                    $query->latest('tanggal_stok')->first();
                },
                'harga' => function ($query) {
                    $query->latest('tanggal_perubahan_harga')->first();
                }
            ])->where('kode_barang', $kodeBarang)->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $barang
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak ditemukan'
            ], 404);
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

            $gambarPath = null;
            if ($request->hasFile('gambar_barang')) {
                $gambarPath = $request->file('gambar_barang')->store('barang');
            }


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


            if ($request->hasFile('gambar_barang')) {

                if ($barang->gambar_barang) {
                    Storage::delete($barang->gambar_barang);
                }
                $gambarPath = $request->file('gambar_barang')->store('barang');
                $barang->gambar_barang = $gambarPath;
            }


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

            if ($barang->gambar_barang) {
                Storage::delete($barang->gambar_barang);
            }

            Stok::where('kode_barang', $kodeBarang)->delete();
            Harga::where('kode_barang', $kodeBarang)->delete();
            $barang->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil dihapus'
            ], 200); 
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus produk: ' . $e->getMessage()
            ], 500);
        }
    }
}
