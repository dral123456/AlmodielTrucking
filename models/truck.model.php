<?php
require_once "connection.php";

class ModelTruck {

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
          status
        ) VALUES (
          :plateNumber,
          :type,
          :capacity,
          :fuel,
          :mileage,
          :brand,
          'active'
        )
      ");

      $stmt->bindParam(":plateNumber", $data["plateNumber"], PDO::PARAM_STR);
      $stmt->bindParam(":type", $data["type"], PDO::PARAM_STR);
      $stmt->bindParam(":capacity", $data["capacity"]);
      $stmt->bindParam(":fuel", $data["fuel"], PDO::PARAM_INT);
      $stmt->bindParam(":mileage", $data["mileage"], PDO::PARAM_INT);
      $stmt->bindParam(":brand", $data["brand"], PDO::PARAM_STR);
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
