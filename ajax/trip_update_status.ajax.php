<?php
session_start();
require_once "../controllers/booking.controller.php";
require_once "../models/booking.model.php";

$tripID = isset($_POST['tripID']) ? (int) $_POST['tripID'] : 0;
$status = $_POST['status'] ?? '';
$driverID = $_SESSION['id'] ?? 0; // adjust to match your session key

if ($tripID <= 0 || $status === '') {
  echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
  exit;
}

$answer = ControllerBooking::ctrUpdateTripDeliveryStatus($tripID, $status, $driverID);

echo json_encode([
  'status' => $answer === 'success' ? 'success' : 'error',
  'message' => $answer === 'success' ? 'Trip status updated.' : 'Unable to update trip status.'
]);