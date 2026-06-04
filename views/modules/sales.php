<?php
require_once "controllers/sales.controller.php";
require_once "models/sales.model.php";

$filters = array(
  "dateFrom" => isset($_GET["dateFrom"]) ? preg_replace("/[^0-9\-]/", "", $_GET["dateFrom"]) : "",
  "dateTo" => isset($_GET["dateTo"]) ? preg_replace("/[^0-9\-]/", "", $_GET["dateTo"]) : "",
  "customerType" => isset($_GET["customerType"]) && in_array($_GET["customerType"], array("individual", "company"), true) ? $_GET["customerType"] : ""
);

$salesData = ControllerSales::ctrSalesDashboard($filters);
$summary = $salesData["summary"];
$salesRows = $salesData["salesRows"];
$expenseRows = $salesData["expenseRows"];
$monthlySeries = $salesData["monthlySeries"];
$hasExpenseTable = $salesData["hasExpenseTable"];

function salesMoney($value) {
  return "PHP " . number_format((float) $value, 2);
}

function salesText($value, $fallback = "-") {
  $value = trim((string) $value);
  return htmlspecialchars($value !== "" ? $value : $fallback);
}

function salesDate($value) {
  if (!$value) {
    return "-";
  }

  $timestamp = strtotime($value);
  return $timestamp ? date("M d, Y h:i A", $timestamp) : $value;
}
?>

