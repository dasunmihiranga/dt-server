<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Transaction extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'description',
        'metadata',
        'reference',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generateReference(): string
    {
        return 'TXN' . strtoupper(uniqid());
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (!$transaction->reference) {
                $transaction->reference = $transaction->generateReference();
            }
        });
    }
}
