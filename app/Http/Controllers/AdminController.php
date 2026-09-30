<?php

namespace App\Http\Controllers;

use App\Models\BagiHasil;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function verifyMotor(Request $request, $id)
    {
        $motor = Motor::findOrFail($id);
        $validated = $request->validate([
            'tarif_harian' => 'required|numeric|min:0',
            'tarif_mingguan' => 'required|numeric|min:0',
            'tarif_bulanan' => 'required|numeric|min:0',
        ]);
        $motor->update(['status' => 'tersedia']);
        $tarif = TarifRental::updateOrCreate(
            ['motor_id' => $motor->id],
            [
                'tarif_harian' => $validated['tarif_harian'],
                'tarif_mingguan' => $validated['tarif_mingguan'],
                'tarif_bulanan' => $validated['tarif_bulanan'],
            ]
        );

        return response()->json([
            'message' => 'Motor berhasil diverifikasi dan tarif telah ditentukan',
            'motor' => $motor->load('tarif'),
        ]);
    }

    public function confirmBooking(Request $request, $id)
    {
        $penyewaan = Penyewaan::with('transaksi')->findOrFail($id);
        if (! $penyewaan->transaksi || $penyewaan->transaksi->status !== 'berhasil') {
            return response()->json(['message' => 'pesanan belum dibayar oleh penyewa'], 400);
        }
        $penyewaan->update(['status' => 'dikonfirmasi']);
        $totalBayar = $penyewaan->harga;
        $porsiPemilik = $totalBayar * 0.80;
        $porsiAdmin = $totalBayar * 0.20;
        $bagiHasil = BagiHasil::updateOrCreate(
            ['pemesanan_id' => $penyewaan->id],
            [
                'bagi_hasil_pemilik' => $porsiPemilik,
                'bagi_hasil_admin' => $porsiAdmin,
                'tanggal' => now()->toDateString(),
            ]
        );

        return response()->json([
            'message' => 'penyewaan berhasil dikonfirmasi, status motor kini disewa dan bagi hasil telah tercatat',
            'penyewaan' => $penyewaan->fresh(['motor']),
            'bagi_hasil' => $bagiHasil,
        ]);
    }

    public function returnBooking($id)
    {
        $penyewaan = Penyewaan::findOrFail($id);
        $penyewaan->update(['status' => 'selesai']);
        if ($penyewaan->bagiHasil) {
            $penyewaan->bagiHasil->update(['settled_at' => now()]);
        }

        return response()->json([
            'message' => 'pengembalian motor berhasil dikonfirmasi. status motor kini kembali tersedia',
            'penyewaan' => $penyewaan->fresh(['motor']),
        ]);
    }

    public function revenueReport()
    {
        $laporan = BagiHasil::with(['penyewaan.motor', 'penyewaan.penyewa'])->get();
        $totalAdmin = $laporan->sum('bagi_hasil_admin');
        $totalPemilik = $laporan->sum('bagi_hasil_pemilik');
        $totalOmset = $totalAdmin + $totalPemilik;

        return response()->json([
            'message' => 'laporan pendapatan admin dan bagi hasil',
            'total_omset' => $totalOmset,
            'total_bagi_hasil_admin' => $totalAdmin,
            'total_bagi_hasil_pemilik' => $totalPemilik,
            'rincian' => $laporan,
        ]);
    }
}
