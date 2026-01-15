<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'uuid',
        'order_number',
        'buyer_name',
        'buyer_cpf',
        'buyer_email',
        'buyer_phone',

        'buyer_postal_code',
        'buyer_address',
        'buyer_address_number',
        'buyer_address_complement',
        'buyer_province',
        'buyer_city',
        'buyer_state',

        'sector_id',
        'total_amount',
        'status',
        'asaas_customer_id',
        'asaas_payment_id',
        'payment_method',
        'gateway_payload',
    ];

    protected $casts = [
        'gateway_payload' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }
}
