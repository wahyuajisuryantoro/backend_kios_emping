<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KategoriController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan'
                ], 401);
            }

            $toko = $user->toko;
            if (!$toko) {
                return response()->json([
                    'message' => 'Toko tidak ditemukan'
                ], 404);
            }

            $kategori = $toko->kategori;
            return response()->json($kategori);
        } catch (\Exception $e) {
            Log::error('Kategori Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $toko = $user->toko;
            
            if (!$toko) {
                return response()->json([
                    'message' => 'Toko tidak ditemukan'
                ], 404);
            }
            $validated = $request->validate([
                'nama_kategori' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('tbl_kategori', 'nama_kategori')
                        ->where('toko_id', $toko->id)
                        ->whereNull('deleted_at')
                ],
                'deskripsi' => 'nullable|string|max:1000'
            ], [
                'nama_kategori.required' => 'Nama kategori harus diisi',
                'nama_kategori.unique' => 'Nama kategori sudah digunakan di toko Anda'
            ]);
    
            $kategori = Kategori::create([
                'nama_kategori' => $validated['nama_kategori'],
                'deskripsi' => $validated['deskripsi'],
                'toko_id' => $toko->id
            ]);
    
            Log::info('Kategori created:', [
                'kategori_id' => $kategori->id,
                'toko_id' => $toko->id,
                'user_id' => $user->id
            ]);
    
            return response()->json([
                'message' => 'Kategori berhasil dibuat',
                'data' => $kategori
            ], 201);
    
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating kategori:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat kategori'
            ], 500);
        }
    }
    
    public function show(Request $request, $id)
    {
        $toko = $request->user()->toko;
        $kategori = Kategori::where('toko_id', $toko->id)
            ->where('id', $id)
            ->firstOrFail();
        return response()->json($kategori);
    }

    public function update(Request $request, $id)
    {
        $toko = $request->user()->toko;
        $kategori = Kategori::where('toko_id', $toko->id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'deskripsi' => 'nullable|string'
        ]);

        $kategori->update($request->only(['nama_kategori', 'deskripsi']));
        return response()->json($kategori);
    }

    public function destroy(Request $request, $id)
    {
        $toko = $request->user()->toko;
        $kategori = Kategori::where('toko_id', $toko->id)
            ->where('id', $id)
            ->firstOrFail();

        $kategori->delete();
        return response()->json(null, 204);
    }
}
