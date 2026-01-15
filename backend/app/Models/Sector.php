<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Usuários vinculados a este setor
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Pedidos destinados a este setor
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
