<?php
require_once "controllers/salary.controller.php";
require_once "models/salary.model.php";

$driverID = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
$salaryRows = $driverID > 0 ? ControllerSalary::ctrSalaryRows($driverID) : array();
$salaryStats = array(
  "records" => count($salaryRows),
  "pending" => 0,
  "paid" => 0,
  "cancelled" => 0,
  "gross" => 0,
  "net" => 0
);

foreach ($salaryRows as $row) {
  $status = strtolower((string) ($row["status"] ?? "pending"));
  if (isset($salaryStats[$status])) {
    $salaryStats[$status]++;
  }
  if ($status !== "cancelled") {
    $salaryStats["gross"] += (float) ($row["grossPay"] ?? 0);
    $salaryStats["net"] += (float) ($row["netPay"] ?? 0);
  }
}

function driverSalaryText($value, $fallback = "-") {
  $value = trim((string) $value);
  return htmlspecialchars($value !== "" ? $value : $fallback);
}

function driverSalaryDate($value) {
  if (!$value) {
    return "-";
  }

  $timestamp = strtotime($value);
  return $timestamp ? date("M d, Y", $timestamp) : $value;
}

function driverSalaryDateTime($value) {
  if (!$value) {
    return "-";
  }

  $timestamp = strtotime($value);
  return $timestamp ? date("M d, Y h:i A", $timestamp) : $value;
}

function driverSalaryMoney($value) {
  return "PHP " . number_format((float) $value, 2);
}

function driverSalaryStatusClass($status) {
  $status = strtolower((string) $status);
  if ($status === "paid") {
    return "bg-success-subtle text-success";
  }
  if ($status === "cancelled") {
    return "bg-secondary-subtle text-secondary";
  }
  return "bg-warning-subtle text-warning";
}
?>

