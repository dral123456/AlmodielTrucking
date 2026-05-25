<?php
require_once "controllers/booking.controller.php";
require_once "models/booking.model.php";

$trips = ControllerBooking::ctrTripOverviewList();
$trucks = ControllerBooking::ctrTruckList();
$drivers = ControllerBooking::ctrEmployeeListByType("driver");
$assistants = ControllerBooking::ctrEmployeeListByType("assistant");
$tripStats = array(
  "total" => count($trips),
  "pending" => 0,
  "stopover" => 0,
  "in-transit" => 0,
  "completed" => 0
);

foreach ($trips as $trip) {
  if (isset($tripStats[$trip["status"]])) {
    $tripStats[$trip["status"]]++;
  }
}
?>

<div class="trip-page">
  <div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Trips</h5>
        <p class="text-muted small mb-0">Monitor generated trips, route points, booking groups, and delivery status.</p>
      </div>
      <span class="badge bg-primary-subtle text-primary fs-6">
        <i class="ri-route-line me-1"></i> Trip Monitoring
      </span>
    </div>

    <div class="card-body p-4">
      <div class="trip-stat-grid mb-4">
        <button type="button" class="trip-stat-card active" data-status-shortcut="all">
          <span class="trip-stat-icon bg-primary-subtle text-primary"><i class="ri-route-line"></i></span>
          <span><small>Total Trips</small><strong><?php echo (int) $tripStats["total"]; ?></strong></span>
        </button>
        <button type="button" class="trip-stat-card" data-status-shortcut="pending">
          <span class="trip-stat-icon bg-warning-subtle text-warning"><i class="ri-time-line"></i></span>
          <span><small>Pending</small><strong><?php echo (int) $tripStats["pending"]; ?></strong></span>
        </button>
        <button type="button" class="trip-stat-card" data-status-shortcut="stopover">
          <span class="trip-stat-icon bg-info-subtle text-info"><i class="ri-map-pin-time-line"></i></span>
          <span><small>Stopover</small><strong><?php echo (int) $tripStats["stopover"]; ?></strong></span>
        </button>
        <button type="button" class="trip-stat-card" data-status-shortcut="in-transit">
          <span class="trip-stat-icon bg-primary-subtle text-primary"><i class="ri-truck-line"></i></span>
          <span><small>On Transit</small><strong><?php echo (int) $tripStats["in-transit"]; ?></strong></span>
        </button>
        <button type="button" class="trip-stat-card" data-status-shortcut="completed">
          <span class="trip-stat-icon bg-success-subtle text-success"><i class="ri-check-double-line"></i></span>
          <span><small>Delivered</small><strong><?php echo (int) $tripStats["completed"]; ?></strong></span>
        </button>
      </div>

      <div class="trip-filter-panel mb-4">
        <div class="trip-filter-grid">
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
            <label class="form-label">Trip Number</label>
            <div class="form-icon">
              <i class="ri-hashtag text-muted"></i>
              <input type="text" class="form-control form-control-icon" id="tripNumberFilter" placeholder="Search trip #">
            </div>
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
      </div>

      <div class="trip-workspace">
        <section class="trip-list-panel">
          <div class="trip-panel-heading">
            <div>
              <h6 class="mb-0">Trip List</h6>
              <p class="text-muted small mb-0" id="tripListSummary">Select a row to view route and booking details.</p>
            </div>
          </div>
          <div class="table-responsive mt-3">
            <table class="table align-middle trip-table mb-0">
              <thead>
                <tr>
                  <th>Trip</th>
                  <th>Date & Time</th>
                  <th>Customer</th>
                  <th>Crew</th>
                  <th class="text-center">Bookings</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="tripTableBody"></tbody>
            </table>
          </div>
        </section>
        <section class="trip-detail-panel">
          <div id="tripDetails" class="trip-detail-shell">
            <div class="text-muted text-center p-4">Select a trip to view details.</div>
          </div>
        </section>
      </div>
    </div>
  </div>
