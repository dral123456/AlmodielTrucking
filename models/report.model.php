<?php
require_once "connection.php";

class ModelReport {

    static public function mdlSummary() {
        $pdo = (new Connection)->connect();

        $successfulStatusSql = self::successfulStatusSql();
        $billingTotal = self::scalar($pdo, "SELECT COALESCE(SUM(price), 0) FROM booking WHERE {$successfulStatusSql}");
        $bookingCount = self::scalar($pdo, "SELECT COUNT(*) FROM booking WHERE {$successfulStatusSql}");
        $pendingCount = self::bookingStatusCount($pdo, "pending");
        $inTransitCount = self::bookingStatusCount($pdo, "in-transit");
        $completedCount = self::bookingStatusCount($pdo, "completed");

        $expenseMeta = self::resolveMoneyTable($pdo, array("expenses", "expense"), array("amount", "cost", "total", "price"));
        $salaryMeta = self::resolveSalaryTable($pdo);

        $salaryTotalSql = $salaryMeta
            ? "SELECT COALESCE(SUM(" . self::quoteIdentifier($salaryMeta["amountColumn"]) . "), 0) FROM " . self::quoteIdentifier($salaryMeta["table"]) . ($salaryMeta["statusColumn"] ? " WHERE " . self::quoteIdentifier($salaryMeta["statusColumn"]) . " <> 'cancelled'" : "")
            : null;

        return array(
            "billingTotal" => (float) $billingTotal,
            "bookingCount" => (int) $bookingCount,
            "pendingCount" => (int) $pendingCount,
            "inTransitCount" => (int) $inTransitCount,
            "completedCount" => (int) $completedCount,
            "expenseTotal" => $expenseMeta ? (float) self::scalar($pdo, "SELECT COALESCE(SUM(" . self::quoteIdentifier($expenseMeta["amountColumn"]) . "), 0) FROM " . self::quoteIdentifier($expenseMeta["table"])) : 0,
            "salaryTotal" => $salaryMeta ? (float) self::scalar($pdo, $salaryTotalSql) : 0,
            "staffCount" => (int) self::scalar($pdo, "SELECT COUNT(*) FROM employee"),
            "activeStaffCount" => (int) self::scalar($pdo, "SELECT COUNT(*) FROM employee WHERE empStatus = 'active'"),
            "hasExpenseTable" => $expenseMeta !== null,
            "hasSalaryTable" => $salaryMeta !== null
        );
    }

