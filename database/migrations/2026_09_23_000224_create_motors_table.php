<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('motors', function (Blueprint $table) {
            $table->id();
            $table->string('merek');
            $table->enum('tipe_cc', ['100', '125', '150']);
            $table->string('no_plat')->unique();
            $table->enum('status', ['menunggu_verifikasi', 'tersedia', 'disewa', 'perawatan'])->default('menunggu_verifikasi');
            $table->string('foto')->nullable();
            $table->string('documen_kepemilikan')->nullable();
            $table->foreignId('pemilik_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motors');
    }
};