<div class="sales-page">
  <div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Sales</h5>
        <p class="text-muted small mb-0">Track completed delivery sales with transparent gross, expenses, and net totals.</p>
      </div>
      <span class="badge bg-success-subtle text-success fs-6">
        <i class="ri-line-chart-line me-1"></i> Sales Transparency
      </span>
    </div>

    <div class="card-body p-4">
      <form class="sales-filter-panel mb-4" method="get">
        <input type="hidden" name="route" value="sales">
        <div class="sales-filter-grid">
          <div>
            <label class="form-label">Date From</label>
            <input type="date" class="form-control" name="dateFrom" value="<?php echo htmlspecialchars($filters["dateFrom"]); ?>">
          </div>
          <div>
            <label class="form-label">Date To</label>
            <input type="date" class="form-control" name="dateTo" value="<?php echo htmlspecialchars($filters["dateTo"]); ?>">
          </div>
          <div>
            <label class="form-label">Customer Type</label>
            <select class="form-select" name="customerType">
              <option value="">All customers</option>
              <option value="individual" <?php echo $filters["customerType"] === "individual" ? "selected" : ""; ?>>Individual</option>
              <option value="company" <?php echo $filters["customerType"] === "company" ? "selected" : ""; ?>>Company</option>
            </select>
          </div>
          <div class="sales-filter-actions">
            <button type="submit" class="btn btn-primary">
              <i class="ri-filter-3-line me-1"></i> Apply
            </button>
            <a href="sales" class="btn btn-light">
              <i class="ri-refresh-line me-1"></i> Clear
            </a>
          </div>
        </div>
      </form>

      <div class="sales-kpi-grid mb-4">
        <div class="sales-kpi-card">
          <span class="sales-kpi-icon bg-success-subtle text-success"><i class="ri-money-dollar-circle-line"></i></span>
          <div>
            <small>Gross Sales</small>
            <strong><?php echo salesMoney($summary["grossSales"]); ?></strong>
            <span>Completed delivery revenue</span>
          </div>
        </div>
        <div class="sales-kpi-card">
          <span class="sales-kpi-icon bg-danger-subtle text-danger"><i class="ri-wallet-3-line"></i></span>
          <div>
            <small>Expenses</small>
            <strong><?php echo salesMoney($summary["expenses"]); ?></strong>
            <span><?php echo $hasExpenseTable ? "Recorded deductions" : "No expense table found"; ?></span>
          </div>
        </div>
        <div class="sales-kpi-card">
          <span class="sales-kpi-icon bg-primary-subtle text-primary"><i class="ri-scales-3-line"></i></span>
          <div>
            <small>Net Sales</small>
            <strong><?php echo salesMoney($summary["netSales"]); ?></strong>
            <span>Gross minus expenses</span>
          </div>
        </div>
        <div class="sales-kpi-card">
          <span class="sales-kpi-icon bg-info-subtle text-info"><i class="ri-file-list-3-line"></i></span>
          <div>
            <small>Completed Bookings</small>
            <strong><?php echo (int) $summary["completedBookings"]; ?></strong>
            <span><?php echo (int) $summary["pendingBookings"]; ?> active bookings excluded</span>
          </div>
        </div>
      </div>

      <div class="sales-layout mb-4">
        <section class="sales-panel">
          <div class="sales-panel-heading">
            <div>
              <h6 class="mb-0">Sales Trend</h6>
              <p class="text-muted small mb-0">Gross, expenses, and net by month.</p>
            </div>
          </div>
          <div class="sales-chart-wrap">
            <canvas id="salesChart"></canvas>
          </div>
        </section>

        <section class="sales-panel">
          <div class="sales-panel-heading">
            <div>
              <h6 class="mb-0">Net Transparency</h6>
              <p class="text-muted small mb-0">How the net total is computed.</p>
            </div>
          </div>
          <div class="sales-breakdown">
            <div>
              <span>Gross Sales</span>
              <strong><?php echo salesMoney($summary["grossSales"]); ?></strong>
            </div>
            <div>
              <span>Less: Expenses</span>
              <strong>- <?php echo salesMoney($summary["expenses"]); ?></strong>
            </div>
            <div class="sales-breakdown-total">
              <span>Net Sales</span>
              <strong><?php echo salesMoney($summary["netSales"]); ?></strong>
            </div>
          </div>
          <div class="sales-split-grid mt-3">
            <div>
              <small>Company Sales</small>
              <strong><?php echo salesMoney($summary["companySales"]); ?></strong>
            </div>
            <div>
              <small>Individual Sales</small>
              <strong><?php echo salesMoney($summary["individualSales"]); ?></strong>
            </div>
          </div>
          <?php if (!$hasExpenseTable): ?>
            <div class="alert alert-warning small mb-0 mt-3">
              Expense deductions are shown as PHP 0.00 because no expense table exists yet. Once an `expenses` or `expense` table with an amount and date column exists, it will be included automatically.
            </div>
          <?php endif; ?>
        </section>
      </div>

      <div class="sales-panel mb-4">
        <div class="sales-panel-heading mb-3">
          <div>
            <h6 class="mb-0">Completed Booking Sales</h6>
            <p class="text-muted small mb-0">Only successful deliveries are counted as sales.</p>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table align-middle sales-table">
            <thead>
              <tr>
                <th>Booking</th>
                <th>Customer</th>
                <th>Trip</th>
                <th>Delivery Date</th>
                <th>Status</th>
                <th class="text-end">Gross Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($salesRows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No completed sales found for the selected filters.</td></tr>
              <?php endif; ?>
              <?php foreach ($salesRows as $row): ?>
                <tr>
                  <td>#<?php echo (int) $row["bookingID"]; ?></td>
                  <td>
                    <strong><?php echo salesText($row["customerName"], "Customer"); ?></strong>
                    <div class="small text-muted"><?php echo salesText(ucfirst($row["customerType"])); ?></div>
                  </td>
                  <td>Trip #<?php echo (int) $row["tripID"]; ?></td>
                  <td><?php echo htmlspecialchars(salesDate($row["pickupDateTime"])); ?></td>
                  <td><span class="badge bg-success-subtle text-success"><?php echo salesText(ucfirst($row["status"])); ?></span></td>
                  <td class="text-end fw-semibold"><?php echo salesMoney($row["price"]); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="sales-panel">
        <div class="sales-panel-heading mb-3">
          <div>
            <h6 class="mb-0">Expense Deductions</h6>
            <p class="text-muted small mb-0">These records reduce gross sales to net sales.</p>
          </div>
        </div>
        <?php if (empty($expenseRows)): ?>
          <div class="sales-empty">No expense deductions found for this period.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle sales-table">
              <thead>
                <tr>
                  <th>Record</th>
                  <th>Date</th>
                  <th>Category</th>
                  <th>Description</th>
                  <th>Status</th>
                  <th class="text-end">Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($expenseRows as $row): ?>
                  <tr>
                    <td>#<?php echo salesText($row["recordID"]); ?></td>
                    <td><?php echo htmlspecialchars(salesDate($row["recordDate"])); ?></td>
                    <td><?php echo salesText($row["category"]); ?></td>
                    <td><?php echo salesText($row["description"]); ?></td>
                    <td><span class="badge bg-secondary-subtle text-secondary"><?php echo salesText($row["status"]); ?></span></td>
                    <td class="text-end fw-semibold"><?php echo salesMoney($row["amount"]); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="views/assets/libs/chart.js/chart.umd.js"></script>
<script>
  window.salesChartData = <?php echo json_encode($monthlySeries, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<style>
  .sales-page {
    max-width: 1480px;
    margin: 0 auto;
  }

  .sales-filter-panel,
  .sales-panel,
  .sales-kpi-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    background: var(--bs-body-bg);
  }

  .sales-filter-panel,
  .sales-panel {
    padding: 1rem;
  }

  .sales-filter-grid {
    display: grid;
    grid-template-columns: minmax(170px, 0.75fr) minmax(170px, 0.75fr) minmax(190px, 0.9fr) minmax(210px, auto);
    align-items: end;
    gap: 1rem;
  }

  .sales-filter-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  .sales-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
  }

  .sales-kpi-card {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 1rem;
    min-width: 0;
  }

  .sales-kpi-icon {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 44px;
    border-radius: 0.5rem;
    font-size: 1.35rem;
    line-height: 1;
    position: relative;
  }

  .sales-kpi-icon i {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    margin: 0;
    text-align: center;
  }

  .sales-kpi-icon i::before {
    display: block;
    line-height: 1;
  }

  .sales-kpi-card small,
  .sales-kpi-card span {
    display: block;
    color: var(--bs-secondary-color);
  }

  .sales-kpi-card strong {
    display: block;
    font-size: 1.35rem;
    line-height: 1.2;
  }

  .sales-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.75fr);
    gap: 1rem;
  }

  .sales-panel-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
  }

  .sales-chart-wrap {
    position: relative;
    min-height: 320px;
    margin-top: 1rem;
  }

  .sales-breakdown {
    display: grid;
    gap: 0.75rem;
    margin-top: 1rem;
  }

  .sales-breakdown > div,
  .sales-split-grid > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 0.75rem;
  }

  .sales-breakdown-total {
    border-color: var(--bs-primary) !important;
    background: var(--bs-primary-bg-subtle);
  }

  .sales-split-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .sales-split-grid small {
    color: var(--bs-secondary-color);
  }

  .sales-table {
    min-width: 820px;
  }

  .sales-empty {
    border: 1px dashed var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
    color: var(--bs-secondary-color);
    background: var(--bs-tertiary-bg);
  }

  @media (max-width: 1199.98px) {
    .sales-kpi-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sales-layout,
    .sales-filter-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 575.98px) {
    .sales-kpi-grid {
      grid-template-columns: 1fr;
    }

    .sales-filter-actions .btn {
      width: 100%;
    }
  }
</style>
