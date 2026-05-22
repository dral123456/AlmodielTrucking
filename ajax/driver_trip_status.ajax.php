<?php
session_start();

require_once "../controllers/booking.controller.php";
require_once "../models/booking.model.php";

header("Content-Type: application/json");

if (!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] !== "ok") {
  echo json_encode(array("status" => "error", "message" => "Not logged in"));
  exit;
}

$tripID = isset($_POST["tripID"]) ? (int) $_POST["tripID"] : 0;
$status = isset($_POST["status"]) ? trim($_POST["status"]) : "";
$driverID = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
$role = $_SESSION["role"] ?? "";
$showAll = in_array($role, array("admin", "employee"), true);

if ($tripID <= 0 || !in_array($status, array("in-transit", "stopover", "completed"), true)) {
  echo json_encode(array("status" => "error", "message" => "Invalid request"));
  exit;
}

$answer = ControllerBooking::ctrUpdateTripDeliveryStatus($tripID, $status, $driverID, $showAll);

echo json_encode(array(
  "status" => $answer === "success" ? "success" : "error",
  "message" => $answer === "success" ? "Trip updated" : "Unable to update trip"
));
