<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TransactionsUser extends Model
{
    use HasFactory;

    protected $table = 'transactions_user'; // Definisikan nama tabel dengan benar

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
        'transaction_code',
        'user_id',
        'booking_id',
        'clinic_id',
        'admin_fee',
        'no_antrian',
        'status'
    ];

    /**
     * Get the user associated with the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the booking associated with the transaction.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the clinic associated with the transaction.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
