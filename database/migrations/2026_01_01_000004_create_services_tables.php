<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('slot_minutes')->default(60);   // minimum bookable block
            $table->unsignedSmallInteger('min_slots')->default(1);
            $table->unsignedSmallInteger('max_slots')->nullable();
            $table->unsignedSmallInteger('buffer_minutes')->default(0);  // gap the vendor wants between bookings
            $table->unsignedSmallInteger('lead_time_minutes')->default(60); // how far ahead bookings must be made
            $table->unsignedSmallInteger('max_players')->nullable();
            $table->time('opens_at')->nullable();  // overrides venue hours when set
            $table->time('closes_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('price_per_slot', 10, 2);
            $table->unsignedSmallInteger('capacity')->default(1); // identical units bookable at once
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('service_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('days'); // [0..6]
            $table->time('starts_at');
            $table->time('ends_at');
            $table->decimal('multiplier', 5, 2)->default(1.00);
            $table->timestamps();
        });

        Schema::create('game_service', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['game_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_service');
        Schema::dropIfExists('service_rates');
        Schema::dropIfExists('service_options');
        Schema::dropIfExists('services');
    }
};
