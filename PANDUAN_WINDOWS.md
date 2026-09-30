# PANDUAN LENGKAP PENGEMBANGAN SISTEM RENTAL MOTOR (LARAVEL 13)
> **Sertifikasi BNSP / Uji Kompetensi Pemrograman Web & Mobile (TPD Junior Coder)**  
> Panduan instalasi dan implementasi source code lengkap 100% dari nol hingga selesai untuk sistem operasi **Windows** (menggunakan XAMPP/Laragon atau PHP CLI + MySQL).

---

## DAFTAR ISI
1. [Prasyarat & Persiapan Lingkungan di Windows](#1-prasyarat--persiapan-lingkungan-di-windows)
2. [Fase 1: Inisialisasi Proyek & Konfigurasi Auth](#fase-1-inisialisasi-proyek--konfigurasi-auth)
3. [Fase 2: Perancangan Skema Database (Migrations & Models)](#fase-2-perancangan-skema-database-migrations--models)
4. [Fase 3: Database Trigger MySQL (Otomatisasi Status Unit)](#fase-3-database-trigger-mysql-otomatisasi-status-unit)
5. [Fase 4: JWT Auth & Custom Role Middleware](#fase-4-jwt-auth--custom-role-middleware)
6. [Fase 5 & 6: Backend REST API Controllers (API Modul)](#fase-5--6-backend-rest-api-controllers-api-modul)
7. [Fase 7: Web Controller & Antarmuka UI (Blade + Chart.js)](#fase-7-web-controller--antarmuka-ui-blade--chartjs)
8. [Fase 8: Konfigurasi Seluruh Routing (API & Web)](#fase-8-konfigurasi-seluruh-routing-api--web)
9. [Cara Menjalankan & Menguji di Windows](#9-cara-menjalankan--menguji-di-windows)
10. [Tabel Kredensial Akun Pengujian](#10-tabel-kredensial-akun-pengujian)
11. [Panduan Troubleshooting Masalah Umum di Windows](#11-panduan-troubleshooting-masalah-umum-di-windows)

---

## 1. PRASYARAT & PERSIAPAN LINGKUNGAN DI WINDOWS

Pastikan aplikasi berikut sudah terinstal di Windows:
1. **PHP >= 8.2** (Rekomendasi PHP 8.3 / 8.4). Pastikan ekstensi berikut aktif di `php.ini` (hilangkan tanda `;` di depannya):
   - `extension=pdo_mysql`
   - `extension=mbstring`
   - `extension=openssl`
   - `extension=curl`
   - `extension=fileinfo`
2. **Composer** (Download installer dari [getcomposer.org](https://getcomposer.org)).
3. **MySQL / MariaDB** (Melalui XAMPP, Laragon, atau MySQL Server standalone).

### Langkah Awal Database di Windows:
1. Jalankan MySQL dari kontrol panel XAMPP atau Laragon.
2. Buka Command Prompt / PowerShell / phpMyAdmin, lalu buat database baru:
   ```sql
   CREATE DATABASE rental_motor;
   ```

---

## FASE 1: INISIALISASI PROYEK & KONFIGURASI AUTH

### 1.1 Buat Proyek Laravel Baru
Buka terminal (cmd / PowerShell) di folder kerja Windows kamu:
```bash
composer create-project laravel/laravel rental_motor
cd rental_motor
```

### 1.2 Konfigurasi Database di `.env`
Buka file `.env` di teks editor, sesuaikan konfigurasi database:
```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rental_motor
DB_USERNAME=root
DB_PASSWORD=
```

### 1.3 Install Paket JWT Auth
```bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

### 1.4 Konfigurasi Multi-Guard di `config/auth.php`
Buka file `config/auth.php`, ubah bagian `defaults` dan `guards`:
```php
'defaults' => [
    'guard' => env('AUTH_GUARD', 'api'),
    'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

---

## FASE 2: PERANCANGAN SKEMA DATABASE (MIGRATIONS & MODELS)

Sistem rental ini memiliki 6 entitas yang saling berelasi:
1. `users` (Admin, Pemilik, Penyewa)
2. `motors` (Unit kendaraan)
3. `tarif_rentals` (Tarif harian, mingguan, bulanan)
4. `penyewaans` (Transaksi pemesanan sewa)
5. `transaksis` (Catatan pembayaran)
6. `bagi_hasils` (Kalkulasi otomatis porsi Pemilik 80% & Admin 20%)

### 2.1 Modifikasi Migration Tabel `users`
Buka file `database/migrations/0001_01_01_000000_create_users_table.php`, sesuaikan fungsi `up()`:
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('no_tlpn');
    $table->enum('role', ['admin', 'pemilik', 'penyewa'])->default('penyewa');
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});
```

### 2.2 Buat Migration Entitas Lainnya
Jalankan perintah ini di terminal:
```bash
php artisan make:migration create_motors_table
php artisan make:migration create_tarif_rentals_table
php artisan make:migration create_penyewaans_table
php artisan make:migration create_transaksis_table
php artisan make:migration create_bagi_hasils_table
```

Isi masing-masing file migration:

#### A. File `database/migrations/xxxx_xx_xx_create_motors_table.php`:
```php
Schema::create('motors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pemilik_id')->constrained('users')->onDelete('cascade');
    $table->string('merek', 100);
    $table->enum('tipe_cc', ['100', '125', '150']);
    $table->string('no_plat')->unique();
    $table->enum('status', ['menunggu_verifikasi', 'tersedia', 'disewa'])->default('menunggu_verifikasi');
    $table->string('foto')->nullable();
    $table->string('documen_kepemilikan')->nullable();
    $table->timestamps();
});
```

#### B. File `database/migrations/xxxx_xx_xx_create_tarif_rentals_table.php`:
```php
Schema::create('tarif_rentals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('motor_id')->constrained('motors')->onDelete('cascade');
    $table->decimal('tarif_harian', 12, 2);
    $table->decimal('tarif_mingguan', 12, 2);
    $table->decimal('tarif_bulanan', 12, 2);
    $table->timestamps();
});
```

#### C. File `database/migrations/xxxx_xx_xx_create_penyewaans_table.php`:
```php
Schema::create('penyewaans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('penyewa_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('motor_id')->constrained('motors')->onDelete('cascade');
    $table->date('tanggal_mulai');
    $table->date('tanggal_selesai');
    $table->enum('tipe_durasi', ['harian', 'mingguan', 'bulanan']);
    $table->decimal('harga', 12, 2);
    $table->enum('status', ['menunggu_pembayaran', 'dikonfirmasi', 'selesai', 'dibatalkan'])->default('menunggu_pembayaran');
    $table->timestamps();
});
```

#### D. File `database/migrations/xxxx_xx_xx_create_transaksis_table.php`:
```php
Schema::create('transaksis', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pemesanan_id')->constrained('penyewaans')->onDelete('cascade');
    $table->decimal('jumlah', 12, 2);
    $table->string('metode_pembayaran');
    $table->enum('status', ['pending', 'berhasil', 'gagal'])->default('pending');
    $table->timestamp('tanggal')->useCurrent();
    $table->timestamps();
});
```

#### E. File `database/migrations/xxxx_xx_xx_create_bagi_hasils_table.php`:
```php
Schema::create('bagi_hasils', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pemesanan_id')->constrained('penyewaans')->onDelete('cascade');
    $table->decimal('bagi_hasil_pemilik', 12, 2);
    $table->decimal('bagi_hasil_admin', 12, 2);
    $table->timestamp('settled_at')->nullable();
    $table->date('tanggal');
    $table->timestamps();
});
```

---

### 2.3 Konfigurasi Seluruh Eloquent Models

#### File: `app/Models/User.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'no_tlpn', 'role', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['role' => $this->role];
    }

    public function motors()
    {
        return $this->hasMany(Motor::class, 'pemilik_id');
    }

    public function penyewaans()
    {
        return $this->hasMany(Penyewaan::class, 'penyewa_id');
    }
}
```

#### File: `app/Models/Motor.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motor extends Model
{
    protected $fillable = [
        'pemilik_id', 'merek', 'tipe_cc', 'no_plat', 'status', 'foto', 'documen_kepemilikan'
    ];

    public function pemilik()
    {
        return $this->belongsTo(User::class, 'pemilik_id');
    }

    public function tarif()
    {
        return $this->hasOne(TarifRental::class, 'motor_id');
    }

    public function penyewaans()
    {
        return $this->hasMany(Penyewaan::class, 'motor_id');
    }
}
```

#### File: `app/Models/TarifRental.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifRental extends Model
{
    protected $fillable = [
        'motor_id', 'tarif_harian', 'tarif_mingguan', 'tarif_bulanan'
    ];

    public function motor()
    {
        return $this->belongsTo(Motor::class, 'motor_id');
    }
}
```

#### File: `app/Models/Penyewaan.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penyewaan extends Model
{
    protected $fillable = [
        'penyewa_id', 'motor_id', 'tanggal_mulai', 'tanggal_selesai', 'tipe_durasi', 'harga', 'status'
    ];

    public function penyewa()
    {
        return $this->belongsTo(User::class, 'penyewa_id');
    }

    public function motor()
    {
        return $this->belongsTo(Motor::class, 'motor_id');
    }

    public function transaksi()
    {
        return $this->hasOne(Transaksi::class, 'pemesanan_id');
    }

    public function bagiHasil()
    {
        return $this->hasOne(BagiHasil::class, 'pemesanan_id');
    }
}
```

#### File: `app/Models/Transaksi.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = [
        'pemesanan_id', 'jumlah', 'metode_pembayaran', 'status', 'tanggal'
    ];

    public function penyewaan()
    {
        return $this->belongsTo(Penyewaan::class, 'pemesanan_id');
    }
}
```

#### File: `app/Models/BagiHasil.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BagiHasil extends Model
{
    protected $fillable = [
        'pemesanan_id', 'bagi_hasil_pemilik', 'bagi_hasil_admin', 'settled_at', 'tanggal'
    ];

    public function penyewaan()
    {
        return $this->belongsTo(Penyewaan::class, 'pemesanan_id');
    }
}
```

---

## FASE 3: DATABASE TRIGGER MYSQL (OTOMATISASI STATUS UNIT)

Database trigger ini bertugas otomatis mengubah status unit motor di MySQL:
- Pesanan `'dikonfirmasi'` -> motor status `'disewa'`.
- Pesanan `'selesai'` -> motor status `'tersedia'`.

Jalankan perintah:
```bash
php artisan make:migration create_motor_status_trigger
```

Isi file migration tersebut:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE TRIGGER trg_update_motor_status_after_booking_update
            AFTER UPDATE ON penyewaans
            FOR EACH ROW
            BEGIN
                IF NEW.status = 'dikonfirmasi' THEN
                    UPDATE motors SET status = 'disewa' WHERE id = NEW.motor_id;
                ELSEIF NEW.status = 'selesai' THEN
                    UPDATE motors SET status = 'tersedia' WHERE id = NEW.motor_id;
                END IF;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS trg_update_motor_status_after_booking_update");
    }
};
```

### Jalankan Migration ke Database:
```bash
php artisan migrate
```

---

## FASE 4: JWT AUTH & CUSTOM ROLE MIDDLEWARE

### 4.1 Buat Middleware: `app/Http/Middleware/RoleMiddleware.php`
```bash
php artisan make:middleware RoleMiddleware
```
Isi kodenya:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! auth('api')->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $user = auth('api')->user();
        if (! in_array($user->role, $roles)) {
            return response()->json(['message' => 'Akses ditolak. Anda tidak memiliki izin.'], 403);
        }
        return $next($request);
    }
}
```

### 4.2 Daftarkan Alias Middleware di `bootstrap/app.php`
Buka `bootstrap/app.php`:
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
```

---

## FASE 5 & 6: BACKEND REST API CONTROLLERS (API MODUL)

Buat 4 controller API:
```bash
php artisan make:controller AuthController
php artisan make:controller OwnerController
php artisan make:controller AdminController
php artisan make:controller BookingController
```

#### File: `app/Http/Controllers/AuthController.php`
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'no_tlpn' => 'required|string|max:20',
            'role' => 'required|in:penyewa,pemilik',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'no_tlpn'  => $validated['no_tlpn'],
            'role'     => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'message'    => 'registrasi berhasil',
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        return response()->json([
            'message'    => 'login berhasil',
            'user'       => auth('api')->user(),
            'token'      => $token,
            'token_type' => 'bearer',
        ]);
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Berhasil logout']);
    }
}
```

#### File: `app/Http/Controllers/OwnerController.php`
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Motor;
use App\Models\BagiHasil;

class OwnerController extends Controller
{
    public function storeMotor(Request $request)
    {
        $validated = $request->validate([
            'merek'               => 'required|string|max:100',
            'tipe_cc'             => 'required|in:100,125,150',
            'no_plat'             => 'required|string|unique:motors,no_plat',
            'foto'                => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'documen_kepemilikan' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:2048',
        ]);

        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('motors', 'public')
            : null;
        $docPath = $request->hasFile('documen_kepemilikan')
            ? $request->file('documen_kepemilikan')->store('documents', 'public')
            : null;

        $motor = Motor::create([
            'pemilik_id'          => auth('api')->id(),
            'merek'               => $validated['merek'],
            'tipe_cc'             => $validated['tipe_cc'],
            'no_plat'             => $validated['no_plat'],
            'status'              => 'menunggu_verifikasi',
            'foto'                => $fotoPath,
            'documen_kepemilikan' => $docPath,
        ]);

        return response()->json([
            'message' => 'Motor berhasil didaftarkan dan menunggu verifikasi admin',
            'motor'   => $motor,
        ], 201);
    }

    public function myMotors()
    {
        $motors = Motor::with('tarif')
            ->where('pemilik_id', auth('api')->id())
            ->get();

        return response()->json([
            'message' => 'Daftar motor Anda',
            'motors'  => $motors,
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
            'message'          => 'Laporan pendapatan bagi hasil pemilik',
            'total_pendapatan' => $totalPendapatan,
            'rincian'          => $laporan,
        ]);
    }
}
```

