<?php

namespace App\Models;

use App\Services\ServiceFormSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'icon_class', 'base_price', 'form_fields', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
        'form_fields' => 'array',
        'starting_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // The catalog "Mulai Rp …" always follows the cheapest valid option combination.
        static::saving(function (Service $service): void {
            $service->starting_price = app(ServiceFormSchema::class)
                ->startingPrice($service->form_fields, $service->base_price);
        });

        // Cuy-AI quotes prices from the catalog — refresh its knowledge on every change.
        static::saved(fn () => \App\Services\AiChatService::forgetPrompt());
        static::deleted(fn () => \App\Services\AiChatService::forgetPrompt());
    }

    /** Price shown as "Mulai Rp …" in the catalog and order form. */
    public function getDisplayPriceAttribute(): float
    {
        return (float) ($this->starting_price ?? $this->base_price);
    }

    /**
     * Public price list grouped by field, e.g. "Peringkat SINTA" → SINTA 2 Rp 4.000.000.
     * Only fields that affect the price; options without a price are left out.
     *
     * @return array<int, array{label: string, items: array<int, array{label: string, price: string}>}>
     */
    public function priceList(): array
    {
        $groups = [];

        foreach ($this->formSchema() as $field) {
            $items = match ($field['pricing']) {
                'base' => collect($field['options'])->filter(fn ($option) => (int) $option['price'] > 0)
                    ->map(fn ($option) => ['label' => $option['label'], 'price' => \App\Support\Money::rupiah($option['price'])])->values()->all(),
                'add' => collect($field['options'])->filter(fn ($option) => (int) $option['price'] > 0)
                    ->map(fn ($option) => ['label' => $option['label'], 'price' => '+'.\App\Support\Money::rupiah($option['price'])])->values()->all(),
                'per_unit' => $field['unit_price'] > 0 ? [['label' => 'Per '.($field['unit_label'] ?: 'unit'), 'price' => '+'.\App\Support\Money::rupiah($field['unit_price'])]] : [],
                default => [],
            };

            if ($items !== []) {
                $groups[] = ['label' => $field['label'], 'items' => $items];
            }
        }

        return $groups;
    }

    /** Normalised field definitions (defaults filled in). */
    public function formSchema(): array
    {
        return app(ServiceFormSchema::class)->normalize($this->form_fields);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class)->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
