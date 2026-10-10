<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_name',
        'phone',
        'alt_phone',
        'city',
        'address',
        'notes',
        'delivery_method',
        'subtotal',
        'delivery_fee',
        'total',
        'status',
        'telegram_message_id',
        'telegram_sent_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'delivery_method' => DeliveryMethod::class,
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'total' => 'integer',
            'telegram_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'SP-' . date('Ymd');
        $lastOrder = static::where('order_number', 'LIKE', "{$prefix}-%")->latest('id')->first();

        if ($lastOrder && preg_match('/-(\d{4})$/', $lastOrder->order_number, $matches)) {
            $nextSequence = str_pad((int) $matches[1] + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '0001';
        }

        return "{$prefix}-{$nextSequence}";
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return Money::format($this->subtotal);
    }

    public function getFormattedDeliveryFeeAttribute(): string
    {
        return Money::format($this->delivery_fee);
    }

    public function getFormattedTotalAttribute(): string
    {
        return Money::format($this->total);
    }
}
