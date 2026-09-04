<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indoor_id')->constrained('indoors')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('activities')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('photo')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->decimal('rate', 10, 2)->default(0);
            $table->string('pricing_unit', 20)->default('per_hour');
            $table->unsignedSmallInteger('min_duration_minutes')->default(60);
            $table->unsignedSmallInteger('slot_increment_minutes')->default(30);
            $table->json('custom_fields')->nullable();
            $table->json('opening_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['indoor_id', 'activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
