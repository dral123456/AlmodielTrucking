<?php
require_once "controllers/incident.controller.php";
require_once "models/incident.model.php";

$incidentRows = ControllerIncident::ctrIncidentList();
$incidentCounts = array(
  "open" => 0,
  "reviewing" => 0,
  "resolved" => 0,
  "dismissed" => 0
);

foreach ($incidentRows as $incidentRow) {
  $status = $incidentRow["status"] ?? "open";
  if (isset($incidentCounts[$status])) {
    $incidentCounts[$status]++;
  }
}

function incidentText($value, $fallback = "-") {
  $value = trim((string) $value);
  return htmlspecialchars($value !== "" ? $value : $fallback);
}

function incidentLabel($value) {
  return ucwords(str_replace("_", " ", (string) $value));
}

function incidentDate($value) {
  if (!$value) {
    return "-";
  }

  $timestamp = strtotime($value);
  return $timestamp ? date("M d, Y h:i A", $timestamp) : $value;
}

function incidentStatusClass($status) {
  if ($status === "resolved") {
    return "bg-success-subtle text-success";
  }

  if ($status === "reviewing") {
    return "bg-info-subtle text-info";
  }

  if ($status === "dismissed") {
    return "bg-secondary-subtle text-secondary";
  }

  return "bg-warning-subtle text-warning";
}

function incidentSeverityClass($severity) {
  if ($severity === "critical") {
    return "bg-danger text-white";
  }

  if ($severity === "high") {
    return "bg-danger-subtle text-danger";
  }

  if ($severity === "low") {
    return "bg-success-subtle text-success";
  }

  return "bg-warning-subtle text-warning";
}
?>

