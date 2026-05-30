<?php
require_once "connection.php";

class ModelCustomer {

  static public function mdlCompanyList() {
    $pdo = (new Connection)->connect();

    $hasLocationID = self::columnExists($pdo, "customer", "locationID") && self::tableExists($pdo, "location");
    $hasProvince = self::columnExists($pdo, "customer", "province");
    $hasWarehouseLatitude = self::columnExists($pdo, "customer", "warehouseLatitude");
    $hasWarehouseLongitude = self::columnExists($pdo, "customer", "warehouseLongitude");

    $customerProvince = $hasProvince ? "c.province" : "NULL";
    $customerLatitude = $hasWarehouseLatitude ? "c.warehouseLatitude" : "NULL";
    $customerLongitude = $hasWarehouseLongitude ? "c.warehouseLongitude" : "NULL";

    if ($hasLocationID) {
      $locationID = "c.locationID";
      $province = "COALESCE(NULLIF(l.province, ''), {$customerProvince}) AS province";
      $city = "l.city AS city";
      $barangay = "l.barangay AS barangay";
      $street = "l.street AS street";
      $description = "l.description AS description";
      $latitude = "COALESCE(NULLIF(l.latitude, 0), {$customerLatitude}) AS latitude";
      $longitude = "COALESCE(NULLIF(l.longitude, 0), {$customerLongitude}) AS longitude";
      $join = "LEFT JOIN location l ON l.locationID = c.locationID";
    } else {
      $locationID = "NULL AS locationID";
      $province = "{$customerProvince} AS province";
      $city = "NULL AS city";
      $barangay = "NULL AS barangay";
      $street = "NULL AS street";
      $description = "NULL AS description";
      $latitude = "{$customerLatitude} AS latitude";
      $longitude = "{$customerLongitude} AS longitude";
      $join = "";
    }

    $stmt = $pdo->prepare("
      SELECT
        c.id,
        {$locationID},
        c.customerFName,
        c.contactPerson,
        c.email,
        c.phoneNumber,
        {$province},
        {$city},
        {$barangay},
        {$street},
        {$description},
        {$latitude},
        {$longitude},
        {$latitude},
        {$longitude},
        c.companyDocument,
        c.dateRegistered,
        c.status
      FROM customer c
      {$join}
      WHERE c.customerType = 'company'
      ORDER BY c.customerFName, c.contactPerson
    ");

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
      $row["warehouseLatitude"] = $row["latitude"];
      $row["warehouseLongitude"] = $row["longitude"];
      $row["houseNumber"] = "";
    }

    return $rows;
  }

  static public function mdlSaveCustomer($data) {
    $pdo = (new Connection)->connect();

    try {
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->beginTransaction();

      $customerFName = "";
      $customerLName = "";
      $customerMI = "";
      $contactPerson = "";
      $companyDoc = "";
      $locationID = !empty($data["locationID"]) ? (int) $data["locationID"] : null;
      $province = trim($data["province"] ?? "");
      $warehouseLat = null;
      $warehouseLng = null;

      if ($data["customerType"] === "individual") {
        $customerFName = $data["firstName"] ?? "";
        $customerLName = $data["lastName"] ?? "";
        $customerMI = $data["middleInitial"] ?? "";
      }

      if ($data["customerType"] === "company") {
        $customerFName = $data["companyName"] ?? "";
        $contactPerson = $data["contactPerson"] ?? "";
        $warehouseLat = trim($data["warehouseLatitude"] ?? "") !== "" ? (float) $data["warehouseLatitude"] : null;
        $warehouseLng = trim($data["warehouseLongitude"] ?? "") !== "" ? (float) $data["warehouseLongitude"] : null;

        if (!empty($data["businessDoc"]["tmp_name"])) {
          $targetDir = __DIR__ . "/../uploads/";
          $fileName = time() . "_" . basename($data["businessDoc"]["name"]);
          $targetFile = $targetDir . $fileName;

          if (move_uploaded_file($data["businessDoc"]["tmp_name"], $targetFile)) {
            $companyDoc = $fileName;
          }
        }

        if (self::tableExists($pdo, "location") && self::columnExists($pdo, "customer", "locationID")) {
          $newLocationID = self::saveCompanyLocation($pdo, $data);
          if ($newLocationID) {
            $locationID = $newLocationID;
          }
        }
      }

      if ($province === "" && $locationID) {
        $province = self::getLocationProvince($pdo, $locationID);
      }

      $columns = array(
        "customerType",
        "customerFName",
        "customerLName",
        "customerMI",
        "contactPerson",
        "email",
        "phoneNumber",
        "companyDocument",
        "password",
        "dateRegistered",
        "status"
      );

      $values = array(
        ":customerType",
        ":customerFName",
        ":customerLName",
        ":customerMI",
        ":contactPerson",
        ":email",
        ":phoneNumber",
        ":companyDocument",
        ":password",
        "CURRENT_DATE",
        "'active'"
      );

      foreach (array("province", "warehouseLatitude", "warehouseLongitude", "locationID") as $columnName) {
        if (self::columnExists($pdo, "customer", $columnName)) {
          $columns[] = $columnName;
          $values[] = ":" . $columnName;
        }
      }

      $stmt = $pdo->prepare("
        INSERT INTO customer (`" . implode("`, `", $columns) . "`)
        VALUES (" . implode(", ", $values) . ")
      ");

      $stmt->bindParam(":customerType", $data["customerType"], PDO::PARAM_STR);
      $stmt->bindParam(":customerFName", $customerFName, PDO::PARAM_STR);
      $stmt->bindParam(":customerLName", $customerLName, PDO::PARAM_STR);
      $stmt->bindParam(":customerMI", $customerMI, PDO::PARAM_STR);
      $stmt->bindParam(":contactPerson", $contactPerson, PDO::PARAM_STR);
      $stmt->bindValue(":email", $data["email"] ?? "", PDO::PARAM_STR);
      $stmt->bindValue(":phoneNumber", $data["phoneNumber"] ?? "", PDO::PARAM_STR);
      $stmt->bindParam(":companyDocument", $companyDoc, PDO::PARAM_STR);
      $stmt->bindValue(":password", $data["password"] ?? "", PDO::PARAM_STR);

      if (in_array("province", $columns, true)) {
        $stmt->bindValue(":province", $province, PDO::PARAM_STR);
      }
      if (in_array("warehouseLatitude", $columns, true)) {
        $stmt->bindValue(":warehouseLatitude", $warehouseLat);
      }
      if (in_array("warehouseLongitude", $columns, true)) {
        $stmt->bindValue(":warehouseLongitude", $warehouseLng);
      }
      if (in_array("locationID", $columns, true)) {
        $stmt->bindValue(":locationID", $locationID, $locationID === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
      }

      $stmt->execute();

      $pdo->commit();
      return "success";
    } catch (PDOException $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }

      if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        return "existing";
      }

      return $e->getMessage();
    }
  }

