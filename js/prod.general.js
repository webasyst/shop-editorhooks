(function() { "use strict";

    //
    // validation for top field on product General tab
    // implements JS validation before sending data to server
    //
    $('#js-product-general-section').on('wa_before_save', function(event) {
        var $field_wrapper = $('#editorhooks-general-field-top');
        var $input = $field_wrapper.find('input');

        removeErrorsFromDOM($field_wrapper);

        // Validate field
        var value = $input.val();
        if (!value.match(/^z*$/)) {
            if (true) {
                // Show error near the field that triggered it
                showValidationError($input, $input.data('validation-error-message'));

                // this will cancel form submit
                event.preventDefault();
            } else {
                // Alternatively, we may choose to show generic error at the bottom of the page
                // (this only works on General tab, but not on SKU tab)
                event.form_errors.push({
                    text: $input.data('validation-error-message')
                });
            }
        } else {
            // Normal form elements (<input>, <select>, etc.) will be automatically added to form_data
            // when they contain name="" attribute. For custom widgets, you may need to manually
            // add them here:
            //event.form_data.push({
            //    name: 'editorhooks[some_data_top]',
            //    value: value
            //});
        }

    });

    //
    // validation for bottom field on product General tab
    // implements server-side validation
    //
    $('#js-product-general-section').on('wa_after_save', function(event) {
        if (!event.server_errors || !event.server_errors.length) {
            // we're not interested in successfull save
            return;
        }

        var $field_wrapper = $('#editorhooks-general-field-bottom');
        var $input = $field_wrapper.find('input');

        var errors = event.server_errors || [];
        $.each(errors, function(i, err) {
            if (err.id == 'plugin_error' && err.plugin == 'editorhooks' && err.name == 'general_bottom') {
                showValidationError($input, err.text);

                // If we keep error in the list, it will be shown at the bottom if the form
                // (this may actually be a desired behaviour for some plugins)
                errors[i] = undefined;
            }
        });
    });
    $('#js-product-general-section').on('wa_before_save', function(event) {
        var $field_wrapper = $('#editorhooks-general-field-bottom');
        var $input = $field_wrapper.find('input');

        removeErrorsFromDOM($field_wrapper);

        // Normal form elements (<input>, <select>, etc.) will be automatically added to form_data
        // when they contain name="" attribute. For custom widgets, you may need to manually
        // add them here:
        //event.form_data.push({
        //    name: 'editorhooks[some_data_bottom]',
        //    value: value
        //});

    });

    //
    // Helper functions
    //

    function removeErrorsFromDOM($wrapper) {
        $wrapper.find('.wa-message.error,.wa-error-text').remove();
    }

    function showValidationError($field, error_message) {
        $field.closest('.value').append($("<div />", {
            "class": "wa-error-text"

            // this will render a large message
            //"class": "wa-message error"
        }).text(error_message));
    }

}());