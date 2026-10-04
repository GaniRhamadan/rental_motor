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
   - [7.1 Instalasi MoonShine yang Benar di Windows](#71-instalasi-moonshine-yang-benar-di-windows)
   - [7.2 Registrasi MoonShineServiceProvider (Krusial untuk Laravel 11+)](#72-registrasi-moonshineserviceprovider-krusial-untuk-laravel-11)
   - [7.3 Pembuatan Akun Super Admin MoonShine](#73-pembuatan-akun-super-admin-moonshine)
   - [7.4 Resource MotorResource (CRUD Armada & Fitur Upload Foto Motor)](#74-resource-motorresource-crud-armada--fitur-upload-foto-motor)
   - [7.5 Resource TarifRentalResource (Penetapan Tarif Harian, Mingguan, Bulanan)](#75-resource-tarifrentalresource-penetapan-tarif-harian-mingguan-bulanan)
   - [7.6 Resource PenyewaanResource (Manajemen Transaksi Sewa)](#76-resource-penyewaanresource-manajemen-transaksi-sewa)
   - [7.7 Resource TransaksiResource (Pembayaran & Verifikasi)](#77-resource-transaksiresource-pembayaran--verifikasi)
   - [7.8 Resource BagiHasilResource (Laporan Keuangan & Export Excel/CSV)](#78-resource-bagihasilresource-laporan-keuangan--export-excelcsv)
   - [7.9 Resource UserResource (Manajemen Akun Pengguna)](#79-resource-userresource-manajemen-akun-pengguna)
   - [7.10 Konfigurasi Menu Navigasi MoonShine](#710-konfigurasi-menu-navigasi-moonshine)
   - [7.11 Landing Page Publik Katalog Motor](#711-landing-page-publik-katalog-motor)
8. [Fase 8: Konfigurasi Seluruh Routing (API & Web)](#fase-8-konfigurasi-seluruh-routing-api--web)
9. [Fase 9: Database Seeder & Menjalankan di Windows](#fase-9-database-seeder--menjalankan-di-windows)
   - [9.1 File Seeder Lengkap (DatabaseSeeder.php)](#91-file-seeder-lengkap-databaseseederphp)
   - [9.2 Hubungkan Folder Storage (Symlink Foto Motor di Windows)](#92-hubungkan-folder-storage-symlink-foto-motor-di-windows)
   - [9.3 Migrasi & Eksekusi Seeder](#93-migrasi--eksekusi-seeder)
   - [9.4 Jalankan Server Laravel di Windows](#94-jalankan-server-laravel-di-windows)
   - [9.5 Skenario Pengujian Lengkap & Verifikasi Alur](#95-skenario-pengujian-lengkap--verifikasi-alur)
10. [Tabel Kredensial Akun Pengujian](#10-tabel-kredensial-akun-pengujian)
11. [Panduan Troubleshooting Masalah Umum di Windows](#11-panduan-troubleshooting-masalah-umum-di-windows)

---

## 1. PRASYARAT & PERSIAPAN LINGKUNGAN DI WINDOWS

Pastikan aplikasi berikut sudah terinstal di Windows:
1. **PHP >= 8.2** (Rekomendasi PHP 8.2 atau 8.3).  
   Buka file konfigurasi `php.ini` (misal di `C:\xampp\php\php.ini` atau melalui menu Laragon: **PHP -> php.ini**). Pastikan ekstensi berikut **aktif** (hilangkan tanda titik koma `;` di baris depannya):
   ```ini
   extension=pdo_mysql
   extension=mbstring
   extension=openssl
   extension=curl
   extension=fileinfo
   extension=gd
   extension=intl
   extension=zip
   ```
   > **Catatan:** Setelah mengedit `php.ini`, simpan file dan **Restart Apache / Web Server** Anda.

2. **Composer** (Download installer Windows `.exe` dari [getcomposer.org](https://getcomposer.org)).
3. **MySQL / MariaDB** (Aktif melalui XAMPP Control Panel atau Laragon).
4. **Tips Windows Developer Mode (Sangat Disarankan):**  
   Agar perintah pembuatan symlink gambar (`php artisan storage:link`) tidak gagal akibat pembatasan hak akses Windows (*Error Code 1314: A required privilege is not held by the client*), aktifkan **Developer Mode**:
   - Buka menu Windows **Settings** -> **System** (atau **Update & Security**) -> **For developers**.
   - Ubah toggle **Developer Mode** menjadi **ON**.

### Langkah Awal Database di Windows:
1. Pastikan modul **Apache** dan **MySQL** sudah berstatus **Running** di kontrol panel XAMPP atau Laragon.
2. Buka terminal (PowerShell / Command Prompt) atau buka `http://localhost/phpmyadmin`, lalu jalankan query SQL:
   ```sql
   CREATE DATABASE rental_motor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

---

## FASE 1: INISIALISASI PROYEK & KONFIGURASI AUTH

### 1.1 Buat Proyek Laravel Baru
Buka terminal (CMD / PowerShell / Git Bash) di folder kerja Windows kamu (misal di `C:\xampp\htdocs\` atau `C:\laragon\www\`):
```bash
composer create-project laravel/laravel rental_motor
cd rental_motor
```

### 1.2 Konfigurasi Database di `.env`
Buka file `.env` di teks editor (VS Code, Cursor, atau Notepad++), sesuaikan konfigurasi database:
```env
APP_NAME="Rental Motor"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rental_motor
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
FILESYSTEM_DISK=public
```

> **Catatan Penting Windows:** Pastikan `APP_URL` menggunakan `http://127.0.0.1:8000` (bukan `localhost`) agar pemanggilan aset gambar foto motor di browser selalu konsisten.

### 1.3 Install Paket JWT Auth (Untuk REST API Mobile)
Jalankan perintah berikut di terminal:
```bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```
Perintah `php artisan jwt:secret` akan otomatis menambahkan `JWT_SECRET=xxxx` ke dalam file `.env`.

### 1.4 Konfigurasi Multi-Guard di `config/auth.php`
> **Perhatian Khusus Laravel 11+:**  
> Pada Laravel versi 11 ke atas, folder `config/` dibuat sangat minimalis dan file `config/auth.php` **belum tersedia secara default**. Jika file `config/auth.php` belum ada di proyek Anda, jalankan perintah publish terlebih dahulu:
> ```bash
> php artisan config:publish auth
> ```

Setelah file `config/auth.php` tersedia, buka file tersebut dan ubah bagian `defaults` serta `guards`:
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
2. `motors` (Unit kendaraan + **Foto Unit Motor** & Dokumen STNK)
3. `tarif_rentals` (Tarif harian, mingguan, bulanan)
4. `penyewaans` (Transaksi pemesanan sewa)
5. `transaksis` (Catatan pembayaran)
6. `bagi_hasils` (Kalkulasi otomatis porsi Pemilik 80% & Admin 20%)

### 2.1 Modifikasi Migration Tabel `users`
> **⚠️ PENTING - JANGAN HAPUS TABEL SESSIONS:**  
> Jangan menghapus definisi tabel `password_reset_tokens` dan `sessions` yang ada di file bawaan Laravel! Karena di Laravel 11+ nilai `SESSION_DRIVER=database`, jika tabel `sessions` hilang, aplikasi web dan admin panel MoonShine akan langsung crash dengan error `Table rental_motor.sessions doesn't exist`.

Buka file `database/migrations/0001_01_01_000000_create_users_table.php`, sesuaikan method `up()` menjadi:
```php
public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('no_tlpn', 20);
        $table->enum('role', ['admin', 'pemilik', 'penyewa'])->default('penyewa');
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('password_reset_tokens', function (Blueprint $table) {
        $table->string('email')->primary();
        $table->string('token');
        $table->timestamp('created_at')->nullable();
    });

    Schema::create('sessions', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->foreignId('user_id')->nullable()->index();
        $table->string('ip_address', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->longText('payload');
        $table->integer('last_activity')->index();
    });
}
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

Buka dan isi masing-masing file migration yang baru dibuat di folder `database/migrations/`:

#### A. File `database/migrations/xxxx_xx_xx_create_motors_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemilik_id')->constrained('users')->onDelete('cascade');
            $table->string('merek', 100);
            $table->enum('tipe_cc', ['100', '125', '150']);
            $table->string('no_plat', 20)->unique();
            $table->enum('status', ['menunggu_verifikasi', 'tersedia', 'disewa'])->default('menunggu_verifikasi');
            $table->string('foto')->nullable(); // Path foto unit motor
            $table->string('documen_kepemilikan')->nullable(); // Dokumen STNK / BPKB
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motors');
    }
};
```

#### B. File `database/migrations/xxxx_xx_xx_create_tarif_rentals_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('motor_id')->constrained('motors')->onDelete('cascade');
            $table->decimal('tarif_harian', 12, 2);
            $table->decimal('tarif_mingguan', 12, 2);
            $table->decimal('tarif_bulanan', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_rentals');
    }
};
```

#### C. File `database/migrations/xxxx_xx_xx_create_penyewaans_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('penyewaans');
    }
};
```

#### D. File `database/migrations/xxxx_xx_xx_create_transaksis_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemesanan_id')->constrained('penyewaans')->onDelete('cascade');
            $table->decimal('jumlah', 12, 2);
            $table->string('metode_pembayaran', 50);
            $table->enum('status', ['pending', 'berhasil', 'gagal'])->default('pending');
            $table->timestamp('tanggal')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
```

#### E. File `database/migrations/xxxx_xx_xx_create_bagi_hasils_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bagi_hasils', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemesanan_id')->constrained('penyewaans')->onDelete('cascade');
            $table->decimal('bagi_hasil_pemilik', 12, 2);
            $table->decimal('bagi_hasil_admin', 12, 2);
            $table->timestamp('settled_at')->nullable();
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bagi_hasils');
    }
};
```

---

### 2.3 Konfigurasi Seluruh Eloquent Models

Buat file model melalui perintah Artisan berikut:
```bash
php artisan make:model Motor
php artisan make:model TarifRental
php artisan make:model Penyewaan
php artisan make:model Transaksi
php artisan make:model BagiHasil
```
*(Catatan: Model `User.php` sudah otomatis dibuatkan saat instalasi Laravel di `app/Models/User.php`)*.

Isi masing-masing file model di folder `app/Models/`:

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
        'name',
        'email',
        'no_tlpn',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
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
        'pemilik_id',
        'merek',
        'tipe_cc',
        'no_plat',
        'status',
        'foto',
        'documen_kepemilikan',
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
        'motor_id',
        'tarif_harian',
        'tarif_mingguan',
        'tarif_bulanan',
    ];

    public function motor()
    {
        return $this->belongsTo(Motor::class, 'motor_id');
    }
}
```

#### File: `app/Models/Penyewaan.php`
> **Krusial - Otomatisasi Bagi Hasil via Eloquent Event:**  
> Kode method `booted()` di bawah memastikan bahwa saat admin mengubah status sewa menjadi `dikonfirmasi` (baik lewat REST API maupun Dashboard MoonShine), data pembagian hasil 80% (pemilik) dan 20% (admin) otomatis dihitung dan disimpan ke tabel `bagi_hasils`!
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penyewaan extends Model
{
    protected $fillable = [
        'penyewa_id',
        'motor_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'tipe_durasi',
        'harga',
        'status',
    ];

    protected static function booted(): void
    {
        static::saved(function (Penyewaan $penyewaan) {
            // Jika status penyewaan berubah menjadi 'dikonfirmasi', hitung otomatis Bagi Hasil 80% : 20%
            if ($penyewaan->status === 'dikonfirmasi') {
                $totalBayar   = (float) $penyewaan->harga;
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
            }

            // Jika status berubah menjadi 'selesai', tandai tanggal settled_at
            if ($penyewaan->status === 'selesai' && $penyewaan->bagiHasil) {
                $penyewaan->bagiHasil->update(['settled_at' => now()]);
            }
        });
    }

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
        'pemesanan_id',
        'jumlah',
        'metode_pembayaran',
        'status',
        'tanggal',
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
        'pemesanan_id',
        'bagi_hasil_pemilik',
        'bagi_hasil_admin',
        'settled_at',
        'tanggal',
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

### 4.2 Daftarkan Alias Middleware & Routing di `bootstrap/app.php`
> **Krusial untuk Laravel 11+:**  
> Pada Laravel 11 ke atas, file `routes/api.php` harus didaftarkan di dalam method `withRouting()` pada file `bootstrap/app.php`.

Buka file `bootstrap/app.php`:
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
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
            'no_tlpn'  => 'required|string|max:20',
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
            'message'    => 'Registrasi berhasil',
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
            'message'    => 'Login berhasil',
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
            'no_plat'             => 'required|string|max:20|unique:motors,no_plat',
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
            'message'  => 'Motor berhasil ditambahkan, menunggu verifikasi admin',
            'motor'    => $motor,
            'foto_url' => $motor->foto_url,
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

        // Mengubah status sewa menjadi 'dikonfirmasi'.
        // Event booted() di model Penyewaan akan otomatis mencatat BagiHasil 80% : 20%.
        $penyewaan->update(['status' => 'dikonfirmasi']);

        return response()->json([
            'message'    => 'Penyewaan berhasil dikonfirmasi, status motor kini disewa dan bagi hasil telah tercatat',
            'penyewaan'  => $penyewaan->fresh(['motor', 'bagiHasil']),
        ]);
    }

    public function returnBooking($id)
    {
        $penyewaan = Penyewaan::findOrFail($id);
        // Mengubah status sewa menjadi 'selesai'. Trigger MySQL akan otomatis mengubah status motor kembali 'tersedia'.
        $penyewaan->update(['status' => 'selesai']);

        return response()->json([
            'message'   => 'Pengembalian motor berhasil dikonfirmasi. Status motor kini kembali tersedia',
            'penyewaan' => $penyewaan->fresh(['motor', 'bagiHasil']),
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
            'metode_pembayaran' => 'required|string|max:50',
        ]);

        $penyewaan = Penyewaan::findOrFail($validated['pemesanan_id']);

        if ($penyewaan->penyewa_id !== auth('api')->id()) {
            return response()->json(['message' => 'Tidak memiliki otorisasi untuk pesanan ini'], 403);
        }

        $transaksi = Transaksi::updateOrCreate(
            ['pemesanan_id' => $penyewaan->id],
            [
                'jumlah'            => $penyewaan->harga,
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'status'            => 'berhasil',
                'tanggal'           => now(),
            ]
        );

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

Dengan library **MoonShine 2.x**, seluruh tampilan tabel, form input, validasi, relasi dropdown, badge status, **fitur upload foto motor**, serta export laporan ditangani otomatis tanpa perlu membuat puluhan file Blade manual.

### 7.1 Instalasi MoonShine yang Benar di Windows

> **⚠️ PENTING - HINDARI KESALAHAN VERSI:**  
> 1. Jika Anda hanya mengetik `composer require moonshine/moonshine`, Composer akan menginstal **MoonShine v4** yang memiliki struktur namespace berbeda drastis sehingga seluruh kode resource akan error `Class not found`.  
> 2. Pada Composer versi 2.8 ke atas, Composer secara default memblokir paket dengan peringatan advisory. Oleh karena itu, kita harus menonaktifkan blokir advisory terlebih dahulu sebelum menginstal versi `^2.24`.

Jalankan perintah berikut secara berurutan di terminal proyek:
```bash
composer config policy.advisories.block false
composer require "moonshine/moonshine:^2.24"
```
*(Catatan: Jika Anda menggunakan PHP versi 8.4 ke atas, tambahkan parameter `--ignore-platform-req=php`: `composer require "moonshine/moonshine:^2.24" --ignore-platform-req=php`)*.

Setelah proses composer selesai, jalankan instalasi MoonShine:
```bash
php artisan moonshine:install
```

### 7.2 Registrasi MoonShineServiceProvider (Krusial untuk Laravel 11+)
Pada Laravel 11+, file Service Provider baru harus didaftarkan di dalam file `bootstrap/providers.php`. Buka file `bootstrap/providers.php` dan pastikan `App\Providers\MoonShineServiceProvider::class` sudah terdaftar:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\MoonShineServiceProvider::class,
];
```
> **Peringatan:** Jika baris `App\Providers\MoonShineServiceProvider::class` belum ada, rute `/admin` akan menghasilkan error **404 Not Found**.

Lanjutkan migrasi database MoonShine:
```bash
php artisan migrate
```

---

### 7.3 Pembuatan Akun Super Admin MoonShine
Jalankan perintah ini di terminal Windows untuk membuat akun login Dashboard Admin:
```bash
php artisan moonshine:user
```
Terminal akan meminta input:
* **Username / Email:** `admin@rental.com`
* **Name:** `Admin Rental`
* **Password:** `admin123`

*(Catatan: Akun ini juga otomatis dibuatkan jika Anda menjalankan Seeder di [Fase 9](#fase-9-database-seeder--menjalankan-di-windows))*.

---

### 7.4 Resource MotorResource (CRUD Armada & Fitur Upload Foto Motor)
Jalankan generator resource:
```bash
php artisan moonshine:resource Motor
```
Buka file `app/MoonShine/Resources/MotorResource.php`, sesuaikan isinya:

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

    // Kolom representasi yang ditampilkan di dropdown relasi resource lain
    protected string $column = 'merek';

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

            // FITUR UPLOAD FOTO UNIT MOTOR
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

### 7.5 Resource TarifRentalResource (Penetapan Tarif Harian, Mingguan, Bulanan)
Generate resource:
```bash
php artisan moonshine:resource TarifRental
```
Buka file `app/MoonShine/Resources/TarifRentalResource.php`:

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

### 7.6 Resource PenyewaanResource (Manajemen Transaksi Sewa)
Generate resource:
```bash
php artisan moonshine:resource Penyewaan
```
Buka file `app/MoonShine/Resources/PenyewaanResource.php`:

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
                ->searchable()
                ->required(),

            BelongsTo::make('Unit Motor Disewa', 'motor', resource: new MotorResource())
                ->searchable()
                ->required(),

            Date::make('Mulai Sewa', 'tanggal_mulai')
                ->sortable()
                ->required(),

            Date::make('Selesai Sewa', 'tanggal_selesai')
                ->sortable()
                ->required(),

            Select::make('Durasi', 'tipe_durasi')
                ->options([
                    'harian'   => 'Harian',
                    'mingguan' => 'Mingguan',
                    'bulanan'  => 'Bulanan',
                ])
                ->required(),

            Number::make('Total Biaya (Rp)', 'harga')
                ->sortable()
                ->required(),

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

### 7.7 Resource TransaksiResource (Pembayaran & Verifikasi)
Generate resource:
```bash
php artisan moonshine:resource Transaksi
```
Buka file `app/MoonShine/Resources/TransaksiResource.php`:

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

            BelongsTo::make('ID Pemesanan', 'penyewaan', resource: new PenyewaanResource())
                ->searchable()
                ->required(),

            Number::make('Nominal (Rp)', 'jumlah')
                ->sortable()
                ->required(),

            Text::make('Metode Pembayaran', 'metode_pembayaran')
                ->required(),

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
            'metode_pembayaran' => ['required', 'string', 'max:50'],
            'status'            => ['required', 'in:pending,berhasil,gagal'],
        ];
    }
}
```

---

### 7.8 Resource BagiHasilResource (Laporan Keuangan & Export Excel/CSV)
Generate resource:
```bash
php artisan moonshine:resource BagiHasil
```
Buka file `app/MoonShine/Resources/BagiHasilResource.php`:

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

### 7.9 Resource UserResource (Manajemen Akun Pengguna)
Generate resource:
```bash
php artisan moonshine:resource User
```
Buka file `app/MoonShine/Resources/UserResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\User;
use MoonShine\Resources\ModelResource;
use MoonShine\Fields\ID;
use MoonShine\Fields\Text;
use MoonShine\Fields\Select;
use MoonShine\Fields\Password;
use Illuminate\Support\Facades\Hash;

class UserResource extends ModelResource
{
    protected string $model = User::class;

    protected string $title = 'Pengguna & Pelanggan';

    // Kolom representasi yang ditampilkan di dropdown relasi
    protected string $column = 'name';

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

            Password::make('Kata Sandi', 'password')
                ->hideOnIndex()
                ->onApply(fn($item, $value) => !empty($value) ? $item->password = Hash::make($value) : null),
        ];
    }

    public function rules(mixed $item): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email,' . $item?->id],
            'no_tlpn'  => ['required', 'string', 'max:20'],
            'role'     => ['required', 'in:admin,pemilik,penyewa'],
            'password' => [$item?->exists ? 'nullable' : 'required', 'string', 'min:6'],
        ];
    }
}
```

---

### 7.10 Konfigurasi Menu Navigasi MoonShine
Buka file `app/Providers/MoonShineServiceProvider.php`:

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

### 7.11 Landing Page Publik Katalog Motor
Buat / ganti isi file `resources/views/welcome.blade.php`:

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
                                    <div class="flex justify-between">
                                        <span>Bulanan:</span>
                                        <span>Rp {{ number_format($motor->tarif->tarif_bulanan, 0, ',', '.') }}</span>
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
Buat / edit file `routes/api.php`:
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookingController;

Route::get('/ping', fn () => response()->json(['status' => 'ok', 'message' => 'API Rental Motor Aktif']));

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

// Endpoint Publik Katalog Motor
Route::get('/motors', [BookingController::class, 'availableMotors']);

// Endpoint Penyewa Kendaraan
Route::middleware(['role:penyewa'])->group(function () {
    Route::post('/bookings', [BookingController::class, 'createBooking']);
    Route::post('/payments', [BookingController::class, 'payBooking']);
    Route::get('/bookings/history', [BookingController::class, 'myBookings']);
});
```

### 8.2 File `routes/web.php`
Buka file `routes/web.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

// Halaman Katalog Publik untuk Pengunjung
Route::get('/', function () {
    return view('welcome');
});
```

---

## FASE 9: DATABASE SEEDER & MENJALANKAN DI WINDOWS

### 9.1 File Seeder Lengkap (`DatabaseSeeder.php`)
Buka file `database/seeders/DatabaseSeeder.php` dan isi dengan kode lengkap di bawah ini. Seeder ini secara otomatis menyiapkan akun pengujian (Admin, Pemilik, Penyewa), akun MoonShine, unit motor contoh, serta tarif rental:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Motor;
use App\Models\TarifRental;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Admin Sistem (Bisa login via REST API & Web)
        $admin = User::updateOrCreate(
            ['email' => 'admin@rental.com'],
            [
                'name'     => 'Admin Rental',
                'no_tlpn'  => '081234567890',
                'role'     => 'admin',
                'password' => Hash::make('admin123'),
            ]
        );

        // 2. Akun Super Admin Khusus MoonShine Dashboard
        if (DB::getSchemaBuilder()->hasTable('moonshine_users')) {
            DB::table('moonshine_users')->updateOrInsert(
                ['email' => 'admin@rental.com'],
                [
                    'name'                  => 'Admin Rental',
                    'password'              => Hash::make('admin123'),
                    'moonshine_user_role_id'=> 1,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]
            );
        }

        // 3. Akun Pemilik Motor (Budi)
        $pemilik = User::updateOrCreate(
            ['email' => 'budi@example.com'],
            [
                'name'     => 'Budi Santoso',
                'no_tlpn'  => '081298765432',
                'role'     => 'pemilik',
                'password' => Hash::make('password123'),
            ]
        );

        // 4. Akun Penyewa (Siti)
        $penyewa = User::updateOrCreate(
            ['email' => 'siti@example.com'],
            [
                'name'     => 'Siti Rahma',
                'no_tlpn'  => '085712349876',
                'role'     => 'penyewa',
                'password' => Hash::make('password123'),
            ]
        );

        // 5. Unit Motor Uji Coba 1 (Honda PCX 160)
        $motor1 = Motor::updateOrCreate(
            ['no_plat' => 'B 1234 ABC'],
            [
                'pemilik_id'          => $pemilik->id,
                'merek'               => 'Honda PCX 160 CBS',
                'tipe_cc'             => '150',
                'status'              => 'tersedia',
                'foto'                => null,
                'documen_kepemilikan' => null,
            ]
        );

        TarifRental::updateOrCreate(
            ['motor_id' => $motor1->id],
            [
                'tarif_harian'   => 120000,
                'tarif_mingguan' => 700000,
                'tarif_bulanan'  => 2500000,
            ]
        );

        // 6. Unit Motor Uji Coba 2 (Yamaha NMAX 155)
        $motor2 = Motor::updateOrCreate(
            ['no_plat' => 'B 5678 DEF'],
            [
                'pemilik_id'          => $pemilik->id,
                'merek'               => 'Yamaha NMAX 155 Connected',
                'tipe_cc'             => '150',
                'status'              => 'tersedia',
                'foto'                => null,
                'documen_kepemilikan' => null,
            ]
        );

        TarifRental::updateOrCreate(
            ['motor_id' => $motor2->id],
            [
                'tarif_harian'   => 130000,
                'tarif_mingguan' => 750000,
                'tarif_bulanan'  => 2700000,
            ]
        );
    }
}
```

---

### 9.2 Hubungkan Folder Storage (Symlink Foto Motor di Windows)

Agar gambar dan dokumen yang diunggah ke `storage/app/public` dapat diakses langsung oleh browser melalui URL `/storage/...`:
```bash
php artisan storage:link
```

> **Solusi Error Windows Privileges (Error Code 1314):**  
> Jika muncul error `Cannot create symlink`, solusinya sangat mudah:
> 1. Buka Command Prompt / PowerShell dengan cara klik kanan -> **"Run as Administrator"**, lalu jalankan perintah `php artisan storage:link`.  
> 2. **Atau**, aktifkan **Developer Mode** di Settings Windows Anda (lihat [Bagian 1](#1-prasyarat--persiapan-lingkungan-di-windows)).

---

### 9.3 Migrasi & Eksekusi Seeder

Jalankan migrasi seluruh tabel (termasuk trigger MySQL) dan seeder data awal:
```bash
php artisan migrate:fresh --seed
```
Output akan menunjukkan semua tabel berhasil dibuat dan seluruh user uji coba siap digunakan.

---

### 9.4 Jalankan Server Laravel di Windows

Buka Command Prompt / PowerShell di folder `rental_motor`:
```bash
php artisan serve
```
Server aktif di: **`http://127.0.0.1:8000`**

---

### 9.5 Skenario Pengujian Lengkap & Verifikasi Alur

1. **Akses Landing Page Publik:**
   * Buka browser ke **`http://127.0.0.1:8000/`**.
   * Anda akan melihat 2 unit motor contoh (Honda PCX & Yamaha NMAX) lengkap dengan badge CC dan rincian tarif sewa.
2. **Login Dashboard MoonShine Admin:**
   * Buka browser ke **`http://127.0.0.1:8000/admin`**.
   * Masukkan email: `admin@rental.com` dan password: `admin123`.
3. **Uji Fitur Upload Foto Motor:**
   * Masuk ke menu **Armada Kendaraan -> Daftar Motor & Foto**.
   * Klik tombol edit pada salah satu motor atau klik **Create**.
   * Pada bagian **Foto Unit Motor**, pilih file gambar motor dari komputer Anda (`.jpg` / `.png`).
   * Klik **Save**.
   * **Hasil:** Foto motor langsung tampil sebagai thumbnail rapi di tabel admin MoonShine dan otomatis tampil di halaman utama `http://127.0.0.1:8000/`!
4. **Uji Siklus Transaksi Sewa & Otomatisasi Bagi Hasil:**
   * Masuk ke menu **Operasional Sewa -> Transaksi Penyewaan**.
   * Tambahkan pesanan baru untuk Penyewa `Siti Rahma`, pilih motor `Honda PCX 160`, durasi `harian`, harga `120000`, status `menunggu_pembayaran`.
   * Di menu **Pembayaran**, buat pembayaran dengan status `berhasil`.
   * Edit transaksi sewa tadi, ubah status sewa menjadi **dikonfirmasi**.
   * **Verifikasi Otomatisasi Trigger & Bagi Hasil:**
     - Kembali ke menu **Daftar Motor & Foto**: Status unit motor otomatis berubah menjadi **disewa** via trigger MySQL!
     - Buka menu **Keuangan & Laporan -> Laporan Bagi Hasil**: Data bagi hasil otomatis terisi (Porsi Pemilik 80% = Rp 96.000, Porsi Admin 20% = Rp 24.000)!
   * Ubah status sewa menjadi **selesai**: Status unit motor otomatis kembali menjadi **tersedia**!

---

## 10. TABEL KREDENSIAL AKUN PENGUJIAN

| Peran (Role) | Email | Password | Halaman Akses | Fitur Utama |
|---|---|---|---|---|
| **Admin MoonShine & API** | `admin@rental.com` | `admin123` | `http://127.0.0.1:8000/admin` & `/api/admin/*` | Dashboard visual visual MoonShine, CRUD Armada, **Upload Foto Motor**, verifikasi tarif, pantau booking, dan export laporan bagi hasil |
| **Pemilik Motor** | `budi@example.com` | `password123` | REST API `/api/owner/*` | Titip unit motor baru, upload foto motor via API, cek laporan pendapatan bagi hasil 80% |
| **Penyewa Motor** | `siti@example.com` | `password123` | `http://127.0.0.1:8000/` & REST API `/api/*` | Melihat katalog motor dengan foto & tarif di web publik, sewa motor dan riwayat booking via API |

---

## 11. PANDUAN TROUBLESHOOTING MASALAH UMUM DI WINDOWS

### 1. Masalah: Composer Memblokir MoonShine 2.x karena Security Advisory
* **Gejala:** `Root composer.json requires moonshine/moonshine ^2.24... affected by security advisories`.
* **Solusi di Windows:**  
  Composer 2.8+ secara default memblokir paket berstatus advisory. Jalankan perintah ini:
  ```bash
  composer config policy.advisories.block false
  composer require "moonshine/moonshine:^2.24"
  ```

---

### 2. Masalah: Halaman Admin MoonShine Mengembalikan Error 404 Not Found
* **Gejala:** Membuka `http://127.0.0.1:8000/admin` menghasilkan halaman `404 Not Found`.
* **Penyebab:** Pada Laravel 11+, file `MoonShineServiceProvider` belum terdaftar di daftar provider aplikasi.
* **Solusi:**  
  Buka file `bootstrap/providers.php`, tambahkan `App\Providers\MoonShineServiceProvider::class,` ke dalam array:
  ```php
  return [
      App\Providers\AppServiceProvider::class,
      App\Providers\MoonShineServiceProvider::class,
  ];
  ```

---

### 3. Masalah: Error Saat Upload Foto Motor (`GD` / `Fileinfo` Extension)
* **Gejala:** `Call to undefined function imagecreatefromjpeg()` atau `Class 'finfo' not found`.
* **Solusi:**
  1. Buka file `php.ini` di folder PHP Anda (`C:\xampp\php\php.ini` atau melalui menu Laragon).
  2. Cari baris berikut dan hilangkan tanda titik koma `;` di depannya:
     ```ini
     extension=fileinfo
     extension=gd
     ```
  3. Simpan file dan restart Apache / server web Anda.

---

### 4. Masalah: Foto Motor Tidak Muncul di Browser (Error 404 pada URL Gambar)
* **Gejala:** Gambar rusak atau URL `http://127.0.0.1:8000/storage/motors/...` mengembalikan 404 Not Found.
* **Solusi di Windows:**
  1. Hapus folder pintasan `public/storage` jika sebelumnya gagal dibuat secara sempurna.
  2. Buka Command Prompt / PowerShell dengan **"Run as Administrator"**.
  3. Jalankan kembali:
     ```bash
     php artisan storage:link
     ```
  4. Pastikan di file `.env` nilai `APP_URL=http://127.0.0.1:8000`.

---

### 5. Masalah: Error Tabel Sessions Tidak Ditemukan (`Table 'rental_motor.sessions' doesn't exist`)
* **Gejala:** Saat membuka halaman web atau admin, muncul error `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'rental_motor.sessions' doesn't exist`.
* **Penyebab:** Anda menghapus skema tabel `sessions` pada file migrasi `0001_01_01_000000_create_users_table.php`.
* **Solusi:**  
  Buka kembali file `database/migrations/0001_01_01_000000_create_users_table.php`, pastikan method `up()` berisi kode lengkap pembuatan tabel `users`, `password_reset_tokens`, dan `sessions` sesuai panduan di [Fase 2.1](#21-modifikasi-migration-tabel-users). Lalu jalankan `php artisan migrate:fresh --seed`.

---

### 6. Masalah: Hak Akses Pembuatan Trigger MySQL di Windows
* **Gejala:** `This function has none of DETERMINISTIC...` atau `Access denied for user to CREATE TRIGGER`.
* **Solusi:**  
  Buka phpMyAdmin atau HeidiSQL, masuk ke tab SQL dan jalankan query berikut:
  ```sql
  SET GLOBAL log_bin_trust_function_creators = 1;
  ```
  Kemudian jalankan ulang `php artisan migrate`.

---

### 7. Masalah: Secret Key JWT Belum Dibuat
* **Gejala:** `Tymon\JWTAuth\Exceptions\JWTException: Secret key is not set`.
* **Solusi:**  
  Jalankan perintah ini di terminal:
  ```bash
  php artisan jwt:secret
  php artisan optimize:clear
  ```

---

### 8. Masalah: Driver MySQL Tidak Ditemukan (`could not find driver`)
* **Gejala:** `PDOException: could not find driver` saat menjalankan perintah Artisan database.
* **Solusi:**  
  Pastikan `extension=pdo_mysql` di `php.ini` sudah diaktifkan (tanpa titik koma `;`), lalu restart terminal dan server web Anda.


---

## LAMPIRAN OPSIONAL: PROYEK NATIVE PHP & MATRIKS 8 UNIT KOMPETENSI

> **Catatan:** Bagian ini bersifat opsional jika skema asesmen/uji kompetensi hanya menguji 8 unit teknis inti secara native (menggunakan PHP Native & MySQL tanpa framework).

### 1. Database MySQL (`db_fastrent`)
Jalankan query SQL berikut di phpMyAdmin atau MySQL Console:
```sql
CREATE DATABASE IF NOT EXISTS db_fastrent;
USE db_fastrent;

CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_penyewa VARCHAR(100) NOT NULL,
    jenis_motor VARCHAR(50) NOT NULL,
    lama_sewa INT NOT NULL,
    total_bayar INT NOT NULL
);
```

### 2. File `koneksi.php`
```php
<?php
// Pengaturan parameter koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_fastrent";

// Membuka koneksi ke MySQL
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Penanganan galat koneksi (Error Handling & Debugging)
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>
```

### 3. File `index.php`
```php
<?php
// Mengaktifkan laporan galat untuk kebutuhan debugging (Unit 8)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'koneksi.php';

// ===================================================================
// UNIT 5 & 7: FUNGSI TERSTRUKTUR & DOKUMENTASI FORMAL (PHPDoc)
// ===================================================================

/**
 * Menghitung total biaya rental motor berdasarkan jenis kendaraan dan durasi hari.
 * Menerapkan diskon 10% jika peminjaman melebihi batas 3 hari.
 *
 * @param string $jenisMotor Merk kendaraan yang dipilih penyewa
 * @param int $durasiHari Jumlah hari peminjaman
 * @return int Total nominal bersih yang wajib dibayar
 */
function hitungTotalBiaya(string $jenisMotor, int $durasiHari): int {
    $tarifHarian = 0;

    // Struktur Kontrol Percabangan (Unit 5)
    if ($jenisMotor === "Honda Beat") {
        $tarifHarian = 60000;
    } elseif ($jenisMotor === "Honda Vario") {
        $tarifHarian = 75000;
    } elseif ($jenisMotor === "Yamaha NMAX") {
        $tarifHarian = 90000;
    }

    $subtotal = $durasiHari * $tarifHarian;

    // Penerapan aturan diskon
    $diskon = ($durasiHari > 3) ? ($subtotal * 0.10) : 0;

    return (int) ($subtotal - $diskon);
}

// ===================================================================
// UNIT 4 & 5: PROSES PENYIMPANAN DATA FORM (CREATE)
// ===================================================================
if (isset($_POST['btn_simpan'])) {
    // Sanitasi dan validasi input (Defensive Coding - Unit 4)
    $namaPenyewa = htmlspecialchars(trim($_POST['nama_penyewa']));
    $jenisMotor  = $_POST['jenis_motor'];
    $lamaSewa    = (int) $_POST['lama_sewa'];

    if (!empty($namaPenyewa) && $lamaSewa > 0) {
        $totalBayar = hitungTotalBiaya($jenisMotor, $lamaSewa);

        $querySimpan = "INSERT INTO transaksi (nama_penyewa, jenis_motor, lama_sewa, total_bayar) 
                        VALUES ('$namaPenyewa', '$jenisMotor', '$lamaSewa', '$totalBayar')";
        mysqli_query($koneksi, $querySimpan);

        header("Location: index.php");
        exit();
    }
}

// ===================================================================
// UNIT 1: EKSTRAKSI STRUKTUR DATA ARRAY (READ)
// ===================================================================
$hasilData = mysqli_query($koneksi, "SELECT * FROM transaksi ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastRent - Sistem Rental Motor</title>
    <!-- UNIT 6: INTEGRASI LIBRARY PRE-EXISTING (Bootstrap CSS CDN - MIT License) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-4">

<div class="container">
    <div class="row">
        <!-- UNIT 2: FORM USER INTERFACE SEMANTIK & AKSESIBILITAS -->
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Form Peminjaman Motor</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="namaPenyewa" class="form-label">Nama Lengkap Penyewa:</label>
                            <input type="text" id="namaPenyewa" name="nama_penyewa" class="form-control" required placeholder="Contoh: Rian Pratama">
                        </div>
                        <div class="mb-3">
                            <label for="jenisMotor" class="form-label">Pilih Motor:</label>
                            <select id="jenisMotor" name="jenis_motor" class="form-select" required>
                                <option value="Honda Beat">Honda Beat (Rp 60.000 / hari)</option>
                                <option value="Honda Vario">Honda Vario (Rp 75.000 / hari)</option>
                                <option value="Yamaha NMAX">Yamaha NMAX (Rp 90.000 / hari)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="lamaSewa" class="form-label">Durasi Sewa (Hari):</label>
                            <input type="number" id="lamaSewa" name="lama_sewa" class="form-control" min="1" required placeholder="Contoh: 4">
                            <div class="form-text text-danger">*Sewa lebih dari 3 hari otomatis mendapat diskon 10%</div>
                        </div>
                        <button type="submit" name="btn_simpan" class="btn btn-primary w-100">Simpan Transaksi</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- UNIT 2 & 1: TABEL PENYAJIAN DATA MENGGUNAKAN PERULANGAN -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Riwayat Transaksi Rental</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Penyewa</th>
                                    <th>Motor</th>
                                    <th>Durasi</th>
                                    <th>Total Bayar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $nomor = 1;
                                // UNIT 1 & 5: Perulangan while mengekstrak data Associative Array dari DB
                                while ($baris = mysqli_fetch_assoc($hasilData)) : 
                                ?>
                                <tr>
                                    <td><?= $nomor++; ?></td>
                                    <td><?= $baris['nama_penyewa']; ?></td>
                                    <td><?= $baris['jenis_motor']; ?></td>
                                    <td><?= $baris['lama_sewa']; ?> Hari</td>
                                    <td>Rp <?= number_format($baris['total_bayar'], 0, ',', '.'); ?></td>
                                    <td>
                                        <a href="hapus.php?id=<?= $baris['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
```

---

### 4. Matriks Pembuktian 8 Unit Kompetensi ke Asesor

Saat sesi wawancara verifikasi bukti setelah koding selesai, buka editor VS Code Anda dan gunakan panduan jawaban ini saat asesor memeriksa setiap unit:

| Kode Unit & Judul | Di Mana Letaknya di Kode Anda? | Kalimat Penjelasan ke Asesor |
| :--- | :--- | :--- |
| **1. J.620100.004.02**<br>Struktur Data | Baris perulangan:<br>`while ($baris = mysqli_fetch_assoc(...))` di `index.php`. | *"Saya menerapkan struktur data **Array Asosiatif** untuk menampung baris data dari database MySQL dan mengakses nilai kolomnya seperti `$baris['nama_penyewa']`."* |
| **2. J.620100.005.02**<br>User Interface | Tag `<form>`, `<label for="...">`, `<input id="...">`, dan tabel di `index.php`. | *"Antarmuka dibangun semantik dengan menghubungkan atribut `for` pada label ke `id` input untuk aksesibilitas, serta tabel hasil mutasi data yang responsif."* |
| **3. J.620100.011.01**<br>Software Tools | Lingkungan kerja laptop Anda (VS Code, Apache/MySQL Laragon/XAMPP, browser). | *"Saya menggunakan text editor VS Code, runtime PHP dan database MySQL melalui web server lokal, serta browser untuk eksekusi antarmuka."* |
| **4. J.620100.016.01**<br>Guidelines & Best Practices | Penggunaan penamaan camelCase (`$namaPenyewa`, `$lamaSewa`), `trim()`, dan `htmlspecialchars()`. | *"Saya menerapkan penamaan variabel camelCase, sanitasi input menggunakan `htmlspecialchars()` untuk keamanan XSS, serta validasi data sebelum disimpan ke basis data."* |
| **5. J.620100.017.02**<br>Pemrograman Terstruktur | Deklarasi fungsi `hitungTotalBiaya()`, percabangan `if-else` diskon, dan loop `while`. | *"Saya memisahkan proses perhitungan ke dalam fungsi modular independen yang memiliki parameter dan return value, dilengkapi struktur percabangan diskon."* |
| **6. J.620100.019.02**<br>Library Pre-Existing | Tag `<link href="...bootstrap.min.css">` di bagian `<head>` file `index.php`. | *"Saya mengintegrasikan library CSS Bootstrap 5 melalui CDN dengan lisensi open source **MIT License** yang legal digunakan dan dimodifikasi."* |
| **7. J.620100.023.02**<br>Dokumen Kode | Blok komentar PHPDoc di atas fungsi `hitungTotalBiaya()`. | *"Saya menyusun dokumentasi resmi standar PHPDoc yang menjelaskan algoritma fungsi, anotasi masukan `@param`, dan tipe balikan `@return`."* |
| **8. J.620100.025.02**<br>Melakukan Debugging | `error_reporting(E_ALL);` di baris teratas `index.php` dan `or die(mysqli_connect_error())`. | *"Saya mengaktifkan pelaporan galat penuh di awal file untuk mendeteksi runtime error, dan jika ada anomali form, saya menelusurinya via `var_dump($_POST); die();`."* |