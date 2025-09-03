<?php

namespace Modules\User\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Product\Http\Entities\Product;
use Modules\User\Database\Factories\UserFactory;

class User extends  Authenticatable
{
    use HasFactory, HasApiTokens;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function newFactory():UserFactory
    {
        return UserFactory::new();
    }

    public function favorites()
    {
        return $this->belongsToMany(
            Product::class,
            'user_favorites',
            'user_id',
            'product_id'
        )->withTimestamps();
    }

    public function cartItems()
    {
        return $this->belongsToMany(
            Product::class,
            'user_carts',
            'user_id',
            'product_id'
        )
            ->withPivot('quantity')
            ->withTimestamps();
    }

}
