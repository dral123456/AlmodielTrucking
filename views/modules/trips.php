<?php
require_once "controllers/booking.controller.php";
require_once "models/booking.model.php";

$trips = ControllerBooking::ctrTripOverviewList();
?>

<div class="trip-page">
  <div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Trips</h5>
        <p class="text-muted small mb-0">View generated trips, connected bookings, delivery status, and route points.</p>
      </div>
      <span class="badge bg-primary-subtle text-primary fs-6">
        <i class="ri-route-line me-1"></i> Trip Monitoring
      </span>
    </div>

    <div class="card-body p-4">
      <div class="trip-filter-grid mb-4">
        <div>
          <label class="form-label">Sort by Date & Time</label>
          <select class="form-select" id="tripSort">
            <option value="date_desc">Newest first</option>
            <option value="date_asc">Oldest first</option>
            <option value="time_asc">Earliest time first</option>
            <option value="time_desc">Latest time first</option>
          </select>
        </div>
        <div>
          <label class="form-label">Status</label>
          <select class="form-select" id="tripStatusFilter">
            <option value="all">All trips</option>
            <option value="pending">Pending</option>
            <option value="stopover">Stopover</option>
            <option value="in-transit">On Transit</option>
            <option value="completed">Delivered</option>
          </select>
        </div>
        <div>
          <label class="form-label">Trip Date Range</label>
          <div class="form-icon">
            <i class="ri-calendar-line text-muted"></i>
            <input type="text" class="form-control form-control-icon" id="tripDateRangeFilter" placeholder="Select date range" autocomplete="off" readonly>
          </div>
          <div class="form-text" id="tripDateHint">Dates with bookings are marked in the calendar.</div>
        </div>
        <div class="trip-filter-action">
          <button type="button" class="btn btn-light w-100" id="tripClearFilters">
            <i class="ri-refresh-line me-1"></i> Clear Filters
          </button>
        </div>
      </div>

      <div class="trip-workspace">
        <section class="trip-list-panel">
          <div id="tripList" class="trip-list"></div>
        </section>
        <section class="trip-map-panel">
          <div class="trip-map-shell">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <div>
                <h6 class="text-uppercase text-muted mb-1">
                  <i class="ri-road-map-line me-1"></i> Leaflet Map
                </h6>
                <p class="text-muted small mb-0" id="tripMapStatus">Select a trip to view pickup and destination pins.</p>
              </div>
              <span class="badge bg-secondary-subtle text-secondary" id="tripMapBadge">No trip selected</span>
            </div>
            <div id="tripMap"></div>
          </div>
        </section>
      </div>
    </div>
  </div>
</div>

<script>
  window.tripOverviewData = <?php echo json_encode($trips, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<style>
  .trip-page {
    max-width: 1440px;
    margin: 0 auto;
  }

  .trip-filter-grid {
    display: grid;
    grid-template-columns: minmax(190px, 1fr) minmax(170px, 0.8fr) minmax(280px, 1.35fr) minmax(150px, 180px);
    align-items: start;
    gap: 1rem;
  }

  .trip-filter-action {
    align-self: start;
    padding-top: 1.85rem;
  }

  .trip-workspace {
    display: grid;
    grid-template-columns: 420px minmax(0, 1fr);
    align-items: start;
    gap: 1.25rem;
  }

  .trip-list-panel,
  .trip-map-panel {
    min-width: 0;
  }

  .trip-list {
    display: grid;
    gap: 0.75rem;
    max-height: 560px;
    overflow: auto;
    padding-right: 0.25rem;
  }

  .trip-item {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    background: var(--bs-body-bg);
    padding: 1rem;
    text-align: left;
    width: 100%;
    min-width: 0;
  }

  .trip-item.active {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.12);
  }

  .trip-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    color: var(--bs-secondary-color);
    font-size: 0.8125rem;
  }

  .trip-booking-row {
    border-top: 1px solid var(--bs-border-color);
    padding-top: 0.75rem;
    margin-top: 0.75rem;
  }

  .air-datepicker-cell.-trip-has-booking- {
    font-weight: 700;
  }

  .trip-calendar-day {
    position: relative;
    width: 100%;
    height: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .trip-calendar-day i {
    position: absolute;
    bottom: 4px;
    left: 50%;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    transform: translateX(-50%);
    background: var(--bs-primary);
  }

  .air-datepicker-cell.-selected- .trip-calendar-day i,
  .air-datepicker-cell.-range-from- .trip-calendar-day i,
  .air-datepicker-cell.-range-to- .trip-calendar-day i {
    background: #fff;
  }

  .trip-map-shell {
    position: sticky;
    top: 90px;
  }

  #tripMap {
    width: 100%;
    height: 560px;
    min-height: 480px;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    overflow: hidden;
  }

  @media (max-width: 1399.98px) {
    .trip-workspace {
      grid-template-columns: 400px minmax(0, 1fr);
    }
  }

  @media (max-width: 1199.98px) {
    .trip-filter-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .trip-workspace {
      grid-template-columns: 1fr;
    }

    .trip-list {
      max-height: none;
      overflow: visible;
    }

    .trip-map-shell {
      position: static;
    }

    #tripMap {
      height: 460px;
      min-height: 460px;
    }
  }

  @media (max-width: 575.98px) {
    .trip-filter-grid {
      grid-template-columns: 1fr;
    }

    #tripMap {
      height: 340px;
      min-height: 340px;
    }

    .trip-item {
      padding: 0.875rem;
    }
  }
</style>
