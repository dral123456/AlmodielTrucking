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
      "monthlySeries" => self::monthlySeries($pdo, $expenseMeta),
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

  static private function salesDashboardFromSalesTable($pdo, $salesMeta, $expenseMeta, $filters) {
    $where = self::salesTableWhere($filters, $salesMeta);
    $bindings = self::salesTableBindings($filters);
    $summary = self::salesTableSummary($pdo, $salesMeta, $where, $bindings);

    return array(
      "summary" => $summary,
      "salesRows" => self::salesTableRows($pdo, $salesMeta, $where, $bindings),
      "expenseRows" => self::expenseRows($pdo, $expenseMeta, $filters),
      "monthlySeries" => self::monthlySeriesFromSalesTable($pdo, $salesMeta),
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
    $dateExpr = self::salesDateExpression($salesMeta, "s");
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
        {$statusExpr} AS status,
        {$customerTypeExpr} AS customerType,
        COALESCE(NULLIF(TRIM(CONCAT(c.customerFName, ' ', c.customerLName)), ''), c.contactPerson, 'Customer') AS customerName
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

  static private function monthlySeriesFromSalesTable($pdo, $salesMeta) {
    $dateExpr = self::salesDateExpression($salesMeta, "s");
    $grossExpr = self::salesGrossExpression($salesMeta, "s");
    $expenseExpr = self::salesExpenseExpression($salesMeta, "s");
    $netExpr = self::salesNetExpression($salesMeta, "s");

    $stmt = $pdo->prepare("
      SELECT
        DATE_FORMAT({$dateExpr}, '%Y-%m') AS monthKey,
        COALESCE(SUM({$grossExpr}), 0) AS grossSales,
        COALESCE(SUM({$expenseExpr}), 0) AS expenses,
        COALESCE(SUM({$netExpr}), 0) AS netSales
      FROM " . self::quoteIdentifier($salesMeta["table"]) . " s
      GROUP BY DATE_FORMAT({$dateExpr}, '%Y-%m')
      ORDER BY monthKey ASC
    ");
    $stmt->execute();

    $rows = array_slice($stmt->fetchAll(PDO::FETCH_ASSOC), -12);
    $labels = array();
    $gross = array();
    $expenses = array();
    $net = array();

    foreach ($rows as $row) {
      $labels[] = date("M Y", strtotime($row["monthKey"] . "-01"));
      $gross[] = (float) $row["grossSales"];
      $expenses[] = (float) $row["expenses"];
      $net[] = (float) $row["netSales"];
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
    $expenses = $expenseMeta ? self::expenseTotal($pdo, $expenseMeta, $filters) : 0;
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
        COALESCE(NULLIF(TRIM(CONCAT(c.customerFName, ' ', c.customerLName)), ''), c.contactPerson, 'Customer') AS customerName
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

  static private function monthlySeries($pdo, $expenseMeta) {
    $salesStmt = $pdo->prepare("
      SELECT
        DATE_FORMAT(b.pickupDateTime, '%Y-%m') AS monthKey,
        COALESCE(SUM(b.price), 0) AS grossSales
      FROM booking b
      WHERE " . self::successfulStatusSql("b") . "
      GROUP BY DATE_FORMAT(b.pickupDateTime, '%Y-%m')
      ORDER BY monthKey ASC
    ");
    $salesStmt->execute();

    $months = array();
    foreach ($salesStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $months[$row["monthKey"]] = array(
        "label" => date("M Y", strtotime($row["monthKey"] . "-01")),
        "gross" => (float) $row["grossSales"],
        "expenses" => 0
      );
    }

    if ($expenseMeta) {
      $dateColumn = $expenseMeta["dateColumn"];
      $amountColumn = $expenseMeta["amountColumn"];
      $expenseStmt = $pdo->prepare("
        SELECT
          DATE_FORMAT(" . self::quoteIdentifier($dateColumn) . ", '%Y-%m') AS monthKey,
          COALESCE(SUM(" . self::quoteIdentifier($amountColumn) . "), 0) AS expenses
        FROM " . self::quoteIdentifier($expenseMeta["table"]) . "
        GROUP BY DATE_FORMAT(" . self::quoteIdentifier($dateColumn) . ", '%Y-%m')
        ORDER BY monthKey ASC
      ");
      $expenseStmt->execute();

      foreach ($expenseStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!isset($months[$row["monthKey"]])) {
          $months[$row["monthKey"]] = array(
            "label" => date("M Y", strtotime($row["monthKey"] . "-01")),
            "gross" => 0,
            "expenses" => 0
          );
        }
        $months[$row["monthKey"]]["expenses"] = (float) $row["expenses"];
      }
    }

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
    if (!$expenseMeta) {
      return array();
    }

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

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    $dateExpr = self::salesDateExpression($salesMeta, "s");
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
      "salesStatus" => "recorded",
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
