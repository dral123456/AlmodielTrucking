<?php
session_start();

require_once __DIR__ . "/../controllers/truck.controller.php";
require_once __DIR__ . "/../models/truck.model.php";

header("Content-Type: application/json");

if (!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] !== "ok" || ($_SESSION["role"] ?? "") !== "admin") {
  echo json_encode(array("status" => "error", "message" => "Only admins can update truck readings."));
  exit;
}

$action = trim((string) ($_POST["action"] ?? ""));
$data = array(
  "truckID" => $_POST["truckID"] ?? 0,
  "fuel" => $_POST["fuel"] ?? -1,
  "mileage" => $_POST["mileage"] ?? -1,
  "logDate" => $_POST["logDate"] ?? "",
  "litersAdded" => $_POST["litersAdded"] ?? 0,
  "fuelAfter" => $_POST["fuelAfter"] ?? "",
  "odometer" => $_POST["odometer"] ?? 0,
  "amount" => $_POST["amount"] ?? 0,
  "station" => $_POST["station"] ?? "",
  "referenceNo" => $_POST["referenceNo"] ?? "",
  "notes" => $_POST["notes"] ?? "",
  "createdBy" => $_SESSION["id"] ?? 0
);

if ($action === "fuel") {
  $answer = ControllerTruck::ctrSaveTruckFuelLog($data);
} elseif ($action === "readings") {
  $answer = ControllerTruck::ctrUpdateTruckReadings($data);
} else {
  $answer = "invalid";
}

$messages = array(
  "success" => "Truck information saved.",
  "invalid" => "Please check the required values.",
  "not-found" => "Truck record was not found.",
  "error" => "Unable to save truck information."
);

echo json_encode(array(
  "status" => $answer === "success" ? "success" : "error",
  "message" => $messages[$answer] ?? $messages["error"]
));