#### File: `app/Http/Controllers/AdminController.php`
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Motor;
use App\Models\TarifRental;
use App\Models\Penyewaan;
use App\Models\BagiHasil;

class AdminController extends Controller
{
    public function verifyMotor(Request $request, $id)
    {
        $motor = Motor::findOrFail($id);
        $validated = $request->validate([
            'tarif_harian'   => 'required|numeric|min:0',
            'tarif_mingguan' => 'required|numeric|min:0',
            'tarif_bulanan'  => 'required|numeric|min:0',
        ]);

        $motor->update(['status' => 'tersedia']);

        TarifRental::updateOrCreate(
            ['motor_id' => $motor->id],
            [
                'tarif_harian'   => $validated['tarif_harian'],
                'tarif_mingguan' => $validated['tarif_mingguan'],
                'tarif_bulanan'  => $validated['tarif_bulanan'],
            ]
        );

        return response()->json([
            'message' => 'Motor berhasil diverifikasi dan tarif telah ditentukan',
            'motor'   => $motor->load('tarif'),
        ]);
    }

    public function confirmBooking(Request $request, $id)
    {
        $penyewaan = Penyewaan::with('transaksi')->findOrFail($id);

        if (! $penyewaan->transaksi || $penyewaan->transaksi->status !== 'berhasil') {
            return response()->json(['message' => 'Pesanan belum dibayar oleh penyewa'], 400);
        }

        $penyewaan->update(['status' => 'dikonfirmasi']);

        $totalBayar   = $penyewaan->harga;
        $porsiPemilik = $totalBayar * 0.80;
        $porsiAdmin   = $totalBayar * 0.20;

        $bagiHasil = BagiHasil::updateOrCreate(
            ['pemesanan_id' => $penyewaan->id],
            [
                'bagi_hasil_pemilik' => $porsiPemilik,
                'bagi_hasil_admin'   => $porsiAdmin,
                'tanggal'            => now()->toDateString(),
            ]
        );

        return response()->json([
            'message'    => 'Penyewaan berhasil dikonfirmasi, status motor kini disewa dan bagi hasil telah tercatat',
            'penyewaan'  => $penyewaan->fresh(['motor']),
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
            'message'   => 'Pengembalian motor berhasil dikonfirmasi. Status motor kini kembali tersedia',
            'penyewaan' => $penyewaan->fresh(['motor']),
        ]);
    }

