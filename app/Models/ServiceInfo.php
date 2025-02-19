<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceInfo extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'service_info';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'price',
        'clinic_id',
    ];

    /**
     * Relasi ke tabel clinics
     */
    public function clinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id', 'id');
    }

    public function transactionsUser(): HasMany
    {
        return $this->hasMany(TransactionsUser::class, 'service_info_id');
    }
}
