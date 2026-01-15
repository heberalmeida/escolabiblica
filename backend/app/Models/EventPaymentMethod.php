<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPaymentMethod extends Model
{
    protected $fillable = [
        'event_id',
        'method',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