    public function revenueReport()
    {
        $laporan = BagiHasil::with(['penyewaan.motor', 'penyewaan.penyewa'])->get();
        $totalAdmin   = $laporan->sum('bagi_hasil_admin');
        $totalPemilik = $laporan->sum('bagi_hasil_pemilik');
        $totalOmset   = $totalAdmin + $totalPemilik;

        return response()->json([
            'message'                  => 'Laporan pendapatan admin dan bagi hasil',
            'total_omset'              => $totalOmset,
            'total_bagi_hasil_admin'   => $totalAdmin,
            'total_bagi_hasil_pemilik' => $totalPemilik,
            'rincian'                  => $laporan,
        ]);
    }
}
```

#### File: `app/Http/Controllers/BookingController.php`
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function availableMotors()
    {
        $motors = Motor::with('tarif')
            ->where('status', 'tersedia')
            ->get();

        return response()->json([
            'message' => 'Daftar motor tersedia',
            'motors'  => $motors,
        ]);
    }

    public function createBooking(Request $request)
    {
        $validated = $request->validate([
            'motor_id'      => 'required|exists:motors,id',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tipe_durasi'   => 'required|in:harian,mingguan,bulanan',
        ]);

        $motor = Motor::with('tarif')->findOrFail($validated['motor_id']);

        if ($motor->status !== 'tersedia' || ! $motor->tarif) {
            return response()->json(['message' => 'Motor tidak tersedia untuk disewa'], 400);
        }

        $mulai = Carbon::parse($validated['tanggal_mulai']);

        if ($validated['tipe_durasi'] == 'harian') {
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
            'penyewa_id'      => auth('api')->id(),
            'motor_id'        => $motor->id,
            'tanggal_mulai'   => $mulai->toDateString(),
            'tanggal_selesai' => $selesai->toDateString(),
            'tipe_durasi'     => $validated['tipe_durasi'],
            'harga'           => $harga,
            'status'          => 'menunggu_pembayaran',
        ]);

        return response()->json([
            'message'   => 'Pesanan sewa berhasil dibuat, silahkan lakukan pembayaran',
            'penyewaan' => $penyewaan->load('motor'),
        ], 201);
    }

    public function payBooking(Request $request)
    {
        $validated = $request->validate([
            'pemesanan_id'      => 'required|exists:penyewaans,id',
            'metode_pembayaran' => 'required|string',
        ]);

        $penyewaan = Penyewaan::findOrFail($validated['pemesanan_id']);

        $transaksi = Transaksi::create([
            'pemesanan_id'      => $penyewaan->id,
            'jumlah'            => $penyewaan->harga,
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'status'            => 'berhasil',
            'tanggal'           => now(),
        ]);

        return response()->json([
            'message'   => 'Pembayaran berhasil dicatat, menunggu konfirmasi admin',
            'transaksi' => $transaksi,
        ], 201);
    }

    public function myBookings()
    {
        $bookings = Penyewaan::with(['motor', 'transaksi'])
            ->where('penyewa_id', auth('api')->id())
            ->get();

        return response()->json([
            'message'  => 'Riwayat penyewaan Anda',
            'bookings' => $bookings,
        ]);
    }
}
```