</div>

<script>
  window.tripOverviewData = <?php echo json_encode($trips, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  window.tripTruckOptions = <?php echo json_encode($trucks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  window.tripDriverOptions = <?php echo json_encode($drivers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  window.tripAssistantOptions = <?php echo json_encode($assistants, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<style>
  .trip-page {
    max-width: 1480px;
    margin: 0 auto;
    width: 100%;
  }

  .trip-stat-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.75rem;
  }

  .trip-stat-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    padding: 0.875rem;
    text-align: left;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }

  .trip-stat-card.active,
  .trip-stat-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.10);
  }

  .trip-stat-icon {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 40px;
    border-radius: 0.5rem;
    font-size: 1.2rem;
  }

  .trip-stat-card small {
    display: block;
    color: var(--bs-secondary-color);
    line-height: 1.2;
  }

  .trip-stat-card strong {
    display: block;
    font-size: 1.25rem;
    line-height: 1.15;
  }

  .trip-filter-panel,
  .trip-list-panel,
  .trip-detail-shell {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    background: var(--bs-body-bg);
  }

  .trip-filter-panel,
  .trip-list-panel,
  .trip-detail-shell {
    padding: 1rem;
  }

  .trip-filter-grid {
    display: grid;
    grid-template-columns: minmax(170px, 0.8fr) minmax(150px, 0.7fr) minmax(170px, 0.75fr) minmax(260px, 1.2fr) minmax(150px, 180px);
    align-items: start;
    gap: 1rem;
  }

  .trip-filter-action {
    align-self: start;
    padding-top: 1.85rem;
  }

  .trip-workspace {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 1rem;
  }

  .trip-list-panel,
  .trip-detail-panel {
    min-width: 0;
  }

  .trip-panel-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
  }

  .trip-table th {
    color: var(--bs-secondary-color);
    font-size: 0.8125rem;
    text-transform: uppercase;
    white-space: nowrap;
  }

  .trip-row {
    cursor: pointer;
  }

  .trip-row:hover {
    background: var(--bs-tertiary-bg);
  }

  .trip-row.active {
    background: var(--bs-primary-bg-subtle);
  }

  .trip-row-main {
    font-weight: 700;
    white-space: nowrap;
  }

  .trip-row-sub {
    color: var(--bs-secondary-color);
    font-size: 0.8125rem;
    max-width: 360px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .trip-booking-row {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 0.875rem;
    background: var(--bs-body-bg);
  }

  .trip-booking-locations {
    display: grid;
    gap: 0.25rem;
    margin-top: 0.375rem;
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

  .trip-detail-shell {
    padding: 1rem;
  }

  .trip-detail-grid {
    display: grid;
    grid-template-columns: minmax(320px, 0.8fr) minmax(0, 1.2fr);
    gap: 1rem;
    align-items: start;
  }

  .trip-booking-list {
    display: grid;
    gap: 0.75rem;
  }

  .trip-map-shell {
    min-width: 0;
  }

  #tripMap {
    width: 100%;
    height: min(64vh, 660px);
    min-height: 520px;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    overflow: hidden;
    background: #dbeafe;
  }

  @media (max-width: 1399.98px) {
    .trip-stat-grid {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
  }

  @media (max-width: 1199.98px) {
    .trip-filter-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .trip-filter-action {
      padding-top: 0;
      align-self: end;
    }

    .trip-detail-grid {
      grid-template-columns: 1fr;
    }

    #tripMap {
      height: 460px;
      min-height: 460px;
    }
  }

  @media (max-width: 767.98px) {
    .trip-stat-grid,
    .trip-filter-grid {
      grid-template-columns: 1fr;
    }

    .trip-filter-action {
      align-self: stretch;
    }

    #tripMap {
      height: 360px;
      min-height: 360px;
    }
  }

  @media (max-width: 575.98px) {
    .trip-list-panel,
    .trip-detail-shell,
    .trip-filter-panel {
      padding: 0.875rem;
    }
  }
</style>
