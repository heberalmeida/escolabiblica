<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Registration extends Model
{
    protected $fillable = [
        'event_id',
        'registration_number',
        'qr_code',
        'name',
        'phone',
        'cpf',
        'birth_date',
        'sector',
        'congregation',
        'church_type',
        'church_affiliation',
        'other_church_name',
        'position',
        'gender',
        'whatsapp_authorization',
        'price_paid',
        'payment_method',
        'payment_status',
        'asaas_customer_id',
        'asaas_payment_id',
        'gateway_payload',
        'validated',
        'validated_at',
        'validated_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'whatsapp_authorization' => 'boolean',
        'price_paid' => 'integer',
        'validated' => 'boolean',
        'validated_at' => 'datetime',
        'gateway_payload' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($registration) {
            if (empty($registration->registration_number)) {
                $registration->registration_number = self::generateRegistrationNumber();
            }
            if (empty($registration->qr_code)) {
                $registration->qr_code = self::generateQrCode();
            }
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public static function generateRegistrationNumber(): string
    {
        do {
            $number = strtoupper(Str::random(8));
        } while (self::where('registration_number', $number)->exists());

        return $number;
    }

    public static function generateQrCode(): string
    {
        do {
            $code = Str::uuid()->toString();
        } while (self::where('qr_code', $code)->exists());

        return $code;
    }

    public function validate(string $validatedBy): bool
    {
        if ($this->validated) {
            return false;
        }

        $this->update([
            'validated' => true,
            'validated_at' => now(),
            'validated_by' => $validatedBy,
        ]);

        return true;
    }
}
