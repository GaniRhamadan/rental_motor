<?php

namespace App\Http\Controllers;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function availableMotors()
    {
        $motors = Motor::with('tarif')
            ->where('status', 'tersedia')
            ->get();

        return response()->json([
            'message' => 'Daftar motor tersedia',
            'motors' => $motors,
        ]);
    }

    public function createBooking(Request $request)
    {
        $validated = $request->validate([
            'motor_id' => 'required|exists:motors,id',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tipe_durasi' => 'required|in:harian,mingguan,bulanan',
        ]);
        $motor = Motor::with('tarif')->findOrFail($validated['motor_id']);

        if ($motor->status !== 'tersedia' || ! $motor->tarif) {
            return response()->json(['message' => 'Motor tidak tersedia untuk disewa'], 400);
        }
        $mulai = Carbon::parse($validated['tanggal_mulai']);

        if ($validated['tipe_durasi'] == 'harian') {
            $selesai = $mulai->copy()->addDay();
            $harga = $motor->tarif->tarif_harian;
        } elseif ($validated['tipe_durasi'] === 'mingguan') {
            $selesai = $mulai->copy()->addWeek();
            $harga = $motor->tarif->tarif_mingguan;
        } else {
            $selesai = $mulai->copy()->addMonth();
            $harga = $motor->tarif->tarif_bulanan;
        }
        $penyewaan = Penyewaan::create([
            'penyewa_id' => auth('api')->id(),
            'motor_id' => $motor->id,
            'tanggal_mulai' => $mulai->toDateString(),
            'tanggal_selesai' => $selesai->toDateString(),
            'tipe_durasi' => $validated['tipe_durasi'],
            'harga' => $harga,
            'status' => 'menunggu_pembayaran',
        ]);

        return response()->json([
            'message' => 'Pesanan sewa berhasil dibuat, silahkan lakukan pembayaran',
            'penyewaan' => $penyewaan->load('motor'),
        ], 201);
    }

    public function payBooking(Request $request)
    {
        $validated = $request->validate([
            'pemesanan_id' => 'required|exists:penyewaans,id',
            'metode_pembayaran' => 'required|string',
        ]);
        $penyewaan = Penyewaan::where('id', $validated['pemesanan_id'])
            ->where('penyewa_id', auth('api')->id())
            ->firstOrFail();
        if ($penyewaan->status !== 'menunggu_pembayaran') {
            return response()->json(['message' => 'Pesanan tidak dalam status menunggu pembayaran'], 400);
        }
        $transaksi = Transaksi::create([
            'pemesanan_id' => $penyewaan->id,
            'jumlah' => $penyewaan->harga,
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'status' => 'berhasil',
            'tanggal' => now(),
        ]);

        return response()->json([
            'message' => 'pembayaran berhasil dicatat, menunggu konfirmasi admin',
            'transaksi' => $transaksi,
        ], 201);
    }

    public function myBookings()
    {
        $booking = Penyewaan::with(['motor', 'transaksi'])
            ->where('penyewa_id', auth('api')->id())
            ->latest()
            ->get();

        return response()->json([
            'message' => 'riwayat penyewaan anda',
            'bookings' => $booking,
        ]);
    }
}
