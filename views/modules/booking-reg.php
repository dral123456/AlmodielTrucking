<?php
require_once "controllers/booking.controller.php";
require_once "models/booking.model.php";

$customers = ControllerBooking::ctrCustomerList();
$trucks = ControllerBooking::ctrTruckList();
$drivers = ControllerBooking::ctrEmployeeListByType("driver");
$assistants = ControllerBooking::ctrEmployeeListByType("assistant");
$truckCrewMap = array();

foreach ($trucks as $truck) {
  $truckCrewMap[$truck["id"]] = ControllerBooking::ctrTruckDefaultCrew($truck["id"]);
}
?>

<div class="row justify-content-center booking-page">
  <div class="col-12 col-xxl-10">
    <div class="card">
      <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="booking-title">
          <h5 class="mb-0">Booking Registration</h5>
          <p class="text-muted small mb-0">Create a delivery booking step by step.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary fs-6 booking-badge">
          <i class="ri-route-line me-1"></i> Stepper
        </span>
      </div>

      <div class="card-body p-4">
        <div class="booking-stepper">
          <div class="booking-step-nav mb-4">
            <button type="button" class="booking-step-pill active" data-step="0">
              <span>1</span>
              <strong>Booking</strong>
            </button>
            <button type="button" class="booking-step-pill" data-step="1">
              <span>2</span>
              <strong>Cargo</strong>
            </button>
            <button type="button" class="booking-step-pill" data-step="2">
              <span>3</span>
              <strong>Locations</strong>
            </button>
            <button type="button" class="booking-step-pill" data-step="3">
              <span>4</span>
              <strong>Review</strong>
            </button>
          </div>

          <div class="progress booking-step-progress mb-4">
            <div class="progress-bar" id="bookingStepProgress" role="progressbar" style="width: 0%"></div>
          </div>

          <div class="booking-step active" data-step="0">
            <h6 class="text-uppercase text-muted mb-3">
              <i class="ri-clipboard-line me-1"></i> Booking Details
            </h6>
            <div class="row">
              <div class="col-12 col-lg-6 mb-3">
                <label class="form-label">Customer <span class="text-danger">*</span></label>
                <select class="form-select" id="bookingCustomer">
                  <option value="">Select customer</option>
                  <?php foreach ($customers as $customer): ?>
                    <?php
                      $customerName = trim($customer["customerFName"] . " " . $customer["customerLName"]);
                      if ($customerName === "") {
                        $customerName = $customer["contactPerson"];
                      }
                    ?>
                    <option
                      value="<?php echo htmlspecialchars($customer["id"]); ?>"
                      data-type="<?php echo htmlspecialchars($customer["customerType"]); ?>"
                      data-province="<?php echo htmlspecialchars($customer["province"] ?? ""); ?>"
                      data-city="<?php echo htmlspecialchars($customer["city"] ?? ""); ?>"
                      data-barangay="<?php echo htmlspecialchars($customer["barangay"] ?? ""); ?>"
                      data-street="<?php echo htmlspecialchars($customer["street"] ?? ""); ?>"
                      data-house="<?php echo htmlspecialchars($customer["houseNumber"] ?? ""); ?>"
                      data-latitude="<?php echo htmlspecialchars($customer["warehouseLatitude"] ?? ""); ?>"
                      data-longitude="<?php echo htmlspecialchars($customer["warehouseLongitude"] ?? ""); ?>"
                    >
                      <?php echo htmlspecialchars($customerName); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-6 col-lg-3 mb-3">
                <label class="form-label">Trip ID</label>
                <div class="form-control bg-light text-muted" id="bookingTripDisplay">
                  Generated on save
                </div>
              </div>
              <div class="col-12 col-md-6 col-lg-3 mb-3">
                <label class="form-label">Price <span class="text-danger">*</span></label>
                <div class="form-icon">
                  <i class="ri-money-dollar-circle-line text-muted"></i>
                  <input type="number" class="form-control form-control-icon" id="bookingPrice" min="0" step="0.01" placeholder="0.00">
                </div>
              </div>
              <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Pickup Date & Time <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" id="bookingPickupDateTime">
              </div>
              <div class="col-12">
                <hr class="my-3">
                <h6 class="text-uppercase text-muted mb-3">
                  <i class="ri-team-line me-1"></i> Trip Crew Assignment
                </h6>
              </div>
              <div class="col-12 col-lg-6 mb-3">
                <label class="form-label">Truck <span class="text-danger">*</span></label>
                <select class="form-select" id="bookingTruck">
                  <option value="">Select truck</option>
                  <?php foreach ($trucks as $truck): ?>
                    <option value="<?php echo htmlspecialchars($truck["id"]); ?>">
                      <?php echo htmlspecialchars($truck["plateNumber"] . " - " . $truck["brand"] . " " . $truck["type"]); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-lg-6 mb-3">
                <label class="form-label">Driver <span class="text-danger">*</span></label>
                <select class="form-select" id="bookingDriver">
                  <option value="">Select driver</option>
                  <?php foreach ($drivers as $driver): ?>
                    <option value="<?php echo htmlspecialchars($driver["id"]); ?>">
                      <?php echo htmlspecialchars(trim($driver["empFName"] . " " . $driver["empLName"])); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                  <label class="form-label mb-0">Assistants <span class="text-danger">*</span></label>
                  <button class="btn btn-sm btn-outline-primary" type="button" id="bookingAddAssistant">
                    <i class="ri-user-add-line me-1"></i> Add Assistant
                  </button>
                </div>
                <div class="row" id="bookingAssistantList">
                  <div class="col-12 col-md-6 mb-3 booking-assistant-item">
                    <select class="form-select booking-assistant" data-default-slot="0">
                      <option value="">Select assistant</option>
                      <?php foreach ($assistants as $assistant): ?>
                        <option value="<?php echo htmlspecialchars($assistant["id"]); ?>">
                          <?php echo htmlspecialchars(trim($assistant["empFName"] . " " . $assistant["empLName"])); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12 col-md-6 mb-3 booking-assistant-item">
                    <select class="form-select booking-assistant" data-default-slot="1">
                      <option value="">Select assistant</option>
                      <?php foreach ($assistants as $assistant): ?>
                        <option value="<?php echo htmlspecialchars($assistant["id"]); ?>">
                          <?php echo htmlspecialchars(trim($assistant["empFName"] . " " . $assistant["empLName"])); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="booking-step" data-step="1">
            <h6 class="text-uppercase text-muted mb-3">
              <i class="ri-box-3-line me-1"></i> Cargo Details
            </h6>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
              <label class="form-label mb-0">Cargo Items <span class="text-danger">*</span></label>
              <button class="btn btn-sm btn-outline-primary" type="button" id="bookingAddCargo">
                <i class="ri-add-line me-1"></i> Add Cargo
              </button>
            </div>
            <div id="bookingCargoList" class="booking-cargo-list">
              <div class="booking-cargo-item">
                <div class="row g-2 align-items-end">
                  <div class="col-12 col-md-7">
                    <label class="form-label">Cargo Type <span class="text-danger">*</span></label>
                    <input type="text" class="form-control cargo-type" maxlength="100" placeholder="e.g. Construction materials">
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" class="form-control cargo-quantity" min="1" step="1" placeholder="Quantity">
                  </div>
                  <div class="col-12 col-md-1 d-grid">
                    <button class="btn btn-outline-danger booking-remove-cargo" type="button" aria-label="Remove cargo" disabled>
                      <i class="ri-close-line"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-12 mb-3">
                <label class="form-label">Condition</label>
                <input type="text" class="form-control" id="cargoCondition" maxlength="100" placeholder="e.g. Fragile, sealed, dry">
              </div>
              <div class="col-12 col-lg-6 mb-3">
                <label class="form-label">Cargo Description</label>
                <textarea class="form-control" id="cargoDescription" rows="4" placeholder="Describe the cargo"></textarea>
              </div>
              <div class="col-12 col-lg-6 mb-3">
                <label class="form-label">Special Handling</label>
                <textarea class="form-control" id="cargoSpecialHandling" rows="4" placeholder="Special handling instructions"></textarea>
              </div>
            </div>
          </div>

          <div class="booking-step" data-step="2">
            <div class="row g-4">
              <div class="col-12 col-xl-5">
                <h6 class="text-uppercase text-muted mb-3">
                  <i class="ri-map-pin-2-line me-1"></i> Pickup Location
                </h6>
                <div class="row">
                  <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Province <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="pickupProvince" placeholder="Province">
                  </div>
                  <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">City <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="pickupCity" placeholder="City">
                  </div>
                  <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Barangay <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="pickupBarangay" placeholder="Barangay">
                  </div>
                  <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Street <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="pickupStreet" placeholder="Street">
                  </div>
                  <div class="col-12 mb-4">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="pickupDescription" rows="2" placeholder="Landmark or notes"></textarea>
                  </div>
                  <input type="hidden" id="pickupLatitude">
                  <input type="hidden" id="pickupLongitude">
                </div>

                <h6 class="text-uppercase text-muted mb-3">
                  <i class="ri-flag-line me-1"></i> Destination Location
                </h6>
                <div class="row">
                  <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Province <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="destinationProvince" placeholder="Province">
                  </div>
                  <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">City <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="destinationCity" placeholder="City">
                  </div>
                  <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Barangay <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="destinationBarangay" placeholder="Barangay">
                  </div>
                  <div class="col-12 col-md-6 mb-3">
                    <label class="form-label">Street <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="destinationStreet" placeholder="Street">
                  </div>
                  <div class="col-12 mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="destinationDescription" rows="2" placeholder="Landmark or notes"></textarea>
                  </div>
                  <input type="hidden" id="destinationLatitude">
                  <input type="hidden" id="destinationLongitude">
                </div>
              </div>

              <div class="col-12 col-xl-7">
                <div class="booking-map-panel">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                      <h6 class="text-uppercase text-muted mb-1">
                        <i class="ri-road-map-line me-1"></i> Map Pinning
                      </h6>
                      <p class="text-muted small mb-0" id="bookingMapStatus">Click the map to place the pickup pin.</p>
                    </div>
                    <div class="btn-group" role="group" aria-label="Map pin mode">
                      <input type="radio" class="btn-check" name="bookingMapMode" id="mapModePickup" value="pickup" autocomplete="off" checked>
                      <label class="btn btn-outline-primary" for="mapModePickup">
                        <i class="ri-map-pin-2-line me-1"></i> Pickup
                      </label>
                      <input type="radio" class="btn-check" name="bookingMapMode" id="mapModeDestination" value="destination" autocomplete="off">
                      <label class="btn btn-outline-primary" for="mapModeDestination">
                        <i class="ri-flag-line me-1"></i> Destination
                      </label>
                    </div>
                  </div>

                  <div id="bookingMap"></div>

                  <div class="row mt-3">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                      <div class="booking-coordinates">
                        <span class="text-muted small d-block">Pickup Coordinates</span>
                        <strong id="pickupCoordinateText">Not pinned</strong>
                      </div>
                    </div>
                    <div class="col-12 col-md-6">
                      <div class="booking-coordinates">
                        <span class="text-muted small d-block">Destination Coordinates</span>
                        <strong id="destinationCoordinateText">Not pinned</strong>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="booking-step" data-step="3">
            <h6 class="text-uppercase text-muted mb-3">
              <i class="ri-check-double-line me-1"></i> Review Booking
            </h6>
            <div class="row g-3" id="bookingReview">
              <div class="col-12 col-lg-6">
                <div class="booking-review-box">
                  <span>Customer</span>
                  <strong id="reviewCustomer">-</strong>
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="booking-review-box">
                  <span>Trip / Pickup Schedule</span>
                  <strong id="reviewTripSchedule">-</strong>
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="booking-review-box">
                  <span>Truck / Crew</span>
                  <strong id="reviewCrew">-</strong>
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="booking-review-box">
                  <span>Cargo</span>
                  <strong id="reviewCargo">-</strong>
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="booking-review-box">
                  <span>Price</span>
                  <strong id="reviewPrice">-</strong>
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="booking-review-box">
                  <span>Pickup</span>
                  <strong id="reviewPickup">-</strong>
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="booking-review-box">
                  <span>Destination</span>
                  <strong id="reviewDestination">-</strong>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex justify-content-between gap-2 flex-wrap">
            <button class="btn btn-light" type="button" id="bookingBtnReset">
              <i class="ri-refresh-line me-1"></i> Reset
            </button>
            <div class="d-flex gap-2 ms-auto">
              <button class="btn btn-outline-secondary" type="button" id="bookingBtnPrev">
                <i class="ri-arrow-left-line me-1"></i> Back
              </button>
              <button class="btn btn-primary px-4" type="button" id="bookingBtnNext">
                Next <i class="ri-arrow-right-line ms-1"></i>
              </button>
              <button class="btn btn-primary px-4 d-none" type="button" id="bookingBtnRegister">
                <i class="ri-save-line me-1"></i> Save Booking
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  window.bookingTruckCrew = <?php echo json_encode($truckCrewMap); ?>;
</script>

