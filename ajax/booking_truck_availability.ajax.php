<?php
session_start();

require_once __DIR__ . "/../controllers/booking.controller.php";
require_once __DIR__ . "/../models/booking.model.php";

header("Content-Type: application/json");

if (!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] !== "ok") {
  echo json_encode(array("available" => false, "status" => "unauthorized"));
  exit;
}

$answer = ControllerBooking::ctrTruckAvailability(
  $_POST["truckID"] ?? 0,
  $_POST["pickupDateTime"] ?? "",
  $_POST["excludeTripID"] ?? 0
);

echo json_encode($answer);
