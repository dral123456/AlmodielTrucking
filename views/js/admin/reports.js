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
  var tcpdfButton = document.getElementById("tcpdfExportPdf");
if (tcpdfButton) {
  tcpdfButton.addEventListener("click", function () {
    window.open("views/modules/reports-pdf.php", "_blank");
  });
}
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
