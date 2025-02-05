<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Harga;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BarangController extends Controller
{

    public function index(Request $request)
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
            $query = Barang::with([
                'kategori',
                'stok' => function ($query) {
                    $query->select('kode_barang', 'stok_awal', 'status_barang');
                },
                'harga' => function ($query) {
                    $query->select('kode_barang', 'harga_beli');
                }
            ])->where('toko_id', $toko->id)
                ->select([
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
            $user = Auth::user();
            $toko = $user->toko;

            if (!$toko) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data toko tidak ditemukan'
                ], 404);
            }

            $barang = Barang::with([
                'kategori',
                'stok' => function ($query) {
                    $query->select(
                        'kode_barang',
                        'stok_awal',
                        'stok_masuk',
                        'stok_keluar',
                        'stok_akhir',
                        'batas_minimum_stok',
                        'lokasi_penyimpanan',
                        'status_barang'
                    );
                },
                'harga' => function ($query) {
                    $query->select(
                        'kode_barang',
                        'harga_beli',
                        'harga_jual_eceran',
                        'harga_jual_grosir',
                        'min_qty_grosir',
                        'diskon_eceran',
                        'diskon_grosir'
                    );
                }
            ])
                ->where('kode_barang', $kodeBarang)
                ->where('toko_id', $toko->id)
                ->firstOrFail();

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
        try {
            $user = Auth::user();
            $toko = $user->toko;

            if (!$toko) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data toko tidak ditemukan'
                ], 404);
            }

            $request->validate([
                'kode_barang' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('tbl_barang')->where(function ($query) use ($toko) {
                        return $query->where('toko_id', $toko->id);
                    })
                ],
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
                    'toko_id' => $toko->id,
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
                    'toko_id' => $toko->id,
                    'kode_barang' => $barang->kode_barang,
                    'stok_awal' => $stokAwal,
                    'stok_masuk' => 0,
                    'stok_keluar' => 0,
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
                if ($gambarPath) {
                    Storage::delete($gambarPath);
                }

                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data barang: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $kodeBarang)
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
                $barang = Barang::where('kode_barang', $kodeBarang)
                    ->where('toko_id', $toko->id)
                    ->firstOrFail();
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
                $stok = Stok::where('kode_barang', $kodeBarang)
                    ->where('toko_id', $toko->id)
                    ->first();

                if ($stok) {
                    $stok->update([
                        'stok_awal' => $request->stok_awal,
                        'stok_akhir' => $request->stok_awal,
                        'batas_minimum_stok' => $request->batas_minimum_stok,
                        'lokasi_penyimpanan' => $request->lokasi_penyimpanan,
                        'status_barang' => $request->stok_awal <= 0 ? 'Habis' : ($request->batas_minimum_stok && $request->stok_awal <= $request->batas_minimum_stok ?
                                'Hampir Habis' : 'Tersedia')
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
                if (isset($gambarPath)) {
                    Storage::delete($gambarPath);
                }
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data barang: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function destroy($kodeBarang)
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
    
            DB::beginTransaction();
            try {
                $barang = Barang::where('kode_barang', $kodeBarang)
                               ->where('toko_id', $toko->id)
                               ->firstOrFail();
    
                if ($barang->gambar_barang) {
                    Storage::delete($barang->gambar_barang);
                }
    
                Stok::where('kode_barang', $kodeBarang)
                    ->where('toko_id', $toko->id)
                    ->delete();
                    
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
