<?php
require_once "models/connection.php";
require_once "models/booking.model.php";
require_once "models/employee.model.php";
require_once "controllers/booking.controller.php";
require_once "controllers/employee.controller.php";

$empID = (int) $_SESSION['id'];

// Fetch trips assigned to this assistant
$trips = ControllerBooking::ctrTripOverviewList($empID, "assistant");

$pending   = 0;
$completed = 0;

foreach ($trips as $trip) {
    if ($trip["status"] === "completed") {
        $completed++;
    } else {
        $pending++;
    }
}
?>

<body>

<div class="container py-5">

    <div class="text-center mb-4">
        <h2>Assistant Dashboard</h2>
        <p class="text-muted">Assigned Deliveries</p>
    </div>

    <!-- COUNTER CARDS -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 text-center p-4">
                <h5 class="text-warning">Pending</h5>
                <h1><?php echo $pending; ?></h1>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 text-center p-4">
                <h5 class="text-success">Completed</h5>
                <h1><?php echo $completed; ?></h1>
            </div>
        </div>
    </div>

    <!-- TRIPS TABLE -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="mb-3">My Assigned Trips</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="">
                        <tr>
                            <th>Trip ID</th>
                            <th>Pickup Date</th>
                            <th>Bookings</th>
                            <th>Customers</th>
                            <th>Total Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($trips)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No assigned trips found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trip["tripID"]); ?></td>
                            <td><?php echo htmlspecialchars($trip["firstPickupDateTime"]); ?></td>
                            <td><?php echo (int) $trip["bookingCount"]; ?></td>
                            <td><?php echo htmlspecialchars(implode(", ", $trip["customers"])); ?></td>
                            <td>₱<?php echo number_format((float) $trip["totalPrice"], 2); ?></td>
                            <td>
                                <?php
                                $statusMap = [
                                    "completed"  => ["bg-success",           "Completed"],
                                    "in-transit" => ["bg-primary",           "In Transit"],
                                    "stopover"   => ["bg-info text-dark",    "Stopover"],
                                    "pending"    => ["bg-warning text-dark", "Pending"],
                                ];
                                [$badgeClass, $label] = $statusMap[$trip["status"]] ?? ["bg-secondary", ucfirst($trip["status"])];
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo $label; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

</body>
</html>