<?php
session_start();

require_once "../controllers/booking.controller.php";
require_once "../models/booking.model.php";

class BookingReschedule {

    public function rescheduleBooking() {

        $bookingID = $_POST["bookingID"] ?? 0;
        $newDate   = trim($_POST["newDate"] ?? "");
        $newTime   = trim($_POST["newTime"] ?? "");
        $reason    = trim($_POST["reason"] ?? "");

        if (
            empty($bookingID) ||
            empty($newDate) ||
            empty($newTime)
        ) {

            echo json_encode([
                "status" => "error",
                "message" => "Missing required fields."
            ]);

            return;
        }

        $pickupDateTime = date(
            "Y-m-d H:i:s",
            strtotime($newDate . " " . $newTime)
        );

        $data = [
            "bookingID"      => (int)$bookingID,
            "pickupDateTime" => $pickupDateTime,
            "reason"         => $reason,
            "updatedBy"      => $_SESSION["id"] ?? 0
        ];

        $answer = ControllerBooking::ctrRescheduleBooking($data);

        echo json_encode([
            "status" => $answer
        ]);
    }
}

$reschedule = new BookingReschedule();
$reschedule->rescheduleBooking();