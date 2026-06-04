<?php
require_once "controllers/booking.controller.php";
require_once "models/booking.model.php";
require_once "controllers/salary.controller.php";
require_once "models/salary.model.php";

$driverID = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
$role = $_SESSION["role"] ?? "";
$showAll = in_array($role, array("admin", "employee"), true);
$canStartDelivery = $role === "driver";
$driverTrips = ControllerBooking::ctrDriverTripList($driverID, $showAll);
$driverSalaryRows = $driverID > 0 ? ControllerSalary::ctrSalaryRows($driverID) : array();
$driverTripStats = array(
  "total" => count($driverTrips),
  "active" => 0,
  "completed" => 0,
  "pending" => 0
);

foreach ($driverTrips as $trip) {
  if (in_array($trip["status"], array("in-transit", "stopover"), true)) {
    $driverTripStats["active"]++;
  } elseif ($trip["status"] === "completed") {
    $driverTripStats["completed"]++;
  } else {
    $driverTripStats["pending"]++;
  }
}

function driverTripDate($value) {
  if (!$value) {
    return "-";
  }

  $timestamp = strtotime($value);
  return $timestamp ? date("M d, Y h:i A", $timestamp) : $value;
}

function driverTripText($value, $fallback = "-") {
  $value = trim((string) $value);
  return htmlspecialchars($value !== "" ? $value : $fallback);
}

function driverTripStatusClass($status) {
  if ($status === "completed") {
    return "bg-success-subtle text-success";
  }

  if ($status === "stopover") {
    return "bg-info-subtle text-info";
  }

  if ($status === "in-transit") {
    return "bg-primary-subtle text-primary";
  }

  return "bg-warning-subtle text-warning";
}

function driverTripMoney($value) {
  return "PHP " . number_format((float) $value, 2);
}
?>

