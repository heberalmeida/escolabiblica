<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'image',
        'price',
        'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'integer',
        'active' => 'boolean',
    ];

    public function paymentMethods()
    {
        return $this->hasMany(EventPaymentMethod::class);
    }

    public function activePaymentMethods()
    {
        return $this->hasMany(EventPaymentMethod::class)->where('active', true);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function isFree()
    {
        return $this->price === 0;
    }
}
