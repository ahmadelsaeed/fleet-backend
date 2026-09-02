<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'bus_id', 'date'];

    protected $casts = ['date' => 'date'];

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function tripStops(): HasMany
    {
        return $this->hasMany(TripStop::class)->orderBy('sequence_order');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
