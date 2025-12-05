<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = 
    [
        'order_id',
        'amount',
        'proof_image_url',
        'paid_at',
        'payment_method',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethodEnum::class,
            'status' => PaymentStatusEnum::class,
        ];
    }

    public function order(): BelongsTo 
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who made this payment through the order
     */
    public function getUserAttribute()
    {
        return $this->order ? $this->order->user : null;
    }

    /**
     * Get the package for this payment through the order
     */
    public function getPackageAttribute()
    {
        return $this->order ? $this->order->package : null;
    }
}
