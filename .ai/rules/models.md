---
paths:
  - 'app/Models/**'
---

# Models

## All domain models use SoftDeletes + HasFactory
Every model except User uses SoftDeletes (deleted_at). All models use HasFactory. Booking "cancellation" is a soft-delete — no status column exists. Booking.startStop uses FK start_trip_stop_id; endStop uses end_trip_stop_id.
