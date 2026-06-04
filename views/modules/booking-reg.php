<?php
require_once "controllers/booking.controller.php";
require_once "models/booking.model.php";

$isCustomerIndividual = isset($_SESSION["role"]) && $_SESSION["role"] === "customer-individual";
$sessionCustomerID    = $isCustomerIndividual ? ($_SESSION["id"] ?? "") : "";

$customers = $isCustomerIndividual ? [] : ControllerBooking::ctrCustomerList();
$trucks    = ControllerBooking::ctrTruckList();
$drivers   = ControllerBooking::ctrEmployeeListByType("driver");
$assistants = ControllerBooking::ctrEmployeeListByType("assistant");
$truckCrewMap = array();
$minimumPickupDateTime = (new DateTimeImmutable("today", new DateTimeZone("Asia/Manila")))->format("Y-m-d\T00:00");

foreach ($trucks as $truck) {
  $truckCrewMap[$truck["id"]] = ControllerBooking::ctrTruckDefaultCrew($truck["id"]);
}
?>

<script>
  window.bookingTruckCrew        = <?php echo json_encode($truckCrewMap); ?>;
  window.bookingIsCustomerIndividual = <?php echo $isCustomerIndividual ? 'true' : 'false'; ?>;
  window.bookingSessionCustomerID    = <?php echo json_encode($sessionCustomerID); ?>;