  static public function mdlGetCustomerCredentials($tableUsers, $item, $value) {
    $allowedTables = array("customer");
    $allowedColumns = array("phoneNumber");

    if (!in_array($tableUsers, $allowedTables, true) || !in_array($item, $allowedColumns, true)) {
      return false;
    }

    $stmt = (new Connection)->connect()->prepare("
      SELECT *
      FROM {$tableUsers}
      WHERE {$item} = :value
      ORDER BY (password <> '') DESC, id DESC
      LIMIT 1
    ");
    $stmt->bindParam(":value", $value, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  static private function getLocationProvince($pdo, $locationID) {
    $stmt = $pdo->prepare("SELECT province FROM location WHERE locationID = :locationID LIMIT 1");
    $stmt->bindValue(":locationID", (int) $locationID, PDO::PARAM_INT);
    $stmt->execute();

    return trim((string) $stmt->fetchColumn());
  }

  static private function saveCompanyLocation($pdo, $data) {
    $province = trim($data["province"] ?? "");
    $city = trim($data["city"] ?? "");
    $barangay = trim($data["barangay"] ?? "");
    $street = trim($data["street"] ?? "");
    $houseNo = trim($data["houseNumber"] ?? "");
    $latitude = trim($data["warehouseLatitude"] ?? "");
    $longitude = trim($data["warehouseLongitude"] ?? "");

    if ($province === "" && $city === "" && $barangay === "" && $street === "" && $latitude === "" && $longitude === "") {
      return null;
    }

    $streetWithHouse = trim(implode(" ", array_filter(array($houseNo, $street))));
    $description = trim($data["description"] ?? "");

    if ($description === "") {
      $description = implode(", ", array_filter(array($streetWithHouse, $barangay, $city, $province, "Philippines")));
    }

    $stmt = $pdo->prepare("
      INSERT INTO location (province, city, barangay, street, description, latitude, longitude)
      VALUES (:province, :city, :barangay, :street, :description, :latitude, :longitude)
    ");

    $stmt->bindValue(":province", $province, PDO::PARAM_STR);
    $stmt->bindValue(":city", $city, PDO::PARAM_STR);
    $stmt->bindValue(":barangay", $barangay, PDO::PARAM_STR);
    $stmt->bindValue(":street", $streetWithHouse !== "" ? $streetWithHouse : $street, PDO::PARAM_STR);
    $stmt->bindValue(":description", $description, PDO::PARAM_STR);
    $stmt->bindValue(":latitude", $latitude !== "" ? (float) $latitude : 0);
    $stmt->bindValue(":longitude", $longitude !== "" ? (float) $longitude : 0);
    $stmt->execute();

    return (int) $pdo->lastInsertId();
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
