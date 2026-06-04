<?php
require_once "connection.php";

class ModelSales {
  static public function mdlSalesDashboard($filters) {
    $pdo = (new Connection)->connect();
    $salesMeta = self::resolveSalesTable($pdo);
    $where = self::salesWhere($filters, "b");
    $bindings = self::salesBindings($filters);
    $expenseMeta = self::resolveExpenseTable($pdo);

    if ($salesMeta) {
      self::mdlSyncCompletedSales($pdo);
      return self::salesDashboardFromSalesTable($pdo, $salesMeta, $expenseMeta, $filters);
    }

    $summary = self::salesSummary($pdo, $where, $bindings, $expenseMeta, $filters);

    return array(
      "summary" => $summary,
      "salesRows" => self::salesRows($pdo, $where, $bindings),
      "expenseRows" => self::expenseRows($pdo, $expenseMeta, $filters),
      "monthlySeries" => self::monthlySeries($pdo, $expenseMeta, $filters),
      "hasExpenseTable" => $expenseMeta !== null,
      "usesSalesTable" => false
    );
  }

  static public function mdlSyncSalesForTrip($pdo, $tripID) {
    $salesMeta = self::resolveSalesTable($pdo);

    if (!$salesMeta || !isset($salesMeta["columns"]["bookingID"])) {
      return "no-sales-table";
    }

    $stmt = $pdo->prepare("
      SELECT
        b.bookingID,
        b.tripID,
        b.customerID,
        b.price,
        c.customerType
      FROM booking b
      LEFT JOIN customer c ON c.id = b.customerID
      WHERE b.tripID = :tripID
        AND " . self::successfulStatusSql("b") . "
    ");
    $stmt->bindParam(":tripID", $tripID, PDO::PARAM_INT);
    $stmt->execute();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $booking) {
      self::upsertSalesRecord($pdo, $salesMeta, $booking);
    }

    return "success";
  }

  static public function mdlSyncCompletedSales($pdo = null) {
    $pdo = $pdo ?: (new Connection)->connect();
    $salesMeta = self::resolveSalesTable($pdo);

    if (!$salesMeta || !isset($salesMeta["columns"]["bookingID"])) {
      return "no-sales-table";
    }

    $stmt = $pdo->prepare("
      SELECT
        b.bookingID,
        b.tripID,
        b.customerID,
        b.price,
        c.customerType
      FROM booking b
      LEFT JOIN customer c ON c.id = b.customerID
      WHERE " . self::successfulStatusSql("b") . "
    ");
    $stmt->execute();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $booking) {
      self::upsertSalesRecord($pdo, $salesMeta, $booking);
    }

    return "success";
  }

