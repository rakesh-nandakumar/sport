<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite cannot modify columns without doctrine/dbal; MySQL/MariaDB does it natively.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('resource_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('resource_id')->nullable()->change();
        });
    }
};
