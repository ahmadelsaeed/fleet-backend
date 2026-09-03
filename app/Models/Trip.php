<?php

namespace App\Models;

use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'bus_id', 'date'])]
#[UseFactory(TripFactory::class)]
class Trip extends Model
{
    use SoftDeletes;

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
