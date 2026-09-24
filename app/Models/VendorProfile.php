<?php

namespace App\Models;

use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProfile extends Model
{
    protected $fillable = [
        'user_id', 'business_name', 'business_type', 'registration_number', 'owner_nic', 'contact_person',
        'contact_phone', 'alt_phone', 'business_email', 'address_line1', 'address_line2', 'city', 'district',
        'postal_code', 'latitude', 'longitude', 'website', 'facebook', 'instagram', 'description',
        'years_operating', 'venue_count_estimate', 'activity_type_ids', 'br_document_path', 'nic_document_path',
        'status', 'review_notes', 'reviewed_by', 'reviewed_at', 'terms_accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'activity_type_ids' => 'array',
            'reviewed_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeStatus(Builder $query, VendorStatus|string|null $status): Builder
    {
        if (! $status) {
            return $query;
        }

        return $query->where('status', $status instanceof VendorStatus ? $status->value : $status);
    }

    public function isActive(): bool
    {
        return $this->status === VendorStatus::Active;
    }

    public function businessTypeLabel(): string
    {
        return config('entrypoint.business_types')[$this->business_type] ?? ucfirst(str_replace('_', ' ', $this->business_type));
    }

    public function fullAddress(): string
    {
        return collect([$this->address_line1, $this->address_line2, $this->city, $this->district, $this->postal_code])->filter()->join(', ');
    }

    public function hasDocuments(): bool
    {
        return (bool) ($this->br_document_path || $this->nic_document_path);
    }
}
