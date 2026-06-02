<?php
require_once __DIR__ . "/connection.php";

class ModelIncident {
  static public function mdlSaveIncident($data) {
    $pdo = (new Connection)->connect();
    self::ensureIncidentTable($pdo);

    $driverID = (int) ($data["driverID"] ?? 0);
    $tripID = (int) ($data["tripID"] ?? 0);
    $bookingID = (int) ($data["bookingID"] ?? 0);
    $incidentType = self::cleanOption($data["incidentType"] ?? "", self::incidentTypes(), "other");
    $severity = self::cleanOption($data["severity"] ?? "", self::severityLevels(), "medium");
    $incidentDateTime = self::normalizeDateTime($data["incidentDateTime"] ?? "");
    $locationText = trim((string) ($data["locationText"] ?? ""));
    $description = trim((string) ($data["description"] ?? ""));
    $actionTaken = trim((string) ($data["actionTaken"] ?? ""));

    if ($driverID <= 0 || $tripID <= 0 || $incidentDateTime === "" || $description === "") {
      return "invalid";
    }

    if (!self::driverOwnsTrip($pdo, $driverID, $tripID)) {
      return "forbidden";
    }

    if ($bookingID > 0 && !self::bookingBelongsToTrip($pdo, $bookingID, $tripID)) {
      return "invalid";
    }

    $stmt = $pdo->prepare("
      INSERT INTO incidentreport (
        tripID,
        bookingID,
        driverID,
        incidentType,
        severity,
        incidentDateTime,
        locationText,
        description,
        actionTaken,
        status,
        dateSubmitted,
        dateUpdated
      ) VALUES (
        :tripID,
        :bookingID,
        :driverID,
        :incidentType,
        :severity,
        :incidentDateTime,
        :locationText,
        :description,
        :actionTaken,
        'open',
        NOW(),
        NOW()
      )
    ");

    $stmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
    self::bindNullableInt($stmt, ":bookingID", $bookingID);
    $stmt->bindValue(":driverID", $driverID, PDO::PARAM_INT);
    $stmt->bindValue(":incidentType", $incidentType, PDO::PARAM_STR);
    $stmt->bindValue(":severity", $severity, PDO::PARAM_STR);
    $stmt->bindValue(":incidentDateTime", $incidentDateTime, PDO::PARAM_STR);
    $stmt->bindValue(":locationText", $locationText, PDO::PARAM_STR);
    $stmt->bindValue(":description", $description, PDO::PARAM_STR);
    $stmt->bindValue(":actionTaken", $actionTaken, PDO::PARAM_STR);

    return $stmt->execute() ? "success" : "error";
  }

  static public function mdlIncidentList() {
    $pdo = (new Connection)->connect();
    self::ensureIncidentTable($pdo);

    $stmt = $pdo->prepare("
      SELECT
        ir.incidentID,
        ir.tripID,
        ir.bookingID,
        ir.driverID,
        ir.incidentType,
        ir.severity,
        ir.incidentDateTime,
        ir.locationText,
        ir.description,
        ir.actionTaken,
        ir.status,
        ir.adminNotes,
        ir.dateSubmitted,
        ir.dateUpdated,
        COALESCE(NULLIF(TRIM(CONCAT(driver.empFName, ' ', driver.empLName)), ''), 'Driver') AS driverName,
        COALESCE(NULLIF(TRIM(CONCAT(reviewer.empFName, ' ', reviewer.empLName)), ''), '') AS reviewerName,
        COALESCE(NULLIF(TRIM(CONCAT(customer.customerFName, ' ', customer.customerLName)), ''), customer.contactPerson, '') AS customerName
      FROM incidentreport ir
      LEFT JOIN employee driver ON driver.id = ir.driverID
      LEFT JOIN employee reviewer ON reviewer.id = ir.reviewedBy
      LEFT JOIN booking b ON b.bookingID = ir.bookingID
      LEFT JOIN customer customer ON customer.id = b.customerID
      ORDER BY FIELD(ir.status, 'open', 'reviewing', 'resolved', 'dismissed'), ir.dateSubmitted DESC
    ");

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlUpdateIncidentStatus($incidentID, $status, $adminNotes, $reviewedBy) {
    $pdo = (new Connection)->connect();
    self::ensureIncidentTable($pdo);

    $incidentID = (int) $incidentID;
    $status = self::cleanOption($status, self::statuses(), "");
    $adminNotes = trim((string) $adminNotes);
    $reviewedBy = (int) $reviewedBy;

    if ($incidentID <= 0 || $status === "" || $reviewedBy <= 0) {
      return "invalid";
    }

    $stmt = $pdo->prepare("
      UPDATE incidentreport
      SET
        status = :status,
        adminNotes = :adminNotes,
        reviewedBy = :reviewedBy,
        dateUpdated = NOW()
      WHERE incidentID = :incidentID
    ");

    $stmt->bindValue(":status", $status, PDO::PARAM_STR);
    $stmt->bindValue(":adminNotes", $adminNotes, PDO::PARAM_STR);
    $stmt->bindValue(":reviewedBy", $reviewedBy, PDO::PARAM_INT);
    $stmt->bindValue(":incidentID", $incidentID, PDO::PARAM_INT);

    return $stmt->execute() ? "success" : "error";
  }

  static private function ensureIncidentTable($pdo) {
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS incidentreport (
        incidentID int NOT NULL AUTO_INCREMENT,
        tripID int NOT NULL,
        bookingID int NULL,
        driverID int NOT NULL,
        incidentType enum('accident','vehicle_breakdown','cargo_damage','delay','route_issue','customer_issue','other') NOT NULL DEFAULT 'other',
        severity enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
        incidentDateTime datetime NOT NULL,
        locationText varchar(255) NULL,
        description text NOT NULL,
        actionTaken text NULL,
        status enum('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open',
        adminNotes text NULL,
        reviewedBy int NULL,
        dateSubmitted datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        dateUpdated datetime NULL,
        PRIMARY KEY (incidentID),
        KEY idx_incidentreport_tripID (tripID),
        KEY idx_incidentreport_bookingID (bookingID),
        KEY idx_incidentreport_driverID (driverID),
        KEY idx_incidentreport_status (status)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");
  }

  static private function driverOwnsTrip($pdo, $driverID, $tripID) {
    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM tripemployee
      WHERE tripID = :tripID
        AND empID = :driverID
        AND role = 'driver'
    ");

    $stmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
    $stmt->bindValue(":driverID", $driverID, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
  }

  static private function bookingBelongsToTrip($pdo, $bookingID, $tripID) {
    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM booking
      WHERE bookingID = :bookingID
        AND tripID = :tripID
    ");

    $stmt->bindValue(":bookingID", $bookingID, PDO::PARAM_INT);
    $stmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
  }

  static private function cleanOption($value, $allowed, $fallback) {
    $value = strtolower(trim((string) $value));
    return in_array($value, $allowed, true) ? $value : $fallback;
  }

  static private function normalizeDateTime($value) {
    $value = trim((string) $value);
    if ($value === "") {
      return "";
    }

    $timestamp = strtotime(str_replace("T", " ", $value));
    return $timestamp ? date("Y-m-d H:i:s", $timestamp) : "";
  }

  static private function bindNullableInt($stmt, $key, $value) {
    $value = (int) $value;
    if ($value <= 0) {
      $stmt->bindValue($key, null, PDO::PARAM_NULL);
      return;
    }

    $stmt->bindValue($key, $value, PDO::PARAM_INT);
  }

  static private function incidentTypes() {
    return array("accident", "vehicle_breakdown", "cargo_damage", "delay", "route_issue", "customer_issue", "other");
  }

  static private function severityLevels() {
    return array("low", "medium", "high", "critical");
  }

  static private function statuses() {
    return array("open", "reviewing", "resolved", "dismissed");
  }
}
