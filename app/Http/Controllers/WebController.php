<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\BagiHasil;

class WebController extends Controller
{
    public function adminDashboard()
    {
        $totalMotor = Motor::count();
        $motors = Motor::with('pemilik', 'tarif')->latest()->get();
        $bookings = Penyewaan::with(['motor', 'penyewa', 'transaksi'])->latest()->get();
        $bagiHasils = BagiHasil::all();

        $totalOmset = $bagiHasils->sum(fn ($b) => $b->bagi_hasil_pemilik + $b->bagi_hasil_admin);
        $totalAdmin = $bagiHasils->sum('bagi_hasil_admin');
        $totalPemilik = $bagiHasils->sum('bagi_hasil_pemilik');

        $chartData = [
            'harian'   => Penyewaan::where('tipe_durasi', 'harian')->count(),
            'mingguan' => Penyewaan::where('tipe_durasi', 'mingguan')->count(),
            'bulanan'  => Penyewaan::where('tipe_durasi', 'bulanan')->count(),
        ];

        return view('admin_dashboard', compact(
            'totalMotor', 'motors', 'bookings', 'totalOmset',
            'totalAdmin', 'totalPemilik', 'chartData'
        ));
    }
    public function ownerDashboard()
    {
        $owner  = \App\Models\User::where('role', 'pemilik')->first();
        $motors = $owner ? Motor::where('pemilik_id', $owner->id)->with('tarif')->get() : collect();
        $totalPendapatan = BagiHasil::whereHas('penyewaan.motor', function ($q) use ($owner) {
            if ($owner) $q->where('pemilik_id', $owner->id);
        })->sum('bagi_hasil_pemilik');

        return view('owner_dashboard', compact('owner', 'motors', 'totalPendapatan'));
    }
    public function rentDashboard()
    {
        $availableMotors = Motor::where('status', 'tersedia')->with('tarif')->get();
        $allBookings = Penyewaan::with('motor', 'penyewa', 'transaksi')->latest()->get();

        return view('rent_dashboard', compact('availableMotors', 'allBookings'));
    }
    public function storeMotorWeb(Request $request)
    {
        $validated = $request->validate([
            'merek'     => 'required|string|max:100',
            'tipe_cc'   => 'required|in:100,125,150',
            'no_plat'   => 'required|string|unique:motors,no_plat',
        ]);

        $owner = \App\Models\User::where('role', 'pemilik')->first();

        Motor::create([
            'pemilik_id'    => $owner ? $owner->id : 1,
            'merek'         => $validated['merek'],
            'tipe_cc'       => $validated['tipe_cc'],
            'no_plat'       => $validated['no_plat'],
            'status'        => 'menunggu_verifikasi',
        ]);

        return redirect('/owner/dashboard')->with('success', 'Motor berhasil didaftarkan dan mengunggu verifikasi admin!');
    }
    public function verifyMotorWeb(Request $request, $id)
    {
        $validated = $request->validate([
            'tarif_harian'   => 'required|numeric|min:0',
            'tarif_mingguan' => 'required|numeric|min:0',
            'tarif_bulanan'  => 'required|numeric|min:0',
        ]);

        $motor = Motor::findOrFail($id);
        $motor->update(['status' => 'tersedia']);

        \App\Models\TarifRental::updateOrCreate(
            ['motor_id' => $motor->id],
            [
                'tarif_harian'   => $validated['tarif_harian'],
                'tarif_mingguan' => $validated['tarif_mingguan'],
                'tarif_bulanan'  => $validated['tarif_bulanan'],
            ]
        );

        return redirect('/admin/dashboard')->with('success', 'Motor ' . $motor->merek . ' berhasil diverifikasi dan kini berstatus Tersedia!');
    }
    public function bookMotorWeb(Request $request, $id)
    {
        $validated = $request->validate([
            'tanggal_mulai'     => 'required|date',
            'tipe_durasi'       => 'required|in:harian,mingguan,bulanan',
            'metode_pembayaran' => 'required|string',
        ]);

        $motor = Motor::with('tarif')->findOrFail($id);
        $penyewa = \App\Models\User::where('role', 'penyewa')->first();

        $mulai = \Carbon\Carbon::parse($validated['tanggal_mulai']);
        if ($validated['tipe_durasi'] === 'harian') {
            $selesai = $mulai->copy()->addDay();
            $harga   = $motor->tarif->tarif_harian;
        } elseif ($validated['tipe_durasi'] === 'mingguan') {
            $selesai = $mulai->copy()->addWeek();
            $harga   = $motor->tarif->tarif_mingguan;
        } else {
            $selesai = $mulai->copy()->addMonth();
            $harga   = $motor->tarif->tarif_bulanan;
        }

        $penyewaan = Penyewaan::create([
            'penyewa_id'      => $penyewa ? $penyewa->id : 3,
            'motor_id'        => $motor->id,
            'tanggal_mulai'   => $mulai->toDateString(),
            'tanggal_selesai' => $selesai->toDateString(),
            'tipe_durasi'     => $validated['tipe_durasi'],
            'harga'           => $harga,
            'status'          => 'menunggu_pembayaran',
        ]);

        \App\Models\Transaksi::create([
            'pemesanan_id'      => $penyewaan->id,
            'jumlah'            => $harga,
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'status'            => 'berhasil',
            'tanggal'           => now(),
        ]);

        return redirect('/rent/dashboard')->with('success', 'Penyewaan berhasil diajukan dan dibayar!');
    }
    public function confirmBookingWeb($id)
    {
        $penyewaan = Penyewaan::with('transaksi')->findOrFail($id);
        $penyewaan->update(['status' => 'dikonfirmasi']);
        $totalBayar   = $penyewaan->harga;
        $porsiPemilik = $totalBayar * 0.80;
        $porsiAdmin   = $totalBayar * 0.20;
        BagiHasil::updateOrCreate([
            'pemesanan_id' => $penyewaan->id],
            [
                'bagi_hasil_pemilik'  => $porsiPemilik,
                'bagi_hasil_admin'    => $porsiAdmin,
                'tanggal'             => now()->toDateString(),
            ]
        );
        return redirect('/admin/dashboard')->with('success', 'penyewaan dikonfirmasi! motor otomatis berstatus disewa.');
    }
    public function returnBookingWeb($id)
    {
        $penyewaan = Penyewaan::findOrFail($id);
        $penyewaan->update(['status' => 'selesai']);
        if ($penyewaan->bagiHasil) {
            $penyewaan->bagiHasil->update(['settled_at' => now()]);
        }
        return redirect('/admin/dashboard')->with('success', 'motor berhasil dikembalikan dan kini kembali tersedia untuk disewa!');
    }
}
