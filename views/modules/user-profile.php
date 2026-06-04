<?php
$role = $_SESSION["role"] ?? '';
$id   = $_SESSION["id"] ?? null;

$isCustomerIndividual = $role === 'customer-individual';
$isCustomerCompany    = $role === 'customer-company';
$isDriver             = $role === 'driver';
$isAssistant          = $role === 'assistant';
$isAdmin              = $role === 'admin';

$user     = null;
$location = null;

if ($isCustomerIndividual || $isCustomerCompany) {
    require_once "models/customer.model.php";
    $user = ControllerCustomer::ctrGetCustomer($id);

    if (!empty($user["locationID"])) {
        $pdo  = (new Connection)->connect();
        $stmt = $pdo->prepare("SELECT * FROM location WHERE locationID = :locationID LIMIT 1");
        $stmt->bindValue(":locationID", (int)$user["locationID"], PDO::PARAM_INT);
        $stmt->execute();
        $location = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} else {
    require_once "models/employee.model.php";
    $user = ControllerEmployee::ctrGetEmployee($id);
}

if (!$user) {
    echo "<div class='alert alert-danger'>Unable to load profile.</div>";
    return;
}

// ── Display values ────────────────────────────────────────────
$displayName = ($isCustomerIndividual || $isCustomerCompany)
    ? trim($user["customerFName"] . " " . ($user["customerMI"] ?? '') . " " . $user["customerLName"])
    : trim($user["empFName"] . " " . ($user["empMI"] ?? '') . " " . $user["empLName"]);

$roleLabel = match($role) {
    'customer-individual' => 'Individual Customer',
    'customer-company'    => 'Company Customer',
    'driver'              => 'Driver',
    'assistant'           => 'Assistant',
    'admin'               => 'Administrator',
    default               => ucfirst($role),
};

$email = $isCustomerIndividual || $isCustomerCompany
    ? ($user["email"] ?? '')
    : ($user["empEmail"] ?? '');

$phone = $isCustomerIndividual || $isCustomerCompany
    ? ($user["phoneNumber"] ?? '')
    : ($user["empPhoneNumber"] ?? '');

$address = null;
if ($location) {
    $address = implode(", ", array_filter([
        $location["street"]   ?? null,
        $location["barangay"] ?? null,
        $location["city"]     ?? null,
        $location["province"] ?? null,
    ])) ?: null;
}

$roleBadgeClass = match($role) {
    'admin'               => 'bg-danger-subtle text-danger',
    'driver'              => 'bg-primary-subtle text-primary',
    'assistant'           => 'bg-info-subtle text-info',
    'customer-company'    => 'bg-warning-subtle text-warning',
    'customer-individual' => 'bg-success-subtle text-success',
    default               => 'bg-secondary-subtle text-secondary',
};

$roleIcon = match($role) {
    'admin'               => 'ri-shield-user-line',
    'driver'              => 'ri-steering-2-line',
    'assistant'           => 'ri-user-2-line',
    'customer-company'    => 'ri-building-line',
    'customer-individual' => 'ri-user-line',
    default               => 'ri-account-circle-line',
};

// ── Booking list ──────────────────────────────────────────────
// ── Booking list ──────────────────────────────────────────────
$bookingList  = [];
$tripList     = [];
$showTripList = false;
require_once "controllers/booking.controller.php";

if ($isCustomerIndividual || $isCustomerCompany) {
  $bookingList = ControllerBooking::ctrCustomerBookingList((int)$id);

} elseif ($isDriver) {
  $tripList     = ControllerBooking::ctrEmployeeTripList((int)$id, 'driver');
  $showTripList = true;
  
} elseif ($isAssistant) {
  $tripList     = ControllerBooking::ctrEmployeeTripList((int)$id, 'assistant');
  $showTripList = true;
}

// ── Helpers ───────────────────────────────────────────────────
function profileDate($value) {
    if (!$value) return "—";
    $ts = strtotime($value);
    return $ts ? date("M d, Y h:i A", $ts) : $value;
}
function profileText($value, $fallback = "—") {
    $value = trim((string) $value);
    return htmlspecialchars($value !== "" ? $value : $fallback);
}
function profileStatusClass($status) {
    return match($status) {
        'completed'  => 'bg-success-subtle text-success',
        'stopover'   => 'bg-info-subtle text-info',
        'in-transit' => 'bg-primary-subtle text-primary',
        default      => 'bg-warning-subtle text-warning',
    };
}
?>

<style>
.profile-page { max-width: 1200px; margin: 0 auto; }

/* ── Cover ── */
.profile-cover {
  height: 180px;
  border-radius: 0.75rem 0.75rem 0 0;
  background:
    linear-gradient(135deg, rgba(105,108,255,0.55) 0%, rgba(105,108,255,0.15) 60%, transparent 100%),
    url('views/assets/images/background.avif') center 60% / cover no-repeat;
  position: relative;
  overflow: hidden;
}
.profile-cover::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
  background-size: 40px 40px;
}

