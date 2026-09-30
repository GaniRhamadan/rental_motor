<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS trg_update_motor_status_confirmed
                AFTER UPDATE ON penyewaans
                FOR EACH ROW
                WHEN NEW.status = 'dikonfirmasi'
                BEGIN
                    UPDATE motors SET status = 'disewa' WHERE id = NEW.motor_id;
                END;
            ");
            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS trg_update_motor_status_completed
                AFTER UPDATE ON penyewaans
                FOR EACH ROW
                WHEN NEW.status = 'selesai'
                BEGIN
                    UPDATE motors SET status = 'tersedia' WHERE id = NEW.motor_id;
                END;
            ");

            return;
        }

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
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_update_motor_status_confirmed;');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_update_motor_status_completed;');

            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS trg_update_motor_status_after_booking_update');
    }
};
