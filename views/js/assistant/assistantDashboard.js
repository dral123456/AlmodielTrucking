$(document).ready(function () {
  $(".viewDetails").on("click", function () {
    const card = $(this).hasClass('driver-trip-card')
        ? $(this)
        : $(this).closest('.driver-trip-card');

    const bookingID = this.getAttribute("data-id");

    // Create form
    const form = $("<form>", {
        method: "POST",
        action: "booking-details"
    });

    // Hidden input
    const input = $("<input>", {
        type: "hidden",
        name: "bookingID",
        value: bookingID
    });

    form.append(input);

    // Add form to body
    $("body").append(form);

    // Submit form
    form.submit();
  });
});