/* ── Avatar ── */
.profile-avatar-wrap {
  position: relative;
  display: inline-block;
  margin-top: -44px;
}
.profile-avatar {
  width: 88px; height: 88px;
  border-radius: 50%;
  border: 4px solid var(--bs-card-bg, #fff);
  object-fit: cover;
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}
.profile-avatar-status {
  position: absolute;
  bottom: 6px; right: 6px;
  width: 13px; height: 13px;
  border-radius: 50%;
  background: #28a745;
  border: 2px solid var(--bs-card-bg, #fff);
}

/* ── Layout ── */
.profile-layout {
  display: grid;
  grid-template-columns: 340px 1fr;
  gap: 1.25rem;
  align-items: start;
}

/* ── Section title ── */
.profile-section-title {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--bs-secondary-color);
  margin-bottom: 0.875rem;
}

/* ── Info rows ── */
.profile-info-row {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.6rem 0;
  border-bottom: 1px solid var(--bs-border-color);
}
.profile-info-row:last-child { border-bottom: none; }
.profile-info-icon {
  width: 30px; height: 30px;
  border-radius: 7px;
  background: var(--bs-primary-bg-subtle);
  color: var(--bs-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
  margin-top: 1px;
}
.profile-info-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--bs-secondary-color);
  margin-bottom: 1px;
  letter-spacing: 0.03em;
}
.profile-info-value {
  font-size: 0.875rem;
  color: var(--bs-body-color);
  word-break: break-word;
}

/* ── Booking cards ── */
.profile-booking-list {
  display: grid;
  gap: 0.875rem;
  max-height: 600px;
  overflow-y: auto;
  padding-right: 0.2rem;
}
.profile-booking-card {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.5rem;
  padding: 1rem;
  background: var(--bs-body-bg);
  transition: border-color 0.2s, box-shadow 0.2s;
}
.profile-booking-card:hover {
  border-color: var(--bs-primary);
  box-shadow: 0 0 0 3px rgba(105,108,255,0.1);
}
.profile-booking-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}
.profile-booking-route {
  display: grid;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}
.profile-booking-route-row {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--bs-secondary-color);
  padding-top: 0.5rem;
  border-top: 1px solid var(--bs-border-color);
}
.profile-booking-route-row:first-child {
  border-top: none;
  padding-top: 0;
}
.profile-booking-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  border-top: 1px solid var(--bs-border-color);
  padding-top: 0.75rem;
  flex-wrap: wrap;
}
.profile-empty {
  border: 1px dashed var(--bs-border-color);
  border-radius: 0.5rem;
  padding: 2.5rem 1rem;
  text-align: center;
  color: var(--bs-secondary-color);
  background: var(--bs-tertiary-bg);
  font-size: 0.9rem;
}

@media (max-width: 991.98px) {
  .profile-layout { grid-template-columns: 1fr; }
}
</style>