---

## FASE 7: WEB CONTROLLER & ANTARMUKA UI (BLADE + CHART.JS)

### 7.1 Web Controller: `app/Http/Controllers/WebController.php`
```bash
php artisan make:controller WebController
```
Isi lengkap file `WebController.php`:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
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

        $totalOmset   = $bagiHasils->sum(fn ($b) => $b->bagi_hasil_pemilik + $b->bagi_hasil_admin);
        $totalAdmin   = $bagiHasils->sum('bagi_hasil_admin');
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
        $owner = Auth::guard('web')->user();
        $motors = $owner ? Motor::where('pemilik_id', $owner->id)->with('tarif')->get() : collect();
        $totalPendapatan = BagiHasil::whereHas('penyewaan.motor', function ($q) use ($owner) {
            if ($owner) $q->where('pemilik_id', $owner->id);
        })->sum('bagi_hasil_pemilik');

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
            'merek'   => 'required|string|max:100',
            'tipe_cc' => 'required|in:100,125,150',
            'no_plat' => 'required|string|unique:motors,no_plat',
        ]);

        Motor::create([
            'pemilik_id' => Auth::guard('web')->id() ?? 1,
            'merek'      => $validated['merek'],
            'tipe_cc'    => $validated['tipe_cc'],
            'no_plat'    => $validated['no_plat'],
            'status'     => 'menunggu_verifikasi',
        ]);

        return redirect('/owner/dashboard')->with('success', 'Motor berhasil didaftarkan dan menunggu verifikasi admin!');
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
            'penyewa_id'      => Auth::guard('web')->id() ?? 3,
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

        BagiHasil::updateOrCreate(
            ['pemesanan_id' => $penyewaan->id],
            [
                'bagi_hasil_pemilik' => $porsiPemilik,
                'bagi_hasil_admin'   => $porsiAdmin,
                'tanggal'            => now()->toDateString(),
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
            'email'    => 'required|email',
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
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'no_tlpn'  => 'required|string|max:20',
            'role'     => 'required|in:pemilik,penyewa',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'no_tlpn'  => $validated['no_tlpn'],
            'role'     => $validated['role'],
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
            'merek'   => 'required|string|max:100',
            'tipe_cc' => 'required|in:100,125,150',
            'no_plat' => 'required|string|unique:motors,no_plat,' . $motor->id,
        ]);

        $motor->update([
            'merek'   => $validated['merek'],
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
```

---

### 7.2 Seluruh File View Blade

#### File: `resources/views/app.blade.php` (Master Template)
```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Rental Motor')</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">Rental Motor</a>
        <div class="navbar-nav ms-auto align-items-center">
            @auth('web')
                <span class="navbar-text me-3 text-white">
                    Halo, <strong>{{ auth('web')->user()->name }}</strong> ({{ ucfirst(auth('web')->user()->role) }})
                </span>

                @if(auth('web')->user()->role == 'admin')
                    <a class="nav-link" href="/admin/dashboard">Admin Panel</a>
                @elseif(auth('web')->user()->role == 'pemilik')
                    <a class="nav-link" href="/owner/dashboard">Motor Saya</a>
                @else
                    <a class="nav-link" href="/rent/dashboard">Katalog & Riwayat Sewa</a>
                @endif

                <form action="/logout" method="POST" class="d-inline ms-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Keluar</button>
                </form>
            @else
                <a class="nav-link me-2" href="/rent/dashboard">Katalog Sewa</a>
                <a class="btn btn-outline-light btn-sm me-2" href="/login">Masuk</a>
                <a class="btn btn-primary btn-sm" href="/register">Daftar</a>
            @endauth
        </div>
    </div>
</nav>

<div class="container">
    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
```

#### File: `resources/views/admin_dashboard.blade.php`
```html
@extends('app')

@section('title', 'Admin Dashboard - Rental Motor')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3">
            <h6 class="text-muted">Total Unit Motor</h6>
            <h3 class="fw-bold">{{ $totalMotor }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3">
            <h6 class="text-muted">Total Omset</h6>
            <h3 class="fw-bold text-success">Rp {{ number_format($totalOmset, 0, ',', '.') }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3">
            <h6 class="text-muted">Bagi Hasil Admin</h6>
            <h3 class="fw-bold text-primary">Rp {{ number_format($totalAdmin, 0, ',', '.') }}</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3">
            <h6 class="text-muted">Bagi Hasil Pemilik</h6>
            <h3 class="fw-bold text-info">Rp {{ number_format($totalPemilik, 0, ',', '.') }}</h3>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Chart.js: Bar Chart Durasi Sewa -->
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm p-4">
            <h5 class="fw-bold mb-3">Statistik Durasi Penyewaan</h5>
            <canvas id="durationChart" height="200"></canvas>
        </div>
    </div>
    <!-- Chart.js: Doughnut Chart Bagi Hasil -->
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm p-4">
            <h5 class="fw-bold mb-3">Komposisi Bagi Hasil</h5>
            <canvas id="revenuePieChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Tabel Daftar Unit Motor & Verifikasi Tarif -->
<div class="card shadow-sm p-4 mb-4">
    <h5 class="fw-bold mb-3">Daftar Unit Motor & Status</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Plat</th>
                    <th>Merek</th>
                    <th>CC</th>
                    <th>Pemilik</th>
                    <th>Status</th>
                    <th>Tarif Harian / Mingguan / Bulanan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($motors as $motor)
                <tr>
                    <td><strong>{{ $motor->no_plat }}</strong></td>
                    <td>{{ $motor->merek }}</td>
                    <td>{{ $motor->tipe_cc }} cc</td>
                    <td>{{ $motor->pemilik->name ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $motor->status == 'tersedia' ? 'bg-success' : ($motor->status == 'disewa' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                            {{ $motor->status }}
                        </span>
                    </td>
                    <td>
                        @if($motor->tarif)
                            Rp {{ number_format($motor->tarif->tarif_harian, 0, ',', '.') }} / 
                            Rp {{ number_format($motor->tarif->tarif_mingguan, 0, ',', '.') }} / 
                            Rp {{ number_format($motor->tarif->tarif_bulanan, 0, ',', '.') }}
                        @else
                            <span class="text-muted">Belum ada tarif</span>
                        @endif
                    </td>
                    <td>
                        @if($motor->status == 'menunggu_verifikasi')
                            <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#verifyModal{{ $motor->id }}">
                                Verifikasi & Atur Tarif
                            </button>

                            <div class="modal fade" id="verifyModal{{ $motor->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="/admin/motors/{{ $motor->id }}/verify-web" method="POST" class="modal-content">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Verifikasi Motor: {{ $motor->merek }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Tarif Harian (Rp)</label>
                                                <input type="number" name="tarif_harian" class="form-control" placeholder="75000" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Tarif Mingguan (Rp)</label>
                                                <input type="number" name="tarif_mingguan" class="form-control" placeholder="450000" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Tarif Bulanan (Rp)</label>
                                                <input type="number" name="tarif_bulanan" class="form-control" placeholder="1500000" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-success btn-sm fw-bold">Konfirmasi & Aktifkan Unit</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            <span class="text-muted small">Sudah diverifikasi</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Tabel Konfirmasi Sewa & Pengembalian -->
<div class="card shadow-sm p-4 mb-4">
    <h5 class="fw-bold mb-3">Pesanan Sewa Masuk (Perlu Konfirmasi Admin)</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Penyewa</th>
                    <th>Motor</th>
                    <th>Durasi Sewa</th>
                    <th>Total Biaya</th>
                    <th>Status Pembayaran</th>
                    <th>Status Pesanan</th>
                    <th>Aksi Admin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $b)
                <tr>
                    <td>{{ $b->penyewa->name ?? '-' }}</td>
                    <td><strong>{{ $b->motor->merek ?? '-' }}</strong> ({{ $b->motor->no_plat ?? '-' }})</td>
                    <td>{{ $b->tanggal_mulai }} s/d {{ $b->tanggal_selesai }} ({{ $b->tipe_durasi }})</td>
                    <td><strong>Rp {{ number_format($b->harga, 0, ',', '.') }}</strong></td>
                    <td>
                        <span class="badge {{ ($b->transaksi && $b->transaksi->status == 'berhasil') ? 'bg-success' : 'bg-danger' }}">
                            {{ $b->transaksi ? 'Sudah Dibayar (' . $b->transaksi->metode_pembayaran . ')' : 'Belum Dibayar' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $b->status == 'dikonfirmasi' ? 'bg-success' : ($b->status == 'selesai' ? 'bg-primary' : 'bg-warning text-dark') }}">
                            {{ $b->status }}
                        </span>
                    </td>
                    <td>
                        @if($b->stat    us == 'menunggu_pembayaran')
                            <form action="/admin/bookings/{{ $b->id }}/confirm-web" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success fw-bold">Konfirmasi Sewa</button>
                            </form>
                        @elseif($b->status == 'dikonfirmasi')
                            <form action="/admin/bookings/{{ $b->id }}/return-web" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-info text-white fw-bold">Motor Kembali</button>
                            </form>
                        @else
                            <span class="text-muted small">Sewa Selesai</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ctxDuration = document.getElementById('durationChart').getContext('2d');
    new Chart(ctxDuration, {
        type: 'bar',
        data: {
            labels: ['Harian', 'Mingguan', 'Bulanan'],
            datasets: [{
                label: 'Jumlah Penyewaan',
                data: [{{ $chartData['harian'] }}, {{ $chartData['mingguan'] }}, {{ $chartData['bulanan'] }}],
                backgroundColor: ['#0d6efd', '#198754', '#ffc107']
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    const ctxRevenue = document.getElementById('revenuePieChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'doughnut',
        data: {
            labels: ['Admin (20%)', 'Pemilik (80%)'],
            datasets: [{
                data: [{{ $totalAdmin }}, {{ $totalPemilik }}],
                backgroundColor: ['#0d6efd', '#0dcaf0']
            }]
        },
        options: { responsive: true }
    });
</script>
@endpush
```

#### File: `resources/views/owner_dashboard.blade.php`
```html
@extends('app')

@section('title', 'Dashboard Pemilik Motor')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm p-4 bg-primary text-white">
            <h5>Total Pendapatan Bagi Hasil Anda</h5>
            <h2 class="fw-bold">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</h2>
            <small>Akun Pemilik: {{ $owner->name ?? 'Belum ada pemilik' }} ({{ $owner->email ?? '-' }})</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm p-4">
            <h5>Total Motor Dititipkan</h5>
            <h2 class="fw-bold text-secondary">{{ $motors->count() }} Unit</h2>
            <small class="text-muted">Status motor diperbarui otomatis oleh sistem</small>
        </div>
    </div>
</div>

<div class="card shadow-sm p-4 mb-4">
    <h5 class="fw-bold mb-3">Titipkan / Tambah Unit Motor Baru</h5>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="/owner/motors/web" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Merek Motor</label>
                <input type="text" name="merek" class="form-control" placeholder="Contoh: Yamaha NMAX 155" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Kapasitas CC</label>
                <select name="tipe_cc" class="form-select" required>
                    <option value="100">100 cc</option>
                    <option value="125">125 cc</option>
                    <option value="150">150 cc</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Nomor Plat Polisi</label>
                <input type="text" name="no_plat" class="form-control" placeholder="Contoh: D 5678 ABC" required>
            </div>
        </div>
        <button type="submit" class="btn btn-success fw-bold">Daftarkan Motor</button>
    </form>
</div>

<div class="card shadow-sm p-4">
    <h5 class="fw-bold mb-3">Motor Milik Anda</h5>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Plat Nomor</th>
                    <th>Merek</th>
                    <th>Kapasitas Mesin</th>
                    <th>Status Unit</th>
                    <th>Tarif Harian</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($motors as $motor)
                <tr>
                    <td><strong>{{ $motor->no_plat }}</strong></td>
                    <td>{{ $motor->merek }}</td>
                    <td>{{ $motor->tipe_cc }} cc</td>
                    <td>
                        <span class="badge {{ $motor->status == 'tersedia' ? 'bg-success' : ($motor->status == 'disewa' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                            {{ $motor->status }}
                        </span>
                    </td>
                    <td>{{ $motor->tarif ? 'Rp ' . number_format($motor->tarif->tarif_harian, 0, ',', '.') : 'Menunggu verifikasi' }}</td>
                    <td>
                        @if($motor->status != 'disewa')
                            <!-- Tombol Edit -->
                            <button class="btn btn-sm btn-warning fw-bold text-dark me-1" data-bs-toggle="modal" data-bs-target="#editMotorModal{{ $motor->id }}">
                                Edit
                            </button>

                            <!-- Tombol Hapus -->
                            <form action="/owner/motors/{{ $motor->id }}/delete-web" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus unit motor ini?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger fw-bold">Hapus</button>
                            </form>

                            <!-- Modal Popup Edit Motor -->
                            <div class="modal fade" id="editMotorModal{{ $motor->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="/owner/motors/{{ $motor->id }}/update-web" method="POST" class="modal-content">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit Data Motor: {{ $motor->merek }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-start">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Merek Motor</label>
                                                <input type="text" name="merek" class="form-control" value="{{ $motor->merek }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Kapasitas CC</label>
                                                <select name="tipe_cc" class="form-select" required>
                                                    <option value="100" {{ $motor->tipe_cc == '100' ? 'selected' : '' }}>100 cc</option>
                                                    <option value="125" {{ $motor->tipe_cc == '125' ? 'selected' : '' }}>125 cc</option>
                                                    <option value="150" {{ $motor->tipe_cc == '150' ? 'selected' : '' }}>150 cc</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Nomor Plat Polisi</label>
                                                <input type="text" name="no_plat" class="form-control" value="{{ $motor->no_plat }}" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary btn-sm fw-bold">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            <span class="badge bg-secondary">Sedang Disewa</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">Belum ada motor yang didaftarkan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
```

#### File: `resources/views/rent_dashboard.blade.php`
```html
@extends('app')

@section('title', 'Katalog Rental Motor')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<h4 class="fw-bold mb-3">Pilihan Motor Tersedia untuk Disewa</h4>
<div class="row mb-5">
    @forelse($availableMotors as $motor)
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body">
                <span class="badge bg-success mb-2">Tersedia</span>
                <h5 class="card-title fw-bold">{{ $motor->merek }} ({{ $motor->tipe_cc }} cc)</h5>
                <p class="text-muted mb-2">Plat: <strong>{{ $motor->no_plat }}</strong></p>
                <hr>
                <div class="small">
                    <div>Harian: <strong>Rp {{ number_format($motor->tarif->tarif_harian ?? 0, 0, ',', '.') }}</strong></div>
                    <div>Mingguan: <strong>Rp {{ number_format($motor->tarif->tarif_mingguan ?? 0, 0, ',', '.') }}</strong></div>
                    <div>Bulanan: <strong>Rp {{ number_format($motor->tarif->tarif_bulanan ?? 0, 0, ',', '.') }}</strong></div>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pb-3">
                <button class="btn btn-primary w-100 btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#rentModal{{ $motor->id }}">
                    Sewa Motor Ini
                </button>

                <div class="modal fade" id="rentModal{{ $motor->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="/rent/motors/{{ $motor->id }}/book-web" method="POST" class="modal-content">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold">Sewa: {{ $motor->merek }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tanggal Mulai Sewa</label>
                                    <input type="date" name="tanggal_mulai" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Pilih Durasi Sewa</label>
                                    <select name="tipe_durasi" class="form-select" required>
                                        <option value="harian">Harian (Rp {{ number_format($motor->tarif->tarif_harian ?? 0, 0, ',', '.') }})</option>
                                        <option value="mingguan">Mingguan (Rp {{ number_format($motor->tarif->tarif_mingguan ?? 0, 0, ',', '.') }})</option>
                                        <option value="bulanan">Bulanan (Rp {{ number_format($motor->tarif->tarif_bulanan ?? 0, 0, ',', '.') }})</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Metode Pembayaran</label>
                                    <select name="metode_pembayaran" class="form-select" required>
                                        <option value="transfer_bank">Transfer Bank</option>
                                        <option value="qris">QRIS Instant</option>
                                        <option value="tunai">Tunai di Tempat</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success btn-sm fw-bold">Konfirmasi & Sewa</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info">Saat ini belum ada unit motor yang berstatus tersedia.</div>
    </div>
    @endforelse
</div>

<div class="card shadow-sm p-4">
    <h5 class="fw-bold mb-3">Riwayat Penyewaan Anda</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Penyewa</th>
                    <th>Unit Motor</th>
                    <th>Durasi Sewa</th>
                    <th>Total Biaya</th>
                    <th>Status Pesanan</th>
                    <th>Status Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allBookings as $b)
                <tr>
                    <td>{{ $b->penyewa->name ?? '-' }}</td>
                    <td>{{ $b->motor->merek ?? '-' }} ({{ $b->motor->no_plat ?? '-' }})</td>
                    <td>{{ $b->tanggal_mulai }} s/d {{ $b->tanggal_selesai }} ({{ $b->tipe_durasi }})</td>
                    <td><strong>Rp {{ number_format($b->harga, 0, ',', '.') }}</strong></td>
                    <td>
                        <span class="badge {{ $b->status == 'dikonfirmasi' ? 'bg-success' : ($b->status == 'selesai' ? 'bg-primary' : 'bg-warning text-dark') }}">
                            {{ $b->status }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ ($b->transaksi && $b->transaksi->status == 'berhasil') ? 'bg-success' : 'bg-danger' }}">
                            {{ $b->transaksi ? $b->transaksi->status : 'belum bayar' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
```

#### File: `resources/views/login.blade.php`
```html
@extends('app')

@section('title', 'Login - Rental Motor')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="fw-bold text-center mb-4">Masuk ke Akun Anda</h4>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form action="/login" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kata Sandi</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Masuk</button>
                </form>

                <div class="text-center mt-3">
                    <small>Belum punya akun? <a href="/register">Daftar Akun Baru</a></small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

#### File: `resources/views/register.blade.php`
```html
@extends('app')

@section('title', 'Daftar Akun - Rental Motor')

@section('content')
<div class="row justify-content-center mt-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="fw-bold text-center mb-4">Registrasi Akun Baru</h4>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="/register" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Budi Santoso" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">No. Telepon / WhatsApp</label>
                        <input type="text" name="no_tlpn" class="form-control" placeholder="08123456789" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Daftar Sebagai</label>
                        <select name="role" class="form-select" required>
                            <option value="penyewa">Penyewa Kendaraan</option>
                            <option value="pemilik">Pemilik Kendaraan (Titip Motor)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kata Sandi</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold">Daftar Sekarang</button>
                </form>

                <div class="text-center mt-3">
                    <small>Sudah punya akun? <a href="/login">Masuk di sini</a></small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

---

## FASE 8: KONFIGURASI SELURUH ROUTING (API & WEB)

#### File: `routes/api.php`
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookingController;

Route::get('/ping', fn () => 'pong');

// Endpoint Autentikasi JWT
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Endpoint Pemilik Kendaraan
Route::middleware(['role:pemilik'])->prefix('owner')->group(function () {
    Route::post('/motors', [OwnerController::class, 'storeMotor']);
    Route::get('/motors', [OwnerController::class, 'myMotors']);
    Route::get('/revenue', [OwnerController::class, 'revenueReport']);
});

// Endpoint Admin Persewaan
Route::middleware(['role:admin'])->prefix('admin')->group(function () {
    Route::patch('/motors/{id}/verify', [AdminController::class, 'verifyMotor']);
    Route::patch('/bookings/{id}/confirm', [AdminController::class, 'confirmBooking']);
    Route::patch('/bookings/{id}/return', [AdminController::class, 'returnBooking']);
    Route::get('/reports/revenue', [AdminController::class, 'revenueReport']);
});

// Endpoint Penyewa Kendaraan
Route::get('/motors', [BookingController::class, 'availableMotors']);
Route::middleware(['role:penyewa'])->group(function () {
    Route::post('/bookings', [BookingController::class, 'createBooking']);
    Route::post('/payments', [BookingController::class, 'payBooking']);
    Route::get('/bookings/history', [BookingController::class, 'myBookings']);
});
```

#### File: `routes/web.php`
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;

// Rute Utama: Arahkan ke Katalog Sewa
Route::get('/', function () {
    return redirect('/rent/dashboard');
});

// Halaman Dashboard Berdasarkan Peran
Route::get('/admin/dashboard', [WebController::class, 'adminDashboard']);
Route::get('/owner/dashboard', [WebController::class, 'ownerDashboard']);
Route::get('/rent/dashboard', [WebController::class, 'rentDashboard']);

// Aksi Interaktif Web Form
Route::post('/owner/motors/web', [WebController::class, 'storeMotorWeb']);
Route::post('/admin/motors/{id}/verify-web', [WebController::class, 'verifyMotorWeb']);
Route::post('/rent/motors/{id}/book-web', [WebController::class, 'bookMotorWeb']);
Route::post('/admin/bookings/{id}/confirm-web', [WebController::class, 'confirmBookingWeb']);
Route::post('/admin/bookings/{id}/return-web', [WebController::class, 'returnBookingWeb']);

// Autentikasi Web Berbasis Session
Route::get('/login', [WebController::class, 'showLoginForm'])->name('login');
Route::post('/login', [WebController::class, 'loginWeb']);
Route::get('/register', [WebController::class, 'showRegisterForm']);
Route::post('/register', [WebController::class, 'registerWeb']);
Route::post('/logout', [WebController::class, 'logoutWeb']);
Route::post('/owner/motors/{id}/update-web', [WebController::class, 'updateMotorWeb']);
Route::post('/owner/motors/{id}/delete-web', [WebController::class, 'deleteMotorWeb']);
```

---

## 9. CARA MENJALANKAN & MENGUJI DI WINDOWS

### 9.1 Hubungkan Folder Storage (Symlink Foto & Dokumen)
Agar gambar foto motor dan file dokumen kepemilikan yang di-upload dapat diakses oleh browser:
```bash
php artisan storage:link
```
> **Catatan Windows:** Jika muncul peringatan *"symlink(): Cannot create symlink"*, jalankan Command Prompt / PowerShell dengan opsi **"Run as Administrator"** atau aktifkan **Developer Mode** di Settings Windows 10/11.

---

### 9.2 Inisialisasi Database & Seeder Akun Pengujian

Jalankan migrasi seluruh tabel dan trigger:
```bash
php artisan migrate
```

Jalankan database seeder untuk membuat 3 akun pengguna bawaan secara otomatis:
```bash
php artisan db:seed
```

Perintah di atas akan otomatis mengisikan data user berikut:
1. **Admin:** `admin@rental.com` (password: `admin123`)
2. **Pemilik:** `budi@example.com` (password: `password123`)
3. **Penyewa:** `siti@example.com` (password: `password123`)

*(Opsional) Jika ingin membuat user admin tambahan secara manual via Tinker:*
```bash
php artisan tinker
```
Lalu masukkan kode:
```php
App\Models\User::create([
    'name'     => 'Admin Rental',
    'email'    => 'admin2@rental.com',
    'no_tlpn'  => '0899999999',
    'role'     => 'admin',
    'password' => Hash::make('admin123')
]);
exit
```

---

### 9.3 Jalankan Server Laravel di Windows

Buka Command Prompt / PowerShell di folder proyek `rental_motor`:
```bash
php artisan serve
```
Server akan aktif di: **`http://127.0.0.1:8000`**

*(Jika port 8000 sudah terpakai oleh aplikasi lain di Windows, gunakan port lain)*:
```bash
php artisan serve --port=8080
```
Lalu buka browser di `http://127.0.0.1:8080`.

---

### 9.4 Skenario Alur Pengujian Lengkap di Web Browser

1. **Pengujian Halaman Publik & Katalog:**
   - Akses `http://127.0.0.1:8000/`. Halaman otomatis diarahkan ke `/rent/dashboard`.
   - Di navbar atas terdapat pilihan **Katalog Sewa**, tombol **Masuk (Login)**, dan tombol **Daftar (Register)**.

2. **Pengujian Akun Pemilik (Titip, Edit, & Hapus Motor):**
   - Klik **Masuk** dan login dengan akun Pemilik: `budi@example.com` / `password123`.
   - Sistem mengarahkan ke `/owner/dashboard`.
   - Isi form **"Titipkan / Tambah Unit Motor Baru"** (contoh: Honda Vario 125, CC 125, Plat: B 1234 ABC).
   - Motor baru akan masuk ke tabel dengan status `menunggu_verifikasi`.
   - Uji tombol **Edit** untuk mengubah plat atau kapasitas CC.
   - Uji tombol **Hapus** untuk menghapus unit motor yang tidak sedang disewa.

3. **Pengujian Akun Admin (Verifikasi & Penetapan Tarif):**
   - Logout lalu login sebagai Admin: `admin@rental.com` / `admin123`.
   - Di dashboard admin (`/admin/dashboard`), unit motor yang baru dititipkan akan muncul dengan status `menunggu_verifikasi`.
   - Klik tombol **Verifikasi & Atur Tarif**, masukkan tarif (Harian, Mingguan, Bulanan), lalu klik **Konfirmasi & Aktifkan Unit**.
   - Status unit motor langsung berubah menjadi `tersedia`.

4. **Pengujian Akun Penyewa (Sewa Motor & Pembayaran):**
   - Logout lalu login sebagai Penyewa: `siti@example.com` / `password123`.
   - Masuk ke `/rent/dashboard`. Unit motor yang berstatus `tersedia` akan tampil di katalog beserta daftar tarifnya.
   - Klik **Sewa Motor Ini**, pilih durasi sewa, tanggal mulai, dan metode pembayaran (Transfer Bank / QRIS / Tunai).
   - Klik **Konfirmasi & Sewa**. Pesanan sewa berhasil dibuat dengan status `menunggu_pembayaran` dan status transaksi `berhasil`.

5. **Pengujian Konfirmasi Sewa & Pengembalian Unit (Admin):**
   - Login kembali sebagai Admin.
   - Di tabel **"Pesanan Sewa Masuk"**, klik tombol **Konfirmasi Sewa**.
   - Database trigger dan controller otomatis memperbarui:
     - Status sewa menjadi `dikonfirmasi`.
     - Status unit motor otomatis berubah menjadi `disewa`.
     - Catatan bagi hasil 80% (Pemilik) dan 20% (Admin) otomatis tercatat dan memperbarui grafik **Chart.js** di dashboard admin.
   - Setelah masa sewa selesai, Admin mengklik tombol **Motor Kembali**:
     - Status sewa menjadi `selesai`.
     - Status unit motor otomatis kembali menjadi `tersedia` untuk disewa oleh pelanggan lain.

---

## 10. TABEL KREDENSIAL AKUN PENGUJIAN

| Peran (Role) | Email | Password | Halaman Dashboard | Fitur Utama |
|---|---|---|---|---|
| **Admin** | `admin@rental.com` | `admin123` | `/admin/dashboard` | Verifikasi unit, atur tarif sewa, konfirmasi sewa, motor kembali, dan grafik Chart.js omset & bagi hasil |
| **Pemilik** | `budi@example.com` | `password123` | `/owner/dashboard` | Titip unit motor baru, edit unit, hapus unit, pantau status unit, dan cek akumulasi bagi hasil 80% |
| **Penyewa** | `siti@example.com` | `password123` | `/rent/dashboard` | Katalog motor tersedia, modal formulir sewa motor, bayar sewa, dan cek riwayat sewa pribadi |

---

## 11. PANDUAN TROUBLESHOOTING MASALAH UMUM DI WINDOWS

### 1. Masalah: `'php'` atau `'composer'` tidak dikenali di Terminal
*Gejala:* `'php' is not recognized as an internal or external command`  
*Solusi:*
1. Tekan tombol **Windows + R**, ketik `sysdm.cpl` lalu tekan Enter.
2. Buka tab **Advanced** -> klik **Environment Variables**.
3. Pada bagian **System variables**, cari variabel **Path** lalu klik **Edit**.
4. Klik **New** dan masukkan path folder PHP Anda (misal `C:\xampp\php` atau `C:\laragon\bin\php\php-8.x.x`).
5. Klik **OK**, tutup dan buka kembali Command Prompt / PowerShell baru.

---

### 2. Masalah: `could not find driver` atau Driver MySQL tidak ditemukan
*Gejala:* `PDOException: could not find driver` saat menjalankan `php artisan migrate`  
*Solusi:*
1. Buka file konfigurasi `php.ini` di folder PHP Anda (misal `C:\xampp\php\php.ini` atau menu Laragon -> PHP -> php.ini).
2. Cari baris berikut dan pastikan tanda titik koma (`;`) di depannya telah dihapus:
   ```ini
   extension=pdo_mysql
   extension=mbstring
   extension=openssl
   extension=curl
   extension=fileinfo
   ```
3. Simpan file `php.ini`, lalu restart Apache/PHP dan jalankan kembali `php artisan migrate`.

---

### 3. Masalah: `SQLSTATE[HY000] [2002] Connection refused`
*Gejala:* Laravel gagal terhubung ke database.  
*Solusi:*
1. Pastikan modul **MySQL** di XAMPP Control Panel atau Laragon sudah berstatus **Running** (indikator warna hijau).
2. Periksa kembali file `.env`:
   - Jika menggunakan XAMPP/Laragon default: `DB_USERNAME=root` dan `DB_PASSWORD=` (dikosongkan).
   - Pastikan database `rental_motor` sudah dibuat melalui phpMyAdmin (`http://localhost/phpmyadmin`) atau HeidiSQL.

---

### 4. Masalah: Symlink Storage Gagal di Windows
*Gejala:* `symlink(): Cannot create symlink, errno=1314` saat menjalankan `php artisan storage:link`  
*Solusi:*
- Buka PowerShell / CMD dengan cara: Klik Kanan -> **"Run as administrator"**, lalu jalankan `php artisan storage:link`.
- Atau aktifkan **Developer Mode** di Windows: Buka **Settings** -> **Update & Security** -> **For developers** -> aktifkan **Developer Mode**.

---

### 5. Masalah: Error JWT Token / Secret Key Missing
*Gejala:* `Tymon\JWTAuth\Exceptions\JWTException: Secret key is not set`  
*Solusi:*
Jalankan perintah generate secret key JWT:
```bash
php artisan jwt:secret
```
Perintah ini akan otomatis mengisi variabel `JWT_SECRET` di dalam file `.env`.

---

### 6. Masalah: Hak Akses Pembuatan Trigger MySQL di Windows
*Gejala:* `This function has none of DETERMINISTIC...` atau `Access denied for user to CREATE TRIGGER`  
*Solusi:*
Masuk ke SQL CLI atau phpMyAdmin dan jalankan query berikut sebagai root:
```sql
SET GLOBAL log_bin_trust_function_creators = 1;
```
Lalu jalankan ulang `php artisan migrate`.
