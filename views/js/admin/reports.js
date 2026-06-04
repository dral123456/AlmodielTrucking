(function () {
  "use strict";

  var page = document.querySelector(".reports-page");
  if (!page) {
    return;
  }

  var categorySelect = document.getElementById("reportCategory");
  var specificSelect = document.getElementById("reportSpecific");
  var rangeInput = document.getElementById("reportDateRangeFilter");
  var datePicker = null;
  var clearButton = document.getElementById("reportClearDate");
  var csvButton = document.getElementById("reportExportCsv");
  var pdfButton = document.getElementById("reportExportPdf");
<<<<<<< HEAD
  if (pdfButton) {
    pdfButton.addEventListener("click", function () {
      exportPdf();
    });
  }
=======
  var addExpenseButton = document.getElementById("reportAddExpense");
>>>>>>> 6119ee673ff417b1dc47d90c9d94a630af527e62
  var specificOptions = {
    billing: [
      { value: "all", label: "All Billing" },
      { value: "individual", label: "Individual" },
      { value: "company", label: "Company" }
    ],
    expenses: [
      { value: "all", label: "All Expenses" }
    ],
    staff: [
      { value: "all", label: "All Staff" },
      { value: "admin", label: "Admin" },
      { value: "driver", label: "Driver" },
      { value: "assistant", label: "Assistant" }
    ],
    salary: [
      { value: "all", label: "All Staff Salary" }
    ]
  };

  function getActivePane() {
    return page.querySelector('.report-pane[data-report-pane="' + categorySelect.value + '"]');
  }

  function getActiveTitle() {
    var selected = categorySelect.options[categorySelect.selectedIndex];
    return selected ? selected.textContent.trim() : "Report";
  }

  function parseDate(value) {
    if (!value) {
      return null;
    }

    var parts = value.split("-");
    if (parts.length !== 3) {
      return null;
    }

    return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
  }

  function parseDateRange(value) {
    var dates = String(value || "").match(/\d{4}-\d{2}-\d{2}/g) || [];
    var from = dates[0] || "";
    var to = dates[1] || from;

    if (from && to && from > to) {
      return { from: to, to: from };
    }

    return { from: from, to: to };
  }

  function initDateRangePicker() {
    if (typeof AirDatepicker === "undefined" || !rangeInput) {
      return;
    }

    var localeEn = {
      days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
      daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
      daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
      months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
      monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
      today: "Today",
      clear: "Clear",
      dateFormat: "yyyy-MM-dd",
      timeFormat: "hh:mm aa",
      firstDay: 0
    };

    datePicker = new AirDatepicker("#reportDateRangeFilter", {
      range: true,
      multipleDatesSeparator: " to ",
      dateFormat: "yyyy-MM-dd",
      locale: localeEn,
      autoClose: false,
      buttons: ["today", "clear"],
      onSelect: applyDateFilter
    });
  }

  function titleCase(value) {
    return String(value || "")
      .replace(/[-_]+/g, " ")
      .replace(/\b\w/g, function (letter) {
        return letter.toUpperCase();
      });
  }

  function collectSpecificOptions(category) {
    var options = (specificOptions[category] || [{ value: "all", label: "All" }]).slice();
    var seen = {};

    options.forEach(function (option) {
      seen[option.value] = true;
    });

    var pane = page.querySelector('.report-pane[data-report-pane="' + category + '"]');
    if (!pane) {
      return options;
    }

    pane.querySelectorAll(".report-data-row").forEach(function (row) {
      var value = row.getAttribute("data-report-specific") || "";
      if (!value || seen[value]) {
        return;
      }

      seen[value] = true;
      options.push({ value: value, label: titleCase(value) });
    });

    return options;
  }

  function renderSpecificOptions() {
    var category = categorySelect.value;
    var options = collectSpecificOptions(category);

    specificSelect.innerHTML = options.map(function (option) {
      return '<option value="' + escapeHtml(option.value) + '">' + escapeHtml(option.label) + "</option>";
    }).join("");
  }

  function renderActiveReport() {
    var category = categorySelect.value;

    page.querySelectorAll(".report-pane").forEach(function (pane) {
      pane.classList.toggle("d-none", pane.getAttribute("data-report-pane") !== category);
    });

    renderSpecificOptions();
    applyDateFilter();
  }

  function rowMatchesDate(row, fromDate, toDate, hasFilter) {
    if (!hasFilter) {
      return true;
    }

    var rowDate = parseDate(row.getAttribute("data-report-date"));
    if (!rowDate) {
      return false;
    }

    if (fromDate && rowDate < fromDate) {
      return false;
    }

    if (toDate && rowDate > toDate) {
      return false;
    }

    return true;
  }

  function rowMatchesSpecific(row) {
    var specific = specificSelect.value || "all";

    if (specific === "all") {
      return true;
    }

    return (row.getAttribute("data-report-specific") || "") === specific;
  }

  function ensureFilterEmpty(pane) {
    var empty = pane.querySelector(".report-filter-empty");
    if (!empty) {
      empty = document.createElement("div");
      empty.className = "report-filter-empty d-none";
      empty.textContent = "No records match the selected date range.";
      pane.appendChild(empty);
    }

    return empty;
  }

  function applyDateFilter() {
    var dateRange = parseDateRange(rangeInput ? rangeInput.value : "");
    var fromDate = parseDate(dateRange.from);
    var toDate = parseDate(dateRange.to);
    var hasFilter = Boolean(fromDate || toDate);

    page.querySelectorAll(".report-pane").forEach(function (pane) {
      var rows = Array.prototype.slice.call(pane.querySelectorAll(".report-data-row"));
      var visibleCount = 0;

      rows.forEach(function (row) {
        var visible = rowMatchesDate(row, fromDate, toDate, hasFilter) && rowMatchesSpecific(row);
        row.classList.toggle("d-none", !visible);
        if (visible) {
          visibleCount += 1;
        }
      });

      var table = pane.querySelector(".table-responsive");
      var empty = ensureFilterEmpty(pane);
      var shouldShowFilterEmpty = rows.length > 0 && visibleCount === 0;

      if (table) {
        table.classList.toggle("d-none", shouldShowFilterEmpty);
      }

      empty.classList.toggle("d-none", !shouldShowFilterEmpty);
    });
  }

  function visibleRows(table) {
    return Array.prototype.slice.call(table.querySelectorAll("tbody tr")).filter(function (row) {
      return !row.classList.contains("d-none");
    });
  }

  function csvEscape(value) {
    var text = String(value || "").replace(/\s+/g, " ").trim();
    return '"' + text.replace(/"/g, '""') + '"';
  }

  function exportCsv() {
    var pane = getActivePane();
    var table = pane ? pane.querySelector("table") : null;

    if (!table || visibleRows(table).length === 0) {
      alert("No records to export.");
      return;
    }

    var headers = Array.prototype.slice.call(table.querySelectorAll("thead th")).map(function (header) {
      return csvEscape(header.textContent);
    });

    var rows = visibleRows(table).map(function (row) {
      return Array.prototype.slice.call(row.children).map(function (cell) {
        return csvEscape(cell.textContent);
      }).join(",");
    });

    var csv = [headers.join(",")].concat(rows).join("\r\n");
    var blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    var link = document.createElement("a");
    var fileTitle = getActiveTitle().toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");

    link.href = URL.createObjectURL(blob);
    link.download = fileTitle + "-report.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
  }

  function buildPrintableTable(table) {
    var clone = table.cloneNode(true);
    Array.prototype.slice.call(clone.querySelectorAll("tbody tr.d-none")).forEach(function (row) {
      row.remove();
    });
    return clone.outerHTML;
  }

  function exportPdf() {
    var pane = getActivePane();
    var table = pane ? pane.querySelector("table") : null;

    if (!table || visibleRows(table).length === 0) {
      alert("No records to export.");
      return;
    }

    var title = getActiveTitle() + " Report";
    var selectedRange = parseDateRange(rangeInput ? rangeInput.value : "");
    var dateRange = [
      selectedRange.from ? "From " + selectedRange.from : "",
      selectedRange.to ? "To " + selectedRange.to : ""
    ].filter(Boolean).join(" ");

    var printWindow = window.open("", "_blank", "width=1100,height=800");
    if (!printWindow) {
      alert("Please allow popups to export the PDF.");
      return;
    }

    printWindow.document.write(
      "<!doctype html><html><head><title>" + title + "</title>" +
      "<style>" +
      "body{font-family:Arial,sans-serif;color:#111827;margin:32px;}" +
      "h1{font-size:22px;margin:0 0 4px;}" +
      "p{color:#6b7280;margin:0 0 20px;}" +
      "table{width:100%;border-collapse:collapse;font-size:12px;}" +
      "th,td{border:1px solid #e5e7eb;padding:8px;text-align:left;vertical-align:top;}" +
      "th{background:#f9fafb;font-weight:700;}" +
      ".text-end{text-align:right;}" +
      ".badge{font-weight:400;}" +
      ".small{font-size:11px;color:#6b7280;}" +
      "@media print{@page{size:landscape;margin:14mm;}body{margin:0;}}" +
      "</style></head><body>" +
      "<h1>" + title + "</h1>" +
      "<p>" + (dateRange || "All dates") + "</p>" +
      buildPrintableTable(table) +
      "</body></html>"
    );

    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
  }

  function showExpenseEntryPopup() {
    Swal.fire({
      html: buildExpenseEntryHtml(),
      width: "min(980px, calc(100vw - 32px))",
      showCancelButton: true,
      showCloseButton: true,
      confirmButtonText: "Save Expense",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#696cff",
      focusConfirm: false,
      scrollbarPadding: false,
      customClass: {
        popup: "expense-entry-popup",
        actions: "expense-entry-actions",
        confirmButton: "expense-entry-save",
        cancelButton: "expense-entry-cancel"
      },
      didOpen: function () {
        ["expenseEntryDate", "expenseEntryCategory", "expenseEntryAmount"].forEach(function (id) {
          document.getElementById(id).addEventListener("input", function () {
            document.getElementById("expenseEntryError").classList.add("d-none");
          });
        });
      },
      preConfirm: function () {
        var expenseDate = document.getElementById("expenseEntryDate").value;
        var category = document.getElementById("expenseEntryCategory").value;
        var amount = document.getElementById("expenseEntryAmount").value;
        var amountNumber = Number(amount);

        if (!expenseDate || !category || !amount || !Number.isFinite(amountNumber) || amountNumber <= 0) {
          document.getElementById("expenseEntryError").classList.remove("d-none");
          return false;
        }

        document.getElementById("expenseEntryError").classList.add("d-none");
        return {
          expenseDate: expenseDate,
          category: category,
          amount: amount,
          truckID: document.getElementById("expenseEntryTruck").value,
          referenceNo: document.getElementById("expenseEntryReference").value,
          status: document.getElementById("expenseEntryStatus").value,
          description: document.getElementById("expenseEntryDescription").value
        };
      }
    }).then(function (result) {
      if (result.isConfirmed) {
        saveExpense(result.value);
      }
    });
  }

  function buildExpenseEntryHtml() {
    return (
      '<div class="expense-entry-form">' +
        '<div class="expense-entry-header">' +
          '<div>' +
            '<h4 class="mb-1">Add Expense</h4>' +
            '<p class="text-muted mb-0">Record a manual business expense for reporting and sales deductions.</p>' +
          '</div>' +
        '</div>' +
        '<div class="expense-entry-error d-none" id="expenseEntryError">Date, category, and an amount greater than zero are required.</div>' +
        '<div class="expense-entry-section">' +
          '<div class="expense-entry-section-title">Expense Information</div>' +
          '<div class="expense-entry-grid expense-entry-grid-main">' +
            expenseField("Expense Date", '<input type="date" class="form-control" id="expenseEntryDate" value="' + todayValue() + '">', true) +
            expenseField("Category", buildExpenseCategorySelect(), true) +
            expenseField("Amount", '<div class="input-group"><span class="input-group-text">PHP</span><input type="number" class="form-control" id="expenseEntryAmount" min="0.01" step="0.01" placeholder="0.00"></div>', true, "expense-entry-amount") +
            expenseField("Truck", buildExpenseTruckSelect()) +
            expenseField("Status", buildExpenseStatusSelect()) +
          '</div>' +
        '</div>' +
        '<div class="expense-entry-section">' +
          '<div class="expense-entry-section-title">Reference & Notes</div>' +
          '<div class="expense-entry-grid">' +
            expenseField("Reference No.", '<input type="text" class="form-control" id="expenseEntryReference" placeholder="Optional receipt or invoice number">', false, "expense-entry-wide") +
            '<div class="expense-entry-wide">' +
              '<label class="form-label">Description</label>' +
              '<textarea class="form-control" id="expenseEntryDescription" placeholder="Example: Replaced brake pads and changed engine oil."></textarea>' +
              '<div class="form-text">Add enough detail so this expense is easy to identify later.</div>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  function expenseField(label, inputHtml, required, className) {
    return (
      '<div class="' + escapeHtml(className || "") + '">' +
        '<label class="form-label">' + escapeHtml(label) + (required ? ' <span class="text-danger">*</span>' : '') + '</label>' +
        inputHtml +
      '</div>'
    );
  }

  function buildExpenseCategorySelect() {
    var categories = [
      ["fuel", "Fuel"],
      ["truck_maintenance", "Truck Maintenance"],
      ["repair", "Repair"],
      ["truck_document", "Truck Document"],
      ["toll", "Toll"],
      ["parking", "Parking"],
      ["employee_salary", "Employee Salary"],
      ["office", "Office"],
      ["other", "Other"]
    ];

    return '<select class="form-select" id="expenseEntryCategory">' +
      categories.map(function (category) {
        return '<option value="' + category[0] + '">' + category[1] + '</option>';
      }).join("") +
    "</select>";
  }

  function buildExpenseTruckSelect() {
    var trucks = Array.isArray(window.reportExpenseTruckOptions) ? window.reportExpenseTruckOptions : [];
    var options = '<option value="">No truck / general expense</option>';

    trucks.forEach(function (truck) {
      var label = [truck.plateNumber, truck.brand, truck.type].filter(Boolean).join(" | ");
      options += '<option value="' + escapeHtml(truck.id) + '">' + escapeHtml(label || truck.id) + "</option>";
    });

    return '<select class="form-select" id="expenseEntryTruck">' + options + "</select>";
  }

  function buildExpenseStatusSelect() {
    return (
      '<select class="form-select" id="expenseEntryStatus">' +
        '<option value="paid">Paid</option>' +
        '<option value="pending">Pending</option>' +
        '<option value="approved">Approved</option>' +
        '<option value="cancelled">Cancelled</option>' +
      "</select>"
    );
  }

  function saveExpense(data) {
    $.ajax({
      url: "ajax/expense_save_record.ajax.php",
      method: "POST",
      dataType: "json",
      data: data,
      success: function (response) {
        if (response && response.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Expense Saved",
            text: response.message || "Expense saved successfully.",
            confirmButtonColor: "#696cff"
          }).then(function () {
            location.reload();
          });
          return;
        }

        showExpenseError(response && response.message ? response.message : "Unable to save expense.");
      },
      error: function () {
        showExpenseError("Unable to save expense.");
      }
    });
  }

  function showExpenseError(message) {
    Swal.fire({
      icon: "error",
      title: "Save Failed",
      text: message,
      confirmButtonColor: "#696cff"
    });
  }

  function todayValue() {
    var date = new Date();
    var month = String(date.getMonth() + 1).padStart(2, "0");
    var day = String(date.getDate()).padStart(2, "0");
    return date.getFullYear() + "-" + month + "-" + day;
  }

  initDateRangePicker();

  rangeInput.addEventListener("change", applyDateFilter);
  clearButton.addEventListener("click", function () {
    rangeInput.value = "";
    if (datePicker) {
      datePicker.clear();
    }
    applyDateFilter();
  });
  categorySelect.addEventListener("change", renderActiveReport);
  specificSelect.addEventListener("change", applyDateFilter);
  csvButton.addEventListener("click", exportCsv);
  pdfButton.addEventListener("click", exportPdf);
  if (addExpenseButton) {
    addExpenseButton.addEventListener("click", showExpenseEntryPopup);
  }

  renderActiveReport();

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
})();
