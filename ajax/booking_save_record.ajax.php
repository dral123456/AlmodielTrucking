<?php
session_start();

require_once "../controllers/booking.controller.php";
require_once "../models/booking.model.php";

class BookingRegistration {

  public function saveBooking() {

    // Assistants (JSON safe decode)
    $assistantIDs = [];

    if (!empty($_POST["assistantIDs"])) {
      $decodedAssistants = json_decode($_POST["assistantIDs"], true);
      if (is_array($decodedAssistants)) {
        $assistantIDs = $decodedAssistants;
      }
    }

    $cargoItems = array();

    if (isset($_POST["cargoItems"])) {
      $decodedCargo = json_decode($_POST["cargoItems"], true);
      if (is_array($decodedCargo)) {
        $cargoItems = $decodedCargo;
      }
    }

    $cargoItems = array_values(array_filter($cargoItems, function ($item) {
      return isset($item["cargoType"], $item["quantity"]) &&
        trim((string) $item["cargoType"]) !== "" &&
        (int) $item["quantity"] > 0;
    }));

    if (empty($cargoItems)) {
      echo "error";
      return;
    }
    // 🔥 SAFE POST ACCESS (prevents undefined key warnings)
    $customerID      = $_POST["customerID"] ?? null;
    $truckID         = $_POST["truckID"] ?? null;
    $driverID        = $_POST["driverID"] ?? null;
    $pickupDateTime  = $_POST["pickupDateTime"] ?? null;
    $price           = $_POST["price"] ?? null;
    $cargoCondition  = $_POST["cargoCondition"] ?? null;
    $cargoDescription = $_POST["cargoDescription"] ?? null;
    $cargoSpecialHandling = $_POST["cargoSpecialHandling"] ?? null;

    // 🚨 NEW FLOW: LOCATION IDS ONLY (NO ADDRESS FIELDS HERE)
    $pickupLocationID      = $_POST["pickupLocationID"] ?? null;
    $destinationLocationID = $_POST["destinationLocationID"] ?? null;

    // 🔴 VALIDATION (IMPORTANT)
    if (
      empty($customerID) ||
      empty($truckID) ||
      empty($driverID) ||
      empty($pickupLocationID) ||
      empty($destinationLocationID)
    ) {
      echo "Missing required booking data or location IDs.";
      return;
    }

    // if (
    //   !$this->isValidNegrosCoordinate($_POST["pickupLatitude"] ?? null, $_POST["pickupLongitude"] ?? null) ||
    //   !$this->isValidNegrosCoordinate($_POST["destinationLatitude"] ?? null, $_POST["destinationLongitude"] ?? null)
    // ) {
    //   echo "error";
    //   return;
    // }
    $data = array(
      "customerID" => $_POST["customerID"],
      "truckID" => $_POST["truckID"],
      "pickupDateTime" => $_POST["pickupDateTime"],
      "price" => $_POST["price"],
      "pickupLocationID"     => $_POST["pickupLocationID"] ?? null,      // ← ADD
      "destinationLocationID"=> $_POST["destinationLocationID"] ?? null, // ← ADD
      "createdBy" => isset($_SESSION["id"]) ? $_SESSION["id"] : 0,

      "crew" => array(
        "driverID" => $_POST["driverID"],
        "assistantIDs" => json_decode($_POST["assistantIDs"] ?? "[]", true)
      ),

      "cargo" => array(
        "items" => $cargoItems,
        "condition" => $_POST["cargoCondition"],
        "description" => $_POST["cargoDescription"],
        "specialHandling" => $_POST["cargoSpecialHandling"]
      ),

      // ✅ FIXED STRUCTURE
      "pickup" => array(
        "province" => $_POST["pickupProvince"] ?? null,
        "city" => $_POST["pickupCity"] ?? null,
        "barangay" => $_POST["pickupBarangay"] ?? null,
        "street" => $_POST["pickupStreet"] ?? null,
        "description" => $_POST["pickupDescription"] ?? null,
        "latitude" => $_POST["pickupLatitude"] ?? null,
        "longitude" => $_POST["pickupLongitude"] ?? null
      ),

      "destination" => array(
        "province" => $_POST["destinationProvince"] ?? null,
        "city" => $_POST["destinationCity"] ?? null,
        "barangay" => $_POST["destinationBarangay"] ?? null,
        "street" => $_POST["destinationStreet"] ?? null,
        "description" => $_POST["destinationDescription"] ?? null,
        "latitude" => $_POST["destinationLatitude"] ?? null,
        "longitude" => $_POST["destinationLongitude"] ?? null
      )
    );

    $answer = (new ControllerBooking)->ctrSaveBooking($data);
    echo $answer;
  }

  private function isValidNegrosCoordinate($latitude, $longitude) {
    if ($latitude === null || $longitude === null || $latitude === "" || $longitude === "") {
      return false;
    }

    $lat = (float) $latitude;
    $lng = (float) $longitude;

    return is_finite($lat) &&
      is_finite($lng) &&
      $lat >= 9 &&
      $lat <= 11.2 &&
      $lng >= 122 &&
      $lng <= 123.6;
  }
}

$save_booking = new BookingRegistration();
$save_booking->saveBooking();