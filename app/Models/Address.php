<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_name',
        'phone',
        'address_line',
        'landmark',
        'locality',
        'pincode',
        'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function oneLine(): string
    {
        return collect([$this->address_line, $this->landmark, $this->locality, $this->pincode])->filter()->implode(', ');
    }
}
