<?php
session_start();

require_once "../controllers/booking.controller.php";
require_once "../models/booking.model.php";

$tripID = isset($_POST["tripID"]) ? (int) $_POST["tripID"] : 0;
$assistantIDs = json_decode($_POST["assistantIDs"] ?? "[]", true);

if (!is_array($assistantIDs)) {
  $assistantIDs = array();
}

$data = array(
  "pickupDateTime" => $_POST["pickupDateTime"] ?? "",
  "status" => $_POST["status"] ?? "",
  "truckID" => $_POST["truckID"] ?? 0,
  "driverID" => $_POST["driverID"] ?? 0,
  "assistantIDs" => $assistantIDs,
  "bookingID" => $_POST["bookingID"] ?? 0,
  "price" => $_POST["price"] ?? "",
  "destination" => array(
    "province" => $_POST["destinationProvince"] ?? "",
    "city" => $_POST["destinationCity"] ?? "",
    "barangay" => $_POST["destinationBarangay"] ?? "",
    "street" => $_POST["destinationStreet"] ?? "",
    "description" => $_POST["destinationDescription"] ?? "",
    "latitude" => $_POST["destinationLatitude"] ?? "",
    "longitude" => $_POST["destinationLongitude"] ?? ""
  )
);

if ($tripID <= 0) {
  echo json_encode(array("status" => "error", "message" => "Invalid trip."));
  exit;
}

$answer = ControllerBooking::ctrUpdateTripInfo($tripID, $data);

echo json_encode(array(
  "status" => $answer === "success" ? "success" : "error",
  "message" => $answer === "success" ? "Trip updated." : "Unable to update trip."
));
