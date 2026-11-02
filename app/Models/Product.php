<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    public const UNITS = ['pcs', 'pack', 'kg', 'g', 'litre', 'ml', 'dozen'];

    public const FRACTIONAL_UNITS = ['kg', 'litre'];

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'unit',
        'price',
        'mrp',
        'cost_price',
        'low_stock_threshold',
        'track_stock',
        'is_active',
        'is_featured',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'mrp' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock_qty' => 'decimal:3',
            'low_stock_threshold' => 'decimal:3',
            'track_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('track_stock', true)->whereColumn('stock_qty', '<=', 'low_stock_threshold');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function allowsFraction(): bool
    {
        return in_array($this->unit, self::FRACTIONAL_UNITS, true);
    }

    public function quantityStep(): string
    {
        return $this->allowsFraction() ? '0.25' : '1';
    }

    public function isInStock(): bool
    {
        return ! $this->track_stock || bccomp($this->stock_qty, '0', 3) > 0;
    }

    public function hasStockFor(string $quantity): bool
    {
        return ! $this->track_stock || bccomp($this->stock_qty, $quantity, 3) >= 0;
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && bccomp($this->stock_qty, $this->low_stock_threshold, 3) <= 0;
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function discountPercent(): ?int
    {
        if (! $this->mrp || bccomp($this->mrp, $this->price, 2) <= 0) {
            return null;
        }

        return (int) floor((1 - ((float) $this->price / (float) $this->mrp)) * 100);
    }
}