<div class="profile-page">
  <div class="card overflow-hidden">

    <!-- Cover -->
    <div class="profile-cover"></div>

    <!-- Identity strip -->
    <div class="card-body pb-0">
      <div class="d-flex align-items-flex-end justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-end gap-3 flex-wrap">
          <div class="profile-avatar-wrap">
            <img src="views/assets/images/avatar/avatar-3.jpg" alt="Avatar" class="profile-avatar">
            <span class="profile-avatar-status"></span>
          </div>
          <div class="pb-1">
            <h4 class="mb-1 fw-semibold"><?= htmlspecialchars($displayName) ?></h4>
            <span class="badge <?= $roleBadgeClass ?>">
              <i class="<?= $roleIcon ?> me-1"></i><?= $roleLabel ?>
            </span>
          </div>
        </div>
        <div class="pb-1">
          <span class="badge bg-success-subtle text-success">
            <i class="ri-checkbox-circle-line me-1"></i>Active
          </span>
        </div>
      </div>
    </div>

    <hr class="mx-4 my-0">

    <!-- Main body -->
    <div class="card-body">
      <div class="profile-layout">

        <!-- ── LEFT: account details ── -->
        <div class="d-flex flex-column gap-3">

          <!-- Account info -->
          <div class="card mb-0">
            <div class="card-body">
              <p class="profile-section-title">
                <i class="ri-account-circle-line me-1"></i>Account Details
              </p>

              <div class="profile-info-row">
                <div class="profile-info-icon"><i class="ri-id-card-line"></i></div>
                <div>
                  <div class="profile-info-label">Full Name</div>
                  <div class="profile-info-value"><?= htmlspecialchars($displayName) ?></div>
                </div>
              </div>

              <div class="profile-info-row">
                <div class="profile-info-icon"><i class="<?= $roleIcon ?>"></i></div>
                <div>
                  <div class="profile-info-label">Role</div>
                  <div class="profile-info-value"><?= $roleLabel ?></div>
                </div>
              </div>

              <?php if (!empty($email)): ?>
              <div class="profile-info-row">
                <div class="profile-info-icon"><i class="ri-mail-line"></i></div>
                <div>
                  <div class="profile-info-label">Email Address</div>
                  <div class="profile-info-value"><?= htmlspecialchars($email) ?></div>
                </div>
              </div>
              <?php endif; ?>

              <?php if (!empty($phone)): ?>
              <div class="profile-info-row">
                <div class="profile-info-icon"><i class="ri-phone-line"></i></div>
                <div>
                  <div class="profile-info-label">Phone Number</div>
                  <div class="profile-info-value"><?= htmlspecialchars($phone) ?></div>
                </div>
              </div>
              <?php endif; ?>

              <?php if ($isAdmin): ?>

              <p class="profile-section-title">
                <i class="ri-file-list-3-line me-1"></i>Bookings
              </p>
              <div class="profile-empty">
                <i class="ri-information-line fs-4 mb-2 d-block"></i>
                Admins do not have associated bookings.
              </div>

              <?php elseif ($showTripList): ?>
              <?php endif; ?>
              <?php if ($address): ?>
              <div class="profile-info-row">
                <div class="profile-info-icon"><i class="ri-map-pin-line"></i></div>
                <div>
                  <div class="profile-info-label">Address</div>
                  <div class="profile-info-value"><?= htmlspecialchars($address) ?></div>
                </div>
              </div>
              <?php endif; ?>

            </div>
          </div>

        </div>

        <!-- ── RIGHT: bookings ── -->
        <div class="card mb-0">
          <div class="card-body">

          <?php if ($isAdmin): ?>

          <p class="profile-section-title">
            <i class="ri-file-list-3-line me-1"></i>Bookings
          </p>
          <div class="profile-empty">
            <i class="ri-information-line fs-4 mb-2 d-block"></i>
            Admins do not have associated bookings.
          </div>

          <?php elseif ($showTripList): ?>

          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <p class="profile-section-title mb-0">
              <i class="ri-route-line me-1"></i>Trips Involved In
            </p>
          </div>

          <?php if (empty($tripList)): ?>
            <div class="profile-empty">
              <i class="ri-inbox-line fs-4 mb-2 d-block"></i>
              No trips found.
            </div>
          <?php else: ?>
            <div class="profile-booking-list">
              <?php foreach ($tripList as $trip): ?>
                <div class="profile-booking-card">

                  <div class="profile-booking-header">
                    <div>
                      <div class="fw-semibold small">
                        Trip #<?= (int)$trip["tripID"] ?>
                      </div>
                      <div class="text-muted" style="font-size:0.78rem;">
                        <i class="ri-calendar-line me-1"></i><?= profileText(profileDate($trip["pickupDateTime"])) ?>
                        &nbsp;·&nbsp;
                        <i class="ri-file-list-line me-1"></i><?= (int)$trip["bookingCount"] ?> booking<?= $trip["bookingCount"] != 1 ? 's' : '' ?>
                      </div>
                    </div>
                    <span class="badge <?= profileStatusClass($trip["status"]) ?>">
                      <?= profileText(ucfirst(str_replace('-', ' ', $trip["status"]))) ?>
                    </span>
                  </div>

                  <?php foreach ($trip["bookings"] as $b): ?>
                    <div class="profile-booking-route mb-2">
                      <div style="font-size:0.72rem;font-weight:700;color:var(--bs-secondary-color);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.375rem;">
                        Booking #<?= (int)$b["bookingID"] ?> &mdash; <?= htmlspecialchars($b["customerName"]) ?>
                      </div>
                      <div class="profile-booking-route-row">
                        <i class="ri-map-pin-2-line text-primary mt-1 flex-shrink-0"></i>
                        <div>
                          <div style="font-size:0.7rem;font-weight:600;color:var(--bs-secondary-color);margin-bottom:1px;">Pickup</div>
                          <?= profileText($b["pickupAddress"]) ?>
                          <?php if (!empty($b["pickupDescription"])): ?>
                            <div class="text-muted" style="font-size:0.78rem;"><?= profileText($b["pickupDescription"]) ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="profile-booking-route-row">
                        <i class="ri-flag-line text-danger mt-1 flex-shrink-0"></i>
                        <div>
                          <div style="font-size:0.7rem;font-weight:600;color:var(--bs-secondary-color);margin-bottom:1px;">Destination</div>
                          <?= profileText($b["destinationAddress"]) ?>
                          <?php if (!empty($b["destinationDescription"])): ?>
                            <div class="text-muted" style="font-size:0.78rem;"><?= profileText($b["destinationDescription"]) ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>

                  <div class="profile-booking-footer">
                    <div class="text-muted" style="font-size:0.8rem;">
                      <i class="ri-price-tag-3-line me-1"></i>
                      PHP <?= number_format(array_sum(array_column($trip["bookings"], "price")), 2) ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-light"
                            onclick="sessionStorage.setItem('selectedTripID', '<?= (int)$trip["tripID"] ?>'); window.location.href='trip-details';"
                      <i class="ri-eye-line me-1"></i>View Details
                    </button>
                  </div>

                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php else: ?>

          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <p class="profile-section-title mb-0">
              <i class="ri-file-list-3-line me-1"></i>My Bookings
            </p>
            <?php if ($isCustomerIndividual || $isCustomerCompany): ?>
              <a href="?route=booking-reg" class="btn btn-sm btn-primary">
                <i class="ri-add-line me-1"></i>Add Booking
              </a>
            <?php endif; ?>
          </div>

          <?php if (empty($bookingList)): ?>
            <div class="profile-empty">
              <i class="ri-inbox-line fs-4 mb-2 d-block"></i>
              No bookings found.
            </div>
          <?php else: ?>
            <div class="profile-booking-list">
              <?php foreach ($bookingList as $b): ?>
                <div class="profile-booking-card">

                  <div class="profile-booking-header">
                    <div>
                      <div class="fw-semibold small">
                        Booking #<?= (int)$b["bookingID"] ?>
                      </div>
                      <div class="text-muted" style="font-size:0.78rem;">
                        <i class="ri-road-map-line me-1"></i>Trip #<?= (int)$b["tripID"] ?>
                        &nbsp;·&nbsp;
                        <i class="ri-calendar-line me-1"></i><?= profileText(profileDate($b["pickupDateTime"])) ?>
                      </div>
                    </div>
                    <span class="badge <?= profileStatusClass($b["status"]) ?>">
                      <?= profileText(ucfirst(str_replace('-', ' ', $b["status"]))) ?>
                    </span>
                  </div>

                  <div class="profile-booking-route">
                    <div class="profile-booking-route-row">
                      <i class="ri-map-pin-2-line text-primary mt-1 flex-shrink-0"></i>
                      <div>
                        <div style="font-size:0.7rem;font-weight:600;color:var(--bs-secondary-color);margin-bottom:1px;">Pickup</div>
                        <?= profileText($b["pickupAddress"]) ?>
                        <?php if (!empty($b["pickupDescription"])): ?>
                          <div class="text-muted" style="font-size:0.78rem;"><?= profileText($b["pickupDescription"]) ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="profile-booking-route-row">
                      <i class="ri-flag-line text-danger mt-1 flex-shrink-0"></i>
                      <div>
                        <div style="font-size:0.7rem;font-weight:600;color:var(--bs-secondary-color);margin-bottom:1px;">Destination</div>
                        <?= profileText($b["destinationAddress"]) ?>
                        <?php if (!empty($b["destinationDescription"])): ?>
                          <div class="text-muted" style="font-size:0.78rem;"><?= profileText($b["destinationDescription"]) ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <div class="profile-booking-footer">
                    <div class="fw-semibold text-primary" style="font-size:0.95rem;">
                      PHP <?= number_format((float)$b["price"], 2) ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-light viewDetails"
                            data-id="<?= (int)$b["bookingID"] ?>">
                      <i class="ri-eye-line me-1"></i>View Details
                    </button>
                  </div>

                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php endif; ?>

          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<script>
  $(".viewDetails").on("click", function () {
    const bookingID = $(this).data("id");
    if (!bookingID) return;

    const form = $("<form>", {
      method: "POST",
      action: "booking-details"
    });

    form.append($("<input>", {
      type: "hidden",
      name: "bookingID",
      value: bookingID
    }));

    $("body").append(form);
    form.submit();
  });
</script>