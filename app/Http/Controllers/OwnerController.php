<?php

namespace App\Http\Controllers;

use App\Models\BagiHasil;
use App\Models\Motor;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function storeMotor(Request $request)
    {
        $validated = $request->validate([
            'merek' => 'required|string|max:100',
            'tipe_cc' => 'required|in:100,125,150',
            'no_plat' => 'required|string|unique:motors,no_plat',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'documen_kepemilikan' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:2048',
        ]);
        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('motors', 'public')
            : null;
        $docPath = $request->hasFile('documen_kepemilikan')
            ? $request->file('documen_kepemilikan')->store('documents', 'public')
            : null;
        $motor = Motor::create([
            'pemilik_id' => auth('api')->id(),
            'merek' => $validated['merek'],
            'tipe_cc' => $validated['tipe_cc'],
            'no_plat' => $validated['no_plat'],
            'status' => 'menunggu_verifikasi',
            'foto' => $fotoPath,
            'documen_kepemilikan' => $docPath,
        ]);

        return response()->json([
            'message' => 'Motor berhasil didaftarkan dan menunggu verifikasi admin',
            'motor' => $motor,
        ], 201);
    }

    public function myMotors()
    {
        $motors = Motor::with('tarif')
            ->where('pemilik_id', auth('api')->id())
            ->get();

        return response()->json([
            'message' => 'Daftar motor Anda',
            'motors' => $motors,
        ]);
    }

    public function revenueReport()
    {
        $ownerId = auth('api')->id();

        $laporan = BagiHasil::with(['penyewaan.motor'])
            ->whereHas('penyewaan.motor', function ($query) use ($ownerId) {
                $query->where('pemilik_id', $ownerId);
            })
            ->get();
        $totalPendapatan = $laporan->sum('bagi_hasil_pemilik');

        return response()->json([
            'message' => 'Laporan pendapatan bagi hasil pemilik',
            'total_pendapatan' => $totalPendapatan,
            'rincian' => $laporan,
        ]);
    }
}
