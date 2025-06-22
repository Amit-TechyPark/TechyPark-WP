jQuery(document).ready(function ($) {
    'use strict';
    $('#quantum-clear-cache').on('click', function(e) {
        e.preventDefault();
        var $button = $(this), $status = $('#quantum-cache-status');
        $button.prop('disabled', true).addClass('updating-message');
        $status.text('Clearing...').removeClass('success error');
        $.post(quantumCache.ajax_url, { action: 'quantum_clear_cache', nonce: quantumCache.nonce })
            .done(function(response) {
                if(response.success) $status.text(response.data).addClass('success');
                else $status.text('An error occurred.').addClass('error');
            })
            .fail(function() { $status.text('Request failed.').addClass('error'); })
            .always(function() {
                $button.prop('disabled', false).removeClass('updating-message');
                setTimeout(function() { $status.text('').removeClass('success error'); }, 4000);
            });
    });

    $('.db-optimize-btn').on('click', function(e) {
        e.preventDefault();
        var $button = $(this), optimizationType = $button.data('type');
        var $spinner = $('<span class="spinner is-active"></span>'), $status = $('#db-status-message');
        $button.prop('disabled', true).after($spinner);
        $status.text('').removeClass('success error');
        $.post(quantumCache.ajax_url, { action: 'quantum_db_optimize', nonce: quantumCache.nonce, optimization_type: optimizationType })
            .done(function (response) {
                if (response.success) $status.text('Success! Cleaned ' + response.data.count + ' entries.').addClass('success');
                else $status.text('An error occurred.').addClass('error');
            })
            .fail(function() { $status.text('Request failed.').addClass('error'); })
            .always(function() {
                $button.prop('disabled', false);
                $spinner.remove();
                setTimeout(function() { $status.text('').removeClass('success error'); }, 5000);
            });
    });
});
