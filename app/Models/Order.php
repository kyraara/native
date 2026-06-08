<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'tracking_token', 'service_id', 'client_name', 'client_email', 'client_phone',
        'title', 'description', 'deadline', 'budget', 'attachment_path',
        'status', 'admin_notes', 'price_final',
    ];

    protected $casts = [
        'deadline' => 'date',
        'price_final' => 'decimal:2',
    ];

    const STATUSES = [
        'pending'    => ['label' => 'Menunggu Konfirmasi', 'color' => 'yellow', 'icon' => 'fas fa-clock'],
        'processing' => ['label' => 'Sedang Dikerjakan',   'color' => 'blue',   'icon' => 'fas fa-pen-nib'],
        'review'     => ['label' => 'Review',              'color' => 'purple', 'icon' => 'fas fa-search'],
        'done'       => ['label' => 'Selesai',             'color' => 'green',  'icon' => 'fas fa-check-double'],
        'cancelled'  => ['label' => 'Dibatalkan',          'color' => 'red',    'icon' => 'fas fa-times-circle'],
    ];

    public function getRouteKeyName(): string
    {
        return 'tracking_token';
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
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
