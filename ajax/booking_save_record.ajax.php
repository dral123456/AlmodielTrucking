<?php
session_start();

require_once "../controllers/booking.controller.php";
require_once "../models/booking.model.php";

class BookingRegistration {
  public function saveBooking() {
    $assistantIDs = array();

    if (isset($_POST["assistantIDs"])) {
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

    $data = array(
      "customerID" => $_POST["customerID"],
      "truckID" => $_POST["truckID"],
      "pickupDateTime" => $_POST["pickupDateTime"],
      "price" => $_POST["price"],
      "createdBy" => isset($_SESSION["id"]) ? $_SESSION["id"] : 0,
      "crew" => array(
        "driverID" => $_POST["driverID"],
        "assistantIDs" => $assistantIDs
      ),
      "cargo" => array(
        "items" => $cargoItems,
        "condition" => $_POST["cargoCondition"],
        "description" => $_POST["cargoDescription"],
        "specialHandling" => $_POST["cargoSpecialHandling"]
      ),
      "pickup" => array(
        "province" => $_POST["pickupProvince"],
        "city" => $_POST["pickupCity"],
        "barangay" => $_POST["pickupBarangay"],
        "street" => $_POST["pickupStreet"],
        "description" => $_POST["pickupDescription"],
        "latitude" => $_POST["pickupLatitude"],
        "longitude" => $_POST["pickupLongitude"]
      ),
      "destination" => array(
        "province" => $_POST["destinationProvince"],
        "city" => $_POST["destinationCity"],
        "barangay" => $_POST["destinationBarangay"],
        "street" => $_POST["destinationStreet"],
        "description" => $_POST["destinationDescription"],
        "latitude" => $_POST["destinationLatitude"],
        "longitude" => $_POST["destinationLongitude"]
      )
    );

    $answer = (new ControllerBooking)->ctrSaveBooking($data);
    echo $answer;
  }
}

$save_booking = new BookingRegistration();
$save_booking->saveBooking();
