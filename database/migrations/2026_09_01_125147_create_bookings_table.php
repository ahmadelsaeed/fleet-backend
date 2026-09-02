<?php

use App\Models\Seat;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(Trip::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Seat::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');

            $table->foreignIdFor(TripStop::class, 'start_trip_stop_id')->constrained('trip_stops')->onDelete('cascade');
            $table->foreignIdFor(TripStop::class, 'end_trip_stop_id')->constrained('trip_stops')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['trip_id', 'seat_id']);
            $table->index(['start_trip_stop_id', 'end_trip_stop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
