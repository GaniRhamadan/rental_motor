<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS trg_update_motor_status_after_booking_update");
    }
};

