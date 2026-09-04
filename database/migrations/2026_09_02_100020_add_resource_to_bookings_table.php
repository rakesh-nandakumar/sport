<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('resource_id')->nullable()->after('indoor_id')->constrained('resources')->cascadeOnDelete();
            $table->string('status', 20)->default('confirmed')->after('comments');
            $table->decimal('total_price', 10, 2)->nullable()->after('status');
            $table->unsignedSmallInteger('unit_quantity')->default(1)->after('total_price');
            $table->json('selected_options')->nullable()->after('unit_quantity');
            $table->index(['resource_id', 'start_time', 'finish_time']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['resource_id', 'start_time', 'finish_time']);
            $table->dropConstrainedForeignId('resource_id');
            $table->dropColumn(['status', 'total_price', 'unit_quantity', 'selected_options']);
        });
    }
};
