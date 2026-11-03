<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->verified_at !== null => 'Verified',
            $this->attempts >= config('otp.max_attempts') => 'Too many tries',
            $this->expires_at->isPast() => 'Expired',
            default => 'Waiting',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->statusLabel()) {
            'Verified' => 'bg-emerald-100 text-emerald-800',
            'Waiting' => 'bg-amber-100 text-amber-800',
            default => 'bg-rose-100 text-rose-800',
        };
    }
}
