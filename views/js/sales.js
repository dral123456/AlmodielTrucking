$(document).ready(function () {
  window.salesEnhancedFilterReady = true;
  let salesChartInstance = null;
  let salesDatePicker = null;

  const localeEn = {
    days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
    daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    daysMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
    months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    today: 'Today',
    clear: 'Clear',
    dateFormat: 'yyyy-MM-dd',
    timeFormat: 'hh:mm aa',
    firstDay: 0
  };

  function parseDate(value) {
    const parts = String(value || '').split('-');
    if (parts.length !== 3) {
      return null;
    }

    const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    return Number.isNaN(date.getTime()) ? null : date;
  }

  function parseDateText(value) {
    const text = String(value || '').trim();
    const isoMatch = text.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    const shortMatch = text.match(/^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$/);

    if (isoMatch) {
      return new Date(Number(isoMatch[1]), Number(isoMatch[2]) - 1, Number(isoMatch[3]));
    }

    if (shortMatch) {
      return new Date(Number(shortMatch[3]), Number(shortMatch[1]) - 1, Number(shortMatch[2]));
    }

    const parsed = new Date(text);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  function formatDate(date) {
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
      return '';
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
  }

  function syncHiddenRangeFromDates(selectedDates) {
    const dates = (selectedDates || []).map(formatDate).filter(Boolean).sort();
    const from = dates[0] || '';
    const to = dates[1] || from;
    const dateFromInput = document.getElementById('salesDateFrom');
    const dateToInput = document.getElementById('salesDateTo');

    if (dateFromInput) {
      dateFromInput.value = from;
    }

    if (dateToInput) {
      dateToInput.value = to;
    }
  }

  function syncHiddenRangeFromText() {
    const rangeInput = document.getElementById('salesDateRangeFilter');
    const dateFromInput = document.getElementById('salesDateFrom');
    const dateToInput = document.getElementById('salesDateTo');

    if (!rangeInput) {
      return;
    }

    const rangeText = String(rangeInput.value || '');
    const isoDates = rangeText.match(/\d{4}-\d{2}-\d{2}/g) || [];
    const dates = isoDates.length
      ? isoDates
      : rangeText.split(/\s+(?:to|until)\s+/i).map(function (part) {
        return formatDate(parseDateText(part));
      }).filter(Boolean);
    const sortedDates = dates.slice(0, 2).sort();
    const from = sortedDates[0] || '';
    const to = sortedDates[1] || from;

    if (dateFromInput) {
      dateFromInput.value = from;
    }

    if (dateToInput) {
      dateToInput.value = to;
    }
  }

  function buildSalesUrl(form) {
    syncHiddenRangeFromText();

    const formData = new FormData(form);
    const params = new URLSearchParams();

    formData.forEach(function (value, key) {
      if (value !== '') {
        params.set(key, value);
      }
    });

    const url = new URL(window.location.href);
    url.search = params.toString();
    return url;
  }

  function extractSalesChartData(html) {
    const match = html.match(/window\.salesChartData\s*=\s*(\{[\s\S]*?\});/);
    if (!match) {
      return { labels: [], gross: [], expenses: [], net: [] };
    }

    try {
      return JSON.parse(match[1]);
    } catch (error) {
      return { labels: [], gross: [], expenses: [], net: [] };
    }
  }

  function setLoading(isLoading) {
    const page = document.querySelector('.sales-page');
    const button = page ? page.querySelector('#salesApplyFilter') : null;

    if (page) {
      page.classList.toggle('is-loading', isLoading);
    }

    if (button) {
      button.disabled = isLoading;
      button.innerHTML = isLoading
        ? '<span class="spinner-border spinner-border-sm me-1"></span> Applying'
        : '<i class="ri-filter-3-line me-1"></i> Apply';
    }
  }

  function replaceSalesPage(html, url, updateHistory) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const currentPage = document.querySelector('.sales-page');
    const nextPage = doc.querySelector('.sales-page');

    if (!currentPage || !nextPage) {
      window.location.href = url.toString();
      return;
    }

    if (salesChartInstance) {
      salesChartInstance.destroy();
      salesChartInstance = null;
    }

    if (salesDatePicker && typeof salesDatePicker.destroy === 'function') {
      salesDatePicker.destroy();
      salesDatePicker = null;
    }

    window.salesChartData = extractSalesChartData(html);
    currentPage.innerHTML = nextPage.innerHTML;

    if (updateHistory) {
      window.history.pushState({ salesAjax: true }, '', url.toString());
    }

    initSalesPage();
  }

  function fetchSalesPage(url, updateHistory) {
    setLoading(true);

    fetch(url.toString(), {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Unable to load filtered sales.');
        }

        return response.text();
      })
      .then(function (html) {
        replaceSalesPage(html, url, updateHistory);
      })
      .catch(function () {
        window.location.href = url.toString();
      })
      .finally(function () {
        setLoading(false);
      });
  }

  window.applySalesAjaxFilter = function (url) {
    fetchSalesPage(url instanceof URL ? url : new URL(url, window.location.href), true);
  };

  function showSalesAlert(options) {
    if (typeof Swal !== 'undefined') {
      return Swal.fire(options);
    }

    window.alert(options.text || options.title || '');
    return Promise.resolve({ isConfirmed: true });
  }

  function markSalesAsPaid(button) {
    const bookingID = button.getAttribute('data-booking-id');

    if (!bookingID) {
      return;
    }

    showSalesAlert({
      icon: 'question',
      title: 'Mark billing as paid?',
      text: 'This will set the paid amount to the full billing amount and close the balance.',
      showCancelButton: true,
      confirmButtonText: 'Yes, mark paid',
      cancelButtonText: 'Cancel'
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      const formData = new FormData();
      formData.append('bookingID', bookingID);
      button.disabled = true;

      fetch('ajax/sales_status_update.ajax.php', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (response) {
          if (!response || response.status !== 'success') {
            throw new Error(response && response.message ? response.message : 'Unable to update billing status.');
          }

          return showSalesAlert({
            icon: 'success',
            title: 'Billing Paid',
            text: response.message || 'Billing marked as paid.',
            timer: 1200,
            showConfirmButton: false
          });
        })
        .then(function () {
          fetchSalesPage(new URL(window.location.href), false);
        })
        .catch(function (error) {
          button.disabled = false;
          showSalesAlert({
            icon: 'error',
            title: 'Update Failed',
            text: error.message || 'Unable to update billing status.'
          });
        });
    });
  }

  function markSalesRangeAsPaid(button) {
    const page = document.querySelector('.sales-page');
    const form = page ? page.querySelector('.sales-filter-panel') : null;

    if (!form) {
      return;
    }

    syncHiddenRangeFromText();

    const dateFromInput = document.getElementById('salesDateFrom');
    const dateToInput = document.getElementById('salesDateTo');
    const customerTypeInput = form.querySelector('[name="customerType"]');
    const dateFrom = dateFromInput ? dateFromInput.value : '';
    const dateTo = dateToInput ? dateToInput.value : '';
    const customerType = customerTypeInput ? customerTypeInput.value : '';

    if (!dateFrom || !dateTo) {
      showSalesAlert({
        icon: 'warning',
        title: 'Select Date Range',
        text: 'Choose the 15-day payment range before marking grouped billing as paid.'
      });
      return;
    }

    showSalesAlert({
      icon: 'question',
      title: 'Mark range as paid?',
      text: 'This will mark all unpaid billing from ' + dateFrom + ' to ' + dateTo + ' as paid.',
      showCancelButton: true,
      confirmButtonText: 'Yes, mark range paid',
      cancelButtonText: 'Cancel'
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      const formData = new FormData();
      formData.append('action', 'group-paid');
      formData.append('dateFrom', dateFrom);
      formData.append('dateTo', dateTo);
      formData.append('customerType', customerType);
      button.disabled = true;

      fetch('ajax/sales_status_update.ajax.php', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (response) {
          if (!response || response.status !== 'success') {
            throw new Error(response && response.message ? response.message : 'Unable to update grouped billing status.');
          }

          return showSalesAlert({
            icon: 'success',
            title: 'Grouped Payment Updated',
            text: response.message || 'Grouped billing marked as paid.',
            timer: 1500,
            showConfirmButton: false
          });
        })
        .then(function () {
          fetchSalesPage(buildSalesUrl(form), false);
        })
        .catch(function (error) {
          button.disabled = false;
          showSalesAlert({
            icon: 'error',
            title: 'Update Failed',
            text: error.message || 'Unable to update grouped billing status.'
          });
        });
    });
  }

  function initDateRangePicker() {
    const rangeInput = document.getElementById('salesDateRangeFilter');
    const dateFromInput = document.getElementById('salesDateFrom');
    const dateToInput = document.getElementById('salesDateTo');

    if (!rangeInput || typeof AirDatepicker === 'undefined') {
      return;
    }

    rangeInput.addEventListener('change', syncHiddenRangeFromText);
    rangeInput.addEventListener('input', syncHiddenRangeFromText);

    const fromDate = dateFromInput ? parseDate(dateFromInput.value) : null;
    const toDate = dateToInput ? parseDate(dateToInput.value) : null;
    const selectedDates = [];

    if (fromDate) {
      selectedDates.push(fromDate);
    }

    if (toDate && (!fromDate || formatDate(toDate) !== formatDate(fromDate))) {
      selectedDates.push(toDate);
    }

    salesDatePicker = new AirDatepicker('#salesDateRangeFilter', {
      range: true,
      multipleDatesSeparator: ' to ',
      dateFormat: 'yyyy-MM-dd',
      locale: localeEn,
      selectedDates: selectedDates,
      autoClose: false,
      buttons: ['today', 'clear'],
      onSelect: function ({ date }) {
        const pickedDates = Array.isArray(date) ? date : (date ? [date] : []);
        syncHiddenRangeFromDates(pickedDates);
      }
    });
  }

  function renderChart() {
    const chartCanvas = document.getElementById('salesChart');
    const data = window.salesChartData || { labels: [], gross: [], expenses: [], net: [] };

    if (!chartCanvas || typeof Chart === 'undefined') {
      return;
    }

    if (!Array.isArray(data.labels) || data.labels.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'sales-empty';
      empty.textContent = 'No completed sales data available for the chart yet.';
      chartCanvas.parentNode.replaceChild(empty, chartCanvas);
      return;
    }

    salesChartInstance = new Chart(chartCanvas, {
      type: 'line',
      data: {
        labels: data.labels,
        datasets: [
          {
            label: 'Gross Sales',
            data: data.gross,
            borderColor: '#28c76f',
            backgroundColor: 'rgba(40,199,111,0.12)',
            tension: 0.35,
            fill: true
          },
          {
            label: 'Expenses',
            data: data.expenses,
            borderColor: '#ff3e1d',
            backgroundColor: 'rgba(255,62,29,0.08)',
            tension: 0.35,
            fill: true
          },
          {
            label: 'Net Sales',
            data: data.net,
            borderColor: '#696cff',
            backgroundColor: 'rgba(105,108,255,0.10)',
            tension: 0.35,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            position: 'bottom'
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return context.dataset.label + ': PHP ' + Number(context.raw || 0).toLocaleString(undefined, {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2
                });
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return 'PHP ' + Number(value).toLocaleString();
              }
            }
          }
        }
      }
    });
  }

  function initSalesPage() {
    const page = document.querySelector('.sales-page');
    const clearButton = page ? page.querySelector('.sales-filter-actions a[href="sales"]') : null;
    const markRangePaidButton = page ? page.querySelector('#salesMarkRangePaid') : null;

    if (!page) {
      return;
    }

    initDateRangePicker();
    renderChart();

    if (clearButton) {
      clearButton.addEventListener('click', function (event) {
        event.preventDefault();

        const url = new URL(window.location.href);
        url.search = 'route=sales';
        fetchSalesPage(url, true);
      });
    }

    page.querySelectorAll('.sales-mark-paid').forEach(function (button) {
      button.addEventListener('click', function () {
        markSalesAsPaid(button);
      });
    });

    if (markRangePaidButton) {
      markRangePaidButton.addEventListener('click', function () {
        markSalesRangeAsPaid(markRangePaidButton);
      });
    }
  }

  window.addEventListener('popstate', function () {
    if (document.querySelector('.sales-page')) {
      fetchSalesPage(new URL(window.location.href), false);
    }
  });

  $(document)
    .off('submit.salesFilter', '.sales-filter-panel')
    .on('submit.salesFilter', '.sales-filter-panel', function (event) {
      event.preventDefault();
      syncHiddenRangeFromText();
      fetchSalesPage(buildSalesUrl(this), true);
    });

  $(document)
    .off('click.salesFilter', '#salesApplyFilter')
    .on('click.salesFilter', '#salesApplyFilter', function (event) {
      event.preventDefault();
      const form = this.closest('.sales-filter-panel');

      if (!form) {
        return;
      }

      syncHiddenRangeFromText();
      fetchSalesPage(buildSalesUrl(form), true);
    });

  initSalesPage();
});
