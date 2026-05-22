<?php
require_once "connection.php";

class ModelTariff {
  static public function mdlLookupTariff($data) {
    $pdo = (new Connection)->connect();

    if (!self::tableExists($pdo, "tariff")) {
      return array("status" => "missing-table");
    }

    $customerID = (int) ($data["customerID"] ?? 0);
    $truckType = self::normalizeTruckType($data["truckType"] ?? "");
    $destinationText = self::normalizeText($data["destinationText"] ?? "");
    $fuelPrice = isset($data["fuelPrice"]) && $data["fuelPrice"] !== "" ? (float) $data["fuelPrice"] : null;

    if ($customerID <= 0 || $truckType === "" || $destinationText === "") {
      return array("status" => "invalid");
    }

    $stmt = $pdo->prepare("
      SELECT
        tariffID,
        customerID,
        branch,
        origin,
        destination,
        distanceKm,
        baseRate,
        truckType,
        fuelRangeStart,
        fuelRangeEnd,
        hasFuelSubsidy,
        fuelSubsidy,
        status
      FROM tariff
      WHERE status = 'active'
        AND (customerID = :customerID OR customerID IS NULL)
    ");
    $stmt->bindValue(":customerID", $customerID, PDO::PARAM_INT);
    $stmt->execute();

    $best = null;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      if (self::normalizeTruckType($row["truckType"]) !== $truckType) {
        continue;
      }

      $score = self::destinationScore($row["destination"], $destinationText);
      if ($score <= 0) {
        continue;
      }

      if ($best === null ||
          $score > $best["score"] ||
          ($score === $best["score"] && $row["customerID"] !== null && $best["row"]["customerID"] === null)) {
        $best = array("score" => $score, "row" => $row);
      }
    }

    if (!$best) {
      return array("status" => "not-found");
    }

    $row = $best["row"];
    $hasFuelSubsidy = !isset($row["hasFuelSubsidy"]) || (int) $row["hasFuelSubsidy"] === 1;
    $fuelSubsidy = $hasFuelSubsidy ? self::calculateFuelSubsidy((float) $row["distanceKm"], $fuelPrice, (float) $row["fuelRangeEnd"]) : 0;
    $baseRate = (float) $row["baseRate"];

    return array(
      "status" => "success",
      "tariffID" => (int) $row["tariffID"],
      "branch" => $row["branch"],
      "origin" => $row["origin"],
      "destination" => $row["destination"],
      "distanceKm" => (float) $row["distanceKm"],
      "baseRate" => $baseRate,
      "fuelPrice" => $fuelPrice,
      "fuelBaseMax" => (float) $row["fuelRangeEnd"],
      "hasFuelSubsidy" => $hasFuelSubsidy,
      "fuelSubsidy" => $fuelSubsidy,
      "totalRate" => $baseRate + $fuelSubsidy,
      "truckType" => $row["truckType"],
      "matchScore" => $best["score"],
      "isCompanySpecific" => $row["customerID"] !== null
    );
  }