</script>

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

          <!-- ===== STEP 0: BOOKING DETAILS ===== -->
          <div class="booking-step active" data-step="0">
            <h6 class="text-uppercase text-muted mb-3">
              <i class="ri-clipboard-line me-1"></i> Booking Details
            </h6>
            <div class="row">

              <?php if ($isCustomerIndividual): ?>
                <input type="hidden" id="bookingCustomer" value="<?php echo htmlspecialchars($sessionCustomerID); ?>">
              <?php else: ?>
                <div class="col-12 col-lg-6 mb-3">
                  <label class="form-label">Customer <span class="text-danger">*</span></label>
                  <select class="form-select" id="bookingCustomer">
                    <option value="">Select customer</option>
                    <?php foreach ($customers as $customer): ?>
                      <?php
                        $customerName = "";
                        if ($isCustomerIndividual) {
                          $customerName = $_SESSION["fullname"] ?? trim(
                            ($_SESSION["fname"] ?? "") . " " .
                            ($_SESSION["MI"] ?? "") . " " .
                            ($_SESSION["lname"] ?? "")
                          );
                        } else {
                          $customerName = trim(($customer["customerFName"] ?? "") . " " . ($customer["customerLName"] ?? ""));

                          if ($customerName === "") {
                            $customerName = $customer["contactPerson"] ?? "Unknown";
                          }
                        }
                      ?>
                      <option
                        value="<?php echo htmlspecialchars($customer["id"]); ?>"
                        data-type="<?php echo htmlspecialchars($customer["customerType"]); ?>"
                        data-province="<?php echo htmlspecialchars($customer["province"] ?? ""); ?>"
                        data-city="<?php echo htmlspecialchars($customer["city"] ?? ""); ?>"
                        data-barangay="<?php echo htmlspecialchars($customer["barangay"] ?? ""); ?>"
                        data-street="<?php echo htmlspecialchars($customer["street"] ?? ""); ?>"
                        data-latitude="<?php echo htmlspecialchars($customer["latitude"] ?? ""); ?>"
                        data-longitude="<?php echo htmlspecialchars($customer["longitude"] ?? ""); ?>"
                        data-location-id="<?php echo htmlspecialchars($customer["locationID"] ?? ""); ?>"
                      >
                        <?php echo htmlspecialchars($customerName); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php endif; ?>

              <div class="col-12 col-lg-6 mb-3">
                <label class="form-label">Store / Customer Name</label>
                <input type="text" class="form-control" id="bookingStoreName" maxlength="150" placeholder="Example: ZEST-O Silay Branch">
                <div class="form-text">Optional name to show on this booking, such as a store, branch, or consignee.</div>
              </div>

              <div class="col-12 <?php echo $isCustomerIndividual ? 'col-lg-6' : 'col-lg-6'; ?> mb-3">
                <label class="form-label">Pickup Date & Time <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="bookingPickupDateTime" autocomplete="off" placeholder="Select pickup date and time" data-min-date="<?php echo htmlspecialchars($minimumPickupDateTime); ?>">
                <?php if (!$isCustomerIndividual): ?>
                  <div class="booking-calendar-legend mt-2" aria-label="Truck date availability legend">
                    <span><i class="booking-calendar-key booking-calendar-key-unavailable"></i> Booked / unavailable</span>
                  </div>
                  <div class="form-text" id="bookingCalendarStatus">Select a truck to view its date availability.</div>
                <?php else: ?>
                  <div class="form-text">Select today or an upcoming date.</div>
                <?php endif; ?>
              </div>

              <?php if (!$isCustomerIndividual): ?>
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
                      <option value="<?php echo htmlspecialchars($truck["id"]); ?>" data-type="<?php echo htmlspecialchars($truck["type"]); ?>">
                        <?php echo htmlspecialchars($truck["plateNumber"] . " - " . $truck["brand"] . " " . $truck["type"]); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="form-text" id="bookingTruckAvailability">Select a pickup date and truck to check availability.</div>
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
                <div class="col-12">
                  <div class="row g-3">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Driver Salary</label>
                      <div class="input-group">
                        <span class="input-group-text">PHP</span>
                        <input type="number" class="form-control" id="bookingCrewSalary" min="0" step="0.01" placeholder="Driver salary">
                      </div>
                      <div class="form-text">Assistants automatically receive PHP 100 less.</div>
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Crew Allowance</label>
                      <div class="input-group">
                        <span class="input-group-text">PHP</span>
                        <input type="number" class="form-control" id="bookingCrewAllowance" min="0" step="0.01" placeholder="Allowance per crew member">
                      </div>
                      <div class="form-text">Added on top of the crew salary.</div>
                    </div>
                  </div>
                </div>
              <?php else: ?>
                <input type="hidden" id="bookingTruck" value=0>
                <input type="hidden" id="bookingDriver" value=0>
              <?php endif; ?>

            </div>
          </div>

          <!-- ===== STEP 1: CARGO ===== -->
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
                <div class="row g-2 align-items-end booking-cargo-item-row">
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

          <!-- ===== STEP 2: LOCATIONS ===== -->
          <div class="booking-step" data-step="2">
            <div class="row g-4">

              <!-- Left column: address fields -->
              <div class="col-12 <?php echo $isCustomerIndividual ? 'col-xl-5' : 'col-xl-5'; ?>">

                <!-- Pickup -->
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

                <!-- Destination -->
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

                <?php if (!$isCustomerIndividual): ?>
                  <h6 class="text-uppercase text-muted mb-3">
                    <i class="ri-money-dollar-circle-line me-1"></i> Pricing
                  </h6>
                  <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                      <label class="form-label">Fuel Pump Price</label>
                      <div class="form-icon">
                        <i class="ri-gas-station-line text-muted"></i>
                        <input type="number" class="form-control form-control-icon" id="bookingFuelPrice" min="0" step="0.01" placeholder="60.00">
                      </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                      <label class="form-label">Price <span class="text-danger">*</span></label>
                      <div class="form-icon">
                        <i class="ri-money-dollar-circle-line text-muted"></i>
                        <input type="number" class="form-control form-control-icon" id="bookingPrice" min="0" step="0.01" placeholder="0.00">
                      </div>
                      <div class="form-text" id="bookingTariffHint">Select company, truck, and destination to use tariff pricing.</div>
                    </div>
                  </div>
                <?php else: ?>
                  <input type="hidden" id="bookingFuelPrice" value="0">
                  <input type="hidden" id="bookingPrice" value="0">
                <?php endif; ?>

              </div>

              <!-- Right column: map -->
              <div class="col-12 <?php echo $isCustomerIndividual ? 'col-xl-7' : 'col-xl-7'; ?>">
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

                  <!-- Pickup search with suggestions -->
                  <div class="booking-map-search-wrap mb-2">
                    <div class="input-group">
                      <span class="input-group-text"><i class="ri-map-pin-2-line text-primary"></i></span>
                      <input type="text" class="form-control" id="pickupMapSearch" placeholder="Search pickup location…">
                      <button type="button" class="btn btn-outline-primary" id="pickupMapSearchBtn">Search</button>
                    </div>
                    <div id="pickupMapSuggestions" class="location-suggestions-box"></div>
                  </div>

                  <!-- Destination search with suggestions -->
                  <div class="booking-map-search-wrap mb-3">
                    <div class="input-group">
                      <span class="input-group-text"><i class="ri-flag-line text-danger"></i></span>
                      <input type="text" class="form-control" id="destinationMapSearch" placeholder="Search destination location…">
                      <button type="button" class="btn btn-outline-danger" id="destinationMapSearchBtn">Search</button>
                    </div>
                    <div id="destinationMapSuggestions" class="location-suggestions-box"></div>
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

          <!-- ===== STEP 3: REVIEW ===== -->
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
                  <span>Store / Customer Name</span>
                  <strong id="reviewStoreName">-</strong>
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="booking-review-box">
                  <span>Trip / Pickup Schedule</span>
                  <strong id="reviewTripSchedule">-</strong>
                </div>
              </div>
              <?php if (!$isCustomerIndividual): ?>
                <div class="col-12 col-lg-6">
                  <div class="booking-review-box">
                    <span>Truck / Crew</span>
                    <strong id="reviewCrew">-</strong>
                  </div>
                </div>
              <?php endif; ?>
              <div class="col-12 col-lg-6">
                <div class="booking-review-box">
                  <span>Cargo</span>
                  <strong id="reviewCargo">-</strong>
                </div>
              </div>
              <?php if (!$isCustomerIndividual): ?>
                <div class="col-12 col-lg-6">
                  <div class="booking-review-box">
                    <span>Price</span>
                    <strong id="reviewPrice">-</strong>
                  </div>
                </div>
              <?php endif; ?>
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

  /* ── Location search suggestions ── */
  .booking-calendar-legend {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    color: var(--bs-secondary-color);
    font-size: 0.76rem;
  }
  .booking-calendar-legend span {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.28rem 0.62rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 999px;
    background: color-mix(in srgb, var(--bs-body-bg) 88%, transparent);
    line-height: 1;
  }
  .booking-calendar-key {
    width: 0.48rem;
    height: 0.48rem;
    border-radius: 999px;
    display: inline-block;
  }
  .booking-calendar-key-unavailable {
    background: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
  }

  .air-datepicker {
    --adp-width: 328px;
    --adp-padding: 12px;
    --adp-border-radius: 18px;
    --adp-cell-border-radius: 12px;
    --adp-day-cell-height: 40px;
    --adp-nav-height: 40px;
    --adp-nav-action-size: 36px;
    --adp-background-color: var(--bs-body-bg);
    --adp-background-color-hover: color-mix(in srgb, var(--bs-primary) 8%, var(--bs-body-bg));
    --adp-background-color-active: color-mix(in srgb, var(--bs-primary) 12%, var(--bs-body-bg));
    --adp-border-color: color-mix(in srgb, var(--bs-border-color) 82%, transparent);
    --adp-border-color-inner: color-mix(in srgb, var(--bs-border-color) 70%, transparent);
    --adp-color: var(--bs-body-color);
    --adp-color-secondary: var(--bs-secondary-color);
    --adp-color-disabled: color-mix(in srgb, var(--bs-secondary-color) 62%, transparent);
    --adp-day-name-color: var(--bs-secondary-color);
    --adp-accent-color: #6366f1;
    --adp-cell-background-color-selected: #6366f1;
    --adp-cell-background-color-selected-hover: #5457e8;
    border: 1px solid color-mix(in srgb, var(--bs-border-color) 78%, transparent);
    border-radius: 20px;
    box-shadow: 0 22px 60px rgba(15, 23, 42, 0.18);
    overflow: hidden;
  }
  .air-datepicker--pointer {
    display: none;
  }
  .air-datepicker-nav {
    border-bottom: 0;
    padding: 12px 12px 4px;
  }
  .air-datepicker-nav--title {
    border-radius: 999px;
    font-weight: 700;
    letter-spacing: -0.01em;
  }
  .air-datepicker-nav--action {
    border-radius: 999px;
  }
  .air-datepicker--content {
    padding: 6px 14px 12px;
  }
  .air-datepicker-body--day-names {
    margin: 6px 0 8px;
  }
  .air-datepicker-body--day-name {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.08em;
  }
  .air-datepicker-cell {
    font-weight: 650;
    transition: background 160ms ease, color 160ms ease, transform 160ms ease;
  }
  .air-datepicker-cell.-focus- {
    transform: translateY(-1px);
  }
  .air-datepicker-cell.-booking-unavailable-,
  .air-datepicker-cell.-booking-unavailable-.-disabled- {
    color: #dc2626;
    background: rgba(239, 68, 68, 0.07);
    opacity: 0.9;
  }
  .air-datepicker-cell.-booking-unavailable-.-focus- {
    background: rgba(239, 68, 68, 0.12);
  }
  .booking-calendar-day {
    position: relative;
    width: 100%;
    height: 100%;
    display: grid;
    place-items: center;
  }
  .booking-calendar-day::after {
    content: "";
    position: absolute;
    left: 50%;
    bottom: 4px;
    width: 4px;
    height: 4px;
    border-radius: 999px;
    transform: translateX(-50%);
  }
  .booking-calendar-day--unavailable::after {
    background: #ef4444;
  }
  .air-datepicker-cell.-selected- .booking-calendar-day::after {
    background: #fff;
  }
  .air-datepicker--time,
  .air-datepicker--buttons {
    border-top: 1px solid color-mix(in srgb, var(--bs-border-color) 65%, transparent);
  }
  .air-datepicker-time {
    padding: 12px 16px;
  }
  .air-datepicker-button {
    border-radius: 12px;
    font-weight: 700;
  }
  [data-bs-theme="dark"] .air-datepicker {
    --adp-background-color: #151827;
    --adp-background-color-hover: rgba(99, 102, 241, 0.12);
    --adp-background-color-active: rgba(99, 102, 241, 0.16);
    --adp-border-color: rgba(255, 255, 255, 0.09);
    --adp-border-color-inner: rgba(255, 255, 255, 0.08);
    --adp-color: #eef2ff;
    --adp-color-secondary: rgba(226, 232, 240, 0.68);
    --adp-color-disabled: rgba(226, 232, 240, 0.34);
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.42);
  }
  [data-bs-theme="dark"] .air-datepicker-cell.-booking-unavailable-,
  [data-bs-theme="dark"] .air-datepicker-cell.-booking-unavailable-.-disabled- {
    color: #fca5a5;
    background: rgba(248, 113, 113, 0.11);
  }

  .booking-map-search-wrap {
    position: relative;
  }
  .location-suggestions-box {
    background-color: #fff;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    border: 1px solid var(--bs-border-color);
    border-top: none;
    border-radius: 0 0 0.375rem 0.375rem;
    max-height: 220px;
    overflow-y: auto;
    display: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  [data-bs-theme="dark"] .location-suggestions-box {
    background-color: #2b2c40;
  }

  .location-suggestion-item {
    background-color: inherit;
    background: var(--bs-body-bg);
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    font-size: 0.875rem;
    border-bottom: 1px solid var(--bs-border-color);
  }
  .location-suggestion-item:last-child {
    border-bottom: none;
  }
  .location-suggestion-item:hover {
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary);
  }

  @media (max-width: 1199.98px) {
    .booking-map-panel { position: static; }
    #bookingMap { min-height: 460px; }
  }
  @media (max-width: 767.98px) {
    .booking-step-nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }
  @media (max-width: 575.98px) {
    .booking-page .card-header { align-items: flex-start !important; }
    .booking-title { width: 100%; min-width: 0; }
    .booking-title h5 { font-size: 1rem; line-height: 1.25; overflow-wrap: anywhere; }
    .booking-title p { max-width: 100%; line-height: 1.35; }
    .booking-badge { font-size: 0.75rem !important; white-space: normal; line-height: 1.25; }
    .booking-step-nav { grid-template-columns: 1fr; }
    #bookingMap { min-height: 340px; }
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
    .booking-review-box { padding: 0.65rem 0.75rem; }
  }
</style>
