<?php
session_start();

require_once __DIR__ . "/../controllers/sales.controller.php";
require_once __DIR__ . "/../models/sales.model.php";

header("Content-Type: application/json");

if (!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] !== "ok" || ($_SESSION["role"] ?? "") !== "admin") {
  echo json_encode(array("status" => "error", "message" => "Only admins can update billing status."));
  exit;
}

$action = $_POST["action"] ?? "single-paid";

if ($action === "group-paid") {
  $filters = array(
    "dateFrom" => isset($_POST["dateFrom"]) ? preg_replace("/[^0-9\-]/", "", $_POST["dateFrom"]) : "",
    "dateTo" => isset($_POST["dateTo"]) ? preg_replace("/[^0-9\-]/", "", $_POST["dateTo"]) : "",
    "customerType" => isset($_POST["customerType"]) && in_array($_POST["customerType"], array("individual", "company"), true) ? $_POST["customerType"] : ""
  );

  $answer = ControllerSales::ctrMarkSalesGroupAsPaid($filters);
  $status = $answer["status"] ?? "error";
  $paidCount = (int) ($answer["paidCount"] ?? 0);
  $totalCount = (int) ($answer["totalCount"] ?? 0);
  $messages = array(
    "success" => "Marked {$paidCount} billing record(s) as paid for the selected range.",
    "already-paid" => "All {$totalCount} billing record(s) in this range are already paid.",
    "missing-range" => "Select a date range before marking a group payment.",
    "no-sales-table" => "Sales table is not available.",
    "not-found" => "No completed billing records were found for the selected range."
  );

  echo json_encode(array(
    "status" => in_array($status, array("success", "already-paid"), true) ? "success" : "error",
    "message" => $messages[$status] ?? "Unable to update grouped billing status.",
    "paidCount" => $paidCount,
    "totalCount" => $totalCount
  ));
  exit;
}

$bookingID = isset($_POST["bookingID"]) ? (int) $_POST["bookingID"] : 0;
$answer = ControllerSales::ctrMarkSalesAsPaid($bookingID);

$messages = array(
  "success" => "Billing marked as paid.",
  "invalid" => "Invalid booking selected.",
  "no-sales-table" => "Sales table is not available.",
  "not-found" => "Completed sales record was not found."
);

echo json_encode(array(
  "status" => $answer === "success" ? "success" : "error",
  "message" => $messages[$answer] ?? "Unable to update billing status."
));