  static public function mdlCompanyList() {
    $stmt = (new Connection)->connect()->prepare("
      SELECT
        id,
        COALESCE(NULLIF(TRIM(customerFName), ''), contactPerson, CONCAT('Company #', id)) AS companyName,
        contactPerson
      FROM customer
      WHERE customerType = 'company'
        AND status = 'active'
      ORDER BY companyName
    ");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlTariffRows($customerID = null) {
    $pdo = (new Connection)->connect();

    if (!self::tableExists($pdo, "tariff")) {
      return array();
    }

    $where = "1=1";
    $bindings = array();

    if ($customerID !== null && (int) $customerID > 0) {
      $where = "t.customerID = :customerID";
      $bindings[":customerID"] = (int) $customerID;
    }

    $stmt = $pdo->prepare("
      SELECT
        t.*,
        COALESCE(NULLIF(TRIM(c.customerFName), ''), c.contactPerson, CONCAT('Company #', c.id), 'Default') AS companyName
      FROM tariff t
      LEFT JOIN customer c ON c.id = t.customerID
      WHERE {$where}
      ORDER BY companyName, t.truckType, t.distanceKm, t.destination
    ");
    self::bindValues($stmt, $bindings);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlSaveTariff($data) {
    $pdo = (new Connection)->connect();

    if (!self::tableExists($pdo, "tariff")) {
      return "missing-table";
    }

    $tariffID = (int) ($data["tariffID"] ?? 0);
    $customerID = (int) ($data["customerID"] ?? 0);
    $destination = trim((string) ($data["destination"] ?? ""));
    $truckType = trim((string) ($data["truckType"] ?? ""));

    if ($customerID <= 0 || $destination === "" || $truckType === "") {
      return "invalid";
    }

    if ($tariffID > 0) {
      $stmt = $pdo->prepare("
        UPDATE tariff
        SET customerID = :customerID,
            branch = :branch,
            origin = :origin,
            destination = :destination,
            distanceKm = :distanceKm,
            truckType = :truckType,
            baseRate = :baseRate,
            fuelRangeStart = :fuelRangeStart,
            fuelRangeEnd = :fuelRangeEnd,
            hasFuelSubsidy = :hasFuelSubsidy,
            fuelSubsidy = :fuelSubsidy,
            status = :status
        WHERE tariffID = :tariffID
      ");
      $stmt->bindValue(":tariffID", $tariffID, PDO::PARAM_INT);
    } else {
      $stmt = $pdo->prepare("
        INSERT INTO tariff (
          customerID,
          branch,
          origin,
          destination,
          distanceKm,
          truckType,
          baseRate,
          fuelRangeStart,
          fuelRangeEnd,
          hasFuelSubsidy,
          fuelSubsidy,
          status,
          dateCreated
        ) VALUES (
          :customerID,
          :branch,
          :origin,
          :destination,
          :distanceKm,
          :truckType,
          :baseRate,
          :fuelRangeStart,
          :fuelRangeEnd,
          :hasFuelSubsidy,
          :fuelSubsidy,
          :status,
          NOW()
        )
      ");
    }

    self::bindTariffValues($stmt, $data);

    try {
      return $stmt->execute() ? "success" : "error";
    } catch (PDOException $e) {
      return "duplicate";
    }
  }

  static public function mdlArchiveTariff($tariffID) {
    $stmt = (new Connection)->connect()->prepare("
      UPDATE tariff
      SET status = 'inactive'
      WHERE tariffID = :tariffID
    ");
    $stmt->bindValue(":tariffID", (int) $tariffID, PDO::PARAM_INT);

    return $stmt->execute() ? "success" : "error";
  }

  static public function mdlBulkUpdateFuelRange($data) {
    $pdo = (new Connection)->connect();

    if (!self::tableExists($pdo, "tariff")) {
      return array("status" => "missing-table", "updated" => 0);
    }

    $customerID = (int) ($data["customerID"] ?? 0);
    $fuelRangeStart = $data["fuelRangeStart"] ?? "";
    $fuelRangeEnd = $data["fuelRangeEnd"] ?? "";

    if ($customerID <= 0 || $fuelRangeStart === "" || $fuelRangeEnd === "") {
      return array("status" => "invalid", "updated" => 0);
    }

    $truckType = self::normalizeTruckType($data["truckType"] ?? "");
    $stmt = $pdo->prepare("
      SELECT tariffID, truckType
      FROM tariff
      WHERE customerID = :customerID
    ");
    $stmt->bindValue(":customerID", $customerID, PDO::PARAM_INT);
    $stmt->execute();

    $tariffIDs = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      if ($truckType !== "" && self::normalizeTruckType($row["truckType"]) !== $truckType) {
        continue;
      }

      $tariffIDs[] = (int) $row["tariffID"];
    }

    if (empty($tariffIDs)) {
      return array("status" => "success", "updated" => 0);
    }

    $placeholders = array();
    $bindings = array(
      ":fuelRangeStart" => (float) $fuelRangeStart,
      ":fuelRangeEnd" => (float) $fuelRangeEnd,
      ":hasFuelSubsidy" => !empty($data["hasFuelSubsidy"]) ? 1 : 0
    );

    foreach ($tariffIDs as $index => $tariffID) {
      $key = ":tariffID{$index}";
      $placeholders[] = $key;
      $bindings[$key] = $tariffID;
    }

    $update = $pdo->prepare("
      UPDATE tariff
      SET fuelRangeStart = :fuelRangeStart,
          fuelRangeEnd = :fuelRangeEnd,
          hasFuelSubsidy = :hasFuelSubsidy,
          fuelSubsidy = 0
      WHERE tariffID IN (" . implode(",", $placeholders) . ")
    ");
    self::bindValues($update, $bindings);

    return $update->execute()
      ? array("status" => "success", "updated" => $update->rowCount())
      : array("status" => "error", "updated" => 0);
  }

  static public function mdlImportTariffCsv($rows, $defaults) {
    $saved = 0;
    $skipped = 0;

    foreach ($rows as $row) {
      $data = array(
        "customerID" => $defaults["customerID"] ?? 0,
        "branch" => self::csvValue($row, array("branch"), $defaults["branch"] ?? "BACOLOD"),
        "origin" => self::csvValue($row, array("origin"), $defaults["origin"] ?? "BACOLOD"),
        "destination" => self::csvValue($row, array("destination", "destination area", "route"), ""),
        "distanceKm" => self::csvNumber(self::csvValue($row, array("distance", "distancekm", "distance km"), 0)),
        "truckType" => $defaults["truckType"] ?? self::csvValue($row, array("trucktype", "truck type"), ""),
        "baseRate" => self::csvNumber(self::csvValue($row, array("currentrate", "current rate", "base rate", "baserate"), 0)),
        "fuelRangeStart" => self::csvNumber($defaults["fuelRangeStart"] ?? 60),
        "fuelRangeEnd" => self::csvNumber($defaults["fuelRangeEnd"] ?? 65),
        "hasFuelSubsidy" => !empty($defaults["hasFuelSubsidy"]) ? 1 : 0,
        "fuelSubsidy" => 0,
        "status" => "active"
      );

      if ((int) $data["customerID"] <= 0 || trim($data["destination"]) === "" || trim($data["truckType"]) === "" || (float) $data["baseRate"] <= 0) {
        $skipped++;
        continue;
      }

      $existingID = self::existingTariffID($data);
      if ($existingID) {
        $data["tariffID"] = $existingID;
      }

      $result = self::mdlSaveTariff($data);
      if ($result === "success") {
        $saved++;
      } else {
        $skipped++;
      }
    }

    return array("status" => "success", "saved" => $saved, "skipped" => $skipped);
  }

  static private function existingTariffID($data) {
    $stmt = (new Connection)->connect()->prepare("
      SELECT tariffID
      FROM tariff
      WHERE customerID = :customerID
        AND branch = :branch
        AND origin = :origin
        AND destination = :destination
        AND truckType = :truckType
      LIMIT 1
    ");
    $stmt->bindValue(":customerID", (int) $data["customerID"], PDO::PARAM_INT);
    $stmt->bindValue(":branch", trim((string) $data["branch"]), PDO::PARAM_STR);
    $stmt->bindValue(":origin", trim((string) $data["origin"]), PDO::PARAM_STR);
    $stmt->bindValue(":destination", trim((string) $data["destination"]), PDO::PARAM_STR);
    $stmt->bindValue(":truckType", trim((string) $data["truckType"]), PDO::PARAM_STR);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
  }

  static private function destinationScore($tariffDestination, $destinationText) {
    $destination = self::normalizeText($tariffDestination);
    if ($destination === "") {
      return 0;
    }

    if (strpos($destinationText, $destination) !== false) {
      return strlen($destination) + 100;
    }

    $parts = preg_split("/[\/,\-]+/", (string) $tariffDestination);
    $score = 0;

    foreach ($parts as $part) {
      $part = self::normalizeText($part);
      if (strlen($part) < 3) {
        continue;
      }

      if (strpos($destinationText, $part) !== false) {
        $score = max($score, strlen($part));
      }
    }

    return $score;
  }

  static private function calculateFuelSubsidy($distanceKm, $fuelPrice, $baseFuelMax) {
    if ($fuelPrice === null || $fuelPrice <= $baseFuelMax || $distanceKm <= 0) {
      return 0;
    }

    $band = (int) ceil(($fuelPrice - $baseFuelMax) / 10);

    return round($band * ($distanceKm * 10 / 7), 2);
  }

  static public function normalizeTruckType($value) {
    $raw = strtolower(trim((string) $value));

    if (preg_match("/\b(4|6|8|10)\s*(w|wheel|wheeler|wheelers)\b/", $raw, $match)) {
      return $match[1] . "w";
    }

    $value = str_replace(array("wheelers", "wheeler", "wheels", "wheel"), "w", $raw);
    $value = preg_replace("/[^a-z0-9]+/", "", $value);

    if (preg_match("/^(4|6|8|10)w/", $value, $match)) {
      return $match[1] . "w";
    }

    return $value;
  }

  static private function normalizeText($value) {
    $value = strtolower(trim((string) $value));
    $value = preg_replace("/[^a-z0-9]+/", " ", $value);

    return trim(preg_replace("/\s+/", " ", $value));
  }

  static private function tableExists($pdo, $tableName) {
    $stmt = $pdo->prepare("SHOW TABLES LIKE :tableName");
    $stmt->bindParam(":tableName", $tableName, PDO::PARAM_STR);
    $stmt->execute();

    return (bool) $stmt->fetchColumn();
  }

  static private function bindTariffValues($stmt, $data) {
    $stmt->bindValue(":customerID", (int) $data["customerID"], PDO::PARAM_INT);
    $stmt->bindValue(":branch", trim((string) ($data["branch"] ?? "BACOLOD")), PDO::PARAM_STR);
    $stmt->bindValue(":origin", trim((string) ($data["origin"] ?? "BACOLOD")), PDO::PARAM_STR);
    $stmt->bindValue(":destination", trim((string) ($data["destination"] ?? "")), PDO::PARAM_STR);
    $stmt->bindValue(":distanceKm", (float) ($data["distanceKm"] ?? 0), PDO::PARAM_STR);
    $stmt->bindValue(":truckType", trim((string) ($data["truckType"] ?? "")), PDO::PARAM_STR);
    $stmt->bindValue(":baseRate", (float) ($data["baseRate"] ?? 0), PDO::PARAM_STR);
    $stmt->bindValue(":fuelRangeStart", $data["fuelRangeStart"] === "" ? null : (float) $data["fuelRangeStart"], $data["fuelRangeStart"] === "" ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(":fuelRangeEnd", $data["fuelRangeEnd"] === "" ? null : (float) $data["fuelRangeEnd"], $data["fuelRangeEnd"] === "" ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(":hasFuelSubsidy", !empty($data["hasFuelSubsidy"]) ? 1 : 0, PDO::PARAM_INT);
    $stmt->bindValue(":fuelSubsidy", (float) ($data["fuelSubsidy"] ?? 0), PDO::PARAM_STR);
    $stmt->bindValue(":status", in_array(($data["status"] ?? "active"), array("active", "inactive"), true) ? $data["status"] : "active", PDO::PARAM_STR);
  }

  static private function csvValue($row, $keys, $fallback) {
    foreach ($keys as $key) {
      $normalized = self::normalizeHeader($key);
      if (isset($row[$normalized]) && trim((string) $row[$normalized]) !== "") {
        return trim((string) $row[$normalized]);
      }
    }

    return $fallback;
  }

  static public function normalizeHeader($value) {
    return preg_replace("/[^a-z0-9]+/", "", strtolower(trim((string) $value)));
  }

  static private function csvNumber($value) {
    return (float) str_replace(",", "", (string) $value);
  }

  static private function bindValues($stmt, $bindings) {
    foreach ($bindings as $key => $value) {
      $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
  }
}
