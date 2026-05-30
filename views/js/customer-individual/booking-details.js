$(document).ready(function () {
	const bookingID = sessionStorage.getItem("bookingID");
	console.log(bookingID);

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
});