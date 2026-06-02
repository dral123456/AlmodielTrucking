$(document).ready(function () {
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

    $(document).on('click', '.incident-status-action', function () {
      const button = $(this);
      const incidentID = button.data('incident-id');
      const status = button.data('status');
      const labels = {
        open: 'reopen this incident',
        reviewing: 'mark this incident as reviewing',
        resolved: 'resolve this incident',
        dismissed: 'dismiss this incident'
      };

      Swal.fire({
        title: 'Update Incident',
        text: 'Do you want to ' + (labels[status] || 'update this incident') + '?',
        input: 'textarea',
        inputLabel: 'Admin notes',
        inputPlaceholder: 'Optional notes about the action taken...',
        showCancelButton: true,
        confirmButtonText: 'Update',
        confirmButtonColor: '#696cff'
      }).then(function (result) {
        if (!result.isConfirmed) {
          return;
        }

        updateIncidentStatus(incidentID, status, result.value || '', button);
      });
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

  function showIncidentError(message) {
    Swal.fire({
      icon: 'error',
      title: 'Update Failed',
      text: message,
      confirmButtonColor: '#696cff'
    });
  }
});
