<?php
require_once "connection.php";

class ModelLocation {

  static public function mdlSaveLocation($data) {
    $pdo = (new Connection)->connect();

    try {
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      $stmt = $pdo->prepare("
        INSERT INTO location (
          province,
          city,
          barangay,
          street,
          description,
          latitude,
          longitude
        ) VALUES (
          :province,
          :city,
          :barangay,
          :street,
          :description,
          :latitude,
          :longitude
        )
      ");

      $stmt->bindParam(":province",    $data["province"],    PDO::PARAM_STR);
      $stmt->bindParam(":city",        $data["city"],        PDO::PARAM_STR);
      $stmt->bindParam(":barangay",    $data["barangay"],    PDO::PARAM_STR);
      $stmt->bindParam(":street",      $data["street"],      PDO::PARAM_STR);
      $stmt->bindParam(":description", $data["description"], PDO::PARAM_STR);
      $stmt->bindParam(":latitude",    $data["latitude"]);
      $stmt->bindParam(":longitude",   $data["longitude"]);
      $stmt->execute();

      return $pdo->lastInsertId();

    } catch (PDOException $e) {
      return null;
    }
  }
}