    static public function mdlBillingRows() {
        $stmt = (new Connection)->connect()->prepare("
            SELECT
                b.bookingID,
                b.tripID,
                b.pickupDateTime,
                b.price,
                b.status,
                COALESCE(NULLIF(TRIM(CONCAT(c.customerFName, ' ', c.customerLName)), ''), c.contactPerson, 'Customer') AS customerName,
                c.customerType
            FROM booking b
            LEFT JOIN customer c ON c.id = b.customerID
            WHERE " . self::successfulStatusSql("b") . "
            ORDER BY b.pickupDateTime DESC, b.bookingID DESC
            LIMIT 50
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlExpenseRows() {
        $pdo = (new Connection)->connect();
        $meta = self::resolveMoneyTable($pdo, array("expenses", "expense"), array("amount", "cost", "total", "price"));

        if (!$meta) {
            return array();
        }

        $table = $meta["table"];
        $amountColumn = $meta["amountColumn"];
        $idColumn = self::firstExistingColumn($pdo, $table, array("expenseID", "expenseId", "id"));
        $dateColumn = self::firstExistingColumn($pdo, $table, array("expenseDate", "dateCreated", "createdAt", "date"));
        $categoryColumn = self::firstExistingColumn($pdo, $table, array("category", "expenseType", "type", "title"));
        $descriptionColumn = self::firstExistingColumn($pdo, $table, array("description", "remarks", "notes", "details"));
        $statusColumn = self::firstExistingColumn($pdo, $table, array("status", "expenseStatus"));

        $stmt = $pdo->prepare("
            SELECT
                " . self::selectAlias($idColumn, "recordID") . ",
                " . self::selectAlias($dateColumn, "recordDate") . ",
                " . self::selectAlias($categoryColumn, "category") . ",
                " . self::selectAlias($descriptionColumn, "description") . ",
                " . self::quoteIdentifier($amountColumn) . " AS amount,
                " . self::selectAlias($statusColumn, "status") . "
            FROM " . self::quoteIdentifier($table) . "
            ORDER BY " . ($dateColumn ? self::quoteIdentifier($dateColumn) : self::quoteIdentifier($idColumn ?: $amountColumn)) . " DESC
            LIMIT 50
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlStaffRows() {
        $stmt = (new Connection)->connect()->prepare("
            SELECT
                id,
                empFName,
                empLName,
                empMI,
                empSuffix,
                empPhoneNumber,
                empEmail,
                empType,
                empStatus,
                dateCreated
            FROM employee
            ORDER BY empStatus DESC, empType, empFName, empLName
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlSalaryRows() {
        $pdo = (new Connection)->connect();
        $meta = self::resolveSalaryTable($pdo);

        if (!$meta) {
            return array();
        }

        $table = $meta["table"];
        $amountColumn = $meta["amountColumn"];
        $idColumn = $meta["idColumn"];
        $empColumn = $meta["empColumn"];
        $dateColumn = $meta["dateColumn"];
        $statusColumn = $meta["statusColumn"];
        $periodStartColumn = $meta["periodStartColumn"];
        $periodEndColumn = $meta["periodEndColumn"];
        $grossColumn = $meta["grossColumn"];
        $deductionColumn = $meta["deductionColumn"];
        $payTypeColumn = $meta["payTypeColumn"];
        $tripColumn = $meta["tripColumn"];
        $creditedBookingColumn = $meta["creditedBookingColumn"];
        $creditedDistanceColumn = $meta["creditedDistanceColumn"];

        $join = $empColumn ? "LEFT JOIN employee e ON e.id = s." . self::quoteIdentifier($empColumn) : "";
        $employeeName = $empColumn ? "COALESCE(NULLIF(TRIM(CONCAT(e.empFName, ' ', e.empLName)), ''), 'Employee')" : "'Employee'";

        $stmt = $pdo->prepare("
            SELECT
                " . self::selectAlias($idColumn, "recordID", "s") . ",
                {$employeeName} AS employeeName,
                " . self::selectAlias($periodStartColumn, "periodStart", "s") . ",
                " . self::selectAlias($periodEndColumn, "periodEnd", "s") . ",
                " . self::selectAlias($tripColumn, "tripID", "s") . ",
                " . self::selectAlias($creditedBookingColumn, "creditedBookingID", "s") . ",
                " . self::selectAlias($creditedDistanceColumn, "creditedDistanceKm", "s") . ",
                " . self::selectAlias($dateColumn, "recordDate", "s") . ",
                s." . self::quoteIdentifier($amountColumn) . " AS amount,
                " . self::selectAlias($grossColumn, "grossPay", "s") . ",
                " . self::selectAlias($deductionColumn, "deductions", "s") . ",
                " . self::selectAlias($payTypeColumn, "payType", "s") . ",
                " . self::selectAlias($statusColumn, "status", "s") . "
            FROM " . self::quoteIdentifier($table) . " s
            {$join}
            ORDER BY " . ($dateColumn ? "s." . self::quoteIdentifier($dateColumn) : "s." . self::quoteIdentifier($idColumn ?: $amountColumn)) . " DESC
            LIMIT 50
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static private function bookingStatusCount($pdo, $status) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE status = :status");
        $stmt->bindParam(":status", $status, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    static private function successfulStatusSql($alias = null) {
        $prefix = $alias ? self::quoteIdentifier($alias) . "." : "";
        return $prefix . "`status` IN ('completed', 'delivered', 'success', 'successful')";
    }

    static private function scalar($pdo, $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    static private function resolveMoneyTable($pdo, $tables, $amountColumns) {
        foreach ($tables as $table) {
            if (!self::tableExists($pdo, $table)) {
                continue;
            }

            $amountColumn = self::firstExistingColumn($pdo, $table, $amountColumns);
            if ($amountColumn) {
                return array("table" => $table, "amountColumn" => $amountColumn);
            }
        }

        return null;
    }

    static private function resolveSalaryTable($pdo) {
        foreach (array("staffsalary", "staff_salary", "employee_salary", "payroll", "salary") as $table) {
            if (!self::tableExists($pdo, $table)) {
                continue;
            }

            $amountColumn = self::firstExistingColumn($pdo, $table, array("netPay", "amount", "salary", "grossPay", "rate", "pay"));
            if (!$amountColumn) {
                continue;
            }

            return array(
                "table" => $table,
                "amountColumn" => $amountColumn,
                "idColumn" => self::firstExistingColumn($pdo, $table, array("salaryID", "payrollID", "id")),
                "empColumn" => self::firstExistingColumn($pdo, $table, array("empID", "employeeID", "employeeId", "employee_id")),
                "dateColumn" => self::firstExistingColumn($pdo, $table, array("datePaid", "salaryDate", "payrollDate", "dateCreated", "createdAt", "date")),
                "statusColumn" => self::firstExistingColumn($pdo, $table, array("status", "salaryStatus", "payrollStatus")),
                "periodStartColumn" => self::firstExistingColumn($pdo, $table, array("payPeriodStart", "periodStart", "salaryStart")),
                "periodEndColumn" => self::firstExistingColumn($pdo, $table, array("payPeriodEnd", "periodEnd", "salaryEnd")),
                "tripColumn" => self::firstExistingColumn($pdo, $table, array("tripID", "tripId")),
                "creditedBookingColumn" => self::firstExistingColumn($pdo, $table, array("creditedBookingID", "bookingID")),
                "creditedDistanceColumn" => self::firstExistingColumn($pdo, $table, array("creditedDistanceKm", "distanceKm")),
                "grossColumn" => self::firstExistingColumn($pdo, $table, array("grossPay", "grossAmount", "salary")),
                "deductionColumn" => self::firstExistingColumn($pdo, $table, array("deductions", "deductionAmount", "totalDeductions")),
                "payTypeColumn" => self::firstExistingColumn($pdo, $table, array("payType", "salaryType", "rateType"))
            );
        }

        return null;
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

    static private function selectAlias($column, $alias, $tableAlias = null) {
        if (!$column) {
            return "NULL AS " . self::quoteIdentifier($alias);
        }

        $prefix = $tableAlias ? self::quoteIdentifier($tableAlias) . "." : "";
        return $prefix . self::quoteIdentifier($column) . " AS " . self::quoteIdentifier($alias);
    }

    static private function quoteIdentifier($identifier) {
        return "`" . str_replace("`", "``", $identifier) . "`";
    }
}
