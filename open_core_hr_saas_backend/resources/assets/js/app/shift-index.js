'use strict';

$(function () {
  // jQuery document ready

  // --- Selectors ---
  const shiftDataTableElement = $('.datatables-shifts');
  const shiftOffcanvasElement = $('#offcanvasAddOrUpdateShift');
  const shiftOffcanvas = shiftOffcanvasElement.length ? new bootstrap.Offcanvas(shiftOffcanvasElement[0]) : null;
  const shiftForm = $('#shiftForm');
  const offcanvasLabel = $('#offcanvasShiftLabel');
  const submitBtn = $('#submitShiftBtn');
  const shiftIdInput = $('#shift_id');
  const shiftMethodInput = $('#shiftMethod');
  const generalErrorDiv = $('#shiftForm .general-error-message'); // General error in form

  // Form Fields
  const startTimeInput = document.getElementById('startTime'); // Get native element for flatpickr
  const endTimeInput = document.getElementById('endTime'); // Get native element for flatpickr

  // --- URLs & CSRF (Ensure these are passed correctly from Blade) ---
  // const shiftListAjaxUrl = "{{ route('shifts.listAjax') }}";
  // const shiftStoreUrl = "{{ route('shifts.store') }}";
  // const shiftBaseUrl = "{{ url('shifts') }}"; // For update/delete/edit/toggle
  // const csrfToken = "{{ csrf_token() }}";

  // --- CSRF Setup ---
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

  // --- Initialize Plugins ---
  let fpStartTime, fpEndTime;
  if (startTimeInput) {
    fpStartTime = flatpickr(startTimeInput, { enableTime: true, noCalendar: true, dateFormat: 'H:i' });
  }
  if (endTimeInput) {
    fpEndTime = flatpickr(endTimeInput, { enableTime: true, noCalendar: true, dateFormat: 'H:i' });
  }

  // --- Helper Functions ---
  // Reset Validation
  function resetShiftFormValidation() {
    shiftForm.find('.is-invalid').removeClass('is-invalid');
    shiftForm.find('.invalid-feedback').text('');
    generalErrorDiv.text('').hide();
  }

  // Reset Form Content
  function resetShiftForm() {
    resetShiftFormValidation();
    shiftForm[0]?.reset();
    shiftIdInput.val('');
    shiftMethodInput.val('POST');
    fpStartTime?.clear();
    fpEndTime?.clear();
    // Set default checked days (e.g., Mon-Fri)
    shiftForm.find('input[type="checkbox"]').prop('checked', false); // Uncheck all first
    ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].forEach(day => {
      $(`#${day}Toggle`).prop('checked', true); // Check Mon-Fri
    });
    offcanvasLabel.text('Add Shift');
    submitBtn.text('Submit').prop('disabled', false);
  }

  // Set Button Loading State
  function setShiftButtonLoading(isLoading) {
    const buttonText = shiftIdInput.val() ? 'Update' : 'Submit';
    submitBtn.prop('disabled', isLoading);
    submitBtn.html(isLoading ? '<span class="spinner-border spinner-border-sm"></span> Processing...' : buttonText);
  }

  // Display Validation Errors
  function displayShiftValidationErrors(errors) {
    resetShiftFormValidation();
    let firstErrorElement = null;
    for (const fieldName in errors) {
      let inputName = fieldName;
      let errorMessage = errors[fieldName][0];
      const inputElement = shiftForm.find(`[name="${inputName}"]`);
      let targetElement = inputElement;
      let feedbackElement = targetElement.closest('.mb-3, .row').find('.invalid-feedback');
      if (!inputElement.length) {
        generalErrorDiv.append(`<div>${escapeHtml(inputName)}: ${escapeHtml(errorMessage)}</div>`).show();
        if (!firstErrorElement) firstErrorElement = generalErrorDiv;
        continue;
      }
      if (inputElement.hasClass('flatpickr-input')) {
        targetElement = inputElement;
      }

      if (targetElement.length) {
        targetElement.addClass('is-invalid');
        if (feedbackElement.length) {
          feedbackElement.text(errorMessage).show();
        } else {
          targetElement.after(`<div class="invalid-feedback d-block">${errorMessage}</div>`);
        }
        if (!firstErrorElement) firstErrorElement = targetElement;
      }
    }
    firstErrorElement?.focus();
  }

  // HTML Escaping
  function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return '';
    return unsafe
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Toast Notifications (Assuming global functions exist)
  // function showSuccessToast(message) { ... }
  // function showErrorToast(message) { ... }

  // --- DataTable Initialization ---
  let dtShift;
  if (shiftDataTableElement.length) {
    dtShift = shiftDataTableElement.DataTable({
      processing: true,
      serverSide: true,
      ajax: { url: shiftListAjaxUrl, type: 'GET' }, // Use GET for data source
      columns: [
        { data: 'id', name: 'id' },
        { data: 'name', name: 'name' },
        { data: 'code', name: 'code' },
        { data: 'shift_days', name: 'shift_days', orderable: false, searchable: false }, // Rendered server-side
        { data: 'status_display', name: 'status', className: 'text-center' }, // Rendered server-side
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
      ],
      columnDefs: [
        { targets: 0, visible: false }, // Hide ID column by default
        { targets: [4, 5], className: 'text-center' }
      ],
      order: [[1, 'asc']], // Order by name
      dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: { sLengthMenu: '_MENU_', search: '', searchPlaceholder: 'Search Shifts...' },
      buttons: [
        /* Export buttons if needed */
      ]
    });
  }

  // --- Offcanvas Handling ---
  if (shiftOffcanvasElement.length) {
    shiftOffcanvasElement.on('hidden.bs.offcanvas', resetShiftForm);
  }

  $('.add-new').on('click', function () {
    // Target '.add-new' class from Blade
    resetShiftForm();
    offcanvasLabel.text('Add New Shift');
    shiftOffcanvas?.show();
  });

  shiftDataTableElement.on('click', '.edit-shift', function () {
    const shiftId = $(this).data('id');
    const editUrl = $(this).data('url'); // URL from button

    resetShiftForm();
    offcanvasLabel.text('Loading Shift Data...');
    shiftOffcanvas?.show();

    $.ajax({
      url: editUrl,
      type: 'GET',
      dataType: 'json',
      success: function (response) {
        if (response.success && response.shift) {
          const data = response.shift;
          offcanvasLabel.text('Edit Shift: ' + data.name);
          shiftIdInput.val(data.id);
          shiftMethodInput.val('PUT'); // Set method for update

          $('#shiftName').val(data.name || '');
          $('#shiftCode').val(data.code || '');
          $('#shiftNotes').val(data.notes || '');
          fpStartTime?.setDate(data.start_time_formatted || '', true); // Use formatted time
          fpEndTime?.setDate(data.end_time_formatted || '', true); // Use formatted time

          // Set checkboxes for days
          ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'].forEach(day => {
            $(`#${day}Toggle`).prop('checked', !!data[day]); // Set checked based on boolean value
          });
        } else {
          shiftOffcanvas?.hide();
          showErrorToast(response.message || 'Failed data load.');
        }
      },
      error: function (jqXHR) {
        shiftOffcanvas?.hide();
        showErrorToast('Could not load shift data.');
      }
    });
  });

  // --- Form Submission (Add/Edit) ---
  if (shiftForm.length) {
    shiftForm.on('submit', function (e) {
      e.preventDefault();
      resetShiftFormValidation();

      const isUpdate = !!shiftIdInput.val();
      const url = isUpdate ? `${shiftBaseUrl}/${shiftIdInput.val()}` : shiftStoreUrl; // Construct URL
      const method = 'POST'; // Always POST for AJAX
      let formData = new FormData(shiftForm[0]);

      if (isUpdate) {
        formData.append('_method', 'PUT');
      }

      // Ensure checkbox values are sent correctly (0 or 1)
      ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'].forEach(day => {
        formData.set(day, $(`#${day}Toggle`).is(':checked') ? '1' : '0');
      });
      // Make sure status isn't included if not editable in form, or set explicitly if it is
      // formData.delete('status'); // Example if status not set via this form

      setShiftButtonLoading(true);

      $.ajax({
        url: url,
        method: method,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            shiftOffcanvas?.hide();
            showSuccessToast(response.message);
            dtShift?.ajax.reload(null, false);
          } else {
            showErrorToast(response.message || 'Operation failed.');
          }
        },
        error: function (jqXHR) {
          if (jqXHR.status === 422 && jqXHR.responseJSON?.errors) {
            displayShiftValidationErrors(jqXHR.responseJSON.errors);
            showErrorToast('Validation failed.');
          } else {
            showErrorToast(jqXHR.responseJSON?.message || 'An error occurred.');
          }
        },
        complete: function () {
          setShiftButtonLoading(false);
        }
      });
    });
  }

  // --- Toggle Status Handling ---
  shiftDataTableElement.on('change', '.shift-status-toggle', function () {
    var checkbox = $(this);
    var url = checkbox.data('url');
    var currentStateIsActive = !checkbox.is(':checked');
    checkbox.prop('disabled', true);

    $.ajax({
      url: url,
      method: 'POST',
      data: { _method: 'POST' },
      dataType: 'json', // Assuming toggle route uses PUT via _method
      success: function (response) {
        if (response.success) {
          // showSuccessToast(response.message || 'Status updated.');
          //dtShift?.row(checkbox.closest('tr')).invalidate('data').draw(false); // Reload row data
        } else {
          showErrorToast(response.message || 'Update failed.');
          checkbox.prop('checked', currentStateIsActive);
        }
      },
      error: function (jqXHR) {
        showErrorToast('Error toggling status.');
        checkbox.prop('checked', currentStateIsActive);
      },
      complete: function () {
        checkbox.prop('disabled', false);
      }
    });
  });

  // --- Delete Shift Handling ---
  shiftDataTableElement.on('click', '.delete-shift', function () {
    const button = $(this);
    const deleteUrl = button.data('url');
    const isAssigned = button.is(':disabled'); // Check if disabled due to assignment

    if (isAssigned) {
      showErrorToast('Cannot delete shift: It is currently assigned to users.');
      return;
    }

    Swal.fire({
      title: 'Are you sure?',
      text: 'Delete this shift?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(function (result) {
      if (result.isConfirmed) {
        $.ajax({
          url: deleteUrl,
          method: 'POST',
          data: { _method: 'DELETE' },
          dataType: 'json',
          success: function (response) {
            if (response.success) {
              showSuccessToast(response.message);
              dtShift?.row(button.closest('tr')).remove().draw(false);
            } else {
              showErrorToast(response.message || 'Delete failed.');
            }
          },
          error: function (jqXHR) {
            showErrorToast(jqXHR.responseJSON?.message || 'Deletion failed.');
          }
        });
      }
    });
  });
}); // End Document Ready
