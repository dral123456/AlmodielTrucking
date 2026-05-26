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
  <div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Driver Trips</h5>
        <p class="text-muted small mb-0">Update delivery progress when a trip reaches a stopover or is delivered.</p>
      </div>
      <span class="badge bg-primary-subtle text-primary fs-6">
        <i class="ri-truck-line me-1"></i> Delivery Updates
      </span>
    </div>

    <div class="card-body p-4">
      <?php if (empty($driverTrips)): ?>
        <div class="driver-empty">No active trips assigned yet.</div>
      <?php else: ?>
        <div class="driver-trip-layout">
          <section class="driver-trip-list">
            <?php foreach ($driverTrips as $trip): ?>
              <article class="driver-trip-card" data-trip-id="<?php echo (int) $trip["tripID"]; ?>">
                <div class="driver-trip-header">
                  <div>
                    <h6 class="mb-1">Trip #<?php echo (int) $trip["tripID"]; ?></h6>
                    <div class="text-muted small">
                      <i class="ri-calendar-line me-1"></i><?php echo htmlspecialchars(driverTripDate($trip["pickupDateTime"])); ?>
                      <span class="ms-2"><i class="ri-file-list-3-line me-1"></i><?php echo (int) $trip["bookingCount"]; ?> booking(s)</span>
                    </div>
                  </div>
                  <span class="badge <?php echo driverTripStatusClass($trip["status"]); ?> driver-trip-status">
                    <?php echo driverTripText(ucfirst(str_replace("-", " ", $trip["status"]))); ?>
                  </span>
                </div>

                <div class="driver-booking-list">
                  <?php foreach ($trip["bookings"] as $booking): ?>
                    <div class="driver-booking-row">
                      <div>
                        <strong>Booking #<?php echo (int) $booking["bookingID"]; ?> - <?php echo driverTripText($booking["customerName"], "Customer"); ?></strong>
                        <div class="small text-muted"><?php echo driverTripText(ucfirst($booking["customerType"])); ?></div>
                      </div>
                      <div class="small">
                        <div><i class="ri-map-pin-2-line text-primary me-1"></i><?php echo driverTripText($booking["pickupAddress"]); ?></div>
                        <?php if (!empty($booking["pickupDescription"]) && trim((string) $booking["pickupDescription"]) !== trim((string) $booking["pickupAddress"])): ?>
                          <div class="text-muted ms-4"><?php echo driverTripText($booking["pickupDescription"]); ?></div>
                        <?php endif; ?>
                        <div class="mt-1"><i class="ri-flag-line text-danger me-1"></i><?php echo driverTripText($booking["destinationAddress"]); ?></div>
                        <?php if (!empty($booking["destinationDescription"]) && trim((string) $booking["destinationDescription"]) !== trim((string) $booking["destinationAddress"])): ?>
                          <div class="text-muted ms-4"><?php echo driverTripText($booking["destinationDescription"]); ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <div class="driver-trip-actions">
                  <button type="button" class="btn btn-light driver-map-focus">
                    <i class="ri-road-map-line me-1"></i> View Map
                  </button>
                  <?php if ($canStartDelivery && $trip["status"] === "pending"): ?>
                    <button type="button" class="btn btn-primary driver-status-action" data-status="in-transit">
                      <i class="ri-play-circle-line me-1"></i> Start Delivery
                    </button>
                  <?php endif; ?>
                  <button type="button" class="btn btn-info driver-status-action" data-status="stopover">
                    <i class="ri-map-pin-time-line me-1"></i> Stopover
                  </button>
                  <button type="button" class="btn btn-success driver-status-action" data-status="completed">
                    <i class="ri-check-double-line me-1"></i> Delivered
                  </button>
                </div>
              </article>
            <?php endforeach; ?>
          </section>
          <section class="driver-map-panel">
            <div class="driver-map-shell">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                  <h6 class="text-uppercase text-muted mb-1">
                    <i class="ri-road-map-line me-1"></i> Delivery Map
                  </h6>
                  <p class="text-muted small mb-0" id="driverMapStatus">Select a trip to view pickup and destination pins.</p>
                </div>
                <span class="badge bg-secondary-subtle text-secondary" id="driverMapBadge">No trip selected</span>
              </div>
              <div id="driverTripMap"></div>
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
    max-width: 1440px;
    margin: 0 auto;
  }

  .driver-trip-layout {
    display: grid;
    grid-template-columns: minmax(360px, 0.8fr) minmax(420px, 1.2fr);
    align-items: start;
    gap: 1rem;
  }

  .driver-trip-list {
    display: grid;
    gap: 1rem;
    max-height: 680px;
    overflow: auto;
    padding-right: 0.25rem;
  }

  .driver-trip-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 1rem;
    background: var(--bs-body-bg);
  }

  .driver-trip-card.active {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.12);
  }

  .driver-trip-header,
  .driver-trip-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
  }

  .driver-map-panel {
    min-width: 0;
    position: sticky;
    top: 90px;
  }

  .driver-map-shell {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 1rem;
    background: var(--bs-body-bg);
  }

  #driverTripMap {
    width: 100%;
    height: min(62vh, 620px);
    min-height: 420px;
    border-radius: 0.5rem;
    overflow: hidden;
    background: #dbeafe;
  }

  .driver-booking-list {
    display: grid;
    gap: 0.75rem;
    margin: 1rem 0;
  }

  .driver-booking-row {
    display: grid;
    grid-template-columns: minmax(180px, 0.55fr) minmax(260px, 1fr);
    gap: 1rem;
    border-top: 1px solid var(--bs-border-color);
    padding-top: 0.75rem;
  }

  .driver-trip-actions {
    justify-content: flex-end;
    border-top: 1px solid var(--bs-border-color);
    padding-top: 1rem;
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

  @media (max-width: 767.98px) {
    .driver-trip-layout {
      grid-template-columns: 1fr;
    }

    .driver-map-panel {
      position: static;
      order: -1;
    }

    #driverTripMap {
      min-height: 320px;
      height: 420px;
    }

    .driver-booking-row {
      grid-template-columns: 1fr;
    }

    .driver-trip-actions .btn {
      width: 100%;
    }
  }
</style>
