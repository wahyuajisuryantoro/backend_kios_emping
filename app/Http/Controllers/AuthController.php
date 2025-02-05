<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'nama_toko' => 'required',
            'alamat' => 'nullable',
            'telepon' => 'nullable'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password
        ]);

        $toko = Toko::create([
            'user_id' => $user->id,
            'nama_toko' => $request->nama_toko,
            'alamat' => $request->alamat,
            'telepon' => $request->telepon
        ]);
        $token = $user->createToken('auth-token')->plainTextToken;
        return response()->json([
            'message' => 'Registrasi berhasil',
            'user' => $user,
            'toko' => $toko,
            'token' => $token
        ]);
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);
            if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'Email atau password salah'
                ], 401);
            }   
            $user = User::where('email', $request->email)->firstOrFail();
            $toko = $user->toko;
            $user->tokens()->delete();
            $token = $user->createToken('auth-token')->plainTextToken;
            Log::info('Login successful', [
                'user_id' => $user->id,
                'token' => $token
            ]);
    
            return response()->json([
                'message' => 'Login berhasil',
                'user' => $user,
                'toko' => $toko,
                'token' => $token 
            ]);
    
        } catch (\Exception $e) {
            Log::error('Login Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}
