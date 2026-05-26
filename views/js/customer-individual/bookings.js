$(document).ready(function () {

  document.getElementById("bookingAddBtn").addEventListener("click", function () {
    window.location.href = "booking-reg";
  });

  console.log("🟢 Booking map script loaded");

  const bookings = Array.isArray(window.customerBookingData)
    ? window.customerBookingData
    : [];

  console.log("📦 Raw window.customerBookingData:", window.customerBookingData);
  console.log("📦 Parsed bookings:", bookings);

  let map = null;
  let markers = [];

  initMap();

  if (bookings.length) {
    console.log("✅ Auto-selecting first booking:", bookings[0].bookingID);
    selectBooking(bookings[0].bookingID);
  } else {
    console.warn("⚠️ No bookings found!");
  }

  // CLICK HANDLER
  $(document).on('click', '.customer-map-focus, .driver-trip-card', function () {

    console.log("🖱️ Click detected");

    const card = $(this).hasClass('driver-trip-card')
      ? $(this)
      : $(this).closest('.driver-trip-card');

    const bookingID = card.data('booking-id');

    console.log("🎯 Selected booking ID:", bookingID);

    selectBooking(bookingID);
  });

  function initMap() {

    console.log("🗺️ initMap() called");

    if (typeof L === 'undefined') {
      console.error("❌ Leaflet (L) is not loaded!");
      return;
    }

    const mapEl = document.getElementById('customerMap');

    if (!mapEl) {
      console.error("❌ Map element #customerMap not found!");
      return;
    }

    map = L.map('customerMap').setView([10.3157, 123.8854], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    setTimeout(() => map.invalidateSize(), 100);

    console.log("🟢 Map initialized successfully");
  }

  function selectBooking(bookingID) {

    console.log("🔎 selectBooking() called with:", bookingID);

    const booking = bookings.find(b => Number(b.bookingID) === Number(bookingID));

    console.log("📍 Found booking:", booking);

    if (!booking) {
      console.error("❌ Booking not found in array!");
      return;
    }

    if (!map) {
      console.error("❌ Map is not initialized!");
      return;
    }

    $('.driver-trip-card').removeClass('active');
    $('.driver-trip-card[data-booking-id="' + bookingID + '"]').addClass('active');

    showBookingOnMap(booking);
  }

  function showBookingOnMap(booking) {

    console.log("🗺️ showBookingOnMap()", booking);

    clearMarkers();

    const pickup = [
      Number(booking.pickupLatitude),
      Number(booking.pickupLongitude)
    ];

    const destination = [
      Number(booking.destinationLatitude),
      Number(booking.destinationLongitude)
    ];

    console.log("📍 Pickup coords:", pickup);
    console.log("📍 Destination coords:", destination);

    if (!validPoint(pickup)) {
      console.error("❌ Invalid pickup coordinates");
      return;
    }

    if (!validPoint(destination)) {
      console.error("❌ Invalid destination coordinates");
      return;
    }

    const pickupMarker = L.marker(pickup, {
      icon: markerIcon('#696cff', 'P')
    })
    .addTo(map)
    .bindPopup(`
      <strong>Pickup</strong><br>
      Booking #${booking.bookingID}
    `);

    const destinationMarker = L.marker(destination, {
      icon: markerIcon('#ff3e1d', 'D')
    })
    .addTo(map)
    .bindPopup(`
      <strong>Destination</strong><br>
      Booking #${booking.bookingID}
    `);

    markers.push(pickupMarker, destinationMarker);

    const bounds = L.latLngBounds([pickup, destination]);
    map.fitBounds(bounds, { padding: [40, 40] });

    console.log("✅ Markers added + map fitted");

    $('#driverMapStatus').text(`Showing Booking #${booking.bookingID}`);
    $('#driverMapBadge').text('Pickup → Destination');
  }

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

  function clearMarkers() {
    console.log("🧹 Clearing markers:", markers.length);

    markers.forEach(m => map.removeLayer(m));
    markers = [];
  }

  function validPoint(p) {
    return Number.isFinite(p[0]) &&
           Number.isFinite(p[1]) &&
           p[0] !== 0 &&
           p[1] !== 0;
  }

});