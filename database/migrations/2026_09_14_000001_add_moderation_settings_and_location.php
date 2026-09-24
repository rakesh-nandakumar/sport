<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Key/value store for super-admin controlled site settings (payment methods, hold timers, amenities…).
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // Everything we know about a business that lists on the platform. One per vendor user.
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('business_type', 40);          // sole_proprietor, partnership, private_limited, club, other
            $table->string('registration_number', 60)->nullable();
            $table->string('owner_nic', 20);
            $table->string('contact_person');
            $table->string('contact_phone', 20);
            $table->string('alt_phone', 20)->nullable();
            $table->string('business_email');
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city', 80);
            $table->string('district', 80);
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('website')->nullable();
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('years_operating')->nullable();
            $table->unsignedSmallInteger('venue_count_estimate')->nullable();
            $table->json('activity_type_ids')->nullable();  // what they plan to list
            $table->string('br_document_path')->nullable();  // business registration certificate
            $table->string('nic_document_path')->nullable(); // owner NIC copy
            $table->string('status', 20)->default('pending'); // pending, active, suspended, rejected
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::table('activity_types', function (Blueprint $table) {
            $table->string('image')->nullable()->after('color');
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->string('postal_code', 10)->nullable()->after('district');
            $table->decimal('latitude', 10, 7)->nullable()->after('postal_code');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::table('bookings', function (Blueprint $table) {
            // Unverified bank transfers release the slot automatically once this passes.
            $table->timestamp('hold_expires_at')->nullable()->after('vendor_confirmed_at');
            $table->index(['status', 'hold_expires_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('role_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('location_label', 120)->nullable()->after('longitude');
            $table->timestamp('location_updated_at')->nullable()->after('location_label');
        });

        // Lightweight view log used for "recently viewed" and activity affinity.
        Schema::create('venue_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->timestamp('viewed_at');

            $table->index(['user_id', 'viewed_at']);
            $table->index(['session_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_views');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['latitude', 'longitude', 'location_label', 'location_updated_at']));
        Schema::table('bookings', function (Blueprint $t) {
            $t->dropIndex(['status', 'hold_expires_at']);
            $t->dropColumn('hold_expires_at');
        });
        Schema::table('venues', fn (Blueprint $t) => $t->dropColumn(['postal_code', 'latitude', 'longitude']));
        Schema::table('activity_types', fn (Blueprint $t) => $t->dropColumn('image'));
        Schema::dropIfExists('vendor_profiles');
        Schema::dropIfExists('settings');
    }
};
