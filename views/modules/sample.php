<?php
include 'connection.php';

$empID = $_SESSION['id'];

/* =========================
  COUNTERS
========================= */

/* PENDING */
$pending = mysqli_num_rows(mysqli_query($conn, "
SELECT b.tripID
FROM booking b
INNER JOIN tripEmployee t
ON b.tripID = t.tripID
WHERE t.empID = '$empID'
AND t.role = 'assistant'
AND b.status = 'pending'
"));

/* COMPLETED */
$completed = mysqli_num_rows(mysqli_query($conn, "
SELECT b.tripID
FROM booking b
INNER JOIN tripEmployee t
ON b.tripID = t.tripID
WHERE t.empID = '$empID'
AND t.role = 'assistant'
AND b.status = 'completed'
"));

/* =========================
   BOOKINGS
========================= */

$bookings = mysqli_query($conn, "
SELECT b.*
FROM booking b
INNER JOIN tripEmployee t
ON b.tripID = t.tripID
WHERE t.empID = '$empID'
AND t.role = 'assistant'
ORDER BY b.tripID DESC
");
?>

<body>

<div class="container py-5">

    <!-- TITLE -->
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

    <!-- BOOKINGS TABLE -->
    <div class="card shadow-sm border-0">

        <div class="card-body">

            <h5 class="mb-3">My Bookings</h5>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th>Trip ID</th>
                            <th>Booking No</th>
                            <th>Price</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php while($row = mysqli_fetch_assoc($bookings)) { ?>

                        <tr>

                            <td>
                                <?php echo $row['tripID']; ?>
                            </td>

                            <td>
                                <?php echo $row['booking_no']; ?>
                            </td>

                            <td>
                                ₱<?php echo number_format($row['price'], 2); ?>
                            </td>

                            <td>
                                <?php echo $row['date_time']; ?>
                            </td>

                            <td>
                                <?php if($row['status'] == "completed"){ ?>

                                    <span class="badge bg-success">
                                        Completed
                                    </span>

                                <?php } else { ?>

                                    <span class="badge bg-warning text-dark">
                                        Pending
                                    </span>

                                <?php } ?>
                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>