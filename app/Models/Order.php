<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'tracking_token', 'service_id', 'client_name', 'client_email', 'client_phone',
        'title', 'description', 'service_details', 'deadline', 'budget', 'price_estimate', 'attachment_path',
        'status', 'source', 'admin_notes', 'price_final',
    ];

    protected $casts = [
        'deadline' => 'date',
        'service_details' => 'array',
        'price_estimate' => 'decimal:2',
        'price_final' => 'decimal:2',
        'price_finalized_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Order $order): void {
            // Track when the agreed (final) price was set; clearing it reopens negotiation.
            if ($order->price_final === null) {
                $order->price_finalized_at = null;
            } elseif (! $order->exists || $order->isDirty('price_final')) {
                $order->price_finalized_at = now();
            }

            if ($order->status !== 'done') {
                $order->completed_at = null;
            } elseif (!$order->exists || $order->isDirty('status')) {
                $order->completed_at = now();
            }
        });
    }

    const STATUSES = [
        'pending'    => ['label' => 'Menunggu Konfirmasi', 'color' => 'yellow', 'icon' => 'fas fa-clock'],
        'processing' => ['label' => 'Sedang Dikerjakan',   'color' => 'blue',   'icon' => 'fas fa-pen-nib'],
        'review'     => ['label' => 'Review',              'color' => 'purple', 'icon' => 'fas fa-search'],
        'done'       => ['label' => 'Selesai',             'color' => 'green',  'icon' => 'fas fa-check-double'],
        'cancelled'  => ['label' => 'Dibatalkan',          'color' => 'red',    'icon' => 'fas fa-times-circle'],
    ];

    /** Where an order came from. "web" = public order form; the rest are added by an admin. */
    const SOURCES = [
        'web' => 'Website',
        'whatsapp' => 'WhatsApp',
        'instagram' => 'Instagram',
        'direct' => 'Langsung / tatap muka',
        'other' => 'Lainnya',
    ];

    /** Statuses that mean work has started — they need an agreed final price first. */
    const WORK_STATUSES = ['processing', 'review', 'done'];

    public static function generateTrackingToken(): string
    {
        do {
            $token = Str::upper(Str::random(16));
        } while (self::where('tracking_token', $token)->exists());

        return $token;
    }

    /**
     * Moving a not-yet-started order (pending/cancelled) into a work status needs a final price.
     * Orders already in progress keep their existing flexibility.
     */
    public static function needsFinalPriceFor(string $fromStatus, string $toStatus, mixed $priceFinal): bool
    {
        return in_array($toStatus, self::WORK_STATUSES, true)
            && ! in_array($fromStatus, self::WORK_STATUSES, true)
            && ($priceFinal === null || $priceFinal === '');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class)->latest()->latest('id');
    }

    /** Append an entry to the order history. $changes: [['field' => .., 'from' => .., 'to' => ..], ...] */
    public function logActivity(string $action, string $description, ?array $changes = null): OrderActivity
    {
        return $this->activities()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'changes' => $changes ?: null,
        ]);
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source ?? 'web'] ?? ucfirst((string) $this->source);
    }

    public function getRouteKeyName(): string
    {
        return 'tracking_token';
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** Answers to the service-specific fields: [['key', 'label', 'value', 'display'], ...]. */
    public function getDetailAnswersAttribute(): array
    {
        return $this->service_details['answers'] ?? [];
    }

    /** Estimate breakdown lines: [['label', 'detail', 'amount'], ...]. */
    public function getEstimateLinesAttribute(): array
    {
        return $this->service_details['estimate']['lines'] ?? [];
    }

    public function hasFinalPrice(): bool
    {
        return $this->price_final !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'gray';
    }
}