<div class="driver-salary-page">
  <div class="card driver-salary-card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">My Salary</h5>
        <p class="text-muted small mb-0">Check your salary credited for each assigned trip.</p>
      </div>
      <span class="badge bg-primary-subtle text-primary fs-6">
        <i class="ri-money-dollar-circle-line me-1"></i> Driver Payroll
      </span>
    </div>

    <div class="card-body p-4">
      <div class="driver-salary-stats mb-4">
        <div class="driver-salary-stat">
          <span class="driver-salary-icon bg-primary-subtle text-primary"><i class="ri-file-list-3-line"></i></span>
          <div>
            <small>Salary Records</small>
            <strong><?php echo (int) $salaryStats["records"]; ?></strong>
          </div>
        </div>
        <div class="driver-salary-stat">
          <span class="driver-salary-icon bg-warning-subtle text-warning"><i class="ri-time-line"></i></span>
          <div>
            <small>Pending</small>
            <strong><?php echo (int) $salaryStats["pending"]; ?></strong>
          </div>
        </div>
        <div class="driver-salary-stat">
          <span class="driver-salary-icon bg-success-subtle text-success"><i class="ri-checkbox-circle-line"></i></span>
          <div>
            <small>Paid</small>
            <strong><?php echo (int) $salaryStats["paid"]; ?></strong>
          </div>
        </div>
        <div class="driver-salary-stat">
          <span class="driver-salary-icon bg-info-subtle text-info"><i class="ri-wallet-3-line"></i></span>
          <div>
            <small>Total Net Pay</small>
            <strong><?php echo driverSalaryMoney($salaryStats["net"]); ?></strong>
          </div>
        </div>
      </div>

      <?php if (empty($salaryRows)): ?>
        <div class="driver-salary-empty">
          <i class="ri-wallet-3-line"></i>
          <h6>No salary records yet</h6>
          <p class="text-muted mb-0">Salary will appear here once payroll is credited to your trips.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle driver-salary-table mb-0">
            <thead>
              <tr>
                <th>Trip</th>
                <th>Route / Booking</th>
                <th>Pay Period</th>
                <th>Role</th>
                <th class="text-end">Gross</th>
                <th class="text-end">Deductions</th>
                <th class="text-end">Net Pay</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($salaryRows as $salary): ?>
                <tr>
                  <td>
                    <?php if (!empty($salary["tripID"])): ?>
                      <strong>Trip #<?php echo (int) $salary["tripID"]; ?></strong>
                      <div class="small text-muted">Booking #<?php echo (int) ($salary["creditedBookingID"] ?? 0); ?></div>
                    <?php else: ?>
                      <strong>General Pay</strong>
                      <div class="small text-muted">Not linked to a trip</div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong><?php echo driverSalaryText($salary["pickupDescription"] ?? "", "Pickup not recorded"); ?></strong>
                    <div class="small text-muted">
                      To: <?php echo driverSalaryText($salary["destinationDescription"] ?? "", "Destination not recorded"); ?>
                    </div>
                    <?php if (!empty($salary["creditedDistanceKm"])): ?>
                      <div class="small text-muted"><?php echo number_format((float) $salary["creditedDistanceKm"], 2); ?> km credited</div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php echo driverSalaryDate($salary["payPeriodStart"] ?? ""); ?>
                    <div class="small text-muted">to <?php echo driverSalaryDate($salary["payPeriodEnd"] ?? ""); ?></div>
                    <?php if (!empty($salary["datePaid"])): ?>
                      <div class="small text-success">Paid <?php echo driverSalaryDateTime($salary["datePaid"]); ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-primary-subtle text-primary">
                      <?php echo driverSalaryText(ucfirst($salary["tripRole"] ?? ""), "Trip"); ?>
                    </span>
                    <div class="small text-muted"><?php echo driverSalaryText(ucfirst($salary["payType"] ?? "trip")); ?></div>
                  </td>
                  <td class="text-end"><?php echo driverSalaryMoney($salary["grossPay"] ?? 0); ?></td>
                  <td class="text-end text-muted"><?php echo driverSalaryMoney($salary["deductions"] ?? 0); ?></td>
                  <td class="text-end fw-semibold"><?php echo driverSalaryMoney($salary["netPay"] ?? 0); ?></td>
                  <td>
                    <span class="badge <?php echo driverSalaryStatusClass($salary["status"] ?? "pending"); ?>">
                      <?php echo driverSalaryText(($salary["status"] ?? "pending") === "pending" ? "Unpaid" : ucfirst($salary["status"] ?? "pending")); ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
  .driver-salary-page {
    max-width: 1500px;
    margin: 0 auto;
  }

  .driver-salary-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 12px 36px rgba(15, 23, 42, 0.08);
  }

  .driver-salary-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
  }

  .driver-salary-stat {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.9rem;
    padding: 1rem;
    background: var(--bs-body-bg);
  }

  .driver-salary-icon {
    width: 44px;
    height: 44px;
    border-radius: 0.8rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex: 0 0 auto;
  }

  .driver-salary-stat small {
    display: block;
    color: var(--bs-secondary-color);
    font-size: 0.78rem;
  }

  .driver-salary-stat strong {
    display: block;
    font-size: 1.25rem;
    line-height: 1.2;
  }

  .driver-salary-table th {
    color: var(--bs-secondary-color);
    font-size: 0.75rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
  }

  .driver-salary-table td {
    vertical-align: top;
  }

  .driver-salary-empty {
    border: 1px dashed var(--bs-border-color);
    border-radius: 1rem;
    padding: 3rem 1rem;
    text-align: center;
  }

  .driver-salary-empty i {
    display: inline-flex;
    width: 56px;
    height: 56px;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary);
    font-size: 1.6rem;
    margin-bottom: 1rem;
  }

  @media (max-width: 992px) {
    .driver-salary-stats {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 576px) {
    .driver-salary-stats {
      grid-template-columns: 1fr;
    }
  }
</style>
