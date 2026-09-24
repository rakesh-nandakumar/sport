<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('nic_front_path')->nullable()->after('pay_at_venue_failure_at');
            $table->string('nic_back_path')->nullable()->after('nic_front_path');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['nic_front_path', 'nic_back_path']);
        });
    }
};
