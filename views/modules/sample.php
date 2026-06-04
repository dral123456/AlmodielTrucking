<?php
require_once "controllers/booking.controller.php";
require_once "controllers/sales.controller.php";
require_once "controllers/truck.controller.php";
require_once "models/booking.model.php";
require_once "models/sales.model.php";
require_once "models/truck.model.php";

date_default_timezone_set("Asia/Manila");

$dashboardRole = $_SESSION["role"] ?? "";

if ($dashboardRole !== "admin") {
  ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="ri-dashboard-line display-5 text-muted"></i>
      <h5 class="mt-3 mb-1">Dashboard</h5>
      <p class="text-muted mb-0">Use the sidebar to open the pages available for your role.</p>
    </div>
  </div>
  <?php
  return;
}

$salesData = ControllerSales::ctrSalesDashboard(array(
  "dateFrom" => "",
  "dateTo" => "",
  "customerType" => ""
));
$summary = $salesData["summary"] ?? array();
$monthlySeries = $salesData["monthlySeries"] ?? array("labels" => array(), "gross" => array(), "expenses" => array(), "net" => array());
$trips = ControllerBooking::ctrTripOverviewList(0, "admin");
$trucks = ControllerTruck::ctrTruckManageList();

$tripStats = array(
  "pending" => 0,
  "in-transit" => 0,
  "stopover" => 0,
  "completed" => 0
);
$activeTruckIDs = array();
$upcomingTrips = array();
$scheduleBuckets = array();
$today = strtotime(date("Y-m-d 00:00:00"));

foreach ($trips as $trip) {
  $status = strtolower((string) ($trip["status"] ?? "pending"));
  if (isset($tripStats[$status])) {
    $tripStats[$status]++;
  }

  if (in_array($status, array("in-transit", "stopover"), true)) {
    foreach ($trip["crew"] ?? array() as $crew) {
      $truckID = (int) ($crew["truckID"] ?? 0);
      if ($truckID > 0) {
        $activeTruckIDs[$truckID] = true;
      }
    }
  }

  $pickupTimestamp = strtotime((string) ($trip["firstPickupDateTime"] ?? ""));
  if ($pickupTimestamp && ($pickupTimestamp >= $today || in_array($status, array("in-transit", "stopover"), true))) {
    $upcomingTrips[] = $trip;
    $dateKey = date("Y-m-d", $pickupTimestamp);
    if (!isset($scheduleBuckets[$dateKey])) {
      $scheduleBuckets[$dateKey] = array(
        "label" => date("M d", $pickupTimestamp),
        "day" => date("D", $pickupTimestamp),
        "trips" => 0,
        "bookings" => 0
      );
    }
    $scheduleBuckets[$dateKey]["trips"]++;
    $scheduleBuckets[$dateKey]["bookings"] += (int) ($trip["bookingCount"] ?? 0);
  }
}

usort($upcomingTrips, function ($a, $b) {
  return strtotime($a["firstPickupDateTime"] ?? "") <=> strtotime($b["firstPickupDateTime"] ?? "");
});
ksort($scheduleBuckets);

$upcomingTrips = array_slice($upcomingTrips, 0, 6);
$scheduleBuckets = array_slice($scheduleBuckets, 0, 7, true);

$truckStats = array("available" => 0, "onTrip" => 0, "inactive" => 0);
foreach ($trucks as $truck) {
  $truckID = (int) ($truck["id"] ?? 0);
  $isInactive = strtolower((string) ($truck["status"] ?? "")) !== "active";
  if ($isInactive) {
    $truckStats["inactive"]++;
  } elseif (isset($activeTruckIDs[$truckID])) {
    $truckStats["onTrip"]++;
  } else {
    $truckStats["available"]++;
  }
}

function adminDashboardMoney($value) {
  return "PHP " . number_format((float) $value, 2);
}

function adminDashboardText($value, $fallback = "-") {
  $value = trim((string) $value);
  return htmlspecialchars($value !== "" ? $value : $fallback);
}

function adminDashboardDate($value) {
  $timestamp = strtotime((string) $value);
  return $timestamp ? date("M d, Y h:i A", $timestamp) : "-";
}

function adminDashboardStatusClass($status) {
  $status = strtolower(trim((string) $status));
  return match ($status) {
    "completed" => "success",
    "in-transit" => "primary",
    "stopover" => "info",
    default => "warning"
  };
}

