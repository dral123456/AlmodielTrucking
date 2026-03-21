/*
Template Name: Urbix - Admin & Dashboard Template
Author: Pixeleyez
Website: https://pixeleyez.com/
File: Auth init js
*/

// Initialize password toggle functionality
document.addEventListener("DOMContentLoaded", function () {
  initPasswordToggle();
});

// const form = document.getElementById("formAuthentication");

// form.addEventListener("submit", (event) => {
//   event.preventDefault(); // Prevent default submission
//   const username = document.getElementById("username").value;
//   const password = document.getElementById("password").value;

//   if (!username || !password) {
//     alert("Please fill in all fields.");
//   } else {
//     alert(`Submitting: ${username}, ${password}`);
//     form.submit();
//   }
// });

function initPasswordToggle() {
  const toggles = document.querySelectorAll(".toggle-password");
  if (!toggles.length) return;

  toggles.forEach((toggle) => {
    toggle.addEventListener("click", function () {
      const targetId = this.getAttribute("data-target");
      if (!targetId) return;

      const targetInput = document.getElementById(targetId);
      if (!targetInput) return;

      const icon = this.querySelector("i");
      if (!icon) return;

      if (targetInput.type === "password") {
        targetInput.type = "text";
        icon.classList.replace("ri-eye-off-line", "ri-eye-line");
      } else {
        targetInput.type = "password";
        icon.classList.replace("ri-eye-line", "ri-eye-off-line");
      }
    });
  });
}