<div class="incident-admin-page">
  <div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Incident Reports</h5>
        <p class="text-muted small mb-0">Review driver-submitted incidents and update the response status.</p>
      </div>
      <span class="badge bg-danger-subtle text-danger fs-6">
        <i class="ri-alarm-warning-line me-1"></i> Safety Desk
      </span>
    </div>

    <div class="card-body p-4">
      <div class="incident-summary-grid mb-4">
        <button type="button" class="incident-stat-card active" data-incident-filter="all">
          <span class="incident-stat-icon bg-primary-subtle text-primary"><i class="ri-file-list-3-line"></i></span>
          <span><small>Total Reports</small><strong><?php echo count($incidentRows); ?></strong></span>
        </button>
        <button type="button" class="incident-stat-card" data-incident-filter="open">
          <span class="incident-stat-icon bg-warning-subtle text-warning"><i class="ri-error-warning-line"></i></span>
          <span><small>Open</small><strong><?php echo (int) $incidentCounts["open"]; ?></strong></span>
        </button>
        <button type="button" class="incident-stat-card" data-incident-filter="reviewing">
          <span class="incident-stat-icon bg-info-subtle text-info"><i class="ri-search-eye-line"></i></span>
          <span><small>Reviewing</small><strong><?php echo (int) $incidentCounts["reviewing"]; ?></strong></span>
        </button>
        <button type="button" class="incident-stat-card" data-incident-filter="resolved">
          <span class="incident-stat-icon bg-success-subtle text-success"><i class="ri-checkbox-circle-line"></i></span>
          <span><small>Resolved</small><strong><?php echo (int) $incidentCounts["resolved"]; ?></strong></span>
        </button>
        <button type="button" class="incident-stat-card" data-incident-filter="dismissed">
          <span class="incident-stat-icon bg-secondary-subtle text-secondary"><i class="ri-close-circle-line"></i></span>
          <span><small>Dismissed</small><strong><?php echo (int) $incidentCounts["dismissed"]; ?></strong></span>
        </button>
      </div>

      <div class="incident-toolbar mb-3">
        <div class="form-icon">
          <i class="ri-search-line text-muted"></i>
          <input type="text" class="form-control form-control-icon" id="incidentSearch" placeholder="Search trip, driver, customer, type, or description">
        </div>
        <select class="form-select" id="incidentStatusFilter">
          <option value="all">All statuses</option>
          <option value="open">Open</option>
          <option value="reviewing">Reviewing</option>
          <option value="resolved">Resolved</option>
          <option value="dismissed">Dismissed</option>
        </select>
      </div>

      <div class="table-responsive incident-table-shell">
        <table class="table align-middle incident-table mb-0">
          <thead>
            <tr>
              <th>Report</th>
              <th>Trip / Booking</th>
              <th>Driver</th>
              <th>Incident</th>
              <th>Details</th>
              <th>Status</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($incidentRows)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-5">No incident reports submitted yet.</td>
              </tr>
            <?php endif; ?>

            <?php foreach ($incidentRows as $row): ?>
              <tr class="incident-row" data-incident-id="<?php echo (int) $row["incidentID"]; ?>" data-status="<?php echo htmlspecialchars($row["status"]); ?>">
                <td>
                  <strong>#<?php echo (int) $row["incidentID"]; ?></strong>
                  <div class="small text-muted"><?php echo htmlspecialchars(incidentDate($row["dateSubmitted"])); ?></div>
                </td>
                <td>
                  <strong>Trip #<?php echo (int) $row["tripID"]; ?></strong>
                  <div class="small text-muted">
                    <?php echo $row["bookingID"] ? "Booking #" . (int) $row["bookingID"] : "No specific booking"; ?>
                  </div>
                  <div class="small text-muted"><?php echo incidentText($row["customerName"] ?? "", "Customer not attached"); ?></div>
                </td>
                <td>
                  <?php echo incidentText($row["driverName"], "Driver"); ?>
                  <div class="small text-muted">Driver ID #<?php echo (int) $row["driverID"]; ?></div>
                </td>
                <td>
                  <span class="badge <?php echo incidentSeverityClass($row["severity"]); ?>"><?php echo incidentText(incidentLabel($row["severity"])); ?></span>
                  <div class="mt-2 fw-semibold"><?php echo incidentText(incidentLabel($row["incidentType"])); ?></div>
                  <div class="small text-muted"><?php echo htmlspecialchars(incidentDate($row["incidentDateTime"])); ?></div>
                </td>
                <td>
                  <div class="incident-description"><?php echo incidentText($row["description"]); ?></div>
                  <div class="small text-muted mt-1"><i class="ri-map-pin-line me-1"></i><?php echo incidentText($row["locationText"], "No location noted"); ?></div>
                  <div class="small text-muted"><i class="ri-tools-line me-1"></i><?php echo incidentText($row["actionTaken"], "No action noted"); ?></div>
                  <?php if (!empty($row["adminNotes"])): ?>
                    <div class="incident-note mt-2">Admin note: <?php echo incidentText($row["adminNotes"]); ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?php echo incidentStatusClass($row["status"]); ?>"><?php echo incidentText(incidentLabel($row["status"])); ?></span>
                </td>
                <td class="text-end">
                  <div class="btn-group incident-action-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-info incident-status-action" data-incident-id="<?php echo (int) $row["incidentID"]; ?>" data-status="reviewing">Review</button>
                    <button type="button" class="btn btn-sm btn-outline-success incident-status-action" data-incident-id="<?php echo (int) $row["incidentID"]; ?>" data-status="resolved">Resolve</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary incident-status-action" data-incident-id="<?php echo (int) $row["incidentID"]; ?>" data-status="dismissed">Dismiss</button>
                    <button type="button" class="btn btn-sm btn-outline-warning incident-status-action" data-incident-id="<?php echo (int) $row["incidentID"]; ?>" data-status="open">Reopen</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  window.incidentReportData = <?php echo json_encode($incidentRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<style>
  .incident-admin-page {
    max-width: 1480px;
    margin: 0 auto;
  }

  .incident-summary-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.75rem;
  }

  .incident-stat-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.625rem;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    padding: 0.875rem;
    text-align: left;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }

  .incident-stat-card.active,
  .incident-stat-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.10);
  }

  .incident-stat-icon {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 42px;
    border-radius: 0.5rem;
    font-size: 1.2rem;
  }

  .incident-stat-card small {
    display: block;
    color: var(--bs-secondary-color);
  }

  .incident-stat-card strong {
    display: block;
    font-size: 1.25rem;
    line-height: 1.1;
  }

  .incident-toolbar {
    display: grid;
    grid-template-columns: minmax(280px, 1fr) minmax(180px, 240px);
    gap: 0.75rem;
  }

  .incident-table-shell {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.625rem;
  }

  .incident-action-group {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.25rem;
  }

  .incident-table {
    width: 100%;
    table-layout: fixed;
  }

  .incident-row {
    cursor: pointer;
  }

  .incident-row:hover {
    background: var(--bs-tertiary-bg);
  }

  .incident-row.active {
    background: var(--bs-primary-bg-subtle);
  }

  .incident-description {
    max-width: 360px;
    white-space: normal;
  }

  .incident-note {
    border-left: 3px solid var(--bs-primary);
    padding-left: 0.5rem;
    color: var(--bs-secondary-color);
    font-size: 0.8125rem;
  }

  .incident-action-group .btn {
    border-radius: 0.375rem !important;
  }

  .incident-review-popup {
    width: min(1080px, calc(100vw - 32px)) !important;
    max-width: min(1080px, calc(100vw - 32px)) !important;
    max-height: calc(100vh - 48px) !important;
    border-radius: 0.875rem !important;
    padding: 0 !important;
    overflow: hidden !important;
  }

  .incident-review-popup .swal2-html-container {
    max-height: calc(100vh - 48px) !important;
    margin: 0 !important;
    overflow-y: auto !important;
    padding: 0 !important;
    text-align: left !important;
  }

  .incident-review-modal {
    color: var(--bs-body-color);
    padding: 1.5rem;
    min-height: min(620px, calc(100vh - 48px));
  }

  .incident-review-header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 1px solid var(--bs-border-color);
    padding-bottom: 1rem;
    margin-bottom: 1rem;
  }

  .incident-review-header h5 {
    margin: 0;
  }

  .incident-review-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
    justify-content: flex-end;
  }

  .incident-review-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem 1.25rem;
    margin-bottom: 1rem;
  }

  .incident-review-field {
    min-width: 0;
  }

  .incident-review-field span,
  .incident-review-notes label {
    color: var(--bs-secondary-color);
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
  }

  .incident-review-field strong,
  .incident-review-field p {
    margin: 0;
    overflow-wrap: anywhere;
  }

  .incident-review-field p {
    white-space: pre-wrap;
  }

  .incident-review-full {
    grid-column: 1 / -1;
  }

  .incident-review-notes {
    border-top: 1px solid var(--bs-border-color);
    padding-top: 1rem;
  }

  .incident-review-notes textarea {
    border-radius: 0.5rem;
    min-height: 130px;
    resize: vertical;
  }

  .incident-review-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.5rem;
    border-top: 1px solid var(--bs-border-color);
    margin-top: 1rem;
    padding-top: 1rem;
  }

  .incident-review-actions .btn {
    border-radius: 0.5rem;
    min-width: 104px;
  }

  .incident-table th:first-child,
  .incident-table td:first-child {
    width: 110px;
    min-width: 110px;
    max-width: 110px;
  }

  .incident-table td:first-child .small {
    white-space: normal;
    line-height: 1.2;
  }

  @media (max-width: 1199.98px) {
    .incident-summary-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .incident-toolbar {
      grid-template-columns: 1fr;
    }

    .incident-review-header,
    .incident-review-grid {
      display: grid;
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 575.98px) {
    .incident-summary-grid {
      grid-template-columns: 1fr;
    }

    .incident-review-modal {
      padding: 1rem;
    }

    .incident-review-actions .btn {
      flex: 1 1 100%;
    }
  }
</style>
