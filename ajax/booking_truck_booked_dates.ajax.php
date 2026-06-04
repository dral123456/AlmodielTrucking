<?php
session_start();

require_once __DIR__ . "/../controllers/booking.controller.php";
require_once __DIR__ . "/../models/booking.model.php";

header("Content-Type: application/json");

if (!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] !== "ok") {
  echo json_encode(array(
    "status" => "error",
    "allUnavailable" => false,
    "dates" => array()
  ));
  exit;
}

$availability = ControllerBooking::ctrTruckCalendarAvailability($_POST["truckID"] ?? 0);
$availability["status"] = $availability["status"] ?? "success";
$availability["requestStatus"] = "success";

echo json_encode($availability);
