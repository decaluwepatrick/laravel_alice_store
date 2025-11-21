<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Cart extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'uuid';
    protected $fillable = [];

    protected static function booted()
    {
        static::creating(function ($cart) {
            $cart->id = (string) Str::uuid();
        });
    }

    public function items() {
        return $this->hasMany(CartItem::class);
    }
}
