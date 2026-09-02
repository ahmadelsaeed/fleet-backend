---
paths:
  - 'app/Services/**'
---

# Services

## SeatAvailabilityService — overlap check pattern
Seat conflict uses inclusive-exclusive segment comparison: existing.start_order < newEndOrder AND newStartOrder < existing.end_order. Implemented via whereHas on startStop/endStop with sequence_order. assertNoConflict() must be called inside DB::transaction() with lockForUpdate() to prevent race conditions. Never compare station IDs directly — always compare sequence_order.
