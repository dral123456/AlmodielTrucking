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
        "cargoType" => $_POST["cargoType"],
        "quantity" => $_POST["cargoQuantity"],
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