function adminDashboardStatusLabel($status) {
  $status = strtolower(trim((string) $status));
  return match ($status) {
    "completed" => "Completed",
    "in-transit" => "In Transit",
    "stopover" => "Stopover",
    default => "Pending"
  };
}

function adminDashboardCrewLine($crew) {
  $names = array();
  foreach ($crew ?? array() as $member) {
    $name = trim(($member["empFName"] ?? "") . " " . ($member["empLName"] ?? ""));
    if ($name !== "") {
      $names[] = ucfirst((string) ($member["role"] ?? "crew")) . ": " . $name;
    }
  }
  return implode(", ", array_slice($names, 0, 3));
}

$grossSales = (float) ($summary["grossSales"] ?? 0);
$netSales = (float) ($summary["netSales"] ?? 0);
$pendingBookings = (int) ($summary["pendingBookings"] ?? 0);
$completedBookings = (int) ($summary["completedBookings"] ?? 0);
$maxTrendValue = max(array_merge(array(1), array_map("floatval", $monthlySeries["gross"] ?? array()), array_map("floatval", $monthlySeries["net"] ?? array())));
$calendarMonthTime = strtotime(date("Y-m-01"));
$calendarMonthLabel = date("F, Y", $calendarMonthTime);
$calendarDaysInMonth = (int) date("t", $calendarMonthTime);
$calendarStartWeekday = (int) date("N", $calendarMonthTime);
$calendarCells = array();

for ($blank = 1; $blank < $calendarStartWeekday; $blank++) {
  $calendarCells[] = null;
}

for ($day = 1; $day <= $calendarDaysInMonth; $day++) {
  $calendarCells[] = $day;
}
?>

