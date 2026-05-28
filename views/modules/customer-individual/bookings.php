<?php
require_once "controllers/booking.controller.php";
require_once "models/booking.model.php";

$customerID = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
$bookingList = ControllerBooking::ctrCustomerBookingList($customerID);

function bookingDate($value) {
  if (!$value) {
    return "-";
  }

  $timestamp = strtotime($value);
  return $timestamp ? date("M d, Y h:i A", $timestamp) : $value;
}

function bookingText($value, $fallback = "-") {
  $value = trim((string) $value);
  return htmlspecialchars($value !== "" ? $value : $fallback);
}

function bookingStatusClass($status) {

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

function bookingStatusLabel($status) {
  return ucfirst(str_replace("-", " ", $status));
}
?>

<div class="driver-trips-page">

  <div class="card">

    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">My Bookings</h5>
        <p class="text-muted small mb-0">
          Track your booking deliveries and destinations
        </p>
      </div>

      <button
        type="button"
        class="btn btn-primary"
        id="bookingAddBtn"
      >
        <i class="ri-add-line me-1"></i> Add Booking
      </button>
    </div>

    <div class="card-body p-4">

      <?php if (empty($bookingList)): ?>

        <div class="driver-empty">
          No bookings found.
        </div>

      <?php else: ?>

        <div class="driver-trip-layout">

          <!-- BOOKING LIST -->
          <section class="driver-trip-list">

            <?php foreach ($bookingList as $booking): ?>

              <article
                class="driver-trip-card"
                data-booking-id="<?php echo (int) $booking["bookingID"]; ?>"
              >

                <div class="driver-trip-header">

                  <div>
                    <h6 class="mb-1">
                      Booking #<?php echo (int) $booking["bookingID"]; ?>
                    </h6>

                    <div class="text-muted small">

                      <div>
                        <i class="ri-road-map-line me-1"></i>
                        Trip #<?php echo (int) $booking["tripID"]; ?>
                      </div>

                      <div class="mt-1">
                        <i class="ri-calendar-line me-1"></i>
                        <?php echo bookingText(bookingDate($booking["pickupDateTime"])); ?>
                      </div>

                    </div>
                  </div>

                  <span class="badge <?php echo bookingStatusClass($booking["status"]); ?>">
                    <?php echo bookingText(bookingStatusLabel($booking["status"])); ?>
                  </span>

                </div>

                <div class="driver-booking-list">

                  <div class="driver-booking-row">

                    <div>
                      <strong>Pickup Location</strong>

                      <div class="small text-muted mt-1">
                        <i class="ri-map-pin-2-line text-primary me-1"></i>
                        <?php echo bookingText($booking["pickupAddress"]); ?>
                      </div>

                      <?php if (!empty($booking["pickupDescription"])): ?>

                        <div class="small text-muted ms-4 mt-1">
                          <?php echo bookingText($booking["pickupDescription"]); ?>
                        </div>

                      <?php endif; ?>
                    </div>

                  </div>

                  <div class="driver-booking-row">

                    <div>
                      <strong>Destination</strong>

                      <div class="small text-muted mt-1">
                        <i class="ri-flag-line text-danger me-1"></i>
                        <?php echo bookingText($booking["destinationAddress"]); ?>
                      </div>

                      <?php if (!empty($booking["destinationDescription"])): ?>

                        <div class="small text-muted ms-4 mt-1">
                          <?php echo bookingText($booking["destinationDescription"]); ?>
                        </div>

                      <?php endif; ?>
                    </div>

                  </div>

                </div>

                <div class="driver-trip-actions">

                  <div class="fw-semibold text-primary">
                    PHP <?php echo number_format((float) $booking["price"], 2); ?>
                  </div>

                  <button
                    type="button"
                    class="btn btn-light customer-map-focus viewDetails"
                    data-id="<?php echo (int) $booking["bookingID"]; ?>"
                  >
                    <i class="ri-road-map-line me-1" ></i>
                    View Details
                  </button>

                </div>

              </article>

            <?php endforeach; ?>

          </section>

          <!-- MAP -->
          <section class="driver-map-panel">

            <div class="driver-map-shell">

              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">

                <div>
                  <h6 class="text-uppercase text-muted mb-1">
                    <i class="ri-road-map-line me-1"></i>
                    Booking Destination Map
                  </h6>

                  <p class="text-muted small mb-0" id="driverMapStatus">
                    Select a booking to view the destination.
                  </p>
                </div>

                <span class="badge bg-secondary-subtle text-secondary" id="driverMapBadge">
                  No booking selected
                </span>

              </div>

              <div id="customerMap"></div>

            </div>

          </section>

        </div>

      <?php endif; ?>

    </div>

  </div>

</div>

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

#customerMap {
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
  border-top: 1px solid var(--bs-border-color);
  padding-top: 0.75rem;
}

.driver-trip-actions {
  justify-content: space-between;
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

@media (max-width: 767.98px) {

  .driver-trip-layout {
    grid-template-columns: 1fr;
  }

  .driver-map-panel {
    position: static;
    order: -1;
  }

  #customerMap {
    min-height: 320px;
    height: 420px;
  }

  .driver-trip-actions .btn {
    width: 100%;
  }

}
</style>

<script>
  window.customerBookingData = <?php echo json_encode(
    $bookingList,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
  ); ?>;

  console.log("🟢 PHP injected customerBookingData:", window.customerBookingData);
</script>