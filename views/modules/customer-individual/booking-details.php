<?php
require_once "controllers/booking.controller.php";
require_once "models/booking.model.php";

$booking = ControllerBooking::ctrGetBooking($_POST['bookingID']);
$customer = ControllerCustomer::ctrGetCustomer($booking['customerID']);

function detailDate($value) {
  if (!$value) return "-";
  $timestamp = strtotime($value);
  if (!$timestamp) return $value;
  return date("M d, Y", $timestamp) . '|' . date("h:i A", $timestamp);
}

function detailText($value, $fallback = "-") {
  $value = trim((string) $value);
  return htmlspecialchars($value !== "" ? $value : $fallback);
}

function detailStatusClass($status) {
  if ($status === "completed")  return "bg-success-subtle text-success";
  if ($status === "stopover")   return "bg-info-subtle text-info";
  if ($status === "in-transit") return "bg-primary-subtle text-primary";
  return "bg-warning-subtle text-warning";
}
?>


<style>
/* ── Page wrapper ─────────────────────────────────── */
.booking-detail-page {
  max-width: 1440px;
  margin: 0 auto;
}

/* ── Two-column layout (mirror driver-trip-layout) ── */
.booking-detail-layout {
  display: grid;
  grid-template-columns: minmax(360px, 0.8fr) minmax(420px, 1.2fr);
  align-items: stretch;
  gap: 1rem;
}

/* ── Left panel: info sections ───────────────────── */
.booking-detail-info {
  display: grid;
  gap: 1rem;
}

/* ── Card shell (mirrors driver-map-shell) ────────── */
.booking-section-card {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.5rem;
  padding: 1rem;
  background: var(--bs-body-bg);
}

.booking-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.booking-section-title {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--bs-secondary-color);
  margin: 0;
}

/* ── Detail rows ─────────────────────────────────── */
.booking-detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem 1.25rem;
}

.booking-detail-grid.single-col {
  grid-template-columns: 1fr;
}

.detail-item .detail-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--bs-secondary-color);
  margin-bottom: 2px;
}

.detail-item .detail-value {
  font-size: 0.9rem;
  color: var(--bs-body-color);
  margin: 0;
  word-break: break-word;
}

/* ── Price highlight ─────────────────────────────── */
.booking-price-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--bs-primary);
}

/* ── Address rows (mirror driver-booking-row) ─────── */
.booking-address-row {
  border-top: 1px solid var(--bs-border-color);
  padding-top: 0.75rem;
  margin-top: 0.75rem;
}

.booking-address-row:first-child {
  border-top: none;
  padding-top: 0;
  margin-top: 0;
}

/* ── Map panel (mirrors driver-map-panel) ─────────── */
.booking-map-panel {
  min-width: 0;
  height: 100%;
  display: flex;
  flex-direction: column;
}

#bookingMap {
  width: 100%;
  height: 100%;
  min-height: 0;
  border-radius: 0.5rem;
  overflow: hidden;
  
}

/* ── Back button row ─────────────────────────────── */
.booking-detail-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  border-top: 1px solid var(--bs-border-color);
  padding-top: 1rem;
  margin-top: 0.5rem;
}

.booking-map-card {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.booking-map-card #bookingMap {
  flex: 1;
  min-height: 0;
}


/* ── Responsive ──────────────────────────────────── */
@media (max-width: 767.98px) {
  .booking-detail-layout {
    grid-template-columns: 1fr;
  }

  .booking-map-panel {
    position: static;
    order: -1;
  }

  #bookingMap {
    min-height: 320px;
  }

  .booking-detail-grid {
    grid-template-columns: 1fr;
  }

  .booking-map-panel,
  .booking-map-card {
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .booking-map-card #bookingMap {
    flex: 1;
    min-height: 300px;
  }
}
</style>