<div class="admin-dashboard-page">
  <div class="dashboard-hero mb-4">
    <div>
      <span class="dashboard-kicker">Operations Overview</span>
      <h4 class="mb-1">Admin Dashboard</h4>
      <p class="text-muted mb-0">Sales, upcoming deliveries, truck readiness, and schedules in one clean workspace.</p>
    </div>
    <div class="dashboard-hero-actions">
      <a href="sales" class="btn btn-light"><i class="ri-line-chart-line me-1"></i> Sales</a>
      <a href="trips" class="btn btn-primary"><i class="ri-route-line me-1"></i> Trips</a>
    </div>
  </div>

  <div class="dashboard-kpi-grid mb-4">
    <div class="dashboard-kpi-card">
      <span class="dashboard-kpi-icon success"><i class="ri-money-dollar-circle-line"></i></span>
      <small>Gross Sales</small>
      <strong><?php echo adminDashboardMoney($grossSales); ?></strong>
      <span><?php echo (int) $completedBookings; ?> completed booking(s)</span>
    </div>
    <div class="dashboard-kpi-card">
      <span class="dashboard-kpi-icon primary"><i class="ri-scales-3-line"></i></span>
      <small>Net Sales</small>
      <strong><?php echo adminDashboardMoney($netSales); ?></strong>
      <span>After recorded expenses</span>
    </div>
    <div class="dashboard-kpi-card">
      <span class="dashboard-kpi-icon warning"><i class="ri-calendar-schedule-line"></i></span>
      <small>Upcoming Work</small>
      <strong><?php echo count($upcomingTrips); ?> trip(s)</strong>
      <span><?php echo (int) $pendingBookings; ?> active booking(s)</span>
    </div>
    <div class="dashboard-kpi-card">
      <span class="dashboard-kpi-icon info"><i class="ri-truck-line"></i></span>
      <small>Truck Status</small>
      <strong><?php echo (int) $truckStats["available"]; ?> available</strong>
      <span><?php echo (int) $truckStats["onTrip"]; ?> on trip, <?php echo (int) $truckStats["inactive"]; ?> inactive</span>
    </div>
  </div>

  <div class="dashboard-grid mb-4">
    <section class="dashboard-panel">
      <div class="dashboard-panel-header">
        <div>
          <h5>Sales Trend</h5>
          <p>Latest gross and net movement.</p>
        </div>
        <a href="sales" class="dashboard-link">View sales</a>
      </div>
      <?php if (empty($monthlySeries["labels"])): ?>
        <div class="dashboard-empty">No sales trend data yet.</div>
      <?php else: ?>
        <div class="dashboard-trend">
          <?php foreach ($monthlySeries["labels"] as $index => $label): ?>
            <?php
              $gross = (float) ($monthlySeries["gross"][$index] ?? 0);
              $net = (float) ($monthlySeries["net"][$index] ?? 0);
              $grossHeight = max(8, (int) round(($gross / $maxTrendValue) * 100));
              $netHeight = max(8, (int) round(($net / $maxTrendValue) * 100));
            ?>
            <div class="dashboard-trend-item" title="<?php echo adminDashboardText($label); ?> gross <?php echo adminDashboardMoney($gross); ?>">
              <div class="dashboard-trend-bars">
                <span class="gross" style="height: <?php echo $grossHeight; ?>%"></span>
                <span class="net" style="height: <?php echo $netHeight; ?>%"></span>
              </div>
              <small><?php echo adminDashboardText($label); ?></small>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="dashboard-legend mt-3">
          <span><i class="gross"></i> Gross</span>
          <span><i class="net"></i> Net</span>
        </div>
      <?php endif; ?>
    </section>

    <section class="dashboard-panel">
      <div class="dashboard-panel-header">
        <div>
          <h5>Schedules</h5>
          <p>Current month trip dates.</p>
        </div>
        <a href="booking-reg" class="dashboard-link">New booking</a>
      </div>
      <div class="dashboard-calendar-card">
        <div class="dashboard-calendar-header">
          <strong><?php echo adminDashboardText($calendarMonthLabel); ?></strong>
          <div class="dashboard-calendar-nav" aria-hidden="true">
            <i class="ri-arrow-left-s-line"></i>
            <i class="ri-arrow-right-s-line"></i>
          </div>
        </div>
        <div class="dashboard-calendar-grid">
          <?php foreach (array("Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun") as $weekday): ?>
            <span class="dashboard-calendar-weekday"><?php echo $weekday; ?></span>
          <?php endforeach; ?>
          <?php foreach ($calendarCells as $day): ?>
            <?php
              $dateKey = $day ? date("Y-m-") . str_pad((string) $day, 2, "0", STR_PAD_LEFT) : "";
              $hasTrips = $dateKey !== "" && isset($scheduleBuckets[$dateKey]);
              $isToday = $day && (int) date("j") === (int) $day;
              $tripCount = $hasTrips ? (int) ($scheduleBuckets[$dateKey]["trips"] ?? 0) : 0;
              $title = $hasTrips ? $tripCount . " trip(s) scheduled" : "";
            ?>
            <span
              class="<?php echo $day ? "dashboard-calendar-day" : "dashboard-calendar-empty"; ?> <?php echo $hasTrips ? "has-trips" : ""; ?> <?php echo $isToday ? "is-today" : ""; ?>"
              title="<?php echo htmlspecialchars($title); ?>"
            >
              <?php echo $day ? (int) $day : ""; ?>
              <?php if ($hasTrips): ?><i></i><?php endif; ?>
            </span>
          <?php endforeach; ?>
        </div>
        <div class="dashboard-calendar-legend">
          <span><i class="today"></i> Today</span>
          <span><i class="trip"></i> Trip date</span>
        </div>
      </div>
    </section>
  </div>

  <div class="dashboard-grid dashboard-grid-wide">
    <section class="dashboard-panel">
      <div class="dashboard-panel-header">
        <div>
          <h5>Upcoming Trips</h5>
          <p>Nearest active and scheduled trips.</p>
        </div>
        <a href="trips" class="dashboard-link">Open trips</a>
      </div>
      <div class="table-responsive">
        <table class="table dashboard-table align-middle mb-0">
          <thead>
            <tr>
              <th>Trip</th>
              <th>Schedule</th>
              <th>Customer</th>
              <th>Crew</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($upcomingTrips)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">No upcoming trips found.</td></tr>
            <?php endif; ?>
            <?php foreach ($upcomingTrips as $trip): ?>
              <tr>
                <td>
                  <strong>#<?php echo (int) $trip["tripID"]; ?></strong>
                  <div class="small text-muted"><?php echo (int) ($trip["bookingCount"] ?? 0); ?> booking(s)</div>
                </td>
                <td><?php echo adminDashboardDate($trip["firstPickupDateTime"] ?? ""); ?></td>
                <td><?php echo adminDashboardText(implode(", ", $trip["customers"] ?? array()), "Customer"); ?></td>
                <td><?php echo adminDashboardText(adminDashboardCrewLine($trip["crew"] ?? array()), "Crew not assigned"); ?></td>
                <td><?php echo adminDashboardMoney($trip["totalPrice"] ?? 0); ?></td>
                <td>
                  <span class="badge bg-<?php echo adminDashboardStatusClass($trip["status"] ?? "pending"); ?>-subtle text-<?php echo adminDashboardStatusClass($trip["status"] ?? "pending"); ?>">
                    <?php echo adminDashboardStatusLabel($trip["status"] ?? "pending"); ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="dashboard-panel">
      <div class="dashboard-panel-header">
        <div>
          <h5>Truck Readiness</h5>
          <p>Current vehicle availability.</p>
        </div>
        <a href="manage-truck" class="dashboard-link">Manage trucks</a>
      </div>
      <div class="dashboard-truck-list">
        <?php if (empty($trucks)): ?>
          <div class="dashboard-empty">No trucks registered yet.</div>
        <?php endif; ?>
        <?php foreach (array_slice($trucks, 0, 6) as $truck): ?>
          <?php
            $truckID = (int) ($truck["id"] ?? 0);
            $isInactive = strtolower((string) ($truck["status"] ?? "")) !== "active";
            $truckState = $isInactive ? "Inactive" : (isset($activeTruckIDs[$truckID]) ? "On Trip" : "Available");
            $truckStateClass = $isInactive ? "secondary" : (isset($activeTruckIDs[$truckID]) ? "primary" : "success");
          ?>
          <div class="dashboard-truck-card">
            <div class="dashboard-truck-icon"><i class="ri-truck-line"></i></div>
            <div>
              <strong><?php echo adminDashboardText($truck["plateNumber"] ?? "Truck"); ?></strong>
              <span><?php echo adminDashboardText(trim(($truck["brand"] ?? "") . " " . ($truck["type"] ?? "")), "Truck details"); ?></span>
            </div>
            <span class="badge bg-<?php echo $truckStateClass; ?>-subtle text-<?php echo $truckStateClass; ?>"><?php echo $truckState; ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<style>
  .admin-dashboard-page {
    max-width: 1500px;
    margin: 0 auto;
  }

  .dashboard-hero,
  .dashboard-panel,
  .dashboard-kpi-card {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    box-shadow: 0 12px 30px rgba(15, 23, 42, .04);
  }

  .dashboard-hero {
    border-radius: 24px;
    padding: 1.4rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }

  .dashboard-kicker {
    display: inline-flex;
    align-items: center;
    margin-bottom: .35rem;
    color: #4f46e5;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
  }

  .dashboard-hero h4,
  .dashboard-panel h5 {
    color: var(--bs-emphasis-color);
  }

  .dashboard-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
  }

  .dashboard-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
  }

  .dashboard-kpi-card {
    border-radius: 20px;
    padding: 1.15rem;
    display: grid;
    gap: .45rem;
  }

  .dashboard-kpi-card small,
  .dashboard-kpi-card span {
    color: var(--bs-secondary-color);
  }

  .dashboard-kpi-card strong {
    color: var(--bs-emphasis-color);
    font-size: clamp(1.25rem, 2vw, 1.75rem);
    line-height: 1.2;
  }

  .dashboard-kpi-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 15px;
    font-size: 1.3rem;
  }

  .dashboard-kpi-icon.success { background: rgba(34, 197, 94, .12); color: #16a34a; }
  .dashboard-kpi-icon.primary { background: rgba(79, 70, 229, .12); color: #4f46e5; }
  .dashboard-kpi-icon.warning { background: rgba(245, 158, 11, .14); color: #d97706; }
  .dashboard-kpi-icon.info { background: rgba(6, 182, 212, .12); color: #0891b2; }

  .dashboard-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(360px, .85fr);
    gap: 1rem;
  }

  .dashboard-grid-wide {
    grid-template-columns: minmax(0, 1.4fr) minmax(340px, .6fr);
  }

  .dashboard-panel {
    border-radius: 22px;
    padding: 1.25rem;
    min-width: 0;
  }

  .dashboard-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .dashboard-panel-header h5,
  .dashboard-panel-header p {
    margin: 0;
  }

  .dashboard-panel-header p {
    color: var(--bs-secondary-color);
    font-size: .86rem;
  }

  .dashboard-link {
    color: #4f46e5;
    font-weight: 700;
    white-space: nowrap;
  }

  .dashboard-trend {
    height: 220px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(52px, 1fr));
    gap: .85rem;
    align-items: end;
    padding-top: 1rem;
  }

  .dashboard-trend-item {
    display: grid;
    gap: .5rem;
    min-width: 0;
  }

  .dashboard-trend-bars {
    height: 160px;
    display: flex;
    align-items: end;
    justify-content: center;
    gap: .25rem;
    padding: .6rem;
    border-radius: 16px;
    background: var(--bs-tertiary-bg);
  }

  .dashboard-trend-bars span {
    width: 12px;
    border-radius: 999px 999px 4px 4px;
  }

  .dashboard-trend-bars .gross {
    background: linear-gradient(180deg, #22c55e, #86efac);
  }

  .dashboard-trend-bars .net {
    background: linear-gradient(180deg, #4f46e5, #a5b4fc);
  }

  .dashboard-trend-item small {
    text-align: center;
    color: var(--bs-secondary-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .dashboard-legend {
    display: flex;
    gap: 1rem;
    color: var(--bs-secondary-color);
    font-size: .85rem;
  }

  .dashboard-legend span {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
  }

  .dashboard-legend i {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display: inline-block;
  }

  .dashboard-legend .gross { background: #22c55e; }
  .dashboard-legend .net { background: #4f46e5; }

  .dashboard-calendar-card,
  .dashboard-truck-list {
    display: grid;
    gap: .75rem;
  }

  .dashboard-calendar-card {
    padding: 0.25rem 0.1rem 0;
  }

  .dashboard-calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .dashboard-calendar-header strong {
    color: var(--bs-emphasis-color);
    font-size: 1rem;
  }

  .dashboard-calendar-nav {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    color: var(--bs-secondary-color);
    font-size: 1.15rem;
  }

  .dashboard-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: .38rem;
    text-align: center;
  }

  .dashboard-calendar-weekday {
    color: var(--bs-secondary-color);
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
  }

  .dashboard-calendar-day,
  .dashboard-calendar-empty {
    position: relative;
    min-height: 34px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    color: var(--bs-emphasis-color);
    font-size: .88rem;
    font-weight: 650;
  }

  .dashboard-calendar-empty {
    color: transparent;
  }

  .dashboard-calendar-day.has-trips {
    color: #4e5dff;
    background: rgba(78, 93, 255, .1);
  }

  .dashboard-calendar-day.has-trips i {
    position: absolute;
    bottom: 3px;
    width: 5px;
    height: 5px;
    border-radius: 999px;
    background: #4e5dff;
  }

  .dashboard-calendar-day.is-today {
    color: #fff;
    background: #ff6b35;
    box-shadow: 0 8px 18px rgba(255, 107, 53, .28);
  }

  .dashboard-calendar-day.is-today i {
    background: #fff;
  }

  .dashboard-calendar-legend {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-top: 1rem;
    color: var(--bs-secondary-color);
    font-size: .82rem;
  }

  .dashboard-calendar-legend span {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
  }

  .dashboard-calendar-legend i {
    width: 9px;
    height: 9px;
    display: inline-block;
    border-radius: 999px;
  }

  .dashboard-calendar-legend .today {
    background: #ff6b35;
  }

  .dashboard-calendar-legend .trip {
    background: #4e5dff;
  }

  .dashboard-truck-card {
    display: grid;
    align-items: center;
    gap: .75rem;
    padding: .9rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    background: var(--bs-tertiary-bg);
  }

  .dashboard-truck-card span {
    color: var(--bs-secondary-color);
  }

  .dashboard-truck-card strong {
    display: block;
    color: var(--bs-emphasis-color);
  }

  .dashboard-table th {
    color: var(--bs-secondary-color);
    font-size: .74rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    white-space: nowrap;
  }

  .dashboard-table td {
    border-color: var(--bs-border-color);
  }

  .dashboard-truck-card {
    grid-template-columns: auto minmax(0, 1fr) auto;
  }

  .dashboard-truck-icon {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    background: rgba(79, 70, 229, .1);
    color: #4e5dff;
    font-size: 1.2rem;
  }

  .dashboard-empty {
    min-height: 160px;
    border: 1px dashed var(--bs-border-color);
    border-radius: 18px;
    display: grid;
    place-items: center;
    color: var(--bs-secondary-color);
    background: var(--bs-tertiary-bg);
  }

  @media (max-width: 1199.98px) {
    .dashboard-kpi-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-grid,
    .dashboard-grid-wide {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 767.98px) {
    .dashboard-hero {
      align-items: stretch;
      flex-direction: column;
    }

    .dashboard-hero-actions .btn {
      flex: 1;
    }

    .dashboard-kpi-grid {
      grid-template-columns: 1fr;
    }

    .dashboard-panel-header {
      flex-direction: column;
    }

    .dashboard-truck-card {
      grid-template-columns: 1fr;
      align-items: flex-start;
    }
  }
</style>
