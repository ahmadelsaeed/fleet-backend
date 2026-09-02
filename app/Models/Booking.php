<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'trip_id',
        'seat_id',
        'user_id',
        'start_trip_stop_id',
        'end_trip_stop_id',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The trip-stop where the passenger boards. */
    public function startStop(): BelongsTo
    {
        return $this->belongsTo(TripStop::class, 'start_trip_stop_id');
    }

    /** The trip-stop where the passenger alights. */
    public function endStop(): BelongsTo
    {
        return $this->belongsTo(TripStop::class, 'end_trip_stop_id');
    }
}