  static public function mdlMarkSalesAsPaid($bookingID) {
    $bookingID = (int) $bookingID;

    if ($bookingID <= 0) {
      return "invalid";
    }

    $pdo = (new Connection)->connect();
    $salesMeta = self::resolveSalesTable($pdo);

    if (!$salesMeta || !isset($salesMeta["columns"]["salesID"], $salesMeta["columns"]["bookingID"])) {
      return "no-sales-table";
    }

    $existing = self::existingSalesRecord($pdo, $salesMeta, $bookingID);

    if (!$existing) {
      $bookingStmt = $pdo->prepare("
        SELECT
          b.bookingID,
          b.tripID,
          b.customerID,
          b.price,
          c.customerType
        FROM booking b
        LEFT JOIN customer c ON c.id = b.customerID
        WHERE b.bookingID = :bookingID
          AND " . self::successfulStatusSql("b") . "
        LIMIT 1
      ");
      $bookingStmt->bindParam(":bookingID", $bookingID, PDO::PARAM_INT);
      $bookingStmt->execute();
      $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);

      if (!$booking) {
        return "not-found";
      }

      self::upsertSalesRecord($pdo, $salesMeta, $booking);
      $existing = self::existingSalesRecord($pdo, $salesMeta, $bookingID);
    }

    if (!$existing) {
      return "not-found";
    }

    $paidAmount = self::salesPaidAmountFromRecord($salesMeta, $existing);
    $updates = array();
    $bindings = array(":salesID" => $existing["salesID"]);

    if ($salesMeta["paidColumn"]) {
      $updates[] = self::quoteIdentifier($salesMeta["paidColumn"]) . " = :paidAmount";
      $bindings[":paidAmount"] = $paidAmount;
    }

    if ($salesMeta["balanceColumn"]) {
      $updates[] = self::quoteIdentifier($salesMeta["balanceColumn"]) . " = 0";
    }

    foreach (array("paymentStatus", "status") as $statusColumn) {
      if (isset($salesMeta["columns"][$statusColumn])) {
        $updates[] = self::quoteIdentifier($statusColumn) . " = 'paid'";
      }
    }

    if (isset($salesMeta["columns"]["salesStatus"])) {
      $updates[] = "`salesStatus` = 'paid'";
    }

    if (isset($salesMeta["columns"]["datePaid"])) {
      $updates[] = "`datePaid` = NOW()";
    }

    if (empty($updates)) {
      return "invalid";
    }

    $stmt = $pdo->prepare("
      UPDATE " . self::quoteIdentifier($salesMeta["table"]) . "
      SET " . implode(", ", array_unique($updates)) . "
      WHERE salesID = :salesID
    ");
    self::bindMixedValues($stmt, $bindings);
    $stmt->execute();

    return "success";
  }

  static public function mdlMarkSalesGroupAsPaid($filters) {
    $dateFrom = trim((string) ($filters["dateFrom"] ?? ""));
    $dateTo = trim((string) ($filters["dateTo"] ?? ""));

    if ($dateFrom === "" || $dateTo === "") {
      return array("status" => "missing-range", "paidCount" => 0, "totalCount" => 0);
    }

    if ($dateFrom > $dateTo) {
      $dateTemp = $dateFrom;
      $dateFrom = $dateTo;
      $dateTo = $dateTemp;
    }

    $filters["dateFrom"] = $dateFrom;
    $filters["dateTo"] = $dateTo;
    $pdo = (new Connection)->connect();
    $salesMeta = self::resolveSalesTable($pdo);

    if (!$salesMeta || !isset($salesMeta["columns"]["salesID"], $salesMeta["columns"]["bookingID"])) {
      return array("status" => "no-sales-table", "paidCount" => 0, "totalCount" => 0);
    }

    self::mdlSyncCompletedSales($pdo);

    $where = self::salesTableWhere($filters, $salesMeta);
    $bindings = self::salesTableBindings($filters);
    $statusExpr = self::salesStatusExpression($salesMeta, "s");
    $stmt = $pdo->prepare("
      SELECT
        s.bookingID,
        {$statusExpr} AS status
      FROM " . self::quoteIdentifier($salesMeta["table"]) . " s
      LEFT JOIN booking b ON b.bookingID = s.bookingID
      LEFT JOIN customer c ON c.id = COALESCE(" . (isset($salesMeta["columns"]["customerID"]) ? "s.customerID" : "NULL") . ", b.customerID)
      WHERE {$where}
      ORDER BY s.bookingID ASC
    ");
    self::bindValues($stmt, $bindings);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $paidCount = 0;

    foreach ($rows as $row) {
      if (strtolower((string) ($row["status"] ?? "")) === "paid") {
        continue;
      }

      if (self::mdlMarkSalesAsPaid((int) $row["bookingID"]) === "success") {
        $paidCount++;
      }
    }

    if (empty($rows)) {
      return array("status" => "not-found", "paidCount" => 0, "totalCount" => 0);
    }

    return array(
      "status" => $paidCount > 0 ? "success" : "already-paid",
      "paidCount" => $paidCount,
      "totalCount" => count($rows)
    );
  }

  static private function salesDashboardFromSalesTable($pdo, $salesMeta, $expenseMeta, $filters) {
    $where = self::salesTableWhere($filters, $salesMeta);
    $bindings = self::salesTableBindings($filters);
    $summary = self::salesTableSummary($pdo, $salesMeta, $where, $bindings);
    $expenses = ($expenseMeta ? self::expenseTotal($pdo, $expenseMeta, $filters) : 0) + self::completedCrewSalaryExpenseTotal($pdo, $filters);
    $summary["expenses"] = (float) $expenses;
    $summary["netSales"] = (float) $summary["grossSales"] - (float) $expenses;

    return array(
      "summary" => $summary,
      "salesRows" => self::salesTableRows($pdo, $salesMeta, $where, $bindings),
      "expenseRows" => self::expenseRows($pdo, $expenseMeta, $filters),
      "monthlySeries" => self::monthlySeriesFromSalesTable($pdo, $salesMeta, $expenseMeta, $filters),
      "hasExpenseTable" => $expenseMeta !== null,
      "usesSalesTable" => true
    );
  }

  static private function salesTableSummary($pdo, $salesMeta, $where, $bindings) {
    $grossExpr = self::salesGrossExpression($salesMeta, "s");
    $expenseExpr = self::salesExpenseExpression($salesMeta, "s");
    $netExpr = self::salesNetExpression($salesMeta, "s");
    $customerTypeExpr = isset($salesMeta["columns"]["customerType"]) ? "`s`.`customerType`" : "`c`.`customerType`";

    $stmt = $pdo->prepare("
      SELECT
        COALESCE(SUM({$grossExpr}), 0) AS grossSales,
        COALESCE(SUM({$expenseExpr}), 0) AS expenses,
        COALESCE(SUM({$netExpr}), 0) AS netSales,
        COUNT(*) AS completedBookings,
        COALESCE(SUM(CASE WHEN {$customerTypeExpr} = 'company' THEN {$grossExpr} ELSE 0 END), 0) AS companySales,
        COALESCE(SUM(CASE WHEN {$customerTypeExpr} = 'individual' THEN {$grossExpr} ELSE 0 END), 0) AS individualSales
      FROM " . self::quoteIdentifier($salesMeta["table"]) . " s
      LEFT JOIN booking b ON b.bookingID = s.bookingID
      LEFT JOIN customer c ON c.id = COALESCE(" . (isset($salesMeta["columns"]["customerID"]) ? "s.customerID" : "NULL") . ", b.customerID)
      WHERE {$where}
    ");

    self::bindValues($stmt, $bindings);
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    return array(
      "grossSales" => (float) ($summary["grossSales"] ?? 0),
      "expenses" => (float) ($summary["expenses"] ?? 0),
      "netSales" => (float) ($summary["netSales"] ?? 0),
      "completedBookings" => (int) ($summary["completedBookings"] ?? 0),
      "companySales" => (float) ($summary["companySales"] ?? 0),
      "individualSales" => (float) ($summary["individualSales"] ?? 0),
      "pendingBookings" => (int) self::scalar($pdo, "SELECT COUNT(*) FROM booking WHERE status IN ('pending', 'in-transit', 'stopover')", array())
    );
  }

  static private function salesTableRows($pdo, $salesMeta, $where, $bindings) {
    $grossExpr = self::salesGrossExpression($salesMeta, "s");
    $netExpr = self::salesNetExpression($salesMeta, "s");
    $paidExpr = $salesMeta["paidColumn"] ? self::quoteIdentifier("s") . "." . self::quoteIdentifier($salesMeta["paidColumn"]) : "0";
    $balanceExpr = $salesMeta["balanceColumn"] ? self::quoteIdentifier("s") . "." . self::quoteIdentifier($salesMeta["balanceColumn"]) : "({$netExpr} - {$paidExpr})";
    $dateExpr = self::salesDisplayDateExpression($salesMeta, "s");
    $statusExpr = self::salesStatusExpression($salesMeta, "s");
    $customerTypeExpr = isset($salesMeta["columns"]["customerType"]) ? "`s`.`customerType`" : "`c`.`customerType`";

    $stmt = $pdo->prepare("
      SELECT
        s.bookingID,
        COALESCE(" . (isset($salesMeta["columns"]["tripID"]) ? "s.tripID" : "NULL") . ", b.tripID) AS tripID,
        COALESCE(b.pickupDateTime, {$dateExpr}) AS pickupDateTime,
        {$dateExpr} AS dateCreated,
        {$grossExpr} AS price,
        {$netExpr} AS netAmount,
        {$paidExpr} AS paidAmount,
        {$balanceExpr} AS balanceAmount,
        {$statusExpr} AS status,
        {$customerTypeExpr} AS customerType,
        COALESCE(NULLIF(TRIM(b.storeName), ''), NULLIF(TRIM(CONCAT(c.customerFName, ' ', c.customerLName)), ''), c.contactPerson, 'Customer') AS customerName
      FROM " . self::quoteIdentifier($salesMeta["table"]) . " s
      LEFT JOIN booking b ON b.bookingID = s.bookingID
      LEFT JOIN customer c ON c.id = COALESCE(" . (isset($salesMeta["columns"]["customerID"]) ? "s.customerID" : "NULL") . ", b.customerID)
      WHERE {$where}
      ORDER BY {$dateExpr} DESC, s.bookingID DESC
      LIMIT 100
    ");

    self::bindValues($stmt, $bindings);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static private function monthlySeriesFromSalesTable($pdo, $salesMeta, $expenseMeta, $filters) {
    $dateExpr = self::salesDisplayDateExpression($salesMeta, "s");
    $grossExpr = self::salesGrossExpression($salesMeta, "s");
    $periodExpr = self::salesTrendPeriodExpression($dateExpr, $filters);
    $where = self::salesTableWhere($filters, $salesMeta);
    $bindings = self::salesTableBindings($filters);

    $stmt = $pdo->prepare("
      SELECT
        {$periodExpr} AS periodKey,
        COALESCE(SUM({$grossExpr}), 0) AS grossSales
      FROM " . self::quoteIdentifier($salesMeta["table"]) . " s
      LEFT JOIN booking b ON b.bookingID = s.bookingID
      LEFT JOIN customer c ON c.id = COALESCE(" . (isset($salesMeta["columns"]["customerID"]) ? "s.customerID" : "NULL") . ", b.customerID)
      WHERE {$where}
      GROUP BY {$periodExpr}
      ORDER BY periodKey ASC
    ");
    self::bindValues($stmt, $bindings);
    $stmt->execute();

    $months = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $months[$row["periodKey"]] = array(
        "label" => self::salesTrendLabel($row["periodKey"], $filters),
        "gross" => (float) $row["grossSales"],
        "expenses" => 0
      );
    }

    self::addExpenseMonths($pdo, $months, $expenseMeta, $filters);
    self::addCrewSalaryExpenseMonths($pdo, $months, $filters);

    ksort($months);
    $months = array_slice($months, -12, 12, true);

    $labels = array();
    $gross = array();
    $expenses = array();
    $net = array();

    foreach ($months as $month) {
      $labels[] = $month["label"];
      $gross[] = $month["gross"];
      $expenses[] = $month["expenses"];
      $net[] = $month["gross"] - $month["expenses"];
    }

    return array(
      "labels" => $labels,
      "gross" => $gross,
      "expenses" => $expenses,
      "net" => $net
    );
  }

  static private function salesSummary($pdo, $where, $bindings, $expenseMeta, $filters) {
    $stmt = $pdo->prepare("
      SELECT
        COALESCE(SUM(b.price), 0) AS grossSales,
        COUNT(*) AS completedBookings,
        COALESCE(SUM(CASE WHEN c.customerType = 'company' THEN b.price ELSE 0 END), 0) AS companySales,
        COALESCE(SUM(CASE WHEN c.customerType = 'individual' THEN b.price ELSE 0 END), 0) AS individualSales
      FROM booking b
      LEFT JOIN customer c ON c.id = b.customerID
      WHERE {$where}
    ");

    self::bindValues($stmt, $bindings);
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    $expenses = ($expenseMeta ? self::expenseTotal($pdo, $expenseMeta, $filters) : 0) + self::completedCrewSalaryExpenseTotal($pdo, $filters);
    $gross = (float) ($summary["grossSales"] ?? 0);

    return array(
      "grossSales" => $gross,
      "expenses" => (float) $expenses,
      "netSales" => $gross - (float) $expenses,
      "completedBookings" => (int) ($summary["completedBookings"] ?? 0),
      "companySales" => (float) ($summary["companySales"] ?? 0),
      "individualSales" => (float) ($summary["individualSales"] ?? 0),
      "pendingBookings" => (int) self::scalar($pdo, "SELECT COUNT(*) FROM booking WHERE status IN ('pending', 'in-transit', 'stopover')", array())
    );
  }

  static private function salesRows($pdo, $where, $bindings) {
    $stmt = $pdo->prepare("
      SELECT
        b.bookingID,
        b.tripID,
        b.pickupDateTime,
        b.dateCreated,
        b.price,
        b.status,
        c.customerType,
        COALESCE(NULLIF(TRIM(b.storeName), ''), NULLIF(TRIM(CONCAT(c.customerFName, ' ', c.customerLName)), ''), c.contactPerson, 'Customer') AS customerName
      FROM booking b
      LEFT JOIN customer c ON c.id = b.customerID
      WHERE {$where}
      ORDER BY b.pickupDateTime DESC, b.bookingID DESC
      LIMIT 100
    ");

    self::bindValues($stmt, $bindings);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static private function monthlySeries($pdo, $expenseMeta, $filters) {
    $where = self::salesWhere($filters, "b");
    $bindings = self::salesBindings($filters);
    $periodExpr = self::salesTrendPeriodExpression("b.pickupDateTime", $filters);

    $salesStmt = $pdo->prepare("
      SELECT
        {$periodExpr} AS periodKey,
        COALESCE(SUM(b.price), 0) AS grossSales
      FROM booking b
      LEFT JOIN customer c ON c.id = b.customerID
      WHERE {$where}
      GROUP BY {$periodExpr}
      ORDER BY periodKey ASC
    ");
    self::bindValues($salesStmt, $bindings);
    $salesStmt->execute();

    $months = array();
    foreach ($salesStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $months[$row["periodKey"]] = array(
        "label" => self::salesTrendLabel($row["periodKey"], $filters),
        "gross" => (float) $row["grossSales"],
        "expenses" => 0
      );
    }

    self::addExpenseMonths($pdo, $months, $expenseMeta, $filters);
    self::addCrewSalaryExpenseMonths($pdo, $months, $filters);

    ksort($months);
    $months = array_slice($months, -12, 12, true);

    $labels = array();
    $gross = array();
    $expenses = array();
    $net = array();

    foreach ($months as $month) {
      $labels[] = $month["label"];
      $gross[] = $month["gross"];
      $expenses[] = $month["expenses"];
      $net[] = $month["gross"] - $month["expenses"];
    }

    return array(
      "labels" => $labels,
      "gross" => $gross,
      "expenses" => $expenses,
      "net" => $net
    );
  }

  static private function expenseRows($pdo, $expenseMeta, $filters) {
    $rows = array();

    if ($expenseMeta) {
      $table = $expenseMeta["table"];
      $dateColumn = $expenseMeta["dateColumn"];
      $amountColumn = $expenseMeta["amountColumn"];
      $idColumn = self::firstExistingColumn($pdo, $table, array("expenseID", "expenseId", "id"));
      $categoryColumn = self::firstExistingColumn($pdo, $table, array("category", "expenseType", "type", "title"));
      $descriptionColumn = self::firstExistingColumn($pdo, $table, array("description", "remarks", "notes", "details"));
      $statusColumn = self::firstExistingColumn($pdo, $table, array("status", "expenseStatus"));
      $where = array("1=1");
      $bindings = array();

      if (!empty($filters["dateFrom"])) {
        $where[] = self::quoteIdentifier($dateColumn) . " >= :expenseDateFrom";
        $bindings[":expenseDateFrom"] = $filters["dateFrom"] . " 00:00:00";
      }

      if (!empty($filters["dateTo"])) {
        $where[] = self::quoteIdentifier($dateColumn) . " <= :expenseDateTo";
        $bindings[":expenseDateTo"] = $filters["dateTo"] . " 23:59:59";
      }

      $stmt = $pdo->prepare("
        SELECT
          " . self::selectAlias($idColumn, "recordID") . ",
          " . self::selectAlias($dateColumn, "recordDate") . ",
          " . self::selectAlias($categoryColumn, "category") . ",
          " . self::selectAlias($descriptionColumn, "description") . ",
          " . self::quoteIdentifier($amountColumn) . " AS amount,
          " . self::selectAlias($statusColumn, "status") . "
        FROM " . self::quoteIdentifier($table) . "
        WHERE " . implode(" AND ", $where) . "
        ORDER BY " . self::quoteIdentifier($dateColumn) . " DESC
        LIMIT 100
      ");

      self::bindValues($stmt, $bindings);
      $stmt->execute();
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $rows = array_merge($rows, self::completedCrewSalaryExpenseRows($pdo, $filters));
    usort($rows, function ($a, $b) {
      return strtotime($b["recordDate"] ?? "") <=> strtotime($a["recordDate"] ?? "");
    });

    return array_slice($rows, 0, 100);
  }

  static private function completedCrewSalaryExpenseTotal($pdo, $filters) {
    if (!self::tableExists($pdo, "staffsalary")) {
      return 0;
    }

    $where = self::completedCrewSalaryWhere($filters, "b", "s");
    $stmt = $pdo->prepare("
      SELECT COALESCE(SUM(s.netPay), 0)
      FROM staffsalary s
      INNER JOIN booking b ON b.bookingID = s.creditedBookingID
      LEFT JOIN customer c ON c.id = b.customerID
      WHERE " . implode(" AND ", $where["conditions"]) . "
    ");
    self::bindValues($stmt, $where["bindings"]);
    $stmt->execute();

    return (float) $stmt->fetchColumn();
  }

  static private function completedCrewSalaryExpenseRows($pdo, $filters) {
    if (!self::tableExists($pdo, "staffsalary")) {
      return array();
    }

    $where = self::completedCrewSalaryWhere($filters, "b", "s");
    $stmt = $pdo->prepare("
      SELECT
        s.salaryID,
        s.tripID,
        s.creditedBookingID,
        s.payPeriodStart,
        s.netPay,
        s.status,
        s.tripRole,
        CONCAT(e.empFName, ' ', e.empLName) AS employeeName
      FROM staffsalary s
      INNER JOIN booking b ON b.bookingID = s.creditedBookingID
      LEFT JOIN customer c ON c.id = b.customerID
      LEFT JOIN employee e ON e.id = s.empID
      WHERE " . implode(" AND ", $where["conditions"]) . "
      ORDER BY s.payPeriodStart DESC, s.salaryID DESC
      LIMIT 100
    ");
    self::bindValues($stmt, $where["bindings"]);
    $stmt->execute();

    return array_map(function ($row) {
      $employeeName = trim((string) ($row["employeeName"] ?? "")) ?: "Crew member";
      $role = trim((string) ($row["tripRole"] ?? ""));
      $description = "Crew salary for " . $employeeName;
      if ($role !== "") {
        $description .= " (" . ucfirst($role) . ")";
      }
      $description .= " - Trip #" . (int) $row["tripID"] . ", Booking #" . (int) $row["creditedBookingID"];

      return array(
        "recordID" => "SAL-" . (int) $row["salaryID"],
        "recordDate" => $row["payPeriodStart"],
        "category" => "employee_salary",
        "description" => $description,
        "amount" => (float) $row["netPay"],
        "status" => $row["status"]
      );
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  static private function completedCrewSalaryWhere($filters, $bookingAlias, $salaryAlias) {
    $conditions = array(
      self::successfulStatusSql($bookingAlias),
      self::quoteIdentifier($salaryAlias) . ".`status` <> 'cancelled'"
    );
    $bindings = array();

    if (!empty($filters["dateFrom"])) {
      $conditions[] = self::quoteIdentifier($salaryAlias) . ".`payPeriodStart` >= :crewSalaryDateFrom";
      $bindings[":crewSalaryDateFrom"] = $filters["dateFrom"];
    }

    if (!empty($filters["dateTo"])) {
      $conditions[] = self::quoteIdentifier($salaryAlias) . ".`payPeriodStart` <= :crewSalaryDateTo";
      $bindings[":crewSalaryDateTo"] = $filters["dateTo"];
    }

    if (!empty($filters["customerType"]) && in_array($filters["customerType"], array("individual", "company"), true)) {
      $conditions[] = "`c`.`customerType` = :crewSalaryCustomerType";
      $bindings[":crewSalaryCustomerType"] = $filters["customerType"];
    }

    return array("conditions" => $conditions, "bindings" => $bindings);
  }

  static private function addExpenseMonths($pdo, &$months, $expenseMeta, $filters = array()) {
    if (!$expenseMeta) {
      return;
    }

    $dateColumn = $expenseMeta["dateColumn"];
    $amountColumn = $expenseMeta["amountColumn"];
    $dateExpr = self::quoteIdentifier($dateColumn);
    $periodExpr = self::salesTrendPeriodExpression($dateExpr, $filters);
    $where = array("1=1");
    $bindings = array();

    if (!empty($filters["dateFrom"])) {
      $where[] = self::quoteIdentifier($dateColumn) . " >= :expenseMonthDateFrom";
      $bindings[":expenseMonthDateFrom"] = $filters["dateFrom"] . " 00:00:00";
    }

    if (!empty($filters["dateTo"])) {
      $where[] = self::quoteIdentifier($dateColumn) . " <= :expenseMonthDateTo";
      $bindings[":expenseMonthDateTo"] = $filters["dateTo"] . " 23:59:59";
    }

    $expenseStmt = $pdo->prepare("
      SELECT
        {$periodExpr} AS periodKey,
        COALESCE(SUM(" . self::quoteIdentifier($amountColumn) . "), 0) AS expenses
      FROM " . self::quoteIdentifier($expenseMeta["table"]) . "
      WHERE " . implode(" AND ", $where) . "
      GROUP BY {$periodExpr}
      ORDER BY periodKey ASC
    ");
    self::bindValues($expenseStmt, $bindings);
    $expenseStmt->execute();

    foreach ($expenseStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      self::ensureSalesPeriod($months, $row["periodKey"], $filters);
      $months[$row["periodKey"]]["expenses"] += (float) $row["expenses"];
    }
  }

  static private function addCrewSalaryExpenseMonths($pdo, &$months, $filters = array()) {
    if (!self::tableExists($pdo, "staffsalary")) {
      return;
    }

    $where = self::completedCrewSalaryWhere($filters, "b", "s");
    $periodExpr = self::salesTrendPeriodExpression("s.payPeriodStart", $filters);
    $stmt = $pdo->prepare("
      SELECT
        {$periodExpr} AS periodKey,
        COALESCE(SUM(s.netPay), 0) AS expenses
      FROM staffsalary s
      INNER JOIN booking b ON b.bookingID = s.creditedBookingID
      LEFT JOIN customer c ON c.id = b.customerID
      WHERE " . implode(" AND ", $where["conditions"]) . "
      GROUP BY {$periodExpr}
      ORDER BY periodKey ASC
    ");
    self::bindValues($stmt, $where["bindings"]);
    $stmt->execute();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      self::ensureSalesPeriod($months, $row["periodKey"], $filters);
      $months[$row["periodKey"]]["expenses"] += (float) $row["expenses"];
    }
  }

  static private function ensureSalesPeriod(&$months, $periodKey, $filters) {
    if (isset($months[$periodKey])) {
      return;
    }

    $months[$periodKey] = array(
      "label" => self::salesTrendLabel($periodKey, $filters),
      "gross" => 0,
      "expenses" => 0
    );
  }

  static private function salesTrendPeriodExpression($dateExpr, $filters) {
    return self::salesTrendUsesDaily($filters) ? "DATE({$dateExpr})" : "DATE_FORMAT({$dateExpr}, '%Y-%m')";
  }

  static private function salesTrendUsesDaily($filters) {
    return !empty($filters["dateFrom"]) || !empty($filters["dateTo"]);
  }

  static private function salesTrendLabel($periodKey, $filters) {
    if (self::salesTrendUsesDaily($filters)) {
      return date("M d, Y", strtotime($periodKey));
    }

    return date("M Y", strtotime($periodKey . "-01"));
  }

  static private function expenseTotal($pdo, $expenseMeta, $filters) {
    $where = array("1=1");
    $bindings = array();

    if (!empty($filters["dateFrom"])) {
      $where[] = self::quoteIdentifier($expenseMeta["dateColumn"]) . " >= :expenseDateFrom";
      $bindings[":expenseDateFrom"] = $filters["dateFrom"] . " 00:00:00";
    }

    if (!empty($filters["dateTo"])) {
      $where[] = self::quoteIdentifier($expenseMeta["dateColumn"]) . " <= :expenseDateTo";
      $bindings[":expenseDateTo"] = $filters["dateTo"] . " 23:59:59";
    }

    return self::scalar(
      $pdo,
      "SELECT COALESCE(SUM(" . self::quoteIdentifier($expenseMeta["amountColumn"]) . "), 0) FROM " . self::quoteIdentifier($expenseMeta["table"]) . " WHERE " . implode(" AND ", $where),
      $bindings
    );
  }

  static private function salesWhere($filters, $alias) {
    $prefix = self::quoteIdentifier($alias) . ".";
    $where = array(self::successfulStatusSql($alias));

    if (!empty($filters["dateFrom"])) {
      $where[] = $prefix . "`pickupDateTime` >= :dateFrom";
    }

    if (!empty($filters["dateTo"])) {
      $where[] = $prefix . "`pickupDateTime` <= :dateTo";
    }

    if (!empty($filters["customerType"]) && in_array($filters["customerType"], array("individual", "company"), true)) {
      $where[] = "`c`.`customerType` = :customerType";
    }

    return implode(" AND ", $where);
  }

  static private function salesBindings($filters) {
    $bindings = array();

    if (!empty($filters["dateFrom"])) {
      $bindings[":dateFrom"] = $filters["dateFrom"] . " 00:00:00";
    }

    if (!empty($filters["dateTo"])) {
      $bindings[":dateTo"] = $filters["dateTo"] . " 23:59:59";
    }

    if (!empty($filters["customerType"]) && in_array($filters["customerType"], array("individual", "company"), true)) {
      $bindings[":customerType"] = $filters["customerType"];
    }

    return $bindings;
  }

  static private function salesTableWhere($filters, $salesMeta) {
    $dateExpr = self::salesDisplayDateExpression($salesMeta, "s");
    $customerTypeExpr = isset($salesMeta["columns"]["customerType"]) ? "`s`.`customerType`" : "`c`.`customerType`";
    $where = array("1=1");

    if (!empty($filters["dateFrom"])) {
      $where[] = "{$dateExpr} >= :salesDateFrom";
    }

    if (!empty($filters["dateTo"])) {
      $where[] = "{$dateExpr} <= :salesDateTo";
    }

    if (!empty($filters["customerType"]) && in_array($filters["customerType"], array("individual", "company"), true)) {
      $where[] = "{$customerTypeExpr} = :salesCustomerType";
    }

    return implode(" AND ", $where);
  }

  static private function salesTableBindings($filters) {
    $bindings = array();

    if (!empty($filters["dateFrom"])) {
      $bindings[":salesDateFrom"] = $filters["dateFrom"] . " 00:00:00";
    }

    if (!empty($filters["dateTo"])) {
      $bindings[":salesDateTo"] = $filters["dateTo"] . " 23:59:59";
    }

    if (!empty($filters["customerType"]) && in_array($filters["customerType"], array("individual", "company"), true)) {
      $bindings[":salesCustomerType"] = $filters["customerType"];
    }

    return $bindings;
  }

  static private function resolveSalesTable($pdo) {
    if (!self::tableExists($pdo, "sales")) {
      return null;
    }

    $columns = self::tableColumns($pdo, "sales");

    if (!isset($columns["bookingID"])) {
      return null;
    }

    return array(
      "table" => "sales",
      "columns" => $columns,
      "grossColumn" => self::firstColumnFromMap($columns, array("grossAmount", "totalAmount")),
      "expenseColumn" => self::firstColumnFromMap($columns, array("expenseAmount")),
      "netColumn" => self::firstColumnFromMap($columns, array("netAmount")),
      "paidColumn" => self::firstColumnFromMap($columns, array("paidAmount")),
      "balanceColumn" => self::firstColumnFromMap($columns, array("balanceAmount")),
      "dateColumn" => self::firstColumnFromMap($columns, array("dateGenerated", "dateCreated", "createdAt")),
      "statusColumn" => self::firstColumnFromMap($columns, array("paymentStatus", "status", "salesStatus"))
    );
  }

  static private function upsertSalesRecord($pdo, $salesMeta, $booking) {
    $existing = self::existingSalesRecord($pdo, $salesMeta, $booking["bookingID"]);
    $gross = (float) $booking["price"];
    $expense = 0;
    $paid = $existing && $salesMeta["paidColumn"] ? (float) ($existing[$salesMeta["paidColumn"]] ?? 0) : 0;
    $net = $gross - $expense;
    $balance = max($net - $paid, 0);

    $values = array(
      "bookingID" => (int) $booking["bookingID"],
      "tripID" => (int) $booking["tripID"],
      "customerID" => (int) $booking["customerID"],
      "customerType" => $booking["customerType"] ?: "",
      "grossAmount" => $gross,
      "totalAmount" => $gross,
      "expenseAmount" => $expense,
      "netAmount" => $net,
      "balanceAmount" => $balance,
      "paymentStatus" => $balance <= 0 && $paid > 0 ? "paid" : ($paid > 0 ? "partial" : "unpaid"),
      "status" => $balance <= 0 && $paid > 0 ? "paid" : ($paid > 0 ? "partial" : "unpaid"),
      "salesStatus" => $balance <= 0 && $paid > 0 ? "paid" : "recorded",
      "remarks" => "Auto-generated from completed booking"
    );

    if ($existing) {
      self::updateSalesRecord($pdo, $salesMeta, $existing, $values);
      return;
    }

    self::insertSalesRecord($pdo, $salesMeta, $values);
  }

  static private function existingSalesRecord($pdo, $salesMeta, $bookingID) {
    $stmt = $pdo->prepare("
      SELECT *
      FROM " . self::quoteIdentifier($salesMeta["table"]) . "
      WHERE bookingID = :bookingID
      LIMIT 1
    ");
    $stmt->bindParam(":bookingID", $bookingID, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  static private function insertSalesRecord($pdo, $salesMeta, $values) {
    $columns = array();
    $params = array();
    $bindings = array();

    foreach ($values as $column => $value) {
      if (!isset($salesMeta["columns"][$column])) {
        continue;
      }

      $columns[] = self::quoteIdentifier($column);
      $params[] = ":" . $column;
      $bindings[":" . $column] = $value;
    }

    if (isset($salesMeta["columns"]["dateGenerated"])) {
      $columns[] = "`dateGenerated`";
      $params[] = "NOW()";
    } elseif (isset($salesMeta["columns"]["dateCreated"])) {
      $columns[] = "`dateCreated`";
      $params[] = "NOW()";
    }

    $stmt = $pdo->prepare("
      INSERT INTO " . self::quoteIdentifier($salesMeta["table"]) . " (" . implode(", ", $columns) . ")
      VALUES (" . implode(", ", $params) . ")
    ");
    self::bindMixedValues($stmt, $bindings);
    $stmt->execute();
  }

  static private function updateSalesRecord($pdo, $salesMeta, $existing, $values) {
    if (!isset($salesMeta["columns"]["salesID"], $existing["salesID"])) {
      return;
    }

    $updates = array();
    $bindings = array(":salesID" => $existing["salesID"]);

    foreach ($values as $column => $value) {
      if (!isset($salesMeta["columns"][$column]) || in_array($column, array("bookingID", "paidAmount"), true)) {
        continue;
      }

      $updates[] = self::quoteIdentifier($column) . " = :" . $column;
      $bindings[":" . $column] = $value;
    }

    if (empty($updates)) {
      return;
    }

    $stmt = $pdo->prepare("
      UPDATE " . self::quoteIdentifier($salesMeta["table"]) . "
      SET " . implode(", ", $updates) . "
      WHERE salesID = :salesID
    ");
    self::bindMixedValues($stmt, $bindings);
    $stmt->execute();
  }

  static private function salesPaidAmountFromRecord($salesMeta, $record) {
    if ($salesMeta["netColumn"] && isset($record[$salesMeta["netColumn"]])) {
      return (float) $record[$salesMeta["netColumn"]];
    }

    if ($salesMeta["grossColumn"] && isset($record[$salesMeta["grossColumn"]])) {
      return (float) $record[$salesMeta["grossColumn"]];
    }

    return 0;
  }

  static private function salesGrossExpression($salesMeta, $alias) {
    if ($salesMeta["grossColumn"]) {
      return self::quoteIdentifier($alias) . "." . self::quoteIdentifier($salesMeta["grossColumn"]);
    }

    return "0";
  }

  static private function salesExpenseExpression($salesMeta, $alias) {
    if ($salesMeta["expenseColumn"]) {
      return self::quoteIdentifier($alias) . "." . self::quoteIdentifier($salesMeta["expenseColumn"]);
    }

    return "0";
  }

  static private function salesNetExpression($salesMeta, $alias) {
    if ($salesMeta["netColumn"]) {
      return self::quoteIdentifier($alias) . "." . self::quoteIdentifier($salesMeta["netColumn"]);
    }

    return "(" . self::salesGrossExpression($salesMeta, $alias) . " - " . self::salesExpenseExpression($salesMeta, $alias) . ")";
  }

  static private function salesDateExpression($salesMeta, $alias) {
    if ($salesMeta["dateColumn"]) {
      return self::quoteIdentifier($alias) . "." . self::quoteIdentifier($salesMeta["dateColumn"]);
    }

    return "NOW()";
  }

  static private function salesDisplayDateExpression($salesMeta, $alias) {
    return "COALESCE(`b`.`pickupDateTime`, " . self::salesDateExpression($salesMeta, $alias) . ")";
  }

  static private function salesStatusExpression($salesMeta, $alias) {
    if ($salesMeta["statusColumn"]) {
      return self::quoteIdentifier($alias) . "." . self::quoteIdentifier($salesMeta["statusColumn"]);
    }

    return "'recorded'";
  }

  static private function tableColumns($pdo, $tableName) {
    $stmt = $pdo->prepare("
      SELECT COLUMN_NAME
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = :tableName
    ");
    $stmt->bindParam(":tableName", $tableName, PDO::PARAM_STR);
    $stmt->execute();

    $columns = array();
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $column) {
      $columns[$column] = true;
    }

    return $columns;
  }

  static private function firstColumnFromMap($columns, $candidates) {
    foreach ($candidates as $candidate) {
      if (isset($columns[$candidate])) {
        return $candidate;
      }
    }

    return null;
  }

  static private function resolveExpenseTable($pdo) {
    foreach (array("expenses", "expense") as $table) {
      if (!self::tableExists($pdo, $table)) {
        continue;
      }

      $amountColumn = self::firstExistingColumn($pdo, $table, array("amount", "cost", "total", "price"));
      $dateColumn = self::firstExistingColumn($pdo, $table, array("expenseDate", "dateCreated", "createdAt", "date"));

      if ($amountColumn && $dateColumn) {
        return array(
          "table" => $table,
          "amountColumn" => $amountColumn,
          "dateColumn" => $dateColumn
        );
      }
    }

    return null;
  }

  static private function successfulStatusSql($alias = null) {
    $prefix = $alias ? self::quoteIdentifier($alias) . "." : "";
    return $prefix . "`status` IN ('completed', 'delivered', 'success', 'successful')";
  }

  static private function scalar($pdo, $sql, $bindings) {
    $stmt = $pdo->prepare($sql);
    self::bindValues($stmt, $bindings);
    $stmt->execute();

    return $stmt->fetchColumn();
  }

  static private function bindValues($stmt, $bindings) {
    foreach ($bindings as $key => $value) {
      $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
  }

  static private function bindMixedValues($stmt, $bindings) {
    foreach ($bindings as $key => $value) {
      $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
      $stmt->bindValue($key, $value, $type);
    }
  }

  static private function tableExists($pdo, $tableName) {
    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = :tableName
    ");

    $stmt->bindParam(":tableName", $tableName, PDO::PARAM_STR);
    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
  }

  static private function firstExistingColumn($pdo, $tableName, $columns) {
    foreach ($columns as $column) {
      if (self::columnExists($pdo, $tableName, $column)) {
        return $column;
      }
    }

    return null;
  }

  static private function columnExists($pdo, $tableName, $columnName) {
    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = :tableName
        AND COLUMN_NAME = :columnName
    ");

    $stmt->bindParam(":tableName", $tableName, PDO::PARAM_STR);
    $stmt->bindParam(":columnName", $columnName, PDO::PARAM_STR);
    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
  }

  static private function selectAlias($column, $alias) {
    if (!$column) {
      return "NULL AS " . self::quoteIdentifier($alias);
    }

    return self::quoteIdentifier($column) . " AS " . self::quoteIdentifier($alias);
  }

  static private function quoteIdentifier($identifier) {
    return "`" . str_replace("`", "``", $identifier) . "`";
  }
}
