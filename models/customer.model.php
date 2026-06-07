<?php
require_once "connection.php";
require_once "location.model.php";

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

  static public function mdlSaveIndividualCustomer($data) {
    $pdo = (new Connection)->connect();

    try {
        $pdo->beginTransaction();

        // ── 1. Resolve locationID (deduplication) ─────────────────────
        $locationID = isset($data["locationID"]) && (int)($data["locationID"]) > 0
            ? (int) $data["locationID"]
            : null;

        if (!$locationID) {
            $street = trim(
                ltrim(trim($data["houseNumber"] ?? "") . " " . trim($data["street"] ?? ""))
            );

            // Individuals have no map pin — coordinates intentionally omitted
            $locationID = ModelLocation::mdlSaveOrReuseLocation([
                "province"    => trim($data["province"]    ?? ""),
                "city"        => trim($data["city"]        ?? ""),
                "barangay"    => trim($data["barangay"]    ?? ""),
                "street"      => $street,
                "description" => trim($data["description"] ?? ""),
                "latitude"    => "",
                "longitude"   => "",
            ]);
        }

        if (!$locationID) {
            $pdo->rollBack();
            return "error";
        }

        // ── 2. Duplicate check ────────────────────────────────────────
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM customer
            WHERE phoneNumber = :phoneNumber
              AND customerType = 'individual'
              AND status = 'active'
        ");
        $check->bindValue(":phoneNumber", trim($data["phoneNumber"] ?? ""));
        $check->execute();

        if ((int) $check->fetchColumn() > 0) {
            $pdo->rollBack();
            return "existing";
        }

        // ── 3. Insert customer ────────────────────────────────────────
        $stmt = $pdo->prepare("
            INSERT INTO customer (
                customerType,
                customerFName,
                customerLName,
                customerMI,
                contactPerson,
                email,
                phoneNumber,
                locationID,
                companyDocument,
                password,
                dateRegistered,
                status
            ) VALUES (
                'individual',
                :firstName,
                :lastName,
                :middleInitial,
                '',
                :email,
                :phoneNumber,
                :locationID,
                '',
                :password,
                CURDATE(),
                'active'
            )
        ");

        $stmt->bindValue(":firstName",     trim($data["firstName"]     ?? ""));
        $stmt->bindValue(":lastName",      trim($data["lastName"]      ?? ""));
        $stmt->bindValue(":middleInitial", trim($data["middleInitial"] ?? ""));
        $stmt->bindValue(":email",         trim($data["email"]         ?? ""));
        $stmt->bindValue(":phoneNumber",   trim($data["phoneNumber"]   ?? ""));
        $stmt->bindValue(":locationID",    $locationID, PDO::PARAM_INT);
        $stmt->bindValue(":password",      $data["password"]);
        $stmt->execute();

        $pdo->commit();
        return "success";

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("INDIVIDUAL SAVE ERROR: " . $e->getMessage());
        return $e->getMessage();
    }
  }

  static public function mdlSaveCompanyCustomer($data) {
    $pdo = (new Connection)->connect();

    try {
        $pdo->beginTransaction();

        // ── 1. Resolve locationID (deduplication) ─────────────────────
        $locationID = isset($data["locationID"]) && (int)($data["locationID"]) > 0
            ? (int) $data["locationID"]
            : null;

        if (!$locationID) {
            $street = trim(
                ltrim(trim($data["houseNumber"] ?? "") . " " . trim($data["street"] ?? ""))
            );

            $locationID = ModelLocation::mdlSaveOrReuseLocation([
                "province"    => trim($data["province"]    ?? ""),
                "city"        => trim($data["city"]        ?? ""),
                "barangay"    => trim($data["barangay"]    ?? ""),
                "street"      => $street,
                "description" => trim($data["description"] ?? ""),
                "latitude"    => trim($data["warehouseLatitude"]  ?? ""),
                "longitude"   => trim($data["warehouseLongitude"] ?? ""),
            ]);
        }

        if (!$locationID) {
            $pdo->rollBack();
            return "error";
        }

        // ── 2. Duplicate check ────────────────────────────────────────
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM customer
            WHERE contactPerson = :contactPerson
              AND status = 'active'
        ");
        $check->bindValue(":contactPerson", trim($data["contactPerson"] ?? ""));
        $check->execute();

        if ((int) $check->fetchColumn() > 0) {
            $pdo->rollBack();
            return "existing";
        }

        // ── 3. Handle companyDocument filename ────────────────────────
        $companyDocument = "";

        if (!empty($data["businessDoc"]["tmp_name"])) {
            $uploadDir = __DIR__ . "/../uploads/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext             = pathinfo($data["businessDoc"]["name"], PATHINFO_EXTENSION);
            $companyDocument = time() . "_" . basename($data["businessDoc"]["name"]);
            $targetPath      = $uploadDir . $companyDocument;

            if (!move_uploaded_file($data["businessDoc"]["tmp_name"], $targetPath)) {
                $pdo->rollBack();
                return "error";
            }
        }

        // ── 4. Insert customer ────────────────────────────────────────
        $stmt = $pdo->prepare("
            INSERT INTO customer (
                customerType,
                customerFName,
                customerLName,
                customerMI,
                contactPerson,
                email,
                phoneNumber,
                locationID,
                companyDocument,
                password,
                dateRegistered,
                status
            ) VALUES (
                'company',
                :companyName,
                '',
                '',
                :contactPerson,
                :email,
                :phoneNumber,
                :locationID,
                :companyDocument,
                :password,
                CURDATE(),
                'active'
            )
        ");

        $stmt->bindValue(":companyName",      trim($data["companyName"]   ?? ""));
        $stmt->bindValue(":contactPerson",    trim($data["contactPerson"] ?? ""));
        $stmt->bindValue(":email",            trim($data["email"]         ?? ""));
        $stmt->bindValue(":phoneNumber",      trim($data["phoneNumber"]   ?? ""));
        $stmt->bindValue(":locationID",       $locationID, PDO::PARAM_INT);
        $stmt->bindValue(":companyDocument",  $companyDocument);
        $stmt->bindValue(":password",         $data["password"]);
        $stmt->execute();

        $pdo->commit();
        return "success";

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("COMPANY SAVE ERROR: " . $e->getMessage());
        return "error";
    }
  }

  static public function mdlSaveCustomer($data) {
    if (($data["customerType"] ?? "") === "company") {
        return self::mdlSaveCompanyCustomer($data);
    }

    return self::mdlSaveIndividualCustomer($data);
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

  static public function mdlGetCustomer($customerId) {
    $pdo = (new Connection)->connect();
    $stmt = $pdo->prepare("SELECT * FROM customer WHERE id = :customerId");
    $stmt->bindParam(":customerId", $customerId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
}