$(document).ready(function () {
  const incidents = Array.isArray(window.incidentReportData) ? window.incidentReportData : [];

  bindIncidentEvents();
  filterIncidentRows();

  function bindIncidentEvents() {
    $(document).on('click', '.incident-stat-card', function () {
      const status = $(this).data('incident-filter');
      $('.incident-stat-card').removeClass('active');
      $(this).addClass('active');
      $('#incidentStatusFilter').val(status);
      filterIncidentRows();
    });

    $(document).on('input change', '#incidentSearch, #incidentStatusFilter', function () {
      const status = $('#incidentStatusFilter').val();
      $('.incident-stat-card').removeClass('active');
      $('.incident-stat-card[data-incident-filter="' + status + '"]').addClass('active');
      filterIncidentRows();
    });

    $(document).on('click', '.incident-row', function () {
      showIncidentReviewPopup($(this).data('incident-id'));
    });

    $(document).on('click', '.incident-status-action', function (event) {
      event.stopPropagation();
      showIncidentReviewPopup($(this).data('incident-id'));
    });
  }

  function filterIncidentRows() {
    const status = $('#incidentStatusFilter').val() || 'all';
    const search = String($('#incidentSearch').val() || '').toLowerCase();
    let visible = 0;

    $('.incident-row').each(function () {
      const row = $(this);
      const rowStatus = String(row.data('status') || '');
      const rowText = row.text().toLowerCase();
      const matchesStatus = status === 'all' || rowStatus === status;
      const matchesSearch = !search || rowText.indexOf(search) !== -1;
      const shouldShow = matchesStatus && matchesSearch;

      row.toggle(shouldShow);
      if (shouldShow) {
        visible++;
      }
    });

    $('.incident-filter-empty').remove();
    if (!visible && $('.incident-row').length) {
      $('.incident-table tbody').append('<tr class="incident-filter-empty"><td colspan="7" class="text-center text-muted py-4">No incident reports match the current filters.</td></tr>');
    }
  }

  function showIncidentReviewPopup(incidentID) {
    const incident = getIncidentByID(incidentID);

    if (!incident) {
      return;
    }

    $('.incident-row').removeClass('active');
    $('.incident-row[data-incident-id="' + Number(incident.incidentID) + '"]').addClass('active');

    Swal.fire({
      html: buildIncidentPopupHtml(incident),
      width: 'min(1080px, calc(100vw - 32px))',
      showConfirmButton: false,
      showCloseButton: true,
      focusConfirm: false,
      scrollbarPadding: false,
      customClass: {
        popup: 'incident-review-popup'
      },
      didOpen: function () {
        $('.incident-review-action').on('click', function () {
          const button = $(this);
          const status = button.data('status');
          const adminNotes = String($('#incidentAdminActionTaken').val() || '').trim();

          if (status === 'resolved' && !adminNotes) {
            $('#incidentResolveMessage').removeClass('d-none');
            $('#incidentAdminActionTaken').trigger('focus');
            return;
          }

          $('#incidentResolveMessage').addClass('d-none');
          confirmIncidentStatus(incident.incidentID, status, adminNotes, button);
        });

        $('#incidentAdminActionTaken').on('input', function () {
          if ($(this).val().trim()) {
            $('#incidentResolveMessage').addClass('d-none');
          }
        });
      }
    });
  }

  function buildIncidentPopupHtml(incident) {
    return (
      '<div class="incident-review-modal">' +
        '<div class="incident-review-header">' +
          '<div>' +
            '<h5>Incident #' + escapeHtml(incident.incidentID) + '</h5>' +
            '<div class="text-muted small">Submitted ' + escapeHtml(formatDateTime(incident.dateSubmitted)) + '</div>' +
          '</div>' +
          '<div class="incident-review-meta">' +
            '<span class="badge ' + statusClass(incident.status) + '">' + escapeHtml(labelize(incident.status)) + '</span>' +
            '<span class="badge ' + severityClass(incident.severity) + '">' + escapeHtml(labelize(incident.severity)) + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="incident-review-grid">' +
          reviewField('Trip', 'Trip #' + incident.tripID + (incident.bookingID ? ' / Booking #' + incident.bookingID : ' / Whole trip')) +
          reviewField('Driver', (incident.driverName || 'Driver') + ' (ID #' + incident.driverID + ')') +
          reviewField('Type', labelize(incident.incidentType)) +
          reviewField('Incident Time', formatDateTime(incident.incidentDateTime)) +
          reviewField('Customer', incident.customerName || 'Customer not attached') +
          reviewField('Location', incident.locationText || 'No location noted') +
          reviewField('Description', incident.description || '-', true) +
          reviewField('Driver Action Taken', incident.actionTaken || 'No action noted', true) +
        '</div>' +
        '<div class="incident-review-notes">' +
          '<label for="incidentAdminActionTaken">Admin Action Taken <span class="text-danger">*</span></label>' +
          '<textarea class="form-control" id="incidentAdminActionTaken" placeholder="Required before resolving. Example: Called driver, arranged replacement truck, notified customer...">' + escapeHtml(incident.adminNotes || '') + '</textarea>' +
          '<div class="text-danger small mt-2 d-none" id="incidentResolveMessage">Admin Action Taken is required before resolving this incident.</div>' +
          '<div class="form-text">Saved as the admin action/note for this report.</div>' +
        '</div>' +
        '<div class="incident-review-actions">' +
          '<button type="button" class="btn btn-outline-info incident-review-action" data-status="reviewing">Review</button>' +
          '<button type="button" class="btn btn-success incident-review-action" data-status="resolved">Resolve</button>' +
          '<button type="button" class="btn btn-outline-secondary incident-review-action" data-status="dismissed">Dismiss</button>' +
          '<button type="button" class="btn btn-outline-warning incident-review-action" data-status="open">Reopen</button>' +
        '</div>' +
      '</div>'
    );
  }

  function reviewField(label, value, fullWidth) {
    return (
      '<div class="incident-review-field' + (fullWidth ? ' incident-review-full' : '') + '">' +
        '<span>' + escapeHtml(label) + '</span>' +
        '<p>' + escapeHtml(value) + '</p>' +
      '</div>'
    );
  }

  function confirmIncidentStatus(incidentID, status, adminNotes, button) {
    const labels = {
      open: 'reopen this incident',
      reviewing: 'mark this incident as reviewing',
      resolved: 'resolve this incident',
      dismissed: 'dismiss this incident'
    };

    Swal.fire({
      title: 'Update Incident',
      text: 'Do you want to ' + (labels[status] || 'update this incident') + '?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Update',
      confirmButtonColor: '#696cff'
    }).then(function (result) {
      if (!result.isConfirmed) {
        showIncidentReviewPopup(incidentID);
        return;
      }

      updateIncidentStatus(incidentID, status, adminNotes, button);
    });
  }

  function updateIncidentStatus(incidentID, status, adminNotes, button) {
    button.prop('disabled', true);

    $.ajax({
      url: 'ajax/incident_status_update.ajax.php',
      method: 'POST',
      dataType: 'json',
      data: {
        incidentID: incidentID,
        status: status,
        adminNotes: adminNotes
      },
      success: function (response) {
        if (response && response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Incident Updated',
            text: response.message || 'Incident report updated.',
            confirmButtonColor: '#696cff'
          }).then(function () {
            location.reload();
          });
          return;
        }

        showIncidentError(response && response.message ? response.message : 'Unable to update incident report.');
        button.prop('disabled', false);
      },
      error: function () {
        showIncidentError('Unable to update incident report.');
        button.prop('disabled', false);
      }
    });
  }

  function getIncidentByID(incidentID) {
    return incidents.find(function (incident) {
      return Number(incident.incidentID) === Number(incidentID);
    });
  }

  function labelize(value) {
    return String(value || '-')
      .replace(/_/g, ' ')
      .replace(/\b\w/g, function (letter) {
        return letter.toUpperCase();
      });
  }

  function formatDateTime(value) {
    if (!value) {
      return '-';
    }

    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
      return value;
    }

    return date.toLocaleString([], {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function statusClass(status) {
    if (status === 'resolved') {
      return 'bg-success-subtle text-success';
    }

    if (status === 'reviewing') {
      return 'bg-info-subtle text-info';
    }

    if (status === 'dismissed') {
      return 'bg-secondary-subtle text-secondary';
    }

    return 'bg-warning-subtle text-warning';
  }

  function severityClass(severity) {
    if (severity === 'critical') {
      return 'bg-danger text-white';
    }

    if (severity === 'high') {
      return 'bg-danger-subtle text-danger';
    }

    if (severity === 'low') {
      return 'bg-success-subtle text-success';
    }

    return 'bg-warning-subtle text-warning';
  }

  function showIncidentError(message) {
    Swal.fire({
      icon: 'error',
      title: 'Update Failed',
      text: message,
      confirmButtonColor: '#696cff'
    });
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
});
