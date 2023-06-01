jQuery(document).ready(function($) {
    $('#custom_date_field input[name="custom_date"]').datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: 0, // Restrict selection to future dates only
    });
});
