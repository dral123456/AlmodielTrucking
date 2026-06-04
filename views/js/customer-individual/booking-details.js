$(document).ready(function () {
	const map = L.map('bookingMap');

  const pickupCoords      = [pickupLat, pickupLng];
  const destinationCoords = [destinationLat, destinationLng];

  map.fitBounds([pickupCoords, destinationCoords], { padding: [50, 50] });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  function markerIcon(color, label) {
    return L.divIcon({
      className: '',
      html: `
        <div style="position:relative; display:flex; align-items:center; justify-content:center;">
          <div style="
            width: 32px;
            height: 32px;
            background: ${color};
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            border: 2px solid #fff;
            box-shadow: 0 6px 14px rgba(0,0,0,0.25);
          "></div>
          <div style="
            position:absolute;
            width: 18px;
            height: 18px;
            background:#fff;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:10px;
            font-weight:700;
            color:${color};
          ">
            ${label}
          </div>
        </div>
      `,
      iconSize: [32, 32],
      iconAnchor: [16, 32]
    });
  }

  L.marker(pickupCoords, { icon: markerIcon('#696cff', 'P') })
    .addTo(map)
    .bindPopup('<strong>Pickup Location</strong>');

  L.marker(destinationCoords, { icon: markerIcon('#ff3e1d', 'D') })
    .addTo(map)
    .bindPopup('<strong>Destination</strong>');

	$('#viewReceipt').click(function() {
		var bookingID = $(this).data('id');
		window.open("pdf/receipt.php?bookingID=" + bookingID, "_blank");
	});
  // let reschedulePickerDate = null;
  // let reschedulePickerTime = null;

  // $('#rescheduleModal').on('shown.bs.modal', function () {
  //   if (!reschedulePickerDate) {
  //     reschedulePickerDate = new AirDatepicker('#rescheduleDate', {
  //       dateFormat: 'MM / dd / yyyy',
  //       minDate: new Date(),
  //       autoClose: true,
  //       container: '#rescheduleModal .modal-body',
  //       position: 'bottom left',
  //       locale:localeEn,
  //     });
  //   }

  //   if (!reschedulePickerTime) {
  //     reschedulePickerTime = new AirDatepicker('#rescheduleTime', {
  //       timepicker: true,
  //       onlyTimepicker: true,
  //       timeFormat: 'hh:mm aa',
  //       autoClose: true,
  //       container: '#rescheduleModal .modal-body',
  //       position: 'bottom left',
  //     });
  //   }
  // });

  $('#rescheduleBtn').on('click', function () {
    const bookingID = $(this).data('id');
    $('#rescheduleBookingID').text('#' + bookingID);
    $('#rescheduleDate').val('');
    $('#rescheduleTime').val('');
  
    new bootstrap.Modal(document.getElementById('rescheduleModal')).show();
  });

  $('#confirmReschedule').on('click', function () {
    const date      = $('#rescheduleDate').val().trim();
    const time      = $('#rescheduleTime').val().trim();
    const bookingID = $('#rescheduleBtn').data('id');
  
    if (!date || !time) {
      Swal.fire({
        icon: 'warning',
        title: 'Missing Fields',
        text: 'Please select both a new date and time.',
      });
      return;
    }
  
    $.ajax({
      url: 'ajax/booking_reschedule.ajax.php',
      method: 'POST',
      data: {
        action: 'reschedule',
        bookingID: bookingID,
        newDate: date,
        newTime: time,
      },
      success: function (response) {
        bootstrap.Modal.getInstance(document.getElementById('rescheduleModal')).hide();
        Swal.fire({
          icon: 'success',
          title: 'Rescheduled!',
          text: `Booking has been rescheduled to ${date} at ${time}.`,
          timer: 2500,
          showConfirmButton: false,
        }).then(() => location.reload());
      },
      error: function () {
        Swal.fire({
          icon: 'error',
          title: 'Failed',
          text: 'Something went wrong. Please try again.',
        });
      }
    });
  });
});