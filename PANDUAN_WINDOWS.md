# PANDUAN LENGKAP PENGEMBANGAN SISTEM RENTAL MOTOR (LARAVEL + MOONSHINE)
> **Sertifikasi BNSP / Uji Kompetensi Pemrograman Web & Mobile (TPD Junior Coder)**  
> Panduan instalasi dan implementasi source code lengkap 100% dari nol hingga selesai untuk sistem operasi **Windows** (menggunakan XAMPP/Laragon atau PHP CLI + MySQL), terintegrasi dengan **MoonShine Library** untuk Dashboard & Admin Panel modern, serta **Fitur Upload & Manajemen Foto Unit Motor**.

---

## DAFTAR ISI
1. [Prasyarat & Persiapan Lingkungan di Windows](#1-prasyarat--persiapan-lingkungan-di-windows)
2. [Fase 1: Inisialisasi Proyek & Konfigurasi Auth](#fase-1-inisialisasi-proyek--konfigurasi-auth)
3. [Fase 2: Perancangan Skema Database (Migrations & Models + Kolom Foto)](#fase-2-perancangan-skema-database-migrations--models--kolom-foto)
4. [Fase 3: Database Trigger MySQL (Otomatisasi Status Unit)](#fase-3-database-trigger-mysql-otomatisasi-status-unit)
5. [Fase 4: JWT Auth & Custom Role Middleware](#fase-4-jwt-auth--custom-role-middleware)
6. [Fase 5 & 6: Backend REST API Controllers (API Modul & Mobile)](#fase-5--6-backend-rest-api-controllers-api-modul--mobile)
7. [Fase 7: Implementasi Admin Panel & CRUD Modern dengan MoonShine](#fase-7-implementasi-admin-panel--crud-modern-dengan-moonshine)
   - [7.1 Instalasi & Inisialisasi MoonShine di Windows](#71-instalasi--inisialisasi-moonshine-di-windows)
   - [7.2 Pembuatan Akun Super Admin MoonShine](#72-pembuatan-akun-super-admin-moonshine)
   - [7.3 Resource MotorResource (CRUD Armada & Fitur Upload Foto Motor)](#73-resource-motorresource-crud-armada--fitur-upload-foto-motor)
   - [7.4 Resource TarifRentalResource (Penetapan Tarif Harian, Mingguan, Bulanan)](#74-resource-tarifrentalresource-penetapan-tarif-harian-mingguan-bulanan)
   - [7.5 Resource PenyewaanResource (Manajemen Transaksi Sewa)](#75-resource-penyewaanresource-manajemen-transaksi-sewa)
   - [7.6 Resource TransaksiResource (Pembayaran & Verifikasi)](#76-resource-transaksiresource-pembayaran--verifikasi)
   - [7.7 Resource BagiHasilResource (Laporan Keuangan & Export Excel/CSV)](#77-resource-bagihasilresource-laporan-keuangan--export-excelcsv)
   - [7.8 Resource UserResource (Manajemen Akun Pengguna)](#78-resource-userresource-manajemen-akun-pengguna)
   - [7.9 Konfigurasi Menu Navigasi MoonShine](#79-konfigurasi-menu-navigasi-moonshine)
   - [7.10 Landing Page Publik Katalog Motor](#710-landing-page-publik-katalog-motor)
8. [Fase 8: Konfigurasi Seluruh Routing (API & Web)](#fase-8-konfigurasi-seluruh-routing-api--web)
9. [Cara Menjalankan & Menguji di Windows](#9-cara-menjalankan--menguji-di-windows)
10. [Tabel Kredensial Akun Pengujian](#10-tabel-kredensial-akun-pengujian)
11. [Panduan Troubleshooting Masalah Umum di Windows](#11-panduan-troubleshooting-masalah-umum-di-windows)

---

## 1. PRASYARAT & PERSIAPAN LINGKUNGAN DI WINDOWS

Pastikan aplikasi berikut sudah terinstal di Windows:
1. **PHP >= 8.2** (Rekomendasi PHP 8.3 / 8.4).  
   Buka file konfigurasi `php.ini` (misal di `C:\xampp\php\php.ini` atau menu Laragon -> PHP -> `php.ini`), pastikan ekstensi berikut aktif (hilangkan tanda titik koma `;` di depannya):
   - `extension=pdo_mysql`
   - `extension=mbstring`
   - `extension=openssl`
   - `extension=curl`
   - `extension=fileinfo` *(Krusial untuk validasi upload foto)*
   - `extension=gd` *(Krusial untuk manipulasi & preview gambar/thumbnail)*
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
Buka terminal (CMD / PowerShell) di folder kerja Windows kamu:
```bash
composer create-project laravel/laravel rental_motor
cd rental_motor
```

### 1.2 Konfigurasi Database di `.env`
Buka file `.env` di teks editor, sesuaikan konfigurasi database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rental_motor
DB_USERNAME=root
DB_PASSWORD=

APP_URL=http://127.0.0.1:8000
```

### 1.3 Install Paket JWT Auth (Untuk REST API Mobile)
```bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

### 1.4 Konfigurasi Multi-Guard di `config/auth.php`
Buka file `config/auth.php`, ubah bagian `defaults` dan `guards`:
```php
'defaults' => [
    'guard' => env('AUTH_GUARD', 'web'),
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

## FASE 2: PERANCANGAN SKEMA DATABASE (MIGRATIONS & MODELS + KOLOM FOTO)

Sistem rental ini memiliki 6 entitas yang saling berelasi:
1. `users` (Admin, Pemilik, Penyewa)
2. `motors` (Unit kendaraan + **Foto Unit Motor**)
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
Jalankan perintah ini di terminal Windows:
```bash
php artisan make:migration create_motors_table
php artisan make:migration create_tarif_rentals_table
php artisan make:migration create_penyewaans_table
php artisan make:migration create_transaksis_table
php artisan make:migration create_bagi_hasils_table
```

Isi masing-masing file migration:

#### A. File `database/migrations/xxxx_xx_xx_create_motors_table.php`:
> **Fitur Tambahan:** Kolom `foto` bertipe string nullable untuk menyimpan path foto motor di storage.
```php
Schema::create('motors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pemilik_id')->constrained('users')->onDelete('cascade');
    $table->string('merek', 100);
    $table->enum('tipe_cc', ['100', '125', '150']);
    $table->string('no_plat')->unique();
    $table->enum('status', ['menunggu_verifikasi', 'tersedia', 'disewa'])->default('menunggu_verifikasi');
    $table->string('foto')->nullable(); // Kolom foto unit motor
    $table->string('documen_kepemilikan')->nullable(); // STNK / BPKB
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

> **💡 Petunjuk Pembuatan File Model di Windows:**
> 1. **File `User.php`**: File ini **sudah otomatis tersedia** bawaan dari instalasi awal Laravel di `app/Models/User.php`. Anda **tidak perlu** membuat file baru, cukup buka dan sesuaikan kodenya.
> 2. **Model Lainnya (`Motor`, `TarifRental`, `Penyewaan`, `Transaksi`, `BagiHasil`)**: Jangan buat file secara manual (klik kanan -> New File). Jalankan perintah **Artisan** berikut di terminal/CMD Windows agar kerangka class-nya dibuatkan otomatis oleh Laravel:
>    ```bash
>    php artisan make:model Motor
>    php artisan make:model TarifRental
>    php artisan make:model Penyewaan
>    php artisan make:model Transaksi
>    php artisan make:model BagiHasil
>    ```
>    *(Tips: Jika Anda membuat proyek dari awal, Anda juga bisa membuat Model sekaligus Migration-nya sekaligus dengan opsi `-m`, contoh: `php artisan make:model Motor -m`)*.
> 3. Setelah file Model berhasil dibuat di folder `app/Models/`, buka masing-masing file dan isi kode konfigurasi `$fillable` serta relasinya sesuai panduan di bawah ini:

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

    // Accessor otomatis untuk URL publik foto motor
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? asset('storage/' . $this->foto) : null;
    }

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

Database trigger ini bertugas menyinkronkan status unit motor secara otomatis saat transaksi penyewaan disetujui atau diselesaikan.

Buat file migration khusus trigger:
```bash
php artisan make:migration create_sync_motor_status_trigger
```

Isi file `database/migrations/xxxx_xx_xx_create_sync_motor_status_trigger.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS after_penyewaan_update_sync_motor;");

        DB::unprepared("
            CREATE TRIGGER after_penyewaan_update_sync_motor
            AFTER UPDATE ON penyewaans
            FOR EACH ROW
            BEGIN
                -- Jika status sewa berubah menjadi 'dikonfirmasi', ubah status motor menjadi 'disewa'
                IF NEW.status = 'dikonfirmasi' AND OLD.status != 'dikonfirmasi' THEN
                    UPDATE motors 
                    SET status = 'disewa' 
                    WHERE id = NEW.motor_id;
                END IF;

                -- Jika status sewa berubah menjadi 'selesai' atau 'dibatalkan', kembalikan status motor menjadi 'tersedia'
                IF (NEW.status = 'selesai' OR NEW.status = 'dibatalkan') AND (OLD.status != NEW.status) THEN
                    UPDATE motors 
                    SET status = 'tersedia' 
                    WHERE id = NEW.motor_id;
                END IF;
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS after_penyewaan_update_sync_motor;");
    }
};
```

---

## FASE 4: JWT AUTH & CUSTOM ROLE MIDDLEWARE

### 4.1 Buat Middleware Peran (Role)
```bash
php artisan make:middleware RoleMiddleware
```

Buka file `app/Http/Middleware/RoleMiddleware.php`:
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
        $user = auth('api')->user() ?? auth('web')->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk peran ini.'
            ], 403);
        }

        return $next($request);
    }
}
```

### 4.2 Daftarkan Alias Middleware di `bootstrap/app.php`
Buka file `bootstrap/app.php`:
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## FASE 5 & 6: BACKEND REST API CONTROLLERS (API MODUL & MOBILE)

Buat controller API:
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
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'no_tlpn'  => 'required|string|max:15',
            'role'     => 'required|in:admin,pemilik,penyewa',
            'password' => 'required|string|min:6',
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
> **Dukungan Upload Foto Motor di API:** Mengunggah file foto ke `storage/app/public/motors`.
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
            'foto'                => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
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
            'message' => 'Motor berhasil ditambahkan, menunggu verifikasi admin',
            'motor'   => $motor,
            'foto_url'=> $motor->foto_url,
        ], 201);
    }

    public function myMotors()
    {
        $motors = Motor::with('tarif')
            ->where('pemilik_id', auth('api')->id())
            ->get();

        return response()->json([
            'message' => 'Daftar motor milik Anda',
            'motors'  => $motors,
        ]);
    }

    public function revenueReport()
    {
        $laporan = BagiHasil::whereHas('penyewaan.motor', function ($query) {
            $query->where('pemilik_id', auth('api')->id());
        })->with('penyewaan.motor')->get();

        $totalPendapatan = $laporan->sum('bagi_hasil_pemilik');

        return response()->json([
            'message'          => 'Laporan pendapatan pemilik kendaraan',
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

## FASE 7: IMPLEMENTASI ADMIN PANEL & CRUD MODERN DENGAN MOONSHINE

Dengan library **MoonShine**, kita tidak perlu menulis puluhan file Blade manual dan Controller web yang panjang. MoonShine menangani seluruh tampilan tabel, form input, validasi, pencarian, badge status, **upload foto motor**, serta statistik visual secara otomatis.

### 7.1 Instalasi & Inisialisasi MoonShine di Windows

Buka terminal di direktori proyek `rental_motor`:
```bash
composer require moonshine/moonshine
php artisan moonshine:install
```
Proses ini akan otomatis:
* Membuat migration tabel bawaan pengguna admin (`moonshine_users`).
* Menerbitkan konfigurasi `config/moonshine.php`.
* Mendaftarkan `MoonShineServiceProvider`.

Jalankan migrasi tabel MoonShine:
```bash
php artisan migrate
```

---

### 7.2 Pembuatan Akun Super Admin MoonShine

Jalankan perintah ini di terminal Windows untuk membuat akun login Dashboard Admin:
```bash
php artisan moonshine:user
```
Terminal akan meminta input:
* **Username / Email:** `admin@rental.com`
* **Name:** `Admin Rental`
* **Password:** `admin123`

---

### 7.3 Resource MotorResource (CRUD Armada & Fitur Upload Foto Motor)

Jalankan perintah generator resource MoonShine:
```bash
php artisan moonshine:resource Motor
```
Perintah ini akan membuat file di `app/MoonShine/Resources/MotorResource.php`.

Edit file `app/MoonShine/Resources/MotorResource.php`:
> **Perhatikan Field Foto:** Menggunakan `Image::make('Foto Unit Motor', 'foto')` dengan disk `public` dan direktori `motors`. MoonShine akan otomatis membuat thumbnail di tabel, menyediakan tombol preview, tombol hapus foto (removable), serta validasi format gambar!

```php
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Motor;
use MoonShine\Resources\ModelResource;
use MoonShine\Fields\ID;
use MoonShine\Fields\Text;
use MoonShine\Fields\Select;
use MoonShine\Fields\Image;
use MoonShine\Fields\File;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\HasOne;

/**
 * @extends ModelResource<Motor>
 */
class MotorResource extends ModelResource
{
    protected string $model = Motor::class;

    protected string $title = 'Armada Motor';

    // Kolom pencarian otomatis
    protected array $search = ['merek', 'no_plat'];

    public function fields(): array
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Pemilik Kendaraan', 'pemilik', resource: new UserResource())
                ->searchable()
                ->required(),

            Text::make('Merek / Model', 'merek')
                ->required()
                ->sortable(),

            Select::make('Kapasitas Mesin', 'tipe_cc')
                ->options([
                    '100' => '100 CC',
                    '125' => '125 CC',
                    '150' => '150 CC',
                ])
                ->required(),

            Text::make('Nomor Plat', 'no_plat')
                ->required()
                ->sortable(),

            Select::make('Status Unit', 'status')
                ->options([
                    'menunggu_verifikasi' => 'Menunggu Verifikasi',
                    'tersedia'            => 'Tersedia',
                    'disewa'               => 'Sedang Disewa',
                ])
                ->badge(fn($status) => match($status) {
                    'tersedia' => 'success',
                    'disewa'   => 'warning',
                    default    => 'gray',
                }),

            // FITUR UPLOAD FOTO MOTOR LENGKAP
            Image::make('Foto Unit Motor', 'foto')
                ->disk('public')
                ->dir('motors')
                ->removable()
                ->allowedExtensions(['jpg', 'jpeg', 'png', 'webp']),

            File::make('Dokumen Kepemilikan (STNK/BPKB)', 'documen_kepemilikan')
                ->disk('public')
                ->dir('documents')
                ->removable()
                ->allowedExtensions(['pdf', 'jpg', 'png']),

            HasOne::make('Tarif Rental', 'tarif', resource: new TarifRentalResource())
                ->hideOnIndex(),
        ];
    }

    public function rules(mixed $item): array
    {
        return [
            'pemilik_id'          => ['required', 'exists:users,id'],
            'merek'               => ['required', 'string', 'max:100'],
            'tipe_cc'             => ['required', 'in:100,125,150'],
            'no_plat'             => ['required', 'string', 'max:20', 'unique:motors,no_plat,' . $item?->id],
            'foto'                => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'documen_kepemilikan' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg', 'max:2048'],
        ];
    }

    public function filters(): array
    {
        return [
            Select::make('Filter Status', 'status')
                ->options([
                    'menunggu_verifikasi' => 'Menunggu Verifikasi',
                    'tersedia'            => 'Tersedia',
                    'disewa'               => 'Sedang Disewa',
                ]),
        ];
    }
}
```

---

### 7.4 Resource TarifRentalResource (Penetapan Tarif Harian, Mingguan, Bulanan)

Generate resource:
```bash
php artisan moonshine:resource TarifRental
```
Buka `app/MoonShine/Resources/TarifRentalResource.php`:
```php
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\TarifRental;
use MoonShine\Resources\ModelResource;
use MoonShine\Fields\ID;
use MoonShine\Fields\Number;
use MoonShine\Fields\Relationships\BelongsTo;

class TarifRentalResource extends ModelResource
{
    protected string $model = TarifRental::class;

    protected string $title = 'Tarif Rental';

    public function fields(): array
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Unit Motor', 'motor', resource: new MotorResource())
                ->searchable()
                ->required(),

            Number::make('Tarif Harian (Rp)', 'tarif_harian')
                ->required()
                ->sortable(),

            Number::make('Tarif Mingguan (Rp)', 'tarif_mingguan')
                ->required()
                ->sortable(),

            Number::make('Tarif Bulanan (Rp)', 'tarif_bulanan')
                ->required()
                ->sortable(),
        ];
    }

    public function rules(mixed $item): array
    {
        return [
            'motor_id'       => ['required', 'exists:motors,id'],
            'tarif_harian'   => ['required', 'numeric', 'min:0'],
            'tarif_mingguan' => ['required', 'numeric', 'min:0'],
            'tarif_bulanan'  => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

---

### 7.5 Resource PenyewaanResource (Manajemen Transaksi Sewa)

Generate resource:
```bash
php artisan moonshine:resource Penyewaan
```
Buka `app/MoonShine/Resources/PenyewaanResource.php`:
```php
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Penyewaan;
use MoonShine\Resources\ModelResource;
use MoonShine\Fields\ID;
use MoonShine\Fields\Date;
use MoonShine\Fields\Select;
use MoonShine\Fields\Number;
use MoonShine\Fields\Relationships\BelongsTo;

class PenyewaanResource extends ModelResource
{
    protected string $model = Penyewaan::class;

    protected string $title = 'Pesanan Sewa';

    public function fields(): array
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Penyewa', 'penyewa', resource: new UserResource())
                ->searchable(),

            BelongsTo::make('Unit Motor Disewa', 'motor', resource: new MotorResource())
                ->searchable(),

            Date::make('Mulai Sewa', 'tanggal_mulai')
                ->sortable(),

            Date::make('Selesai Sewa', 'tanggal_selesai')
                ->sortable(),

            Select::make('Durasi', 'tipe_durasi')
                ->options([
                    'harian'   => 'Harian',
                    'mingguan' => 'Mingguan',
                    'bulanan'  => 'Bulanan',
                ]),

            Number::make('Total Biaya (Rp)', 'harga')
                ->sortable(),

            Select::make('Status Sewa', 'status')
                ->options([
                    'menunggu_pembayaran' => 'Menunggu Pembayaran',
                    'dikonfirmasi'        => 'Dikonfirmasi (Aktif Disewa)',
                    'selesai'             => 'Selesai (Kembali)',
                    'dibatalkan'          => 'Dibatalkan',
                ])
                ->badge(fn($status) => match($status) {
                    'dikonfirmasi' => 'success',
                    'selesai'      => 'info',
                    'dibatalkan'   => 'error',
                    default        => 'warning',
                }),
        ];
    }

    public function rules(mixed $item): array
    {
        return [
            'penyewa_id'      => ['required', 'exists:users,id'],
            'motor_id'        => ['required', 'exists:motors,id'],
            'tanggal_mulai'   => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date'],
            'tipe_durasi'     => ['required', 'in:harian,mingguan,bulanan'],
            'harga'           => ['required', 'numeric', 'min:0'],
            'status'          => ['required', 'in:menunggu_pembayaran,dikonfirmasi,selesai,dibatalkan'],
        ];
    }
}
```

---

### 7.6 Resource TransaksiResource (Pembayaran & Verifikasi)

Generate resource:
```bash
php artisan moonshine:resource Transaksi
```
Buka `app/MoonShine/Resources/TransaksiResource.php`:
```php
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Transaksi;
use MoonShine\Resources\ModelResource;
use MoonShine\Fields\ID;
use MoonShine\Fields\Text;
use MoonShine\Fields\Number;
use MoonShine\Fields\Select;
use MoonShine\Fields\Date;
use MoonShine\Fields\Relationships\BelongsTo;

class TransaksiResource extends ModelResource
{
    protected string $model = Transaksi::class;

    protected string $title = 'Data Pembayaran';

    public function fields(): array
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('ID Pemesanan', 'penyewaan', resource: new PenyewaanResource()),

            Number::make('Nominal (Rp)', 'jumlah')
                ->sortable(),

            Text::make('Metode Pembayaran', 'metode_pembayaran'),

            Select::make('Status Bayar', 'status')
                ->options([
                    'pending'  => 'Pending',
                    'berhasil' => 'Berhasil (Lunas)',
                    'gagal'    => 'Gagal',
                ])
                ->badge(fn($status) => match($status) {
                    'berhasil' => 'success',
                    'gagal'    => 'error',
                    default    => 'warning',
                }),

            Date::make('Tanggal Bayar', 'tanggal')
                ->sortable(),
        ];
    }

    public function rules(mixed $item): array
    {
        return [
            'pemesanan_id'      => ['required', 'exists:penyewaans,id'],
            'jumlah'            => ['required', 'numeric', 'min:0'],
            'metode_pembayaran' => ['required', 'string'],
            'status'            => ['required', 'in:pending,berhasil,gagal'],
        ];
    }
}
```

---

### 7.7 Resource BagiHasilResource (Laporan Keuangan & Export Excel/CSV)

Generate resource:
```bash
php artisan moonshine:resource BagiHasil
```
Buka `app/MoonShine/Resources/BagiHasilResource.php`:
```php
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\BagiHasil;
use MoonShine\Resources\ModelResource;
use MoonShine\Fields\ID;
use MoonShine\Fields\Number;
use MoonShine\Fields\Date;
use MoonShine\Fields\Relationships\BelongsTo;

class BagiHasilResource extends ModelResource
{
    protected string $model = BagiHasil::class;

    protected string $title = 'Laporan Bagi Hasil';

    public function fields(): array
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('Pemesanan Sewa', 'penyewaan', resource: new PenyewaanResource()),

            Number::make('Porsi Pemilik (80%)', 'bagi_hasil_pemilik')
                ->sortable(),

            Number::make('Porsi Admin (20%)', 'bagi_hasil_admin')
                ->sortable(),

            Date::make('Tanggal Transaksi', 'tanggal')
                ->sortable(),

            Date::make('Penyelesaian (Settled)', 'settled_at'),
        ];
    }

    public function rules(mixed $item): array
    {
        return [
            'pemesanan_id'       => ['required', 'exists:penyewaans,id'],
            'bagi_hasil_pemilik' => ['required', 'numeric', 'min:0'],
            'bagi_hasil_admin'   => ['required', 'numeric', 'min:0'],
            'tanggal'            => ['required', 'date'],
        ];
    }
}
```

---

### 7.8 Resource UserResource (Manajemen Akun Pengguna)

Generate resource:
```bash
php artisan moonshine:resource User
```
Buka `app/MoonShine/Resources/UserResource.php`:
```php
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\User;
use MoonShine\Resources\ModelResource;
use MoonShine\Fields\ID;
use MoonShine\Fields\Text;
use MoonShine\Fields\Select;

class UserResource extends ModelResource
{
    protected string $model = User::class;

    protected string $title = 'Pengguna & Pelanggan';

    protected array $search = ['name', 'email', 'no_tlpn'];

    public function fields(): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Nama Lengkap', 'name')
                ->required()
                ->sortable(),

            Text::make('Email', 'email')
                ->required()
                ->sortable(),

            Text::make('No. Telepon/WA', 'no_tlpn')
                ->required(),

            Select::make('Peran (Role)', 'role')
                ->options([
                    'admin'   => 'Admin Sistem',
                    'pemilik' => 'Pemilik Motor',
                    'penyewa' => 'Penyewa / Customer',
                ])
                ->badge(fn($role) => match($role) {
                    'admin'   => 'purple',
                    'pemilik' => 'info',
                    default   => 'gray',
                }),
        ];
    }

    public function rules(mixed $item): array
    {
        return [
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'unique:users,email,' . $item?->id],
            'no_tlpn' => ['required', 'string', 'max:20'],
            'role'    => ['required', 'in:admin,pemilik,penyewa'],
        ];
    }
}
```

---

### 7.9 Konfigurasi Menu Navigasi MoonShine

Daftarkan seluruh resource ke dalam menu sidebar MoonShine. Buka file `app/Providers/MoonShineServiceProvider.php`:
```php
<?php

declare(strict_types=1);

namespace App\Providers;

use MoonShine\Providers\MoonShineApplicationServiceProvider;
use MoonShine\Menu\MenuGroup;
use MoonShine\Menu\MenuItem;
use App\MoonShine\Resources\MotorResource;
use App\MoonShine\Resources\TarifRentalResource;
use App\MoonShine\Resources\PenyewaanResource;
use App\MoonShine\Resources\TransaksiResource;
use App\MoonShine\Resources\BagiHasilResource;
use App\MoonShine\Resources\UserResource;

class MoonShineServiceProvider extends MoonShineApplicationServiceProvider
{
    protected function menu(): array
    {
        return [
            MenuGroup::make('Armada Kendaraan', [
                MenuItem::make('Daftar Motor & Foto', new MotorResource())
                    ->icon('heroicons.outline.truck'),
                MenuItem::make('Tarif Rental', new TarifRentalResource())
                    ->icon('heroicons.outline.currency-dollar'),
            ]),

            MenuGroup::make('Operasional Sewa', [
                MenuItem::make('Transaksi Penyewaan', new PenyewaanResource())
                    ->icon('heroicons.outline.shopping-cart'),
                MenuItem::make('Pembayaran', new TransaksiResource())
                    ->icon('heroicons.outline.credit-card'),
            ]),

            MenuGroup::make('Keuangan & Laporan', [
                MenuItem::make('Laporan Bagi Hasil', new BagiHasilResource())
                    ->icon('heroicons.outline.chart-bar'),
            ]),

            MenuGroup::make('Pengaturan Akun', [
                MenuItem::make('Data Pengguna', new UserResource())
                    ->icon('heroicons.outline.users'),
            ]),
        ];
    }
}
```

---

### 7.10 Landing Page Publik Katalog Motor

Untuk halaman depan pengunjung umum yang ingin melihat katalog motor yang tersedia beserta fotonya, buat tampilan di `resources/views/welcome.blade.php`:

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Motor - Katalog Publik</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
    <!-- Navbar -->
    <header class="bg-indigo-700 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-tight">🏍️ Sistem Rental Motor</h1>
            <div class="space-x-3">
                <a href="/admin" class="bg-white text-indigo-700 font-semibold px-4 py-2 rounded-lg shadow hover:bg-slate-100 transition">
                    Login Admin Panel (MoonShine)
                </a>
            </div>
        </div>
    </header>

    <!-- Konten Katalog -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h2 class="text-3xl font-extrabold text-slate-900">Katalog Armada Tersedia</h2>
            <p class="text-slate-600">Pilih unit motor favorit Anda dengan kondisi prima dan siap pakai.</p>
        </div>

        @php
            $motors = \App\Models\Motor::with('tarif')->where('status', 'tersedia')->get();
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @forelse($motors as $motor)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col hover:shadow-md transition">
                    <!-- Foto Unit Motor -->
                    <div class="h-48 bg-slate-100 flex items-center justify-center overflow-hidden">
                        @if($motor->foto)
                            <img src="{{ asset('storage/' . $motor->foto) }}" alt="{{ $motor->merek }}" class="w-full h-full object-cover">
                        @else
                            <div class="text-slate-400 text-center">
                                <span class="text-4xl">🛵</span>
                                <p class="text-xs mt-1">Belum ada foto</p>
                            </div>
                        @endif
                    </div>

                    <!-- Informasi Motor -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-bold text-slate-900">{{ $motor->merek }}</h3>
                                <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                    {{ $motor->tipe_cc }} CC
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Plat: {{ $motor->no_plat }}</p>
                        </div>

                        <!-- Tarif Rental -->
                        <div class="mt-4 pt-3 border-t border-slate-100">
                            @if($motor->tarif)
                                <div class="text-xs text-slate-600 space-y-1">
                                    <div class="flex justify-between">
                                        <span>Harian:</span>
                                        <span class="font-bold text-indigo-700">Rp {{ number_format($motor->tarif->tarif_harian, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Mingguan:</span>
                                        <span>Rp {{ number_format($motor->tarif->tarif_mingguan, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @else
                                <p class="text-xs text-slate-400 italic">Tarif belum diatur</p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400">
                    <span class="text-5xl block mb-2">🏍️</span>
                    <p class="text-lg font-medium">Belum ada unit motor yang berstatus tersedia saat ini.</p>
                </div>
            @endforelse
        </div>
    </main>
</body>
</html>
```

---

## FASE 8: KONFIGURASI SELURUH ROUTING (API & WEB)

### 8.1 File `routes/api.php`
Endpoint lengkap untuk pengujian REST API (Mobile / Postman):
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
    Route::post('/motors', [OwnerController::class, 'storeMotor']); // Upload foto via API
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

### 8.2 File `routes/web.php`
Dengan MoonShine, rute admin otomatis ditangani oleh engine MoonShine di URL `/admin`. Rute web menjadi sangat ringkas:
```php
<?php

use Illuminate\Support\Facades\Route;

// Halaman Katalog Publik untuk Pengunjung
Route::get('/', function () {
    return view('welcome');
});
```

---

## 9. CARA MENJALANKAN & MENGUJI DI WINDOWS

### 9.1 Hubungkan Folder Storage (Symlink Foto Motor)
Agar gambar foto motor yang diunggah dapat diakses oleh browser:
```bash
php artisan storage:link
```
> **Catatan Windows:** Jika muncul peringatan *"symlink(): Cannot create symlink"*, buka Command Prompt atau PowerShell dengan opsi **"Run as Administrator"** atau aktifkan **Developer Mode** di Settings Windows 10/11.

---

### 9.2 Inisialisasi Database & Seeder Akun Pengujian

Jalankan migrasi seluruh tabel (termasuk tabel MoonShine dan Trigger):
```bash
php artisan migrate
```

Jalankan database seeder:
```bash
php artisan db:seed
```

Data user uji yang dibuat:
1. **Pemilik:** `budi@example.com` (password: `password123`)
2. **Penyewa:** `siti@example.com` (password: `password123`)
3. **Admin MoonShine:** `admin@rental.com` (password: `admin123`) *(Dibuat melalui `php artisan moonshine:user`)*

---

### 9.3 Jalankan Server Laravel di Windows

Buka Command Prompt / PowerShell di folder `rental_motor`:
```bash
php artisan serve
```
Server aktif di: **`http://127.0.0.1:8000`**

---

### 9.4 Skenario Pengujian Lengkap & Pengunggahan Foto

1. **Akses Dashboard MoonShine:**
   * Buka browser ke **`http://127.0.0.1:8000/admin`**.
   * Masukkan email: `admin@rental.com` dan password: `admin123`.

2. **Uji Fitur Upload Foto Motor:**
   * Masuk ke menu **Armada Kendaraan -> Daftar Motor & Foto**.
   * Klik tombol **Create / Tambah Baru**.
   * Pilih Pemilik (`Budi`), ketik Merek: `Honda PCX 160`, Plat: `B 9999 XYZ`, Tipe CC: `150 CC`.
   * Pada input **"Foto Unit Motor"**, klik tombol pilih file dan unggah foto motor (JPG/PNG).
   * Klik **Save**.
   * **Hasil:** Foto langsung tampil sebagai thumbnail rapi di tabel data motor. Klik gambar untuk melihat ukuran penuh.

3. **Uji Penetapan Tarif & Verifikasi:**
   * Masuk ke menu **Armada Kendaraan -> Tarif Rental**.
   * Tambahkan tarif untuk motor tadi (Harian: 120.000, Mingguan: 700.000, Bulanan: 2.500.000).
   * Ubah status motor dari `menunggu_verifikasi` menjadi `tersedia`.

4. **Cek Halaman Publik:**
   * Buka tab baru di browser: **`http://127.0.0.1:8000/`**.
   * Unit motor yang baru Anda upload fotonya dan diberi tarif akan otomatis muncul di grid katalog dengan tampilan kartu yang bersih dan modern.

5. **Uji Siklus Sewa & Trigger MySQL:**
   * Buka menu **Operasional Sewa -> Transaksi Penyewaan**.
   * Buat pesanan baru untuk `Siti`.
   * Buka menu **Pembayaran**, catat pembayaran dengan status `berhasil`.
   * Ubah status sewa menjadi `dikonfirmasi`.
   * **Verifikasi Trigger:** Kembali ke menu **Daftar Motor & Foto**, status unit motor otomatis berubah menjadi `disewa` tanpa Anda ubah manual!
   * Ubah status sewa menjadi `selesai`. Status motor otomatis kembali menjadi `tersedia`.

6. **Cek Laporan Bagi Hasil:**
   * Buka menu **Keuangan & Laporan -> Laporan Bagi Hasil**.
   * Data pembagian hasil 80% (Pemilik) dan 20% (Admin) tercatat dengan rapi dan dapat diekspor langsung ke file Excel/CSV.

---

## 10. TABEL KREDENSIAL AKUN PENGUJIAN

| Peran (Role) | Email | Password | Halaman Akses | Fitur Utama |
|---|---|---|---|---|
| **Admin MoonShine** | `admin@rental.com` | `admin123` | `http://127.0.0.1:8000/admin` | Dashboard visual, CRUD Armada & **Upload Foto Motor**, verifikasi tarif, pantau booking, dan export laporan bagi hasil |
| **Pemilik Motor** | `budi@example.com` | `password123` | Via REST API `/api/owner/*` | Titip unit motor baru, upload foto motor via API, cek laporan pendapatan bagi hasil 80% |
| **Penyewa Motor** | `siti@example.com` | `password123` | `http://127.0.0.1:8000/` & `/api/*` | Melihat katalog motor dengan foto & tarif di web publik, sewa motor dan riwayat booking via API |

---

## 11. PANDUAN TROUBLESHOOTING MASALAH UMUM DI WINDOWS

### 1. Masalah: Error Saat Upload Foto Motor (`GD extension` / `Fileinfo`)
* **Gejala:** `Call to undefined function imagecreatefromjpeg()` atau `Class 'finfo' not found`.
* **Solusi:**
  1. Buka file `php.ini` di folder PHP Anda (`C:\xampp\php\php.ini` atau Laragon).
  2. Cari dan hilangkan titik koma di depan:
     ```ini
     extension=fileinfo
     extension=gd
     ```
  3. Simpan dan restart Apache / PHP.

---

### 2. Masalah: Foto Motor Tidak Muncul di Browser (Error 404 pada Gambar)
* **Gejala:** Gambar rusak atau URL `http://127.0.0.1:8000/storage/motors/...` mengembalikan 404 Not Found.
* **Solusi di Windows:**
  1. Hapus folder shortcut `public/storage` jika sudah terlanjur dibuat secara tidak sempurna.
  2. Buka Command Prompt / PowerShell dengan klik kanan -> **"Run as Administrator"**.
  3. Jalankan kembali:
     ```bash
     php artisan storage:link
     ```

---

### 3. Masalah: Aset Tampilan MoonShine (CSS/JS) Tidak Termuat
* **Gejala:** Halaman `/admin` tampil berantakan tanpa gaya CSS.
* **Solusi:**
  Jalankan perintah publish aset MoonShine:
  ```bash
  php artisan moonshine:publish
  php artisan optimize:clear
  ```

---

### 4. Masalah: Driver MySQL Tidak Ditemukan (`could not find driver`)
* **Gejala:** `PDOException: could not find driver` saat menjalankan `php artisan migrate`.
* **Solusi:**
  Pastikan `extension=pdo_mysql` di `php.ini` sudah aktif, lalu restart server web Anda.

---

### 5. Masalah: Hak Akses Pembuatan Trigger MySQL di Windows
* **Gejala:** `This function has none of DETERMINISTIC...` atau `Access denied for user to CREATE TRIGGER`.
* **Solusi:**
  Masuk ke phpMyAdmin / HeidiSQL, buka tab SQL dan jalankan query:
  ```sql
  SET GLOBAL log_bin_trust_function_creators = 1;
  ```
  Lalu jalankan ulang `php artisan migrate`.

---

### 6. Masalah: Secret Key JWT Belum Dibuat
* **Gejala:** `Tymon\JWTAuth\Exceptions\JWTException: Secret key is not set`.
* **Solusi:**
  ```bash
  php artisan jwt:secret
  ```
