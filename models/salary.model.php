<?php
require_once "connection.php";

class ModelSalary {
  static public function mdlSalaryRows($employeeID = null, $status = "all") {
    $pdo = (new Connection)->connect();

    if (!self::tableExists($pdo, "staffsalary")) {
      return array();
    }

    $where = array("1=1");
    $bindings = array();

    if ($employeeID !== null && (int) $employeeID > 0) {
      $where[] = "s.empID = :employeeID";
      $bindings[":employeeID"] = (int) $employeeID;
    }

    if (in_array($status, array("pending", "paid", "cancelled"), true)) {
      $where[] = "s.status = :status";
      $bindings[":status"] = $status;
    }

    $stmt = $pdo->prepare("
      SELECT
        s.salaryID,
        s.empID,
        s.tripID,
        s.creditedBookingID,
        s.creditedDistanceKm,
        s.tripRole,
        s.payPeriodStart,
        s.payPeriodEnd,
        s.payType,
        s.baseRate,
        s.grossPay,
        s.deductions,
        s.netPay,
        s.datePaid,
        s.status,
        s.remarks,
        s.dateCreated,
        CONCAT(e.empFName, ' ', e.empLName) AS employeeName,
        e.empType,
        cb.pickupDateTime,
        pickup.description AS pickupDescription,
        destination.description AS destinationDescription
      FROM staffsalary s
      INNER JOIN employee e ON e.id = s.empID
      LEFT JOIN booking cb ON cb.bookingID = s.creditedBookingID
      LEFT JOIN location pickup ON pickup.locationID = cb.pickupLocationID
      LEFT JOIN location destination ON destination.locationID = cb.destinationLocationID
      WHERE " . implode(" AND ", $where) . "
      ORDER BY COALESCE(s.datePaid, s.dateCreated) DESC, s.salaryID DESC
      LIMIT 100
    ");

    self::bindValues($stmt, $bindings);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlDeliveredTripOptions() {
    $pdo = (new Connection)->connect();

    $stmt = $pdo->prepare("
      SELECT
        b.tripID,
        MIN(b.pickupDateTime) AS firstPickupDateTime,
        MAX(b.pickupDateTime) AS lastPickupDateTime,
        COUNT(*) AS bookingCount,
        GROUP_CONCAT(DISTINCT COALESCE(NULLIF(TRIM(CONCAT(c.customerFName, ' ', c.customerLName)), ''), c.contactPerson) ORDER BY c.id SEPARATOR ', ') AS customers
      FROM booking b
      LEFT JOIN customer c ON c.id = b.customerID
      WHERE b.status IN ('completed', 'delivered', 'success', 'successful')
      GROUP BY b.tripID
      ORDER BY firstPickupDateTime DESC, b.tripID DESC
      LIMIT 150
    ");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlSaveSalary($data) {
    $pdo = (new Connection)->connect();

    if (!self::tableExists($pdo, "staffsalary")) {
      return "missing-table";
    }

    $employeeID = (int) ($data["empID"] ?? 0);
    $tripID = (int) ($data["tripID"] ?? 0);
    $grossPay = (float) ($data["grossPay"] ?? 0);
    $deductions = (float) ($data["deductions"] ?? 0);
    $netPay = max($grossPay - $deductions, 0);
    $status = in_array(($data["status"] ?? "pending"), array("pending", "paid", "cancelled"), true) ? $data["status"] : "pending";

    if ($employeeID <= 0 || $grossPay <= 0) {
      return "invalid";
    }

    $credit = $tripID > 0 ? self::tripCreditDetails($pdo, $tripID, $employeeID) : self::emptyCredit();

    if ($tripID > 0 && !$credit) {
      return "invalid-trip";
    }

    if ($tripID > 0 && self::salaryExistsForTrip($pdo, $employeeID, $tripID)) {
      return "duplicate-trip";
    }

    $datePaid = $status === "paid" ? date("Y-m-d H:i:s") : null;
    $createdBy = isset($data["createdBy"]) && (int) $data["createdBy"] > 0 ? (int) $data["createdBy"] : null;

    $stmt = $pdo->prepare("
      INSERT INTO staffsalary (
        empID,
        tripID,
        creditedBookingID,
        creditedDistanceKm,
        tripRole,
        payPeriodStart,
        payPeriodEnd,
        payType,
        baseRate,
        grossPay,
        deductions,
        netPay,
        datePaid,
        status,
        remarks,
        createdBy,
        dateCreated
      ) VALUES (
        :empID,
        :tripID,
        :creditedBookingID,
        :creditedDistanceKm,
        :tripRole,
        :payPeriodStart,
        :payPeriodEnd,
        :payType,
        :baseRate,
        :grossPay,
        :deductions,
        :netPay,
        :datePaid,
        :status,
        :remarks,
        :createdBy,
        NOW()
      )
    ");

    $stmt->bindValue(":empID", $employeeID, PDO::PARAM_INT);
    self::bindNullableInt($stmt, ":tripID", $tripID > 0 ? $tripID : null);
    self::bindNullableInt($stmt, ":creditedBookingID", $credit["bookingID"]);
    $stmt->bindValue(":creditedDistanceKm", $credit["distanceKm"], PDO::PARAM_STR);
    $stmt->bindValue(":tripRole", $credit["tripRole"], PDO::PARAM_STR);
    $stmt->bindValue(":payPeriodStart", $data["payPeriodStart"] ?: date("Y-m-d"), PDO::PARAM_STR);
    $stmt->bindValue(":payPeriodEnd", $data["payPeriodEnd"] ?: ($data["payPeriodStart"] ?: date("Y-m-d")), PDO::PARAM_STR);
    $stmt->bindValue(":payType", self::cleanPayType($data["payType"] ?? "trip"), PDO::PARAM_STR);
    $stmt->bindValue(":baseRate", (float) ($data["baseRate"] ?? $grossPay), PDO::PARAM_STR);
    $stmt->bindValue(":grossPay", $grossPay, PDO::PARAM_STR);
    $stmt->bindValue(":deductions", $deductions, PDO::PARAM_STR);
    $stmt->bindValue(":netPay", $netPay, PDO::PARAM_STR);
    self::bindNullableString($stmt, ":datePaid", $datePaid);
    $stmt->bindValue(":status", $status, PDO::PARAM_STR);
    $stmt->bindValue(":remarks", trim((string) ($data["remarks"] ?? "")), PDO::PARAM_STR);
    self::bindNullableInt($stmt, ":createdBy", $createdBy);

    return $stmt->execute() ? "success" : "error";
  }

  static public function mdlMarkSalaryPaid($salaryID) {
    $pdo = (new Connection)->connect();

    $stmt = $pdo->prepare("
      UPDATE staffsalary
      SET status = 'paid',
          datePaid = COALESCE(datePaid, NOW())
      WHERE salaryID = :salaryID
    ");
    $stmt->bindValue(":salaryID", (int) $salaryID, PDO::PARAM_INT);

    return $stmt->execute() ? "success" : "error";
  }

  static private function tripCreditDetails($pdo, $tripID, $employeeID) {
    $crewStmt = $pdo->prepare("
      SELECT role
      FROM tripemployee
      WHERE tripID = :tripID
        AND empID = :empID
      LIMIT 1
    ");
    $crewStmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
    $crewStmt->bindValue(":empID", $employeeID, PDO::PARAM_INT);
    $crewStmt->execute();
    $role = $crewStmt->fetchColumn();

    if (!$role) {
      return false;
    }

    $stmt = $pdo->prepare("
      SELECT
        b.bookingID,
        pickup.latitude AS pickupLatitude,
        pickup.longitude AS pickupLongitude,
        destination.latitude AS destinationLatitude,
        destination.longitude AS destinationLongitude
      FROM booking b
      INNER JOIN location pickup ON pickup.locationID = b.pickupLocationID
      INNER JOIN location destination ON destination.locationID = b.destinationLocationID
      WHERE b.tripID = :tripID
        AND b.status IN ('completed', 'delivered', 'success', 'successful')
    ");
    $stmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
    $stmt->execute();

    $best = null;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $distance = self::distanceKm(
        (float) $row["pickupLatitude"],
        (float) $row["pickupLongitude"],
        (float) $row["destinationLatitude"],
        (float) $row["destinationLongitude"]
      );

      if ($best === null || $distance > $best["distanceKm"]) {
        $best = array(
          "bookingID" => (int) $row["bookingID"],
          "distanceKm" => round($distance, 2),
          "tripRole" => $role
        );
      }
    }

    return $best ?: false;
  }

  static private function salaryExistsForTrip($pdo, $employeeID, $tripID) {
    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM staffsalary
      WHERE empID = :empID
        AND tripID = :tripID
        AND status <> 'cancelled'
    ");
    $stmt->bindValue(":empID", $employeeID, PDO::PARAM_INT);
    $stmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
  }

  static private function emptyCredit() {
    return array("bookingID" => null, "distanceKm" => 0, "tripRole" => "");
  }

  static private function cleanPayType($payType) {
    $payType = strtolower(trim((string) $payType));
    $allowed = array("daily", "weekly", "semi-monthly", "monthly", "trip", "allowance", "bonus", "adjustment");

    return in_array($payType, $allowed, true) ? $payType : "trip";
  }

  static private function distanceKm($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
      cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
      sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
  }

  static private function bindValues($stmt, $bindings) {
    foreach ($bindings as $key => $value) {
      $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
  }

  static private function bindNullableInt($stmt, $key, $value) {
    if ($value === null) {
      $stmt->bindValue($key, null, PDO::PARAM_NULL);
      return;
    }

    $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
  }

  static private function bindNullableString($stmt, $key, $value) {
    if ($value === null || $value === "") {
      $stmt->bindValue($key, null, PDO::PARAM_NULL);
      return;
    }

    $stmt->bindValue($key, $value, PDO::PARAM_STR);
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
}
