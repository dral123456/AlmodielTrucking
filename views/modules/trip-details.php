<?php
require_once "controllers/booking.controller.php";
require_once "models/booking.model.php";

$role = $_SESSION["role"] ?? "";
$employeeID = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
$trips = ControllerBooking::ctrTripOverviewList($employeeID, $role);
$trucks = ControllerBooking::ctrTruckList();
$drivers = ControllerBooking::ctrEmployeeListByType("driver");
$assistants = ControllerBooking::ctrEmployeeListByType("assistant");
$canModifyTrips = $role === "admin";
$canUpdateTripStatus = $role === "driver";
$tripStats = array(
  "total" => count($trips),
  "pending" => 0,
  "stopover" => 0,
  "in-transit" => 0,
  "completed" => 0
);

foreach ($trips as $trip) {
  if (isset($tripStats[$trip["status"]])) {
    $tripStats[$trip["status"]]++;
  }
}
?>

<section class="trip-detail-panel">
  <div id="tripDetails" class="trip-detail-shell">
    <div class="text-muted text-center p-4">Select a trip to view details.</div>
  </div>
</section>
<script>
  window.tripOverviewData = <?php echo json_encode($trips, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  window.tripTruckOptions = <?php echo json_encode($trucks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  window.tripDriverOptions = <?php echo json_encode($drivers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  window.tripAssistantOptions = <?php echo json_encode($assistants, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  window.tripCanModifyInfo = <?php echo $canModifyTrips ? "true" : "false"; ?>;
  window.tripCanUpdateStatus = <?php echo $canUpdateTripStatus ? "true" : "false"; ?>;
</script>
<style>
  .trip-page {
    max-width: 1480px;
    margin: 0 auto;
    width: 100%;
  }

  .trip-stat-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.75rem;
  }

  .trip-stat-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    padding: 0.875rem;
    text-align: left;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }

  .trip-stat-card.active,
  .trip-stat-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.10);
  }

  .trip-stat-icon {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 40px;
    border-radius: 0.5rem;
    font-size: 1.2rem;
  }

  .trip-stat-card small {
    display: block;
    color: var(--bs-secondary-color);
    line-height: 1.2;
  }

  .trip-stat-card strong {
    display: block;
    font-size: 1.25rem;
    line-height: 1.15;
  }

  .trip-filter-panel,
  .trip-list-panel,
  .trip-detail-shell {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    background: var(--bs-body-bg);
  }

  .trip-filter-panel,
  .trip-list-panel,
  .trip-detail-shell {
    padding: 1rem;
  }

  .trip-filter-grid {
    display: grid;
    grid-template-columns: minmax(170px, 0.8fr) minmax(150px, 0.7fr) minmax(170px, 0.75fr) minmax(260px, 1.2fr) minmax(150px, 180px);
    align-items: start;
    gap: 1rem;
  }

  .trip-filter-action {
    align-self: start;
    padding-top: 1.85rem;
  }

  .trip-workspace {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 1rem;
  }

  .trip-list-panel,
  .trip-detail-panel {
    min-width: 0;
  }

  .trip-panel-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
  }

  .trip-table th {
    color: var(--bs-secondary-color);
    font-size: 0.8125rem;
    text-transform: uppercase;
    white-space: nowrap;
  }

  .trip-row {
    cursor: pointer;
  }

  .trip-row:hover {
    background: var(--bs-tertiary-bg);
  }

  .trip-row.active {
    background: var(--bs-primary-bg-subtle);
  }

  .trip-row-main {
    font-weight: 700;
    white-space: nowrap;
  }

  .trip-row-sub {
    color: var(--bs-secondary-color);
    font-size: 0.8125rem;
    max-width: 360px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .trip-booking-row {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 0.875rem;
    background: var(--bs-body-bg);
  }

  .trip-booking-locations {
    display: grid;
    gap: 0.25rem;
    margin-top: 0.375rem;
  }

  .air-datepicker-cell.-trip-has-booking- {
    font-weight: 700;
  }

  .trip-calendar-day {
    position: relative;
    width: 100%;
    height: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .trip-calendar-day i {
    position: absolute;
    bottom: 4px;
    left: 50%;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    transform: translateX(-50%);
    background: var(--bs-primary);
  }

  .air-datepicker-cell.-selected- .trip-calendar-day i,
  .air-datepicker-cell.-range-from- .trip-calendar-day i,
  .air-datepicker-cell.-range-to- .trip-calendar-day i {
    background: #fff;
  }

  .trip-detail-shell {
    padding: 1rem;
  }

  .trip-detail-grid {
    display: grid;
    grid-template-columns: minmax(320px, 0.8fr) minmax(0, 1.2fr);
    gap: 1rem;
    align-items: start;
  }

  .trip-booking-list {
    display: grid;
    gap: 0.75rem;
  }

  .trip-map-shell {
    min-width: 0;
  }

  #tripMap {
    width: 100%;
    height: min(64vh, 660px);
    min-height: 520px;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    overflow: hidden;
    background: #dbeafe;
  }

  .trip-edit-modal {
    width: calc(100vw - 1rem) !important;
    max-width: calc(100vw - 1rem) !important;
    height: calc(100vh - 1rem) !important;
    max-height: calc(100vh - 1rem) !important;
    padding: 1.25rem !important;
    display: flex !important;
    flex-direction: column;
  }

  .trip-edit-modal .swal2-title {
    margin: 0 0 0.75rem;
    font-size: clamp(1.3rem, 1.8vw, 1.65rem);
    flex: 0 0 auto;
  }

  .trip-edit-modal .swal2-html-container {
    flex: 1 1 auto !important;
    min-height: 0;
    margin: 0;
    width: 100%;
    height: calc(100vh - 150px) !important;
    max-height: calc(100vh - 150px) !important;
    overflow: hidden;
    padding: 0;
  }

  .trip-edit-modal .swal2-actions {
    flex: 0 0 auto;
    margin: 0.875rem 0 0;
  }

  .trip-edit-modal .swal2-confirm,
  .trip-edit-modal .swal2-cancel {
    min-width: 132px;
    min-height: 46px;
    border-radius: 0.65rem;
  }

  .trip-edit-shell {
    color: var(--bs-body-color);
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    gap: 0.75rem;
    height: 100% !important;
    min-height: 0;
  }

  .trip-edit-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    color: var(--bs-secondary-color);
    font-size: 0.8125rem;
  }

  .trip-edit-summary span {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 999px;
    background: var(--bs-tertiary-bg);
    padding: 0.32rem 0.625rem;
  }

  .trip-edit-grid {
    display: grid;
    grid-template-columns: minmax(500px, 0.9fr) minmax(680px, 1.1fr);
    gap: 1rem;
    align-items: stretch;
    height: 100% !important;
    min-height: 0;
  }

  .trip-edit-main {
    height: 100% !important;
    min-height: 0;
    overflow: hidden;
  }

  .trip-edit-form {
    display: grid;
    align-content: start;
    gap: 0.75rem;
    min-height: 0;
    overflow: auto;
    padding-right: 0;
  }

  .trip-edit-card,
  .trip-edit-map-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.65rem;
    background: var(--bs-body-bg);
    padding: 0.875rem;
  }

  .trip-edit-primary-card {
    border-color: rgba(105, 108, 255, 0.45);
    box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.08);
  }

  .trip-edit-card-title,
  .trip-edit-map-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.625rem;
    margin-bottom: 0.75rem;
  }

  .trip-edit-card-title {
    justify-content: flex-start;
  }

  .trip-edit-card-title span {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 28px;
    border-radius: 50%;
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary);
    font-weight: 700;
  }

  .trip-edit-card-title small,
  .trip-edit-map-heading small {
    display: block;
    color: var(--bs-secondary-color);
    font-size: 0.78rem;
  }

  .trip-edit-map-card {
    min-height: 0;
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .trip-edit-map-heading {
    align-items: flex-start;
  }

  .trip-edit-map-heading .badge {
    white-space: nowrap;
    margin-top: 0.125rem;
  }

  #editTripDestinationMap {
    width: 100%;
    flex: 1 1 auto;
    height: 100% !important;
    min-height: 520px;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.75rem;
    overflow: hidden;
    background: #dbeafe;
  }

  .trip-edit-map-help {
    margin-top: 0.625rem;
    color: var(--bs-secondary-color);
    font-size: 0.875rem;
  }

  .trip-edit-details {
    padding: 0;
  }

  .trip-edit-details summary {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.875rem;
    cursor: pointer;
    list-style: none;
  }

  .trip-edit-details summary::-webkit-details-marker {
    display: none;
  }

  .trip-edit-details summary > span {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 28px;
    border-radius: 50%;
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary);
    font-weight: 700;
  }

  .trip-edit-details summary > div {
    flex: 1 1 auto;
  }

  .trip-edit-details summary small {
    display: block;
    color: var(--bs-secondary-color);
    font-size: 0.8125rem;
  }

  .trip-edit-details summary i {
    transition: transform 0.15s ease;
  }

  .trip-edit-details[open] summary i {
    transform: rotate(180deg);
  }

  .trip-edit-details .row {
    padding: 0 0.875rem 0.875rem;
  }

  .trip-edit-modal .form-label {
    margin-bottom: 0.3rem;
    font-weight: 600;
  }

  .trip-edit-modal .form-control,
  .trip-edit-modal .form-select {
    min-height: 42px;
  }

  .trip-edit-modal textarea.form-control {
    min-height: 70px;
  }

  @media (max-width: 1399.98px) {
    .trip-stat-grid {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
  }

  @media (max-width: 1199.98px) {
    .trip-filter-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .trip-filter-action {
      padding-top: 0;
      align-self: end;
    }

    .trip-detail-grid {
      grid-template-columns: 1fr;
    }

    #tripMap {
      height: 460px;
      min-height: 460px;
    }

    .trip-edit-grid {
      grid-template-columns: 1fr;
    }

    .trip-edit-main {
      height: auto;
      overflow: auto;
    }

    #editTripDestinationMap {
      height: 560px;
      min-height: 560px;
    }
  }

  @media (max-width: 767.98px) {
    .trip-stat-grid,
    .trip-filter-grid {
      grid-template-columns: 1fr;
    }

    .trip-filter-action {
      align-self: stretch;
    }

    #tripMap {
      height: 360px;
      min-height: 360px;
    }

    #editTripDestinationMap {
      height: 340px;
      flex: 0 0 340px;
      min-height: 340px;
    }

    .trip-edit-summary {
      justify-content: flex-start;
    }
  }

  @media (max-width: 575.98px) {
    .trip-list-panel,
    .trip-detail-shell,
    .trip-filter-panel {
      padding: 0.875rem;
    }
  }
</style>
