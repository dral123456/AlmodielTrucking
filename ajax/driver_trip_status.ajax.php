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
$messages = array(
  "success" => "Trip updated",
  "invalid" => "Invalid delivery status.",
  "not-assigned" => "This trip is not assigned to your driver account.",
  "already-completed" => "This trip is already completed.",
  "invalid-transition" => "Please start the delivery before marking stopover or delivered.",
  "not-updated" => "No trip was updated. Please refresh the page and try again.",
  "error" => "Unable to update trip."
);

echo json_encode(array(
  "status" => $answer === "success" ? "success" : "error",
  "message" => $messages[$answer] ?? "Unable to update trip"
));
