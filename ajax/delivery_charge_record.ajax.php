<?php
session_start();

require_once "../controllers/report.controller.php";
require_once "../models/report.model.php";

if (!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] !== "ok") {
    echo "forbidden";
    exit;
}

$data = array(
    "bookingID" => $_POST["bookingID"] ?? 0,
    "tripID" => $_POST["tripID"] ?? 0,
    "chargeType" => $_POST["chargeType"] ?? "hauling",
    "amount" => $_POST["amount"] ?? 0,
    "notes" => $_POST["notes"] ?? "",
    "createdBy" => $_SESSION["id"] ?? null
);

echo ControllerReport::ctrSaveDeliveryCharge($data);