<div class="driver-trips-page">
  <div class="driver-dashboard">
    <div class="driver-stat-grid">
      <div class="driver-stat-card">
        <span class="driver-stat-icon driver-stat-blue"><i class="ri-truck-line"></i></span>
        <div>
          <small>Total Trips</small>
          <strong><?php echo (int) $driverTripStats["total"]; ?></strong>
          <span class="driver-stat-trend positive">Available assignments</span>
        </div>
        <svg viewBox="0 0 90 28" aria-hidden="true"><polyline points="2,22 12,17 22,19 32,10 42,14 52,8 62,11 72,5 88,2" /></svg>
      </div>
      <div class="driver-stat-card">
        <span class="driver-stat-icon driver-stat-green"><i class="ri-play-circle-line"></i></span>
        <div>
          <small>Active Deliveries</small>
          <strong><?php echo (int) $driverTripStats["active"]; ?></strong>
          <span class="driver-stat-trend positive">In transit or stopover</span>
        </div>
        <svg viewBox="0 0 90 28" aria-hidden="true"><polyline points="2,24 14,20 24,15 34,17 44,9 54,13 64,7 74,9 88,3" /></svg>
      </div>
      <div class="driver-stat-card">
        <span class="driver-stat-icon driver-stat-purple"><i class="ri-checkbox-circle-line"></i></span>
        <div>
          <small>Completed Deliveries</small>
          <strong><?php echo (int) $driverTripStats["completed"]; ?></strong>
          <span class="driver-stat-trend positive">Delivered trips shown</span>
        </div>
        <svg viewBox="0 0 90 28" aria-hidden="true"><polyline points="2,20 12,16 22,18 32,12 42,15 52,7 62,12 72,8 88,5" /></svg>
      </div>
      <div class="driver-stat-card">
        <span class="driver-stat-icon driver-stat-orange"><i class="ri-time-line"></i></span>
        <div>
          <small>Pending Updates</small>
          <strong><?php echo (int) $driverTripStats["pending"]; ?></strong>
          <span class="driver-stat-trend muted">Awaiting progress</span>
        </div>
        <svg viewBox="0 0 90 28" aria-hidden="true"><polyline points="2,23 14,18 24,20 34,12 44,16 54,10 64,13 74,6 88,9" /></svg>
      </div>
    </div>

    <div class="driver-dashboard-body">
      <?php if (empty($driverTrips)): ?>
        <div class="driver-empty">No active trips assigned yet.</div>
      <?php else: ?>
        <div class="driver-trip-layout">
          <section class="driver-trip-list">
            <div class="driver-panel-header">
              <div>
                <h5 class="mb-0">Trips</h5>
                <p class="text-muted small mb-0">Select a row to update the delivery map.</p>
              </div>
              <div class="driver-panel-tools">
                <span><i class="ri-search-line"></i> Search trips...</span>
                <span><i class="ri-filter-3-line"></i> Filter</span>
                <span><i class="ri-arrow-up-down-line"></i> Sort</span>
              </div>
            </div>
            <div class="table-responsive driver-trip-table-wrap">
              <table class="table align-middle driver-trip-table mb-0">
                <thead>
                  <tr>
                    <th>Trip</th>
                    <th>Schedule</th>
                    <th>Booking</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($driverTrips as $trip): ?>
                    <?php
                      $firstBooking = $trip["bookings"][0] ?? array();
                      $bookingLabels = array();
                      $pickupBlocks = array();
                      $destinationBlocks = array();

                      foreach ($trip["bookings"] as $booking) {
                        $bookingLabels[] = "Booking #" . (int) $booking["bookingID"] . " - " . driverTripText($booking["customerName"], "Customer");

                        $pickupText = driverTripText($booking["pickupAddress"]);
                        if (!empty($booking["pickupDescription"]) && trim((string) $booking["pickupDescription"]) !== trim((string) $booking["pickupAddress"])) {
                          $pickupText .= '<div class="text-muted">' . driverTripText($booking["pickupDescription"]) . '</div>';
                        }
                        $pickupBlocks[] = $pickupText;

                        $destinationText = driverTripText($booking["destinationAddress"]);
                        if (!empty($booking["destinationDescription"]) && trim((string) $booking["destinationDescription"]) !== trim((string) $booking["destinationAddress"])) {
                          $destinationText .= '<div class="text-muted">' . driverTripText($booking["destinationDescription"]) . '</div>';
                        }
                        $destinationBlocks[] = $destinationText;
                      }
                    ?>
                    <tr class="driver-trip-row" data-trip-id="<?php echo (int) $trip["tripID"]; ?>">
                      <td>
                        <strong>Trip #<?php echo (int) $trip["tripID"]; ?></strong>
                        <div class="small text-muted"><?php echo (int) $trip["bookingCount"]; ?> booking(s)</div>
                      </td>
                      <td class="driver-trip-date"><?php echo htmlspecialchars(driverTripDate($trip["pickupDateTime"])); ?></td>
                      <td>
                        <strong><?php echo $bookingLabels[0] ?? "No booking"; ?></strong>
                        <?php if (count($bookingLabels) > 1): ?>
                          <div class="small text-muted">+<?php echo count($bookingLabels) - 1; ?> more booking(s)</div>
                        <?php endif; ?>
                        <div class="small text-muted"><?php echo driverTripText(ucfirst($firstBooking["customerType"] ?? "")); ?></div>
                      </td>
                      <td>
                        <span class="badge <?php echo driverTripStatusClass($trip["status"]); ?> driver-trip-status">
                          <?php echo driverTripText(ucfirst(str_replace("-", " ", $trip["status"]))); ?>
                        </span>
                      </td>
                      <td>
                        <div class="driver-trip-actions">
                          <button type="button" class="btn btn-sm btn-light driver-map-focus">
                            <i class="ri-road-map-line me-1"></i> Map
                          </button>
                          <?php if ($canStartDelivery && $trip["status"] === "pending"): ?>
                            <button type="button" class="btn btn-sm btn-primary driver-status-action" data-status="in-transit">
                              <i class="ri-play-circle-line me-1"></i> Start
                            </button>
                          <?php endif; ?>
                          <button type="button" class="btn btn-sm btn-info driver-status-action" data-status="stopover">
                            <i class="ri-map-pin-time-line me-1"></i> Stopover
                          </button>
                          <button type="button" class="btn btn-sm btn-success driver-status-action" data-status="completed">
                            <i class="ri-check-double-line me-1"></i> Delivered
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
          <section class="driver-map-panel">
            <div class="driver-map-shell">
              <div class="driver-map-heading">
                <div>
                  <h5 class="mb-1"><i class="ri-road-map-line me-1"></i> Delivery Map</h5>
                  <p class="text-muted small mb-0" id="driverMapStatus">Select a trip to view pickup and destination pins.</p>
                </div>
                <div class="driver-map-actions">
                  <span class="badge bg-secondary-subtle text-secondary" id="driverMapBadge">No trip selected</span>
                  <button type="button" class="btn btn-sm btn-outline-light">View Details</button>
                  <button type="button" class="btn btn-sm btn-icon btn-outline-light"><i class="ri-more-2-fill"></i></button>
                </div>
              </div>
              <div id="driverTripMap"></div>
              <div class="driver-map-legend">
                <span><i class="driver-dot pickup"></i> Pickup</span>
                <span><i class="driver-dot destination"></i> Destination</span>
                <span><i class="driver-route-line"></i> Route</span>
              </div>
              <div class="driver-trip-detail-panel" id="driverTripDetailPanel">
                <div class="driver-trip-detail-empty">Select a trip to view booking details.</div>
              </div>
            </div>
          </section>
        </div>
      <?php endif; ?>

      <?php if ($role === "driver"): ?>
        <section class="driver-salary-panel mt-4">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
              <h6 class="mb-0">My Salary History</h6>
              <p class="text-muted small mb-0">View trip salary credits, paid records, and unpaid payroll.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary"><?php echo count($driverSalaryRows); ?> record(s)</span>
          </div>

          <?php if (empty($driverSalaryRows)): ?>
            <div class="driver-empty">No salary records yet.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th>Trip</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th>Paid Date</th>
                    <th class="text-end">Net Pay</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($driverSalaryRows as $salary): ?>
                    <tr>
                      <td>
                        <?php if (!empty($salary["tripID"])): ?>
                          <strong>Trip #<?php echo (int) $salary["tripID"]; ?></strong>
                          <div class="small text-muted">Booking #<?php echo (int) $salary["creditedBookingID"]; ?> | <?php echo number_format((float) $salary["creditedDistanceKm"], 2); ?> km credited</div>
                        <?php else: ?>
                          <span class="text-muted">Regular salary</span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars(driverTripDate($salary["payPeriodStart"])); ?> - <?php echo htmlspecialchars(driverTripDate($salary["payPeriodEnd"])); ?></td>
                      <td>
                        <span class="badge <?php echo $salary["status"] === "paid" ? "bg-success-subtle text-success" : ($salary["status"] === "cancelled" ? "bg-secondary-subtle text-secondary" : "bg-warning-subtle text-warning"); ?>">
                          <?php echo htmlspecialchars($salary["status"] === "pending" ? "Unpaid" : ucfirst($salary["status"])); ?>
                        </span>
                      </td>
                      <td><?php echo htmlspecialchars(driverTripDate($salary["datePaid"])); ?></td>
                      <td class="text-end fw-semibold"><?php echo driverTripMoney($salary["netPay"]); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  window.driverTripData = <?php echo json_encode($driverTrips, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<style>
  .driver-trips-page {
    max-width: 1720px;
    margin: 0 auto;
  }

  .driver-dashboard {
    display: grid;
    gap: 1rem;
  }

  .driver-stat-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.875rem;
  }

  .driver-stat-card {
    position: relative;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) 92px;
    align-items: center;
    gap: 0.875rem;
    min-height: 106px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 0.875rem;
    background:
      radial-gradient(circle at top right, rgba(105, 108, 255, 0.14), transparent 36%),
      linear-gradient(145deg, rgba(21, 31, 51, 0.96), rgba(14, 22, 38, 0.98));
    padding: 1rem;
    overflow: hidden;
    box-shadow: 0 16px 34px rgba(0, 0, 0, 0.20);
  }

  .driver-stat-icon {
    width: 48px;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    color: #fff;
    font-size: 1.35rem;
  }

  .driver-stat-blue {
    background: linear-gradient(135deg, #5577ff, #3652f4);
  }

  .driver-stat-green {
    background: linear-gradient(135deg, #2ddc83, #15985d);
  }

  .driver-stat-purple {
    background: linear-gradient(135deg, #8665ff, #6542dc);
  }

  .driver-stat-orange {
    background: linear-gradient(135deg, #ffbd42, #f59e0b);
  }

  .driver-stat-card small {
    display: block;
    color: #aeb8cc;
    font-size: 0.78rem;
  }

  .driver-stat-card strong {
    display: block;
    color: #f8fbff;
    font-size: 1.65rem;
    line-height: 1.1;
    margin: 0.15rem 0;
  }

  .driver-stat-trend {
    display: block;
    font-size: 0.72rem;
  }

  .driver-stat-trend.positive {
    color: #38d996;
  }

  .driver-stat-trend.muted {
    color: #fbbf24;
  }

  .driver-stat-card svg {
    width: 92px;
    height: 32px;
  }

  .driver-stat-card polyline {
    fill: none;
    stroke: #5d7cff;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .driver-dashboard-body {
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 1rem;
    background:
      linear-gradient(160deg, rgba(15, 23, 42, 0.96), rgba(12, 19, 33, 0.98));
    padding: 0;
    box-shadow: 0 20px 44px rgba(0, 0, 0, 0.18);
  }

  .driver-trip-layout {
    display: grid;
    grid-template-columns: minmax(700px, 1.05fr) minmax(520px, 0.95fr);
    align-items: stretch;
    gap: 0;
  }

  .driver-trip-list {
    min-width: 0;
    border-right: 1px solid rgba(148, 163, 184, 0.14);
    background: transparent;
  }

  .driver-panel-header,
  .driver-map-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.14);
  }

  .driver-panel-header h5,
  .driver-map-heading h5 {
    color: #f8fbff;
  }

  .driver-panel-tools,
  .driver-map-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  .driver-panel-tools span {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-height: 34px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 0.5rem;
    color: #91a1bb;
    background: rgba(15, 23, 42, 0.70);
    padding: 0.35rem 0.65rem;
    font-size: 0.78rem;
  }

  .driver-trip-table-wrap {
    min-width: 0;
    max-height: 680px;
    overflow: auto;
  }

  .driver-trip-table {
    min-width: 760px;
    border-collapse: separate;
    border-spacing: 0 0.45rem;
    padding: 0.5rem;
  }

  .driver-trip-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    border: 0;
    background: #10192b;
    color: #7d8da7;
    font-size: 0.68rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    white-space: nowrap;
    padding: 0.75rem;
  }

  .driver-trip-row {
    cursor: pointer;
  }

  .driver-trip-row td {
    border-top: 1px solid rgba(148, 163, 184, 0.12);
    border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    background: rgba(20, 31, 51, 0.88);
    color: #dbe7ff;
    padding: 0.85rem 0.75rem;
  }

  .driver-trip-row td:first-child {
    border-left: 1px solid rgba(148, 163, 184, 0.12);
    border-radius: 0.65rem 0 0 0.65rem;
  }

  .driver-trip-row td:last-child {
    border-right: 1px solid rgba(148, 163, 184, 0.12);
    border-radius: 0 0.65rem 0.65rem 0;
  }

  .driver-trip-row:hover {
    transform: translateY(-1px);
  }

  .driver-trip-row.active {
    box-shadow: none;
  }

  .driver-trip-row.active td {
    border-color: rgba(80, 108, 255, 0.65);
    background: rgba(35, 49, 82, 0.96);
  }

  .driver-trip-table td {
    vertical-align: top;
  }

  .driver-trip-date {
    white-space: nowrap;
  }

  .driver-trip-cell-list {
    display: grid;
    gap: 0.45rem;
    max-width: 300px;
    font-size: 0.78rem;
  }

  .driver-trip-cell-list > div {
    line-height: 1.35;
  }

  .driver-trip-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.35rem;
    min-width: 230px;
  }

  .driver-trip-detail-panel {
    margin: 0 1rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 0.75rem;
    background: rgba(12, 19, 33, 0.72);
    overflow: hidden;
  }

  .driver-trip-detail-empty {
    padding: 1rem;
    color: #91a1bb;
    text-align: center;
  }

  .driver-trip-detail-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.14);
  }

  .driver-trip-detail-heading h6 {
    color: #f8fbff;
  }

  .driver-trip-detail-list {
    display: grid;
    gap: 0.75rem;
    padding: 1rem;
    max-height: 260px;
    overflow: auto;
  }

  .driver-trip-detail-card {
    border: 1px solid rgba(148, 163, 184, 0.12);
    border-radius: 0.75rem;
    background: rgba(20, 31, 51, 0.82);
    padding: 0.875rem;
  }

  .driver-trip-detail-title {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.75rem;
  }

  .driver-trip-detail-title strong {
    display: block;
    color: #f8fbff;
  }

  .driver-trip-detail-title span:not(.badge) {
    display: block;
    color: #91a1bb;
    font-size: 0.8rem;
  }

  .driver-trip-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.875rem;
  }

  .driver-trip-detail-grid small {
    display: block;
    margin-bottom: 0.35rem;
    color: #91a1bb;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }

  .driver-trip-detail-grid p {
    margin: 0;
    color: #dbe7ff;
    line-height: 1.4;
  }

  .driver-trip-detail-grid span {
    display: block;
    margin-top: 0.35rem;
    color: #91a1bb;
    font-size: 0.82rem;
    line-height: 1.4;
  }

  .driver-map-panel {
    min-width: 0;
  }

  .driver-map-shell {
    height: 100%;
    background: transparent;
  }

  #driverTripMap {
    width: 100%;
    height: 430px;
    min-height: 430px;
    border-radius: 0.75rem;
    overflow: hidden;
    background: #dbeafe;
    filter: saturate(0.82) brightness(0.78);
  }

  .driver-map-shell #driverTripMap {
    margin: 1rem;
    width: calc(100% - 2rem);
  }

  .driver-map-legend {
    display: flex;
    align-items: center;
    justify-content: space-around;
    gap: 1rem;
    margin: 0 1rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 0.75rem;
    color: #b5c2d8;
    background: rgba(12, 19, 33, 0.72);
    padding: 0.85rem;
    font-size: 0.82rem;
  }

  .driver-dot {
    width: 10px;
    height: 10px;
    display: inline-block;
    border-radius: 50%;
    margin-right: 0.4rem;
  }

  .driver-dot.pickup {
    background: #4f6bff;
  }

  .driver-dot.destination {
    background: #ef4444;
  }

  .driver-route-line {
    width: 30px;
    height: 2px;
    display: inline-block;
    margin-right: 0.4rem;
    vertical-align: middle;
    background: #4f6bff;
  }

  .driver-empty {
    border: 1px dashed var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 2rem;
    color: var(--bs-secondary-color);
    text-align: center;
    background: var(--bs-tertiary-bg);
  }

  .driver-salary-panel {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 1rem;
    background: var(--bs-body-bg);
  }

  .driver-dashboard .text-muted {
    color: #91a1bb !important;
  }

  @media (max-width: 767.98px) {
    .driver-stat-grid {
      grid-template-columns: 1fr;
    }

    .driver-trip-layout {
      grid-template-columns: 1fr;
    }

    .driver-map-panel {
      position: static;
      order: -1;
    }

    .driver-trip-list {
      max-height: none;
      border-right: 0;
      border-bottom: 1px solid rgba(148, 163, 184, 0.14);
    }

    #driverTripMap {
      min-height: 320px;
      height: 420px;
    }

    .driver-trip-actions {
      flex-wrap: wrap;
      min-width: 220px;
    }

    .driver-trip-detail-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
