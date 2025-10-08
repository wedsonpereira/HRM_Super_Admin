'use strict';
$(function () {
  window.getRoles = async function () {
    var response = await $.ajax({
      url: `${baseUrl}account/getRolesAjax`,
      type: 'GET'
    });

    if (response && response.status === 'success') {
      return response.data;
    } else {
      return [];
    }
  };

  window.getDepartments = async function () {
    var response = await $.ajax({
      url: `${baseUrl}departments/getDepartmentListAjax`,
      type: 'GET'
    });

    if (response && response.status === 'success') {
      return response.data;
    } else {
      return [];
    }
  };

  window.getDesignations = async function () {
    var response = await $.ajax({
      url: `${baseUrl}designations/getDesignationListAjax`,
      type: 'GET'
    });

    if (response && response.status === 'success') {
      return response.data;
    } else {
      return [];
    }
  };

  window.getLocations = async function () {
    var response = await $.ajax({
      url: `${baseUrl}locations/getLocationListAjax`,
      type: 'GET'
    });

    if (response && response.status === 'success') {
      return response.data;
    } else {
      return [];
    }
  };

  window.getTeams = async function () {
    var response = await $.ajax({
      url: `${baseUrl}teams/getTeamListAjax`,
      type: 'GET'
    });

    if (response && response.status === 'success') {
      return response.data;
    } else {
      return [];
    }
  };

  window.getShifts = async function () {
    try { 
      var response = await $.ajax({
        // The URL should resolve to your new route.
        // If `baseUrl` is your application's root URL (e.g., "http://localhost:8000/"),
        // then the path to the shifts active list would be "shifts/active-list-for-dropdown".
        url: `${baseUrl}shifts/getActiveShiftsForDropdown`, // Ensure baseUrl is correctly defined and ends with a / if needed, or adjust accordingly.
        type: 'GET'
      });

      if (response && response.success && response.data) {
        return response.data;
      } else {
        console.error('Failed to load shifts or data format incorrect:', response);
        return [];
      }
    } catch (error) {
      console.error('Error in getShifts AJAX call:', error);
      // You might want to show a user-friendly error message here
      // e.g., using a toast notification library if you have one.
      return [];
    }
  };

  window.getReportingToUsers = async function () {
    var response = await $.ajax({
      url: `${baseUrl}employees/getReportingToUsersAjax`,
      type: 'GET'
    });

    if (response && response.status === 'success') {
      return response.data;
    } else {
      return [];
    }
  };

  window.getUserInRole = async function (role) {
    var response = await $.ajax({
      url: `${baseUrl}account/getUsersByRoleAjax/${role}`,
      type: 'GET'
    });

    if (response && response.status === 'success') {
      return response.data;
    }
    return [];
  };

  window.getUsers = async function () {
    var response = await $.ajax({
      url: `${baseUrl}account/getUsersAjax`,
      type: 'GET'
    });

    if (response && response.status === 'success') {
      return response.data;
    }
    return [];
  };

  $(function () {
    // ajax setup
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });

    function getProfilePictureWithName(userName, profilePicture) {
      if (profilePicture) {
        return `<img src="${profilePicture}" alt="${userName}" class="rounded-circle" width="40" height="40">`;
      }

      var $name = userName,
        $initials = $name.match(/\b\w/g) || [],
        $output;

      $initials = (($initials.shift() || '') + ($initials.pop() || '')).toUpperCase();
      $output = '<span class="avatar-initial rounded-circle bg-label-info">' + $initials + '</span>';

      // Creates full output for row
      return (
        '<div class="d-flex justify-content-start align-items-center user-name">' +
        '<div class="avatar-wrapper">' +
        '<div class="avatar avatar-sm me-4">' +
        $output +
        '</div>' +
        '</div>' +
        '<div class="d-flex flex-column">' +
        '<a href="' +
        '#' +
        '" class="text-truncate text-heading"><span class="fw-medium">' +
        $name +
        '</span></a>' +
        '</div>' +
        '</div>'
      );
    }
  });
});
