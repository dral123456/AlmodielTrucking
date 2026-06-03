<?php
require_once "controllers/truck.controller.php";
require_once "models/truck.model.php";

$truckID = (int) ($_GET["truckID"] ?? 0);
$truck = ControllerTruck::ctrTruckDetails($truckID);

function truckDetailNumber($value, $decimals = 0) {
  return number_format((float) $value, $decimals);
}

function truckDetailDate($value, $includeTime = true) {
  if (!$value) {
    return "-";
  }

  return date($includeTime ? "M d, Y h:i A" : "M d, Y", strtotime($value));
}

function truckDetailStatusMeta($status) {
  $map = array(
    "available" => array("Available", "success", "ri-checkbox-circle-line"),
    "on-trip" => array("On Trip", "primary", "ri-route-line"),
    "inactive" => array("Inactive", "secondary", "ri-forbid-line"),
    "pending" => array("Scheduled", "warning", "ri-time-line"),
    "in-transit" => array("In Transit", "primary", "ri-truck-line"),
    "stopover" => array("Stopover", "info", "ri-map-pin-time-line"),
    "completed" => array("Completed", "success", "ri-check-double-line")
  );

  return $map[$status] ?? array(ucfirst((string) $status), "secondary", "ri-information-line");
}
?>

<?php if (!$truck): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="ri-truck-line display-5 text-muted"></i>
      <h5 class="mt-3">Truck not found</h5>
      <p class="text-muted">The selected truck record does not exist.</p>
      <a href="/almodieltrucking/?route=manage-truck" class="btn btn-primary">Back to Truck Management</a>
    </div>
  </div>
