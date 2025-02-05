<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'booking';
    
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


    protected $fillable = [
        'quota',
        'clinic_id',
        'day',
        'open_time',
        'close_time'
    ];

    protected $casts = [
        'available_date' => 'date',
    ];


    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }
    public function transactionsUser(): HasMany
    {
        return $this->hasMany(TransactionsUser::class, 'booking_id');
    }
}