<div class="booking-detail-page">

  <!-- Page card (mirrors the outer .card in doc 2) -->
  <div class="card">

    <!-- Card header -->
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Booking Details</h5>
        <p class="text-muted small mb-0">
          Full information for Booking #<?php echo detailText($booking['bookingID']); ?>
        </p>
      </div>

      <span class="badge <?php echo detailStatusClass($booking['status']); ?>">
        <?php echo detailText(ucfirst(str_replace('-', ' ', $booking['status']))); ?>
      </span>
    </div>

    <!-- Card body -->
    <div class="card-body p-4">

      <div class="booking-detail-layout">

        <!-- ── LEFT: info sections ───────────────────── -->
        <div class="booking-detail-info">

          <!-- Booking Information -->
          <div class="booking-section-card">
            <div class="booking-section-header">
              <h6 class="booking-section-title">
                <i class="ri-file-list-3-line me-1"></i>Booking Information
              </h6>
            </div>

            <div class="booking-detail-grid">
              <div class="detail-item">
                <div class="detail-label">Booking ID</div>
                <div class="detail-value">#<?php echo detailText($booking['bookingID']); ?></div>
              </div>

              <div class="detail-item">
                <div class="detail-label">Trip ID</div>
                <div class="detail-value">#<?php echo detailText($booking['tripID']); ?></div>
              </div>

              <div class="detail-item">
                <div class="detail-label">Pickup Date & Time</div>
                  <div class="detail-value">
                    <?php
                      $parts = explode('|', detailDate($booking['pickupDateTime']));
                      $date  = $parts[0] ?? '-';
                      $time  = $parts[1] ?? '';
                    ?>
                    <div><i class="ri-calendar-line me-1 text-muted"></i><?php echo htmlspecialchars($date); ?></div>
                    <?php if ($time): ?>
                      <div><i class="ri-time-line me-1 text-muted"></i><?php echo htmlspecialchars($time); ?></div>
                    <?php endif; ?>
                    <div class="mt-1">
                      <a href="javascript:void(0)" class="text-danger small" id="rescheduleBtn" 
                        data-id="<?php echo (int) $booking['bookingID']; ?>">
                        <i class="ri-calendar-schedule-line me-1"></i>Reschedule
                      </a>
                      <div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content">

                            <div class="modal-header">
                              <h5 class="modal-title" id="rescheduleModalLabel">
                                <i class="ri-calendar-schedule-line me-2 text-danger"></i>Reschedule Booking
                              </h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                              <p class="text-muted small mb-3">
                                Booking <strong id="rescheduleBookingID"></strong>
                              </p>

                              <div class="mb-3">
                                <label for="rescheduleDate" class="form-label">New Pickup Date</label>
                                <input type="date" class="form-control" id="rescheduleDate" placeholder="Select a date">
                              </div>

                              <div class="mb-3">
                                <label for="rescheduleTime" class="form-label">New Pickup Time</label>
                                <input type="time" class="form-control" id="rescheduleTime" placeholder="Select a time">
                              </div>

                              
                            </div>

                            <div class="modal-footer">
                              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                              <button type="button" class="btn btn-danger" id="confirmReschedule">
                                <i class="ri-calendar-check-line me-1"></i>Confirm Reschedule
                              </button>
                            </div>

                          </div>
                        </div>
                      </div>
                    </div>
                </div>
              </div>

              <div class="detail-item">
                <div class="detail-label">Price</div>
                <div class="detail-value booking-price-value">
                  PHP <?php echo number_format((float) $booking['price'], 2); ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Customer Information -->
          <div class="booking-section-card">
            <div class="booking-section-header">
              <h6 class="booking-section-title">
                <i class="ri-user-line me-1"></i>Customer Information
              </h6>
            </div>

            <div class="booking-detail-grid">
              <div class="detail-item">
                <div class="detail-label">Customer Name</div>
                <div class="detail-value">
                  <?php echo detailText($booking['customerFName'] . ' ' . $booking['customerLName']); ?>
                </div>
              </div>

              <?php if (!empty($customer['contactPerson'])): ?>
                <div class="detail-item">
                  <div class="detail-label">Contact Person</div>
                  <div class="detail-value"><?php echo detailText($customer['contactPerson']); ?></div>
                </div>
              <?php endif; ?>
              <?php if (!empty($customer['phoneNumber'])): ?>
                <div class="detail-item">
                  <div class="detail-label">Phone Number</div>
                  <div class="detail-value"><?php echo detailText($customer['phoneNumber']); ?></div> 
                </div>
              <?php endif; ?>
              <?php if (!empty($customer['email'])): ?>
                <div class="detail-item">
                  <div class="detail-label">Email</div>
                  <div class="detail-value"><?php echo detailText($customer['email']); ?></div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Locations -->
          <div class="booking-section-card">
            <div class="booking-section-header">
              <h6 class="booking-section-title">
                <i class="ri-road-map-line me-1"></i>Locations
              </h6>
            </div>

            <!-- Pickup -->
            <div class="booking-address-row">
              <strong class="small">
                <i class="ri-map-pin-2-line text-primary me-1"></i>Pickup Location
              </strong>

              <div class="booking-detail-grid mt-2">
                <div class="detail-item">
                  <div class="detail-label">Province</div>
                  <div class="detail-value"><?php echo detailText($booking['pickupProvince']); ?></div>
                </div>

                <div class="detail-item">
                  <div class="detail-label">City</div>
                  <div class="detail-value"><?php echo detailText($booking['pickupCity']); ?></div>
                </div>

                <div class="detail-item">
                  <div class="detail-label">Barangay</div>
                  <div class="detail-value"><?php echo detailText($booking['pickupBarangay']); ?></div>
                </div>

                <div class="detail-item">
                  <div class="detail-label">Street</div>
                  <div class="detail-value"><?php echo detailText($booking['pickupStreet']); ?></div>
                </div>

                <?php if (!empty($booking['pickupDescription'])): ?>
                  <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Description</div>
                    <div class="detail-value text-muted">
                      <?php echo detailText($booking['pickupDescription']); ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Destination -->
            <div class="booking-address-row">
              <strong class="small">
                <i class="ri-flag-line text-danger me-1"></i>Destination
              </strong>

              <div class="booking-detail-grid mt-2">
                <div class="detail-item">
                  <div class="detail-label">Province</div>
                  <div class="detail-value"><?php echo detailText($booking['destinationProvince']); ?></div>
                </div>

                <div class="detail-item">
                  <div class="detail-label">City</div>
                  <div class="detail-value"><?php echo detailText($booking['destinationCity']); ?></div>
                </div>

                <div class="detail-item">
                  <div class="detail-label">Barangay</div>
                  <div class="detail-value"><?php echo detailText($booking['destinationBarangay']); ?></div>
                </div>

                <div class="detail-item">
                  <div class="detail-label">Street</div>
                  <div class="detail-value"><?php echo detailText($booking['destinationStreet']); ?></div>
                </div>

                <?php if (!empty($booking['destinationDescription'])): ?>
                  <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Description</div>
                    <div class="detail-value text-muted">
                      <?php echo detailText($booking['destinationDescription']); ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="booking-detail-actions">
              <button class="btn btn-secondary " onclick="history.back()">
                <i class="ri-arrow-left-line me-1"></i> Back
              </button>
              <button class="btn btn-primary " id="viewReceipt" data-id="<?php echo (int) $booking["bookingID"]; ?>">
                <i class="ri-printer-line me-1"></i> View Receipt
              </button>
            </div>
          </div>
        </div>

        <!-- ── RIGHT: map panel ──────────────────────── -->
        <div class="booking-map-panel">
          <div class="booking-section-card booking-map-card">

            <div class="booking-section-header mb-3">
              <div>
                <h6 class="booking-section-title">
                  <i class="ri-road-map-line me-1"></i>Route Map
                </h6>
                <p class="text-muted small mb-0">
                  Pickup → Destination
                </p>
              </div>

              <span class="badge bg-secondary-subtle text-secondary">
                Booking #<?php echo detailText($booking['bookingID']); ?>
              </span>
            </div>

            <div id="bookingMap"></div>

          </div>
        </div>

      </div>

    </div>

  </div>

</div>

<script>
  const pickupLat     = <?php echo json_encode($booking['pickupLatitude']); ?>;
  const pickupLng     = <?php echo json_encode($booking['pickupLongitude']); ?>;
  const destinationLat = <?php echo json_encode($booking['destinationLatitude']); ?>;
  const destinationLng = <?php echo json_encode($booking['destinationLongitude']); ?>;
</script>