<?php else: ?>
  <?php
    $operationalMeta = truckDetailStatusMeta($truck["operationalStatus"]);
    $currentTrip = $truck["currentTrip"];
  ?>
  <div class="truck-detail-page">
    <div class="truck-detail-header">
      <div>
        <a href="/almodieltrucking/?route=manage-truck" class="truck-back-link"><i class="ri-arrow-left-line"></i> Truck Management</a>
        <div class="d-flex align-items-center gap-3 flex-wrap mt-2">
          <div class="truck-title-icon"><i class="ri-truck-line"></i></div>
          <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <h3 class="mb-0"><?php echo htmlspecialchars($truck["plateNumber"]); ?></h3>
              <span class="badge bg-<?php echo $operationalMeta[1]; ?>-subtle text-<?php echo $operationalMeta[1]; ?>">
                <i class="<?php echo $operationalMeta[2]; ?> me-1"></i><?php echo $operationalMeta[0]; ?>
              </span>
            </div>
            <p class="text-muted mb-0"><?php echo htmlspecialchars(trim($truck["brand"] . " " . $truck["type"])); ?> · <?php echo truckDetailNumber($truck["capacity"]); ?> kg capacity</p>
          </div>
        </div>
      </div>
      <div class="truck-header-actions">
        <button type="button" class="btn btn-light" id="truckUpdateReadingsBtn"><i class="ri-dashboard-3-line me-1"></i> Update Readings</button>
        <button type="button" class="btn btn-primary" id="truckAddFuelBtn"><i class="ri-gas-station-line me-1"></i> Record Fuel</button>
      </div>
    </div>

    <div class="truck-metric-grid">
      <div class="truck-metric-card">
        <div class="truck-metric-icon fuel"><i class="ri-gas-station-line"></i></div>
        <div>
          <span>Current Fuel</span>
          <strong><?php echo truckDetailNumber($truck["fuel"], 1); ?> <small>L</small></strong>
          <small>Latest saved reading</small>
        </div>
      </div>
      <div class="truck-metric-card">
        <div class="truck-metric-icon mileage"><i class="ri-dashboard-3-line"></i></div>
        <div>
          <span>Current Mileage</span>
          <strong><?php echo truckDetailNumber($truck["mileage"], 1); ?> <small>km</small></strong>
          <small>Odometer reading</small>
        </div>
      </div>
      <div class="truck-metric-card">
        <div class="truck-metric-icon efficiency"><i class="ri-speed-up-line"></i></div>
        <div>
          <span>Average Efficiency</span>
          <strong><?php echo truckDetailNumber($truck["fuelEfficiencyKmPerLiter"], 2); ?> <small>km/L</small></strong>
          <small>Based on <?php echo htmlspecialchars($truck["fuelEfficiencySource"]); ?></small>
        </div>
      </div>
      <div class="truck-metric-card">
        <div class="truck-metric-icon trips"><i class="ri-route-line"></i></div>
        <div>
          <span>Recorded Trips</span>
          <strong><?php echo count($truck["trips"]); ?></strong>
          <small>Assigned to this truck</small>
        </div>
      </div>
    </div>

    <div class="truck-detail-grid">
      <section class="truck-panel truck-trip-panel">
        <div class="truck-panel-heading">
          <div>
            <span class="truck-section-kicker">Operations</span>
            <h5>Current Trip</h5>
          </div>
          <?php if ($currentTrip): ?>
            <?php $tripMeta = truckDetailStatusMeta($currentTrip["status"]); ?>
            <span class="badge bg-<?php echo $tripMeta[1]; ?>-subtle text-<?php echo $tripMeta[1]; ?>"><?php echo $tripMeta[0]; ?></span>
          <?php endif; ?>
        </div>

        <?php if ($currentTrip): ?>
          <div class="truck-current-trip">
            <div class="truck-trip-identity">
              <div>
                <span>Trip #<?php echo htmlspecialchars($currentTrip["tripID"]); ?></span>
                <strong><?php echo htmlspecialchars(implode(", ", $currentTrip["customers"])); ?></strong>
              </div>
              <a href="/almodieltrucking/?route=trips" class="btn btn-sm btn-light">Open Trips <i class="ri-arrow-right-line ms-1"></i></a>
            </div>
            <div class="truck-projection-grid">
              <div>
                <span>Round-Trip Distance</span>
                <strong><?php echo truckDetailNumber($currentTrip["roundTripDistanceKm"], 2); ?> km</strong>
                <small class="text-muted"><?php echo truckDetailNumber($currentTrip["oneWayDistanceKm"], 2); ?> km each way</small>
              </div>
              <div>
                <span>Estimated Fuel Needed</span>
                <strong><?php echo truckDetailNumber($currentTrip["estimatedFuelNeeded"], 2); ?> L</strong>
              </div>
              <div>
                <span>Expected Remaining Fuel</span>
                <strong class="<?php echo $currentTrip["estimatedRemainingFuel"] <= 5 ? "text-danger" : "text-success"; ?>"><?php echo truckDetailNumber($currentTrip["estimatedRemainingFuel"], 2); ?> L</strong>
              </div>
              <div>
                <span>Projected Mileage</span>
                <strong><?php echo truckDetailNumber($currentTrip["projectedMileage"], 2); ?> km</strong>
              </div>
            </div>
            <p class="truck-estimate-note mb-0"><i class="ri-information-line"></i> Estimates include the delivery route and return trip, using the truck’s average fuel efficiency.</p>
          </div>
        <?php else: ?>
          <div class="truck-empty-state">
            <i class="ri-roadster-line"></i>
            <h6>No active trip</h6>
            <p>This truck is not currently in transit or at a stopover.</p>
          </div>
        <?php endif; ?>
      </section>

      <section class="truck-panel">
        <div class="truck-panel-heading">
          <div>
            <span class="truck-section-kicker">Assignment</span>
            <h5>Default Crew</h5>
          </div>
        </div>
        <div class="truck-crew-list">
          <?php if ($truck["crew"]): ?>
            <?php foreach ($truck["crew"] as $member): ?>
              <div class="truck-crew-item">
                <span class="truck-crew-avatar"><?php echo htmlspecialchars(strtoupper(substr($member["empFName"], 0, 1) . substr($member["empLName"], 0, 1))); ?></span>
                <div>
                  <strong><?php echo htmlspecialchars(trim($member["empFName"] . " " . $member["empLName"])); ?></strong>
                  <span><?php echo htmlspecialchars(ucfirst($member["role"])); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="truck-empty-state compact"><p>No default crew assigned.</p></div>
          <?php endif; ?>
        </div>
      </section>
    </div>

    <section class="truck-panel mt-4">
      <div class="truck-panel-heading">
        <div>
          <span class="truck-section-kicker">Fuel History</span>
          <h5>Fuel In / Out Logs</h5>
        </div>
        <button type="button" class="btn btn-sm btn-primary" id="truckAddFuelBtnSecondary"><i class="ri-add-line me-1"></i> Add Log</button>
      </div>
      <div class="table-responsive">
        <table class="table align-middle truck-detail-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Movement</th>
              <th>Fuel In</th>
              <th>Fuel Out</th>
              <th>Fuel Balance</th>
              <th>Odometer</th>
              <th>Source</th>
              <th>Cost / Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($truck["fuelMovements"]): ?>
              <?php foreach ($truck["fuelMovements"] as $log): ?>
                <tr>
                  <td><?php echo htmlspecialchars(truckDetailDate($log["movementDate"])); ?></td>
                  <td>
                    <?php if ($log["movementType"] === "in"): ?>
                      <span class="badge bg-success-subtle text-success"><i class="ri-arrow-down-line me-1"></i>In</span>
                    <?php else: ?>
                      <span class="badge bg-danger-subtle text-danger"><i class="ri-arrow-up-line me-1"></i>Out</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-success fw-semibold"><?php echo (float) $log["fuelIn"] > 0 ? "+" . truckDetailNumber($log["fuelIn"], 2) . " L" : "-"; ?></td>
                  <td class="text-danger fw-semibold"><?php echo (float) $log["fuelOut"] > 0 ? "-" . truckDetailNumber($log["fuelOut"], 2) . " L" : "-"; ?></td>
                  <td>
                    <strong><?php echo truckDetailNumber($log["fuelAfter"], 2); ?> L</strong>
                    <small class="d-block text-muted">from <?php echo truckDetailNumber($log["fuelBefore"], 2); ?> L</small>
                  </td>
                  <td><?php echo truckDetailNumber($log["odometer"], 2); ?> km</td>
                  <td>
                    <div><?php echo htmlspecialchars($log["source"] ?: "-"); ?></div>
                    <?php if (!empty($log["referenceNo"])): ?><small class="text-muted"><?php echo htmlspecialchars($log["referenceNo"]); ?></small><?php endif; ?>
                  </td>
                  <td class="truck-notes-cell">
                    <?php if ((float) $log["amount"] > 0): ?><div>₱<?php echo truckDetailNumber($log["amount"], 2); ?></div><?php endif; ?>
                    <small class="text-muted"><?php echo htmlspecialchars($log["notes"] ?: "-"); ?></small>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="8" class="text-center text-muted py-5">No fuel movements yet. Refuels and completed-trip fuel usage will appear here.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="truck-panel mt-4">
      <div class="truck-panel-heading">
        <div>
          <span class="truck-section-kicker">Activity</span>
          <h5>Trip History</h5>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table align-middle truck-detail-table">
          <thead>
            <tr>
              <th>Trip</th>
              <th>Schedule</th>
              <th>Customers</th>
              <th>Bookings</th>
              <th>Distance</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($truck["trips"]): ?>
              <?php foreach ($truck["trips"] as $trip): ?>
                <?php $tripMeta = truckDetailStatusMeta($trip["status"]); ?>
                <tr>
                  <td><strong>#<?php echo htmlspecialchars($trip["tripID"]); ?></strong></td>
                  <td><?php echo htmlspecialchars(truckDetailDate($trip["pickupDateTime"])); ?></td>
                  <td><?php echo htmlspecialchars(implode(", ", $trip["customers"])); ?></td>
                  <td><?php echo truckDetailNumber($trip["bookingCount"]); ?></td>
                  <td><?php echo truckDetailNumber($trip["totalDistanceKm"], 2); ?> km</td>
                  <td><span class="badge bg-<?php echo $tripMeta[1]; ?>-subtle text-<?php echo $tripMeta[1]; ?>"><?php echo $tripMeta[0]; ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-center text-muted py-5">No trips have been assigned to this truck.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <script>
    window.truckDetailData = <?php echo json_encode(array(
      "truckID" => (int) $truck["id"],
      "plateNumber" => $truck["plateNumber"],
      "fuel" => (float) $truck["fuel"],
      "mileage" => (float) $truck["mileage"]
    ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  </script>
<?php endif; ?>

<style>
  .truck-detail-page { max-width: 1540px; margin: 0 auto; }
  .truck-detail-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 1.5rem; margin-bottom: 1.5rem; }
  .truck-back-link { display: inline-flex; align-items: center; gap: .35rem; color: var(--bs-secondary-color); font-size: .875rem; }
  .truck-title-icon { width: 52px; height: 52px; border-radius: 16px; display: grid; place-items: center; background: rgba(78, 93, 255, .12); color: #4e5dff; font-size: 1.5rem; }
  .truck-header-actions { display: flex; gap: .75rem; flex-wrap: wrap; }
  .truck-metric-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
  .truck-metric-card, .truck-panel { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 18px; box-shadow: 0 8px 30px rgba(17, 24, 39, .04); }
  .truck-metric-card { padding: 1.25rem; display: flex; gap: 1rem; align-items: center; }
  .truck-metric-icon { width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; font-size: 1.25rem; flex: 0 0 auto; }
  .truck-metric-icon.fuel { background: rgba(16, 185, 129, .12); color: #10b981; }
  .truck-metric-icon.mileage { background: rgba(59, 130, 246, .12); color: #3b82f6; }
  .truck-metric-icon.efficiency { background: rgba(245, 158, 11, .14); color: #f59e0b; }
  .truck-metric-icon.trips { background: rgba(99, 102, 241, .12); color: #6366f1; }
  .truck-metric-card span, .truck-metric-card small { display: block; color: var(--bs-secondary-color); }
  .truck-metric-card strong { display: block; font-size: 1.45rem; line-height: 1.25; color: var(--bs-emphasis-color); }
  .truck-metric-card strong small { display: inline; font-size: .75rem; font-weight: 600; }
  .truck-detail-grid { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(300px, .65fr); gap: 1rem; }
  .truck-panel { padding: 1.25rem; }
  .truck-panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
  .truck-panel-heading h5 { margin: 0; }
  .truck-section-kicker { display: block; color: var(--bs-secondary-color); text-transform: uppercase; letter-spacing: .1em; font-size: .7rem; font-weight: 700; margin-bottom: .2rem; }
  .truck-current-trip { border-radius: 15px; padding: 1.1rem; background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color); }
  .truck-trip-identity { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--bs-border-color); }
  .truck-trip-identity span, .truck-trip-identity strong { display: block; }
  .truck-trip-identity span { color: var(--bs-secondary-color); font-size: .8rem; }
  .truck-projection-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; padding: 1rem 0; }
  .truck-projection-grid div { padding: .85rem; border-radius: 12px; background: var(--bs-body-bg); }
  .truck-projection-grid span, .truck-projection-grid strong { display: block; }
  .truck-projection-grid span { color: var(--bs-secondary-color); font-size: .75rem; margin-bottom: .25rem; }
  .truck-projection-grid strong { font-size: 1rem; }
  .truck-estimate-note { display: flex; gap: .4rem; align-items: flex-start; color: var(--bs-secondary-color); font-size: .78rem; }
  .truck-crew-list { display: grid; gap: .75rem; }
  .truck-crew-item { display: flex; align-items: center; gap: .75rem; padding: .75rem; border: 1px solid var(--bs-border-color); border-radius: 13px; }
  .truck-crew-avatar { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 50%; background: rgba(78, 93, 255, .12); color: #4e5dff; font-size: .75rem; font-weight: 800; }
  .truck-crew-item strong, .truck-crew-item span { display: block; }
  .truck-crew-item div span { color: var(--bs-secondary-color); font-size: .78rem; }
  .truck-empty-state { min-height: 190px; display: grid; place-items: center; align-content: center; text-align: center; color: var(--bs-secondary-color); }
  .truck-empty-state i { font-size: 2rem; margin-bottom: .35rem; }
  .truck-empty-state h6, .truck-empty-state p { margin-bottom: .25rem; }
  .truck-empty-state.compact { min-height: 100px; }
  .truck-detail-table th { white-space: nowrap; color: var(--bs-secondary-color); font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; }
  .truck-detail-table td { border-color: var(--bs-border-color); }
  .truck-notes-cell { max-width: 280px; white-space: normal; }
  .truck-detail-form { text-align: left; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
  .truck-detail-form .wide { grid-column: 1 / -1; }
  .truck-detail-modal.swal2-popup { width: min(760px, 96vw) !important; border-radius: 20px !important; padding: 1.5rem !important; }
  .truck-detail-modal .swal2-html-container { margin: 1rem 0 0 !important; }
  @media (max-width: 1199.98px) {
    .truck-metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .truck-detail-grid { grid-template-columns: 1fr; }
  }
  @media (max-width: 767.98px) {
    .truck-detail-header { align-items: stretch; flex-direction: column; }
    .truck-header-actions .btn { flex: 1; }
    .truck-metric-grid, .truck-projection-grid { grid-template-columns: 1fr; }
    .truck-trip-identity { align-items: flex-start; flex-direction: column; }
    .truck-detail-form { grid-template-columns: 1fr; }
    .truck-detail-form .wide { grid-column: auto; }
  }
</style>
