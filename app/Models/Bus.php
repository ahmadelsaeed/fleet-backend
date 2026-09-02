<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bus extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['plate_number', 'seats_count'];

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
