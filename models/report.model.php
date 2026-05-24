<?php
require_once "connection.php";

class ModelReport {

    static public function mdlSummary() {
        $pdo = (new Connection)->connect();
        self::ensureDeliveryChargeTable($pdo);

        $successfulStatusSql = self::successfulStatusSql();
        $billingTotal = self::scalar($pdo, "
            SELECT COALESCE(SUM(b.price), 0) + COALESCE((
                SELECT SUM(dc.amount)
                FROM deliverycharge dc
                INNER JOIN booking b2 ON b2.bookingID = dc.bookingID
                WHERE " . self::successfulStatusSql("b2") . "
            ), 0)
            FROM booking b
            WHERE {$successfulStatusSql}
        ");
        $bookingCount = self::scalar($pdo, "SELECT COUNT(*) FROM booking WHERE {$successfulStatusSql}");
        $pendingCount = self::bookingStatusCount($pdo, "pending");
        $inTransitCount = self::bookingStatusCount($pdo, "in-transit");
        $completedCount = self::bookingStatusCount($pdo, "completed");

        $expenseMeta = self::resolveMoneyTable($pdo, array("expenses", "expense"), array("amount", "cost", "total", "price"));
        $salaryMeta = self::resolveMoneyTable($pdo, array("staffsalary", "staff_salary", "employee_salary", "payroll", "salary"), array("amount", "salary", "grossPay", "netPay", "rate", "pay"));

        return array(
            "billingTotal" => (float) $billingTotal,
            "bookingCount" => (int) $bookingCount,
            "pendingCount" => (int) $pendingCount,
            "inTransitCount" => (int) $inTransitCount,
            "completedCount" => (int) $completedCount,
            "expenseTotal" => $expenseMeta ? (float) self::scalar($pdo, "SELECT COALESCE(SUM(" . self::quoteIdentifier($expenseMeta["amountColumn"]) . "), 0) FROM " . self::quoteIdentifier($expenseMeta["table"])) : 0,
            "salaryTotal" => $salaryMeta ? (float) self::scalar($pdo, "SELECT COALESCE(SUM(" . self::quoteIdentifier($salaryMeta["amountColumn"]) . "), 0) FROM " . self::quoteIdentifier($salaryMeta["table"])) : 0,
            "staffCount" => (int) self::scalar($pdo, "SELECT COUNT(*) FROM employee"),
            "activeStaffCount" => (int) self::scalar($pdo, "SELECT COUNT(*) FROM employee WHERE empStatus = 'active'"),
            "hasExpenseTable" => $expenseMeta !== null,
            "hasSalaryTable" => $salaryMeta !== null
        );
    }

    static public function mdlBillingRows() {
        $pdo = (new Connection)->connect();
        self::ensureDeliveryChargeTable($pdo);

        $stmt = $pdo->prepare("
            SELECT
                b.bookingID,
                b.tripID,
                b.pickupDateTime,
                b.price,
                b.status,
                COALESCE(NULLIF(TRIM(CONCAT(c.customerFName, ' ', c.customerLName)), ''), c.contactPerson, 'Customer') AS customerName,
                c.customerType,
                destination.province AS destinationProvince,
                destination.city AS destinationCity,
                destination.barangay AS destinationBarangay,
                destination.street AS destinationStreet,
                t.plateNumber,
                t.type AS truckSize,
                COALESCE(extra.extraAmount, 0) AS extraAmount,
                COALESCE(extra.extraTypes, '') AS extraTypes,
                b.price + COALESCE(extra.extraAmount, 0) AS grossAmount
            FROM booking b
            LEFT JOIN customer c ON c.id = b.customerID
            LEFT JOIN location destination ON destination.locationID = b.destinationLocationID
            LEFT JOIN (
                SELECT tripID, MIN(truckID) AS truckID
                FROM tripemployee
                GROUP BY tripID
            ) tripTruck ON tripTruck.tripID = b.tripID
            LEFT JOIN truck t ON t.id = tripTruck.truckID
            LEFT JOIN (
                SELECT
                    bookingID,
                    SUM(amount) AS extraAmount,
                    GROUP_CONCAT(DISTINCT chargeType ORDER BY chargeType SEPARATOR ', ') AS extraTypes
                FROM deliverycharge
                GROUP BY bookingID
            ) extra ON extra.bookingID = b.bookingID
            WHERE " . self::successfulStatusSql("b") . "
            ORDER BY b.pickupDateTime DESC, b.bookingID DESC
            LIMIT 50
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlSaveDeliveryCharge($data) {
        $pdo = (new Connection)->connect();
        self::ensureDeliveryChargeTable($pdo);

        $bookingID = (int) ($data["bookingID"] ?? 0);
        $tripID = (int) ($data["tripID"] ?? 0);
        $amount = (float) ($data["amount"] ?? 0);
        $chargeType = strtolower(trim((string) ($data["chargeType"] ?? "hauling")));

        if ($bookingID <= 0 || $tripID <= 0 || $amount < 0 || !in_array($chargeType, array("hauling", "others"), true)) {
            return "invalid";
        }

        $stmt = $pdo->prepare("
            INSERT INTO deliverycharge (
                bookingID,
                tripID,
                chargeType,
                amount,
                notes,
                createdBy,
                dateCreated
            ) VALUES (
                :bookingID,
                :tripID,
                :chargeType,
                :amount,
                :notes,
                :createdBy,
                NOW()
            )
        ");

        $stmt->bindValue(":bookingID", $bookingID, PDO::PARAM_INT);
        $stmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
        $stmt->bindValue(":chargeType", $chargeType, PDO::PARAM_STR);
        $stmt->bindValue(":amount", $amount, PDO::PARAM_STR);
        $stmt->bindValue(":notes", trim((string) ($data["notes"] ?? "")), PDO::PARAM_STR);
        self::bindNullableInt($stmt, ":createdBy", $data["createdBy"] ?? null);

        return $stmt->execute() ? "success" : "error";
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
        $meta = self::resolveMoneyTable($pdo, array("staffsalary", "staff_salary", "employee_salary", "payroll", "salary"), array("amount", "salary", "grossPay", "netPay", "rate", "pay"));

        if (!$meta) {
            return array();
        }

        $table = $meta["table"];
        $amountColumn = $meta["amountColumn"];
        $idColumn = self::firstExistingColumn($pdo, $table, array("salaryID", "payrollID", "id"));
        $empColumn = self::firstExistingColumn($pdo, $table, array("empID", "employeeID", "employeeId", "employee_id"));
        $dateColumn = self::firstExistingColumn($pdo, $table, array("salaryDate", "payrollDate", "dateCreated", "createdAt", "date"));
        $statusColumn = self::firstExistingColumn($pdo, $table, array("status", "salaryStatus", "payrollStatus"));

        $join = $empColumn ? "LEFT JOIN employee e ON e.id = s." . self::quoteIdentifier($empColumn) : "";
        $employeeName = $empColumn ? "COALESCE(NULLIF(TRIM(CONCAT(e.empFName, ' ', e.empLName)), ''), 'Employee')" : "'Employee'";

        $stmt = $pdo->prepare("
            SELECT
                " . self::selectAlias($idColumn, "recordID", "s") . ",
                {$employeeName} AS employeeName,
                " . self::selectAlias($dateColumn, "recordDate", "s") . ",
                s." . self::quoteIdentifier($amountColumn) . " AS amount,
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

    static private function ensureDeliveryChargeTable($pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS deliverycharge (
                deliveryChargeID int NOT NULL AUTO_INCREMENT,
                bookingID int NOT NULL,
                tripID int NOT NULL,
                chargeType enum('hauling','others') NOT NULL DEFAULT 'hauling',
                amount double NOT NULL DEFAULT 0,
                notes text NULL,
                createdBy int NULL,
                dateCreated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (deliveryChargeID),
                KEY idx_deliverycharge_bookingID (bookingID),
                KEY idx_deliverycharge_tripID (tripID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }

    static private function bindNullableInt($stmt, $key, $value) {
        if ($value === null || $value === "") {
            $stmt->bindValue($key, null, PDO::PARAM_NULL);
            return;
        }

        $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
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
