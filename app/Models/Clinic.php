<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'address',
        'phone',
        'photo',
        'user_id',
        'price'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function users()
    {
        return $this->belongsTo(User::class);
    }
    public function transactionsUser(): HasMany
    {
        return $this->hasMany(TransactionsUser::class, 'clinic_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'clinic_id');
    }
}
