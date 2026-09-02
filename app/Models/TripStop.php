<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripStop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['trip_id', 'station_id', 'sequence_order'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
