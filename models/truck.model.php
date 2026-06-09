<?php
require_once "connection.php";

class ModelTruck {

  static public function mdlTruckManageList() {
    $pdo = (new Connection)->connect();
    $truckEmployeeTable = self::resolveTruckEmployeeTable($pdo);
    $safeTruckEmployeeTable = "`" . str_replace("`", "``", $truckEmployeeTable) . "`";
    $documentSelect = self::columnExists($pdo, "truck", "corDocument") &&
      self::columnExists($pdo, "truck", "otherDocument")
      ? "t.corDocument, t.otherDocument"
      : "NULL AS corDocument, NULL AS otherDocument";

    $stmt = $pdo->prepare("
      SELECT
        t.id,
        t.plateNumber,
        t.type,
        t.capacity,
        t.fuel,
        t.mileage,
        t.brand,
        t.status,
        {$documentSelect},
        GROUP_CONCAT(
          CONCAT(te.role, ': ', e.empFName, ' ', e.empLName)
          ORDER BY FIELD(te.role, 'driver', 'assistant'), e.empFName, e.empLName
          SEPARATOR '||'
        ) AS crew,
        MAX(CASE WHEN te.role = 'driver' THEN te.empID END) AS driverID,
        GROUP_CONCAT(
          CASE WHEN te.role = 'assistant' THEN te.empID END
          ORDER BY te.truckEmployeeID
          SEPARATOR ','
        ) AS assistantIDs
      FROM truck t
      LEFT JOIN {$safeTruckEmployeeTable} te ON te.truckID = t.id
      LEFT JOIN employee e ON e.id = te.empID
      GROUP BY t.id, t.plateNumber, t.type, t.capacity, t.fuel, t.mileage, t.brand, t.status, t.corDocument, t.otherDocument
      ORDER BY t.status DESC, t.plateNumber
    ");

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlEmployeeListByType($type) {
    $stmt = (new Connection)->connect()->prepare("
      SELECT id, empFName, empLName
      FROM employee
      WHERE empType = :type AND empStatus = 'active'
      ORDER BY empFName, empLName
    ");

    $stmt->bindParam(":type", $type, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static public function mdlSaveTruck($data) {
    $db = new Connection();
    $pdo = $db->connect();

    try {
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->beginTransaction();

      $stmt = $pdo->prepare("
        INSERT INTO truck (
          plateNumber,
          type,
          capacity,
          fuel,
          mileage,
          brand,
          corDocument,
          otherDocument,
          status
        ) VALUES (
          :plateNumber,
          :type,
          :capacity,
          :fuel,
          :mileage,
          :brand,
          :corDocument,
          :otherDocument,
          'active'
        )
      ");

      $stmt->bindParam(":plateNumber", $data["plateNumber"], PDO::PARAM_STR);
      $stmt->bindParam(":type", $data["type"], PDO::PARAM_STR);
      $stmt->bindParam(":capacity", $data["capacity"]);
      $stmt->bindParam(":fuel", $data["fuel"], PDO::PARAM_INT);
      $stmt->bindParam(":mileage", $data["mileage"], PDO::PARAM_INT);
      $stmt->bindParam(":brand", $data["brand"], PDO::PARAM_STR);
      $stmt->bindParam(":corDocument", $data["corDocument"], PDO::PARAM_STR);
      $stmt->bindParam(":otherDocument", $data["otherDocument"], PDO::PARAM_STR);
      $stmt->execute();

      $truckId = $pdo->lastInsertId();
      $junctionTable = self::resolveTruckEmployeeTable($pdo);

      self::insertTruckEmployee($pdo, $junctionTable, $truckId, $data["driverID"], "driver");
      self::insertTruckEmployee($pdo, $junctionTable, $truckId, $data["assistant1ID"], "assistant");
      self::insertTruckEmployee($pdo, $junctionTable, $truckId, $data["assistant2ID"], "assistant");

      $pdo->commit();
      return "success";

    } catch (PDOException $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }

      if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        return "existing";
      }

      return "error";
    }
  }

  static public function mdlTruckDetails($truckID) {
    $truckID = (int) $truckID;
    if ($truckID <= 0) {
      return null;
    }

    $pdo = (new Connection)->connect();
    self::mdlEnsureTruckUsageTables($pdo);
    $documentSelect = self::columnExists($pdo, "truck", "corDocument") &&
      self::columnExists($pdo, "truck", "otherDocument")
      ? "corDocument, otherDocument"
      : "NULL AS corDocument, NULL AS otherDocument";

    $stmt = $pdo->prepare("
      SELECT id, plateNumber, type, capacity, fuel, mileage, brand, status, {$documentSelect}
      FROM truck
      WHERE id = :truckID
      LIMIT 1
    ");
    $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
    $stmt->execute();
    $truck = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$truck) {
      return null;
    }

    $truck["crew"] = self::truckCrewRows($pdo, $truckID);
    $truck["fuelLogs"] = self::truckFuelLogRows($pdo, $truckID);
    $truck["fuelMovements"] = self::truckFuelMovementRows($pdo, $truckID);
    $truck["trips"] = self::truckTripRows($pdo, $truckID);
    $truck["fuelEfficiencyKmPerLiter"] = self::calculateFuelEfficiency($truck["fuelLogs"]);
    $truck["fuelEfficiencySource"] = count($truck["fuelLogs"]) >= 2 ? "truck logs" : "default estimate";
    $truck["currentTrip"] = null;

    foreach ($truck["trips"] as $trip) {
      if (in_array($trip["status"], array("in-transit", "stopover"), true)) {
        $truck["currentTrip"] = $trip;
        break;
      }
    }

    $truck["operationalStatus"] = $truck["status"] === "inactive"
      ? "inactive"
      : ($truck["currentTrip"] ? "on-trip" : "available");

    if ($truck["currentTrip"]) {
      $oneWayDistance = (float) $truck["currentTrip"]["totalDistanceKm"];
      $distance = $oneWayDistance * 2;
      $efficiency = max((float) $truck["fuelEfficiencyKmPerLiter"], 0.1);
      $fuelNeeded = $distance / $efficiency;

      $truck["currentTrip"]["oneWayDistanceKm"] = round($oneWayDistance, 2);
      $truck["currentTrip"]["roundTripDistanceKm"] = round($distance, 2);
      $truck["currentTrip"]["estimatedFuelNeeded"] = round($fuelNeeded, 2);
      $truck["currentTrip"]["estimatedRemainingFuel"] = round(max((float) $truck["fuel"] - $fuelNeeded, 0), 2);
      $truck["currentTrip"]["projectedMileage"] = round((float) $truck["mileage"] + $distance, 2);
    }

    return $truck;
  }

  static public function mdlSaveTruckFuelLog($data) {
    $truckID = (int) ($data["truckID"] ?? 0);
    $litersAdded = (float) ($data["litersAdded"] ?? 0);
    $amount = (float) ($data["amount"] ?? 0);
    $odometer = (float) ($data["odometer"] ?? 0);
    $fuelAfterInput = $data["fuelAfter"] ?? "";
    $logDate = trim((string) ($data["logDate"] ?? ""));

    if ($truckID <= 0 || $litersAdded <= 0 || $odometer < 0 || $logDate === "") {
      return "invalid";
    }

    $pdo = (new Connection)->connect();
    self::ensureTruckFuelLogTable($pdo);

    try {
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->beginTransaction();

      $stmt = $pdo->prepare("SELECT fuel, mileage FROM truck WHERE id = :truckID FOR UPDATE");
      $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
      $stmt->execute();
      $truck = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$truck) {
        $pdo->rollBack();
        return "not-found";
      }

      $fuelAfter = $fuelAfterInput === "" ? (float) $truck["fuel"] + $litersAdded : (float) $fuelAfterInput;
      if ($fuelAfter < 0) {
        $pdo->rollBack();
        return "invalid";
      }

      $stmt = $pdo->prepare("
        INSERT INTO truckfuellog (
          truckID,
          logDate,
          litersAdded,
          fuelAfter,
          odometer,
          amount,
          station,
          referenceNo,
          notes,
          createdBy,
          dateCreated
        ) VALUES (
          :truckID,
          :logDate,
          :litersAdded,
          :fuelAfter,
          :odometer,
          :amount,
          :station,
          :referenceNo,
          :notes,
          :createdBy,
          NOW()
        )
      ");
      $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
      $stmt->bindValue(":logDate", $logDate, PDO::PARAM_STR);
      $stmt->bindValue(":litersAdded", $litersAdded, PDO::PARAM_STR);
      $stmt->bindValue(":fuelAfter", $fuelAfter, PDO::PARAM_STR);
      $stmt->bindValue(":odometer", $odometer, PDO::PARAM_STR);
      $stmt->bindValue(":amount", $amount, PDO::PARAM_STR);
      $stmt->bindValue(":station", trim((string) ($data["station"] ?? "")), PDO::PARAM_STR);
      $stmt->bindValue(":referenceNo", trim((string) ($data["referenceNo"] ?? "")), PDO::PARAM_STR);
      $stmt->bindValue(":notes", trim((string) ($data["notes"] ?? "")), PDO::PARAM_STR);
      self::bindNullableInt($stmt, ":createdBy", $data["createdBy"] ?? null);
      $stmt->execute();

      $nextMileage = max((float) $truck["mileage"], $odometer);
      $stmt = $pdo->prepare("UPDATE truck SET fuel = :fuel, mileage = :mileage WHERE id = :truckID");
      $stmt->bindValue(":fuel", $fuelAfter, PDO::PARAM_STR);
      $stmt->bindValue(":mileage", $nextMileage, PDO::PARAM_STR);
      $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
      $stmt->execute();

      $pdo->commit();
      return "success";
    } catch (PDOException $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      return "error";
    }
  }

  static public function mdlUpdateTruckReadings($data) {
    $truckID = (int) ($data["truckID"] ?? 0);
    $fuel = (float) ($data["fuel"] ?? -1);
    $mileage = (float) ($data["mileage"] ?? -1);

    if ($truckID <= 0 || $fuel < 0 || $mileage < 0) {
      return "invalid";
    }

    $stmt = (new Connection)->connect()->prepare("
      UPDATE truck
      SET fuel = :fuel, mileage = :mileage
      WHERE id = :truckID
    ");
    $stmt->bindValue(":fuel", $fuel, PDO::PARAM_STR);
    $stmt->bindValue(":mileage", $mileage, PDO::PARAM_STR);
    $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);

    return $stmt->execute() && $stmt->rowCount() >= 0 ? "success" : "error";
  }

  static public function mdlEnsureTruckUsageTables($pdo = null) {
    $pdo = $pdo ?: (new Connection)->connect();
    self::ensureTruckFuelLogTable($pdo);
    self::ensureTruckTripUsageTable($pdo);
  }

  static public function mdlApplyCompletedTripUsage($pdo, $tripID) {
    $tripID = (int) $tripID;
    if ($tripID <= 0) {
      return "invalid";
    }

    $stmt = $pdo->prepare("
      SELECT MIN(truckID)
      FROM tripemployee
      WHERE tripID = :tripID
        AND truckID IS NOT NULL
    ");
    $stmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
    $stmt->execute();
    $truckID = (int) $stmt->fetchColumn();

    if ($truckID <= 0) {
      return "skipped";
    }

    $oneWayDistance = self::tripDistanceInKilometers($pdo, $tripID);
    $roundTripDistance = $oneWayDistance * 2;
    if ($roundTripDistance <= 0) {
      return "skipped";
    }

    $ownsTransaction = !$pdo->inTransaction();

    try {
      if ($ownsTransaction) {
        $pdo->beginTransaction();
      }

      $stmt = $pdo->prepare("
        SELECT truckTripUsageID
        FROM trucktripusage
        WHERE tripID = :tripID
        LIMIT 1
        FOR UPDATE
      ");
      $stmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
      $stmt->execute();
      if ($stmt->fetchColumn()) {
        if ($ownsTransaction) {
          $pdo->commit();
        }
        return "already-applied";
      }

      $stmt = $pdo->prepare("SELECT fuel, mileage FROM truck WHERE id = :truckID LIMIT 1 FOR UPDATE");
      $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
      $stmt->execute();
      $truck = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$truck) {
        if ($ownsTransaction) {
          $pdo->rollBack();
        }
        return "skipped";
      }

      $efficiency = self::calculateFuelEfficiency(self::truckFuelLogRows($pdo, $truckID));
      $fuelBefore = (float) $truck["fuel"];
      $mileageBefore = (float) $truck["mileage"];
      $fuelUsed = round($roundTripDistance / max($efficiency, 0.1), 2);
      $fuelAfter = round(max($fuelBefore - $fuelUsed, 0), 2);
      $mileageAfter = round($mileageBefore + $roundTripDistance, 2);

      $stmt = $pdo->prepare("
        INSERT INTO trucktripusage (
          tripID,
          truckID,
          oneWayDistanceKm,
          roundTripDistanceKm,
          efficiencyKmPerLiter,
          fuelUsed,
          fuelBefore,
          fuelAfter,
          mileageBefore,
          mileageAfter,
          dateCreated
        ) VALUES (
          :tripID,
          :truckID,
          :oneWayDistanceKm,
          :roundTripDistanceKm,
          :efficiencyKmPerLiter,
          :fuelUsed,
          :fuelBefore,
          :fuelAfter,
          :mileageBefore,
          :mileageAfter,
          NOW()
        )
      ");
      $stmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
      $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
      $stmt->bindValue(":oneWayDistanceKm", round($oneWayDistance, 2), PDO::PARAM_STR);
      $stmt->bindValue(":roundTripDistanceKm", round($roundTripDistance, 2), PDO::PARAM_STR);
      $stmt->bindValue(":efficiencyKmPerLiter", $efficiency, PDO::PARAM_STR);
      $stmt->bindValue(":fuelUsed", $fuelUsed, PDO::PARAM_STR);
      $stmt->bindValue(":fuelBefore", $fuelBefore, PDO::PARAM_STR);
      $stmt->bindValue(":fuelAfter", $fuelAfter, PDO::PARAM_STR);
      $stmt->bindValue(":mileageBefore", $mileageBefore, PDO::PARAM_STR);
      $stmt->bindValue(":mileageAfter", $mileageAfter, PDO::PARAM_STR);
      $stmt->execute();

      $stmt = $pdo->prepare("
        UPDATE truck
        SET fuel = :fuelAfter,
            mileage = :mileageAfter
        WHERE id = :truckID
      ");
      $stmt->bindValue(":fuelAfter", $fuelAfter, PDO::PARAM_STR);
      $stmt->bindValue(":mileageAfter", $mileageAfter, PDO::PARAM_STR);
      $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
      $stmt->execute();

      if ($ownsTransaction) {
        $pdo->commit();
      }
      return "success";
    } catch (PDOException $e) {
      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
      }

      if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
        return "already-applied";
      }
      return "error";
    }
  }

  static private function truckCrewRows($pdo, $truckID) {
    $table = self::resolveTruckEmployeeTable($pdo);
    $safeTable = "`" . str_replace("`", "``", $table) . "`";
    $stmt = $pdo->prepare("
      SELECT te.empID, te.role, e.empFName, e.empLName
      FROM {$safeTable} te
      INNER JOIN employee e ON e.id = te.empID
      WHERE te.truckID = :truckID
      ORDER BY FIELD(te.role, 'driver', 'assistant'), e.empFName, e.empLName
    ");
    $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static private function truckFuelLogRows($pdo, $truckID) {
    $stmt = $pdo->prepare("
      SELECT
        truckFuelLogID,
        logDate,
        litersAdded,
        fuelAfter,
        odometer,
        amount,
        station,
        referenceNo,
        notes,
        dateCreated
      FROM truckfuellog
      WHERE truckID = :truckID
      ORDER BY logDate DESC, truckFuelLogID DESC
      LIMIT 100
    ");
    $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  static private function truckFuelMovementRows($pdo, $truckID) {
    $movements = array();

    foreach (self::truckFuelLogRows($pdo, $truckID) as $log) {
      $fuelAfter = (float) $log["fuelAfter"];
      $fuelIn = (float) $log["litersAdded"];
      $movements[] = array(
        "movementType" => "in",
        "movementDate" => $log["logDate"],
        "fuelIn" => $fuelIn,
        "fuelOut" => 0,
        "fuelBefore" => max($fuelAfter - $fuelIn, 0),
        "fuelAfter" => $fuelAfter,
        "odometer" => (float) $log["odometer"],
        "amount" => (float) $log["amount"],
        "source" => $log["station"] ?: "Fuel entry",
        "referenceNo" => $log["referenceNo"] ?: "",
        "notes" => $log["notes"] ?: "",
        "tripID" => null
      );
    }

    $stmt = $pdo->prepare("
      SELECT
        tripID,
        fuelUsed,
        fuelBefore,
        fuelAfter,
        mileageAfter,
        roundTripDistanceKm,
        dateCreated
      FROM trucktripusage
      WHERE truckID = :truckID
      ORDER BY dateCreated DESC, truckTripUsageID DESC
      LIMIT 100
    ");
    $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
    $stmt->execute();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $usage) {
      $movements[] = array(
        "movementType" => "out",
        "movementDate" => $usage["dateCreated"],
        "fuelIn" => 0,
        "fuelOut" => (float) $usage["fuelUsed"],
        "fuelBefore" => (float) $usage["fuelBefore"],
        "fuelAfter" => (float) $usage["fuelAfter"],
        "odometer" => (float) $usage["mileageAfter"],
        "amount" => 0,
        "source" => "Completed Trip #" . $usage["tripID"],
        "referenceNo" => "",
        "notes" => "Estimated fuel used for " . number_format((float) $usage["roundTripDistanceKm"], 2) . " km round trip.",
        "tripID" => (int) $usage["tripID"]
      );
    }

    usort($movements, function ($a, $b) {
      return strtotime($b["movementDate"]) <=> strtotime($a["movementDate"]);
    });

    return array_slice($movements, 0, 100);
  }

  static private function truckTripRows($pdo, $truckID) {
    $stmt = $pdo->prepare("
      SELECT
        b.bookingID,
        b.tripID,
        b.pickupDateTime,
        b.status,
        c.customerFName,
        c.customerLName,
        c.contactPerson,
        pickup.latitude AS pickupLatitude,
        pickup.longitude AS pickupLongitude,
        destination.latitude AS destinationLatitude,
        destination.longitude AS destinationLongitude
      FROM booking b
      INNER JOIN customer c ON c.id = b.customerID
      LEFT JOIN location pickup ON pickup.locationID = b.pickupLocationID
      LEFT JOIN location destination ON destination.locationID = b.destinationLocationID
      WHERE EXISTS (
        SELECT 1
        FROM tripemployee te
        WHERE te.tripID = b.tripID
          AND te.truckID = :truckID
      )
      ORDER BY b.pickupDateTime DESC, b.tripID DESC, b.bookingID ASC
    ");
    $stmt->bindValue(":truckID", $truckID, PDO::PARAM_INT);
    $stmt->execute();

    $trips = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $tripID = (string) $row["tripID"];
      if (!isset($trips[$tripID])) {
        $trips[$tripID] = array(
          "tripID" => (int) $row["tripID"],
          "pickupDateTime" => $row["pickupDateTime"],
          "status" => $row["status"],
          "bookingCount" => 0,
          "totalDistanceKm" => 0,
          "customers" => array(),
          "statuses" => array()
        );
      }

      $customerName = trim(($row["customerFName"] ?? "") . " " . ($row["customerLName"] ?? ""));
      if ($customerName === "") {
        $customerName = $row["contactPerson"] ?? "Customer";
      }

      $distance = self::distanceInKilometers(
        (float) ($row["pickupLatitude"] ?? 0),
        (float) ($row["pickupLongitude"] ?? 0),
        (float) ($row["destinationLatitude"] ?? 0),
        (float) ($row["destinationLongitude"] ?? 0)
      );

      $trips[$tripID]["bookingCount"]++;
      $trips[$tripID]["totalDistanceKm"] += $distance;
      $trips[$tripID]["customers"][$customerName] = $customerName;
      $trips[$tripID]["statuses"][] = $row["status"];
    }

    foreach ($trips as $tripID => $trip) {
      $trips[$tripID]["totalDistanceKm"] = round($trip["totalDistanceKm"], 2);
      $trips[$tripID]["customers"] = array_values($trip["customers"]);
      $trips[$tripID]["status"] = self::deriveTripStatus($trip["statuses"]);
      unset($trips[$tripID]["statuses"]);
    }

    return array_values($trips);
  }

  static private function deriveTripStatus($statuses) {
    if (in_array("in-transit", $statuses, true)) {
      return "in-transit";
    }
    if (in_array("stopover", $statuses, true)) {
      return "stopover";
    }
    if (!empty($statuses) && count(array_unique($statuses)) === 1 && $statuses[0] === "completed") {
      return "completed";
    }
    return "pending";
  }

  static private function calculateFuelEfficiency($fuelLogs) {
    if (count($fuelLogs) < 2) {
      return 7.0;
    }

    $logs = array_reverse($fuelLogs);
    $samples = array();
    for ($index = 1; $index < count($logs); $index++) {
      $distance = (float) $logs[$index]["odometer"] - (float) $logs[$index - 1]["odometer"];
      $liters = (float) $logs[$index]["litersAdded"];
      if ($distance > 0 && $liters > 0) {
        $efficiency = $distance / $liters;
        if ($efficiency >= 1 && $efficiency <= 20) {
          $samples[] = $efficiency;
        }
      }
    }

    if (!$samples) {
      return 7.0;
    }

    return round(array_sum($samples) / count($samples), 2);
  }

  static private function distanceInKilometers($lat1, $lng1, $lat2, $lng2) {
    if (!$lat1 || !$lng1 || !$lat2 || !$lng2) {
      return 0;
    }

    $earthRadius = 6371;
    $latDistance = deg2rad($lat2 - $lat1);
    $lngDistance = deg2rad($lng2 - $lng1);
    $a = sin($latDistance / 2) * sin($latDistance / 2) +
      cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
      sin($lngDistance / 2) * sin($lngDistance / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
  }

  static private function tripDistanceInKilometers($pdo, $tripID) {
    $stmt = $pdo->prepare("
      SELECT
        pickup.latitude AS pickupLatitude,
        pickup.longitude AS pickupLongitude,
        destination.latitude AS destinationLatitude,
        destination.longitude AS destinationLongitude
      FROM booking b
      LEFT JOIN location pickup ON pickup.locationID = b.pickupLocationID
      LEFT JOIN location destination ON destination.locationID = b.destinationLocationID
      WHERE b.tripID = :tripID
    ");
    $stmt->bindValue(":tripID", $tripID, PDO::PARAM_INT);
    $stmt->execute();

    $distance = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $distance += self::distanceInKilometers(
        (float) ($row["pickupLatitude"] ?? 0),
        (float) ($row["pickupLongitude"] ?? 0),
        (float) ($row["destinationLatitude"] ?? 0),
        (float) ($row["destinationLongitude"] ?? 0)
      );
    }

    return round($distance, 2);
  }

  static private function ensureTruckFuelLogTable($pdo) {
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS truckfuellog (
        truckFuelLogID int NOT NULL AUTO_INCREMENT,
        truckID int NOT NULL,
        logDate datetime NOT NULL,
        litersAdded double NOT NULL DEFAULT 0,
        fuelAfter double NOT NULL DEFAULT 0,
        odometer double NOT NULL DEFAULT 0,
        amount double NOT NULL DEFAULT 0,
        station varchar(150) NULL,
        referenceNo varchar(100) NULL,
        notes text NULL,
        createdBy int NULL,
        dateCreated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (truckFuelLogID),
        KEY idx_truckfuellog_truckID (truckID),
        KEY idx_truckfuellog_logDate (logDate)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
  }

  static private function ensureTruckTripUsageTable($pdo) {
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS trucktripusage (
        truckTripUsageID int NOT NULL AUTO_INCREMENT,
        tripID int NOT NULL,
        truckID int NOT NULL,
        oneWayDistanceKm double NOT NULL DEFAULT 0,
        roundTripDistanceKm double NOT NULL DEFAULT 0,
        efficiencyKmPerLiter double NOT NULL DEFAULT 0,
        fuelUsed double NOT NULL DEFAULT 0,
        fuelBefore double NOT NULL DEFAULT 0,
        fuelAfter double NOT NULL DEFAULT 0,
        mileageBefore double NOT NULL DEFAULT 0,
        mileageAfter double NOT NULL DEFAULT 0,
        dateCreated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (truckTripUsageID),
        UNIQUE KEY uq_trucktripusage_tripID (tripID),
        KEY idx_trucktripusage_truckID (truckID)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
  }

  static private function bindNullableInt($stmt, $key, $value) {
    if ($value === null || $value === "" || (int) $value <= 0) {
      $stmt->bindValue($key, null, PDO::PARAM_NULL);
      return;
    }

    $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
  }

  static private function resolveTruckEmployeeTable($pdo) {
    $candidates = ["truckemployee", "truck_employee"];

    foreach ($candidates as $table) {
      $stmt = $pdo->prepare("SHOW TABLES LIKE :tableName");
      $stmt->bindParam(":tableName", $table, PDO::PARAM_STR);
      $stmt->execute();

      if ($stmt->fetchColumn()) {
        return $table;
      }
    }

    return "truckemployee";
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

  static private function insertTruckEmployee($pdo, $table, $truckId, $empId, $role) {
    $safeTable = "`" . str_replace("`", "``", $table) . "`";
    $dateCreated = date("Y-m-d H:i:s");

    $stmt = $pdo->prepare("
      INSERT INTO {$safeTable} (
        truckID,
        empID,
        role,
        dateCreated
      ) VALUES (
        :truckID,
        :empID,
        :role,
        :dateCreated
      )
    ");

    $stmt->bindParam(":truckID", $truckId, PDO::PARAM_INT);
    $stmt->bindParam(":empID", $empId, PDO::PARAM_INT);
    $stmt->bindParam(":role", $role, PDO::PARAM_STR);
    $stmt->bindParam(":dateCreated", $dateCreated, PDO::PARAM_STR);
    $stmt->execute();
  }
}
