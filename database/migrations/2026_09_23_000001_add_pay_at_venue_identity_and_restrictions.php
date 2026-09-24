<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('pay_at_venue_banned_at')->nullable()->after('location_updated_at');
            $table->text('pay_at_venue_ban_note')->nullable()->after('pay_at_venue_banned_at');
            $table->string('nic_front_path')->nullable()->after('pay_at_venue_ban_note');
            $table->string('nic_back_path')->nullable()->after('nic_front_path');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('pay_at_venue_failure_at')->nullable()->after('cancelled_at');
        });

        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email')->unique();
            $table->string('phone', 20)->unique();
            $table->string('password');
            $table->string('nic_front_path')->nullable();
            $table->string('nic_back_path')->nullable();
            $table->string('verification_token_hash');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['pay_at_venue_failure_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pay_at_venue_banned_at', 'pay_at_venue_ban_note', 'nic_front_path', 'nic_back_path']);
        });

        Schema::dropIfExists('pending_registrations');
    }
};