<style>
  .booking-step {
    display: none;
  }

  .booking-step.active {
    display: block;
  }

  .booking-step-nav {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
  }

  .booking-step-pill {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    background: var(--bs-body-bg);
    padding: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.625rem;
    color: var(--bs-body-color);
    text-align: left;
  }

  .booking-step-pill span {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--bs-secondary-bg);
    color: var(--bs-secondary-color);
    flex-shrink: 0;
    font-weight: 700;
  }

  .booking-step-pill.active {
    border-color: var(--bs-primary);
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary);
  }

  .booking-step-pill.active span,
  .booking-step-pill.complete span {
    background: var(--bs-primary);
    color: #fff;
  }

  .booking-step-pill.complete {
    border-color: var(--bs-primary);
  }

  .booking-cargo-list {
    display: grid;
    gap: 0.75rem;
  }

  .booking-cargo-item {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 0.75rem;
    background: var(--bs-body-bg);
  }

  #bookingMap {
    width: 100%;
    min-height: 520px;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .booking-map-panel {
    position: sticky;
    top: 90px;
  }

  .booking-coordinates,
  .booking-review-box {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    min-height: 64px;
    background: var(--bs-body-bg);
  }

  .booking-review-box span {
    display: block;
    color: var(--bs-secondary-color);
    font-size: 0.8125rem;
    margin-bottom: 0.25rem;
  }

  @media (max-width: 1199.98px) {
    .booking-map-panel {
      position: static;
    }

    #bookingMap {
      min-height: 460px;
    }
  }

  @media (max-width: 767.98px) {
    .booking-step-nav {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 575.98px) {
    .booking-page .card-header {
      align-items: flex-start !important;
    }

    .booking-title {
      width: 100%;
      min-width: 0;
    }

    .booking-title h5 {
      font-size: 1rem;
      line-height: 1.25;
      overflow-wrap: anywhere;
    }

    .booking-title p {
      max-width: 100%;
      line-height: 1.35;
    }

    .booking-badge {
      font-size: 0.75rem !important;
      white-space: normal;
      line-height: 1.25;
    }

    .booking-step-nav {
      grid-template-columns: 1fr;
    }

    #bookingMap {
      min-height: 340px;
    }

    .booking-map-panel .btn-group {
      display: grid;
      grid-template-columns: 1fr 1fr;
      width: 100%;
    }

    .booking-map-panel .btn-group .btn {
      width: 100%;
      padding-left: 0.5rem;
      padding-right: 0.5rem;
    }

    .booking-coordinates,
    .booking-review-box {
      padding: 0.65rem 0.75rem;
    }
  }
</style>
