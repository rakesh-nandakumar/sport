<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method', 20);
            $table->string('payment_status', 30)->default('unpaid');
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('LKR');
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['venue_id', 'created_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('booking_order_id')->nullable()->after('id')->constrained('booking_orders')->nullOnDelete();
            $table->index('booking_order_id');
        });

        Schema::create('booking_game', function (Blueprint $table) {
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->primary(['booking_id', 'game_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('booking_order_id')->nullable()->after('booking_id')->constrained('booking_orders')->nullOnDelete();
            $table->index('booking_order_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->unsignedSmallInteger('game_minutes_per_title')->default(45)->after('slot_minutes');
        });

        DB::table('bookings')
            ->whereNotNull('game_id')
            ->orderBy('id')
            ->each(function (object $booking): void {
                DB::table('booking_game')->insertOrIgnore([
                    'booking_id' => $booking->id,
                    'game_id' => $booking->game_id,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_order_id');
        });

        Schema::dropIfExists('booking_game');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_order_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('game_minutes_per_title');
        });

        Schema::dropIfExists('booking_orders');
    }
};
