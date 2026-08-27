(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.jQuery === 'undefined') {
            return;
        }

        var $ = window.jQuery;
        var config = window.SaTenantConfig || {};

        $(document).on('click', '.sa-tenant-status-btn, .banUser, .unbanUser', function () {
            var userId = $(this).data('id');
            var newStatus = $(this).data('status');
            var actionText = newStatus === 'active' ? 'activate' : 'suspend';
            var reason = '';

            if (newStatus === 'suspended') {
                reason = window.prompt('Suspend reason (required):', '');
                if (reason === null) {
                    return;
                }
                reason = String(reason).trim();
                if (reason.length < 4) {
                    toastr.error('Please enter a suspend reason (at least 4 characters).');
                    return;
                }
            } else if (!confirm('Are you sure you want to activate this tenant?')) {
                return;
            }

            $.ajax({
                url: config.statusUrl || '',
                type: 'POST',
                data: {
                    _token: config.csrf || '',
                    user_id: userId,
                    status: newStatus,
                    reason: reason,
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success('Tenant ' + actionText + 'd successfully.');
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        toastr.error(response.message || 'Could not update tenant status.');
                    }
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        || 'An error occurred. Please try again.';
                    toastr.error(msg);
                },
            });
        });
    });
})();
