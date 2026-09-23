<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
class AuthController extends Controller
{
    public function  register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'no_tlpn' => 'required|string|max:20',
            'role' => 'required|in:penyewa,pemilik',
            'password' => 'required|string|min:8',
        ]);
        $user = User::create([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'no_tlpn' => $validated['no_tlpn'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);
        $token = auth('api')->login($user);
        return response()->json([
            'message'   => 'registrasi berhasil',
            'user'      => $user,
            'token'     => $token,
            'token_type' => 'bearer',
        ], 201);
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }
        return response()->json([
            'message'   => 'login berhasil',
            'user'      => auth('api')->user(),
            'token'     => $token,
            'token_type' => 'bearer',
        ]);
    }
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Berhasil logout']);
    }
}
