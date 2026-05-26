<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Driver Assistant Dashboard</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    body{
    background:#f4f6f9;
    }

    .sidebar{
    height:100vh;
    background:#1e293b;
    color:white;
    padding:20px;
    }

    .sidebar a{
    color:white;
    text-decoration:none;
    display:block;
    padding:10px;
    border-radius:10px;
    margin-bottom:10px;
    }

    .sidebar a:hover{
    background:#334155;
    }

    .card-box{
    border:none;
    border-radius:15px;
    color:white;
    padding:20px;
    }

    .table-container{
    background:white;
    border-radius:15px;
    padding:20px;
    }
</style>
</head>

<body>

<div class="container-fluid">
<div class="row">

    <!-- SIDEBAR -->
    <div class="col-md-2 sidebar">

    <h3 class="mb-4">Delivery System</h3>

    <a href="#">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <a href="#">
        <i class="bi bi-truck"></i> Deliveries
    </a>

    <a href="#">
        <i class="bi bi-person"></i> Drivers
    </a>

    <a href="#">
        <i class="bi bi-car-front"></i> Vehicles
    </a>

      <a href="#">
        <i class="bi bi-file-earmark-bar-graph"></i> Reports
      </a>

    </div>

    <!-- MAIN CONTENT -->
    <div class="col-md-10 p-4">

      <h2 class="mb-4">Driver Assistant Dashboard</h2>

      <!-- CARDS -->
      <div class="row">

        <div class="col-md-3 mb-3">
          <div class="card-box bg-primary">
            <h5>Total Deliveries</h5>
            <h2>120</h2>
          </div>
        </div>

        <div class="col-md-3 mb-3">
          <div class="card-box bg-success">
            <h5>Delivered</h5>
            <h2>90</h2>
          </div>
        </div>

        <div class="col-md-3 mb-3">
          <div class="card-box bg-warning">
            <h5>Pending</h5>
            <h2>20</h2>
          </div>
        </div>

        <div class="col-md-3 mb-3">
          <div class="card-box bg-danger">
            <h5>Cancelled</h5>
            <h2>10</h2>
          </div>
        </div>

      </div>

      <!-- DELIVERY TABLE -->
      <div class="table-container mt-4">

        <h4 class="mb-3">Delivery Monitoring</h4>

        <table class="table table-hover">

          <thead class="table-dark">
            <tr>
              <th>Tracking #</th>
              <th>Customer</th>
              <th>Destination</th>
              <th>Driver</th>
              <th>Status</th>
            </tr>
          </thead>

          <tbody>

            <tr>
              <td>TRK-1001</td>
              <td>Juan Dela Cruz</td>
              <td>Cebu City</td>
              <td>Mark</td>
              <td>
                <span class="badge bg-success">
                  Delivered
                </span>
              </td>
            </tr>

            <tr>
              <td>TRK-1002</td>
              <td>Maria Santos</td>
              <td>Mandaue</td>
              <td>John</td>
              <td>
                <span class="badge bg-warning">
                  Pending
                </span>
              </td>
            </tr>

          </tbody>

        </table>

      </div>

    </div>

  </div>
</div>

</body>
</html>