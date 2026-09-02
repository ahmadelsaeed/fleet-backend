<?php

use App\Models\Station;
use App\Models\Trip;
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
        Schema::create('trip_stops', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(Trip::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Station::class)->constrained()->onDelete('cascade');
            $table->unsignedSmallInteger('sequence_order');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['trip_id', 'sequence_order']);
            $table->unique(['trip_id', 'station_id']);
            $table->index(['trip_id', 'sequence_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_stops');
    }
};
