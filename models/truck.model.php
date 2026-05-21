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
