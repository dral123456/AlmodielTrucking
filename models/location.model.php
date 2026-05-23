<?php
require_once "connection.php";

class ModelLocation {

  // ─────────────────────────────────────────────────────────────
  // Find nearby existing location
  // ─────────────────────────────────────────────────────────────
  static public function mdlFindNearbyLocation($data, $radiusMeters = 50) {

    $pdo = (new Connection)->connect();

    $province   = strtolower(trim($data["province"] ?? ""));
    $city       = strtolower(trim($data["city"] ?? ""));
    $barangay   = strtolower(trim($data["barangay"] ?? ""));
    $street     = strtolower(trim($data["street"] ?? ""));
    $description= strtolower(trim($data["description"] ?? ""));

    $latitude   = $data["latitude"] ?? null;
    $longitude  = $data["longitude"] ?? null;

    if (
      $latitude === null ||
      $longitude === null ||
      $latitude === "" ||
      $longitude === ""
    ) {
      return null;
    }

    $stmt = $pdo->prepare("
      SELECT
        locationID,

        (
          6371000 * acos(
            LEAST(
              1.0,
              cos(radians(:lat1))
              * cos(radians(latitude))
              * cos(radians(longitude) - radians(:lng1))
              + sin(radians(:lat2))
              * sin(radians(latitude))
            )
          )
        ) AS distance

      FROM location

      WHERE
        LOWER(province) = :province
        AND LOWER(city) = :city

        AND (
          (:barangay <> '' AND LOWER(barangay) = :barangay)
          OR
          (:street <> '' AND LOWER(street) = :street)
          OR
          (:description <> '' AND LOWER(description) = :description)
        )

      HAVING distance < :radius

      ORDER BY distance ASC

      LIMIT 1
    ");

    $stmt->bindParam(":lat1", $latitude);
    $stmt->bindParam(":lat2", $latitude);
    $stmt->bindParam(":lng1", $longitude);

    $stmt->bindParam(":province", $province, PDO::PARAM_STR);
    $stmt->bindParam(":city", $city, PDO::PARAM_STR);

    $stmt->bindParam(":barangay", $barangay, PDO::PARAM_STR);
    $stmt->bindParam(":street", $street, PDO::PARAM_STR);
    $stmt->bindParam(":description", $description, PDO::PARAM_STR);

    $stmt->bindParam(":radius", $radiusMeters, PDO::PARAM_INT);

    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int)$row["locationID"] : null;
  }

  // ─────────────────────────────────────────────────────────────
  // Save new location
  // ─────────────────────────────────────────────────────────────
  static public function mdlSaveLocation($data) {

    $pdo = (new Connection)->connect();

    try {

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

      $stmt->bindValue(":province", trim($data["province"] ?? ""));
      $stmt->bindValue(":city", trim($data["city"] ?? ""));
      $stmt->bindValue(":barangay", trim($data["barangay"] ?? ""));
      $stmt->bindValue(":street", trim($data["street"] ?? ""));
      $stmt->bindValue(":description", trim($data["description"] ?? ""));
      $stmt->bindValue(":latitude", $data["latitude"]);
      $stmt->bindValue(":longitude", $data["longitude"]);

      $stmt->execute();

      return (int)$pdo->lastInsertId();

    } catch (PDOException $e) {
      die("LOCATION ERROR: " . $e->getMessage());
    }
  }

  // ─────────────────────────────────────────────────────────────
  // Save OR reuse existing location
  // ─────────────────────────────────────────────────────────────
  static public function mdlSaveOrReuseLocation($data) {

    $existingLocationID = self::mdlFindNearbyLocation($data);

    if ($existingLocationID) {
      return $existingLocationID;
    }

    return self::mdlSaveLocation($data);
  }

  // ─────────────────────────────────────────────────────────────
  // Search locations
  // ─────────────────────────────────────────────────────────────
  static public function mdlSearchLocations($query, $limit = 8) {

    $pdo = (new Connection)->connect();

    $like = "%" . $query . "%";

    $stmt = $pdo->prepare("
      SELECT
        locationID,
        province,
        city,
        barangay,
        street,
        description,
        latitude,
        longitude,

        (
          CASE WHEN street LIKE :q1 THEN 4 ELSE 0 END +
          CASE WHEN description LIKE :q2 THEN 3 ELSE 0 END +
          CASE WHEN barangay LIKE :q3 THEN 2 ELSE 0 END +
          CASE WHEN city LIKE :q4 THEN 1 ELSE 0 END +
          CASE WHEN province LIKE :q5 THEN 1 ELSE 0 END
        ) AS relevance

      FROM location

      WHERE
        province LIKE :q6
        OR city LIKE :q7
        OR barangay LIKE :q8
        OR street LIKE :q9
        OR description LIKE :q10

      ORDER BY relevance DESC, city ASC, barangay ASC

      LIMIT :lim
    ");

    $stmt->bindParam(":q1", $like, PDO::PARAM_STR);
    $stmt->bindParam(":q2", $like, PDO::PARAM_STR);
    $stmt->bindParam(":q3", $like, PDO::PARAM_STR);
    $stmt->bindParam(":q4", $like, PDO::PARAM_STR);
    $stmt->bindParam(":q5", $like, PDO::PARAM_STR);

    $stmt->bindParam(":q6", $like, PDO::PARAM_STR);
    $stmt->bindParam(":q7", $like, PDO::PARAM_STR);
    $stmt->bindParam(":q8", $like, PDO::PARAM_STR);
    $stmt->bindParam(":q9", $like, PDO::PARAM_STR);
    $stmt->bindParam(":q10", $like, PDO::PARAM_STR);

    $stmt->bindParam(":lim", $limit, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}