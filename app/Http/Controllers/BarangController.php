<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Harga;
use App\Models\Satuan;
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

            $perPage = $request->per_page ?? 10;

            $barang = Barang::where('toko_id', $toko->id)
                ->select([
                    'kode_barang',
                    'nama_barang',
                    'kategori_id',
                    'gambar_barang',
                    'tanggal_kadaluarsa'
                ])
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nama_barang', 'like', "%{$search}%")
                            ->orWhere('kode_barang', 'like', "%{$search}%");
                    });
                })
                ->when($request->kategori_id, function ($query, $kategoriId) {
                    $query->whereHas('kategori', function ($q) use ($kategoriId) {
                        $q->where('id', $kategoriId);
                    });
                })
                ->withCount(['stok', 'harga'])
                ->with(['kategori', 'stok', 'harga'])
                ->latest('created_at')
                ->paginate($perPage);

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
                        'satuan_input_id',
                        'satuan_akhir_id',
                        'batas_minimum_stok',
                        'lokasi_penyimpanan',
                        'status_barang'
                    )->with([
                        'satuanInput' => function ($q) {
                            $q->select('id', 'nama_satuan', 'simbol', 'tipe_satuan', 'nilai_konversi');
                        },
                        'satuanAkhir' => function ($q) {
                            $q->select('id', 'nama_satuan', 'simbol', 'tipe_satuan', 'nilai_konversi');
                        }
                    ]);
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
                'message' => 'Barang tidak ditemukan: ' . $e->getMessage()
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

            // Validasi request
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
                'tipe_perhitungan' => 'required|in:berat,unit,isi',
                'satuan_dasar_id' => 'required|exists:tbl_satuan,id',
                'satuan_input_id' => 'required|exists:tbl_satuan,id',
                'jumlah_input' => 'required|numeric|min:0',
                'harga_beli' => 'required|numeric|min:0',
                'harga_jual_eceran' => 'required|numeric|min:0',
                'harga_jual_grosir' => 'required|numeric|min:0',
                'min_qty_grosir' => 'required|numeric|min:1',
                'batas_minimum_stok' => 'nullable|numeric|min:0',
                'gambar_barang' => 'nullable|image|max:2048'
            ]);

            DB::beginTransaction();
            try {
                $gambarPath = null;
                if ($request->hasFile('gambar_barang')) {
                    $gambarPath = $request->file('gambar_barang')->store('barang');
                }

                $satuanInput = Satuan::findOrFail($request->satuan_input_id);
                $satuanDasar = Satuan::findOrFail($request->satuan_dasar_id);

                if (
                    $satuanInput->tipe_satuan !== $request->tipe_perhitungan ||
                    $satuanDasar->tipe_satuan !== $request->tipe_perhitungan
                ) {
                    throw new \Exception('Tipe satuan tidak sesuai dengan tipe perhitungan');
                }

                $nilaiDasar = $this->konversiKeSatuanDasar(
                    $request->jumlah_input,
                    $satuanInput->nilai_konversi,
                    $satuanDasar->nilai_konversi
                );

                $barang = Barang::create([
                    'kode_barang' => $request->kode_barang,
                    'toko_id' => $toko->id,
                    'nama_barang' => $request->nama_barang,
                    'kategori_id' => $request->kategori_id,
                    'tipe_perhitungan' => $request->tipe_perhitungan,
                    'satuan_dasar_id' => $request->satuan_dasar_id,
                    'nilai_barang' => $nilaiDasar,
                    'merk_barang' => $request->merk_barang,
                    'deskripsi_barang' => $request->deskripsi_barang,
                    'gambar_barang' => $gambarPath,
                    'tanggal_kadaluarsa' => $request->tanggal_kadaluarsa
                ]);

                $stok = Stok::create([
                    'toko_id' => $toko->id,
                    'kode_barang' => $barang->kode_barang,
                    'satuan_input_id' => $request->satuan_input_id,
                    'stok_awal' => $request->jumlah_input,
                    'stok_masuk' => 0,
                    'stok_keluar' => 0,
                    'stok_akhir' => $nilaiDasar,
                    'satuan_akhir_id' => $request->satuan_dasar_id,
                    'batas_minimum_stok' => $request->batas_minimum_stok,
                    'lokasi_penyimpanan' => $request->lokasi_penyimpanan,
                    'tanggal_stok' => now(),
                    'status_barang' => $this->hitungStatusStok($nilaiDasar, $request->batas_minimum_stok)
                ]);

                $harga = Harga::create([
                    'kode_barang' => $barang->kode_barang,
                    'satuan_jual_id' => $request->satuan_dasar_id,
                    'harga_beli' => $request->harga_beli,
                    'harga_jual_eceran' => $request->harga_jual_eceran,
                    'harga_jual_grosir' => $request->harga_jual_grosir,
                    'min_qty_grosir' => $request->min_qty_grosir,
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
                        'harga' => $harga,
                        'konversi' => [
                            'input' => "{$request->jumlah_input} {$satuanInput->nama_satuan}",
                            'hasil' => "{$nilaiDasar} {$satuanDasar->nama_satuan}"
                        ]
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

            // Validasi request
            $request->validate([
                'nama_barang' => 'required|string|max:100',
                'kategori_id' => 'required|exists:tbl_kategori,id',
                'tipe_perhitungan' => 'required|in:berat,unit,isi',
                'satuan_dasar_id' => 'required|exists:tbl_satuan,id',
                'satuan_input_id' => 'required|exists:tbl_satuan,id',
                'jumlah_input' => 'required|numeric|min:0',
                'harga_beli' => 'required|numeric|min:0',
                'harga_jual_eceran' => 'required|numeric|min:0',
                'harga_jual_grosir' => 'required|numeric|min:0',
                'min_qty_grosir' => 'required|numeric|min:1',
                'batas_minimum_stok' => 'nullable|numeric|min:0',
                'gambar_barang' => 'nullable|image|max:2048'
            ]);

            DB::beginTransaction();
            try {
                $barang = Barang::where('kode_barang', $kodeBarang)
                    ->where('toko_id', $toko->id)
                    ->firstOrFail();

                $gambarPath = $barang->gambar_barang;
                if ($request->hasFile('gambar_barang')) {
                    if ($barang->gambar_barang) {
                        Storage::delete($barang->gambar_barang);
                    }
                    $gambarPath = $request->file('gambar_barang')->store('barang');
                }

                $satuanInput = Satuan::findOrFail($request->satuan_input_id);
                $satuanDasar = Satuan::findOrFail($request->satuan_dasar_id);

                if (
                    $satuanInput->tipe_satuan !== $request->tipe_perhitungan ||
                    $satuanDasar->tipe_satuan !== $request->tipe_perhitungan
                ) {
                    throw new \Exception('Tipe satuan tidak sesuai dengan tipe perhitungan');
                }

                $nilaiDasar = $this->konversiKeSatuanDasar(
                    $request->jumlah_input,
                    $satuanInput->nilai_konversi,
                    $satuanDasar->nilai_konversi
                );

                $barang->update([
                    'nama_barang' => $request->nama_barang,
                    'kategori_id' => $request->kategori_id,
                    'tipe_perhitungan' => $request->tipe_perhitungan,
                    'satuan_dasar_id' => $request->satuan_dasar_id,
                    'nilai_barang' => $nilaiDasar,
                    'merk_barang' => $request->merk_barang,
                    'deskripsi_barang' => $request->deskripsi_barang,
                    'gambar_barang' => $gambarPath,
                    'tanggal_kadaluarsa' => $request->tanggal_kadaluarsa
                ]);

                $stok = Stok::where('kode_barang', $kodeBarang)
                    ->where('toko_id', $toko->id)
                    ->firstOrFail();

                $stok->update([
                    'satuan_input_id' => $request->satuan_input_id,
                    'stok_awal' => $request->jumlah_input,
                    'stok_akhir' => $nilaiDasar,
                    'satuan_akhir_id' => $request->satuan_dasar_id,
                    'batas_minimum_stok' => $request->batas_minimum_stok,
                    'lokasi_penyimpanan' => $request->lokasi_penyimpanan,
                    'tanggal_stok' => now(),
                    'status_barang' => $this->hitungStatusStok($nilaiDasar, $request->batas_minimum_stok)
                ]);

                $harga = Harga::where('kode_barang', $kodeBarang)->firstOrFail();
                $harga->update([
                    'satuan_jual_id' => $request->satuan_dasar_id,
                    'harga_beli' => $request->harga_beli,
                    'harga_jual_eceran' => $request->harga_jual_eceran,
                    'harga_jual_grosir' => $request->harga_jual_grosir,
                    'min_qty_grosir' => $request->min_qty_grosir,
                    'diskon_eceran' => $request->diskon_eceran,
                    'diskon_grosir' => $request->diskon_grosir,
                    'tanggal_perubahan_harga' => now()
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Data barang berhasil diperbarui',
                    'data' => [
                        'barang' => $barang,
                        'stok' => $stok,
                        'harga' => $harga,
                        'konversi' => [
                            'input' => "{$request->jumlah_input} {$satuanInput->nama_satuan}",
                            'hasil' => "{$nilaiDasar} {$satuanDasar->nama_satuan}"
                        ]
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                if (isset($gambarPath) && $gambarPath !== $barang->gambar_barang) {
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
                $stok = Stok::where('kode_barang', $kodeBarang)
                    ->where('toko_id', $toko->id)
                    ->first();

                $harga = Harga::where('kode_barang', $kodeBarang)
                    ->first();

                if ($barang->gambar_barang) {
                    Storage::delete($barang->gambar_barang);
                }
                if ($stok) {
                    $stok->delete();
                }
                if ($harga) {
                    $harga->delete();
                }
                $barang->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Barang berhasil dihapus'
                ], 200);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus barang: ' . $e->getMessage()
            ], 500);
        }
    }


    private function konversiKeSatuanDasar($nilai, $nilaiKonversiAwal, $nilaiKonversiDasar)
    {
        return ($nilai * $nilaiKonversiAwal) / $nilaiKonversiDasar;
    }

    private function hitungStatusStok($stokAkhir, $batasMinimum)
    {
        if ($stokAkhir <= 0) {
            return 'Habis';
        } elseif ($stokAkhir <= $batasMinimum) {
            return 'Hampir Habis';
        }
        return 'Tersedia';
    }
}
