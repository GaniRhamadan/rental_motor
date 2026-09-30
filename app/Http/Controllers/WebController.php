<?php

namespace App\Http\Controllers;

use App\Models\BagiHasil;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            'harian' => Penyewaan::where('tipe_durasi', 'harian')->count(),
            'mingguan' => Penyewaan::where('tipe_durasi', 'mingguan')->count(),
            'bulanan' => Penyewaan::where('tipe_durasi', 'bulanan')->count(),
        ];

        return view('admin_dashboard', compact(
            'totalMotor', 'motors', 'bookings', 'totalOmset',
            'totalAdmin', 'totalPemilik', 'chartData'
        ));
    }

    public function ownerDashboard()
    {
        $owner = Auth::guard('web')->user();
        $motors = $owner ? Motor::where('pemilik_id', $owner->id)->with('tarif')->get() : collect();
        $totalPendapatan = $owner
            ? BagiHasil::whereHas('penyewaan.motor', function ($q) use ($owner) {
                $q->where('pemilik_id', $owner->id);
            })->sum('bagi_hasil_pemilik')
            : 0;

        return view('owner_dashboard', compact('owner', 'motors', 'totalPendapatan'));
    }

    public function rentDashboard()
    {
        $availableMotors = Motor::where('status', 'tersedia')->with('tarif')->get();
        $user = Auth::guard('web')->user();
        $allBookings = ($user && $user->role === 'penyewa')
            ? Penyewaan::where('penyewa_id', $user->id)->with('motor', 'penyewa', 'transaksi')->latest()->get()
            : Penyewaan::with('motor', 'penyewa', 'transaksi')->latest()->get();

        return view('rent_dashboard', compact('availableMotors', 'allBookings'));
    }

    public function storeMotorWeb(Request $request)
    {
        $validated = $request->validate([
            'merek' => 'required|string|max:100',
            'tipe_cc' => 'required|in:100,125,150',
            'no_plat' => 'required|string|unique:motors,no_plat',
        ]);

        $ownerId = Auth::guard('web')->id() ?? User::where('role', 'pemilik')->value('id') ?? User::value('id');

        Motor::create([
            'pemilik_id' => $ownerId,
            'merek' => $validated['merek'],
            'tipe_cc' => $validated['tipe_cc'],
            'no_plat' => $validated['no_plat'],
            'status' => 'menunggu_verifikasi',
        ]);

        return redirect('/owner/dashboard')->with('success', 'Motor berhasil didaftarkan dan menunggu verifikasi admin!');
    }

    public function verifyMotorWeb(Request $request, $id)
    {
        $validated = $request->validate([
            'tarif_harian' => 'required|numeric|min:0',
            'tarif_mingguan' => 'required|numeric|min:0',
            'tarif_bulanan' => 'required|numeric|min:0',
        ]);

        $motor = Motor::findOrFail($id);
        $motor->update(['status' => 'tersedia']);

        TarifRental::updateOrCreate(
            ['motor_id' => $motor->id],
            [
                'tarif_harian' => $validated['tarif_harian'],
                'tarif_mingguan' => $validated['tarif_mingguan'],
                'tarif_bulanan' => $validated['tarif_bulanan'],
            ]
        );

        return redirect('/admin/dashboard')->with('success', 'Motor '.$motor->merek.' berhasil diverifikasi dan kini berstatus Tersedia!');
    }

    public function bookMotorWeb(Request $request, $id)
    {
        $validated = $request->validate([
            'tanggal_mulai' => 'required|date',
            'tipe_durasi' => 'required|in:harian,mingguan,bulanan',
            'metode_pembayaran' => 'required|string',
        ]);

        $motor = Motor::with('tarif')->findOrFail($id);

        $mulai = Carbon::parse($validated['tanggal_mulai']);
        if ($validated['tipe_durasi'] === 'harian') {
            $selesai = $mulai->copy()->addDay();
            $harga = $motor->tarif->tarif_harian ?? 0;
        } elseif ($validated['tipe_durasi'] === 'mingguan') {
            $selesai = $mulai->copy()->addWeek();
            $harga = $motor->tarif->tarif_mingguan ?? 0;
        } else {
            $selesai = $mulai->copy()->addMonth();
            $harga = $motor->tarif->tarif_bulanan ?? 0;
        }

        $penyewaId = Auth::guard('web')->id() ?? User::where('role', 'penyewa')->value('id') ?? User::value('id');

        $penyewaan = Penyewaan::create([
            'penyewa_id' => $penyewaId,
            'motor_id' => $motor->id,
            'tanggal_mulai' => $mulai->toDateString(),
            'tanggal_selesai' => $selesai->toDateString(),
            'tipe_durasi' => $validated['tipe_durasi'],
            'harga' => $harga,
            'status' => 'menunggu_pembayaran',
        ]);

        Transaksi::create([
            'pemesanan_id' => $penyewaan->id,
            'jumlah' => $harga,
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'status' => 'berhasil',
            'tanggal' => now(),
        ]);

        return redirect('/rent/dashboard')->with('success', 'Penyewaan berhasil diajukan dan dibayar!');
    }

    public function confirmBookingWeb($id)
    {
        $penyewaan = Penyewaan::with('transaksi')->findOrFail($id);
        $penyewaan->update(['status' => 'dikonfirmasi']);
        $totalBayar = $penyewaan->harga;
        $porsiPemilik = $totalBayar * 0.80;
        $porsiAdmin = $totalBayar * 0.20;

        BagiHasil::updateOrCreate(
            ['pemesanan_id' => $penyewaan->id],
            [
                'bagi_hasil_pemilik' => $porsiPemilik,
                'bagi_hasil_admin' => $porsiAdmin,
                'tanggal' => now()->toDateString(),
            ]
        );

        return redirect('/admin/dashboard')->with('success', 'Penyewaan dikonfirmasi! Motor otomatis berstatus Disewa.');
    }

    public function returnBookingWeb($id)
    {
        $penyewaan = Penyewaan::findOrFail($id);
        $penyewaan->update(['status' => 'selesai']);
        if ($penyewaan->bagiHasil) {
            $penyewaan->bagiHasil->update(['settled_at' => now()]);
        }

        return redirect('/admin/dashboard')->with('success', 'Motor berhasil dikembalikan dan kini kembali Tersedia untuk disewa!');
    }

    public function showLoginForm()
    {
        return view('login');
    }

    public function loginWeb(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::guard('web')->user();

            if ($user->role === 'admin') {
                return redirect('/admin/dashboard');
            } elseif ($user->role === 'pemilik') {
                return redirect('/owner/dashboard');
            } else {
                return redirect('/rent/dashboard');
            }
        }

        return back()->with('error', 'Email atau kata sandi salah!');
    }

    public function showRegisterForm()
    {
        return view('register');
    }

    public function registerWeb(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'no_tlpn' => 'required|string|max:20',
            'role' => 'required|in:pemilik,penyewa',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'no_tlpn' => $validated['no_tlpn'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::guard('web')->login($user);

        return redirect($user->role === 'pemilik' ? '/owner/dashboard' : '/rent/dashboard')
            ->with('success', 'Akun berhasil dibuat dan Anda telah masuk!');
    }

    public function logoutWeb(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Anda telah berhasil keluar.');
    }

    public function updateMotorWeb(Request $request, $id)
    {
        $motor = Motor::findOrFail($id);

        if (Auth::guard('web')->check() && Auth::guard('web')->user()->role === 'pemilik' && $motor->pemilik_id !== Auth::guard('web')->id()) {
            return redirect('/owner/dashboard')->with('error', 'Anda tidak memiliki hak untuk mengubah data motor ini');
        }

        $validated = $request->validate([
            'merek' => 'required|string|max:100',
            'tipe_cc' => 'required|in:100,125,150',
            'no_plat' => 'required|string|unique:motors,no_plat,'.$motor->id,
        ]);

        $motor->update([
            'merek' => $validated['merek'],
            'tipe_cc' => $validated['tipe_cc'],
            'no_plat' => $validated['no_plat'],
        ]);

        return redirect('/owner/dashboard')->with('success', 'Data motor berhasil diperbarui');
    }

    public function deleteMotorWeb($id)
    {
        $motor = Motor::findOrFail($id);

        if (Auth::guard('web')->check() && Auth::guard('web')->user()->role === 'pemilik' && $motor->pemilik_id !== Auth::guard('web')->id()) {
            return redirect('/owner/dashboard')->with('error', 'Anda tidak memiliki hak untuk menghapus motor ini');
        }

        if ($motor->status === 'disewa') {
            return redirect('/owner/dashboard')->with('error', 'Motor sedang dalam masa sewa dan tidak dapat dihapus');
        }

        $motor->delete();

        return redirect('/owner/dashboard')->with('success', 'Unit motor berhasil dihapus dari sistem');
    }
}
