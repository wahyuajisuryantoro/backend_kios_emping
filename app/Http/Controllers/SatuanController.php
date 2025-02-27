<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    public function index()
    {
        $satuanList = Satuan::all();
        return response()->json($satuanList);
    }
    
    public function getSatuanByTipe($tipe)
    {
        $satuan = Satuan::where('tipe_satuan', $tipe)
                        ->select('id', 'nama_satuan', 'nilai_konversi', 'simbol')
                        ->get();
                        
        return response()->json([
            'success' => true,
            'data' => $satuan
        ]);
    }
}
