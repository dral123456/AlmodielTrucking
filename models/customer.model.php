<?php
require_once "connection.php";

class ModelCustomer {

  static public function mdlSaveCustomer($data) {

    $db = new Connection();
    $pdo = $db->connect();

    try {
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->beginTransaction();

      // 🟡 Prepare default values
      $customerFName = "";
      $customerLName = "";
      $customerMI    = "";
      $contactPerson = "";
      $companyDoc    = "";

    // 🟢 Handle Individual
  if ($data["customerType"] === "individual") {
    $customerFName = $data["firstName"];
    $customerLName = $data["lastName"];
    $customerMI    = $data["middleInitial"];
  }

    // 🔵 Handle Company
  if ($data["customerType"] === "company") {
    $customerFName = $data["companyName"]; // ✅ store company name here
    $customerLName = "";                   // optional: keep empty
    $customerMI    = "";                   // optional: keep empty

    $contactPerson = $data["contactPerson"];

    // File upload
    if (!empty($data["businessDoc"]["tmp_name"])) {
      $targetDir = __DIR__ . "/../uploads/";
      $fileName = time() . "_" . basename($data["businessDoc"]["name"]);
      $targetFile = $targetDir . $fileName;

      if (move_uploaded_file($data["businessDoc"]["tmp_name"], $targetFile)) {
      $companyDoc = $fileName;
      }
    }
  }

      // 🟣 Insert Query
      $stmt = $pdo->prepare("
        INSERT INTO customer (
          customerType,
          customerFName,
          customerLName,
          customerMI,
          contactPerson,
          email,
          phoneNumber,
          province,
          city,
          barangay,
          street,
          houseNumber,
          companyDocument,
          dateRegistered,
          status
        ) VALUES (
          :customerType,
          :customerFName,
          :customerLName,
          :customerMI,
          :contactPerson,
          :email,
          :phoneNumber,
          :province,
          :city,
          :barangay,
          :street,
          :houseNumber,
          :companyDocument,
          CURRENT_DATE,
          'active'
        )
      ");

      // 🔗 Bind values
      $stmt->bindParam(":customerType", $data["customerType"], PDO::PARAM_STR);
      $stmt->bindParam(":customerFName", $customerFName, PDO::PARAM_STR);
      $stmt->bindParam(":customerLName", $customerLName, PDO::PARAM_STR);
      $stmt->bindParam(":customerMI", $customerMI, PDO::PARAM_STR);
      $stmt->bindParam(":contactPerson", $contactPerson, PDO::PARAM_STR);
      $stmt->bindParam(":email", $data["email"], PDO::PARAM_STR);
      $stmt->bindParam(":phoneNumber", $data["phoneNumber"], PDO::PARAM_STR);
      $stmt->bindParam(":province", $data["province"], PDO::PARAM_STR);
      $stmt->bindParam(":city", $data["city"], PDO::PARAM_STR);
      $stmt->bindParam(":barangay", $data["barangay"], PDO::PARAM_STR);
      $stmt->bindParam(":street", $data["street"], PDO::PARAM_STR);
      $stmt->bindParam(":houseNumber", $data["houseNumber"], PDO::PARAM_STR);
      $stmt->bindParam(":companyDocument", $companyDoc, PDO::PARAM_STR);

      $stmt->execute();

      $pdo->commit();
      return "success";

    } catch (PDOException $e) {

      $pdo->rollBack();

      // Duplicate (optional if you add UNIQUE email later)
      if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        return "existing";
      }

      return "error";
    }
  }
}