(function() { "use strict";

    //
    // implements JS validation before sending data to server on sku tab
    //
    $('#js-product-sku-section-wrapper').on('wa_before_save', function(event) {
        var $field_wrapper = $('#editorhooks-sku-field-top');
        var $input = $field_wrapper.find('input');

        removeErrorsFromDOM($field_wrapper);

        // Validate field
        var value = $input.val();
        if (!value.match(/^z*$/)) {
            // Show error near the field that triggered it
            showValidationError($input, $input.data('validation-error-message'));

            // this will cancel form submit
            event.preventDefault();
        } else {
            // Normal form elements (<input>, <select>, etc.) will be automatically added to form_data
            // when they contain name="" attribute. For custom widgets, you may need to manually
            // add them here:
            //event.form_data.push({
            //    name: $input.attr('name'),
            //    value: value
            //});
        }

    });

    //
    // implements server-side validation for non-sku field on sku tab
    //
    $('#js-product-sku-section-wrapper').on('wa_after_save', function(event) {
        if (!event.server_errors || !event.server_errors.length) {
            // we're not interested in successfull save
            return;
        }

        var errors = event.server_errors || [];
        $.each(errors, function(i, err) {
            if (err.id == 'plugin_error' && err.plugin == 'editorhooks' && err.name == 'sku_bottom') {
                showValidationError($('#editorhooks-sku-field-bottom input'), err.text);

                // If we keep error in the list, it will be shown at the bottom if the form
                // (this may actually be a desired behaviour for some plugins)
                errors[i] = undefined;
            }
        });
    });
    $('#js-product-sku-section-wrapper').on('wa_before_save', function(event) {
        var $field_wrapper = $('#editorhooks-sku-field-bottom');
        var $input = $field_wrapper.find('input');

        removeErrorsFromDOM($field_wrapper);

        // Normal form elements (<input>, <select>, etc.) will be automatically added to form_data
        // when they contain name="" attribute. For custom widgets, you may need to manually
        // add them here:
        //event.form_data.push({
        //    name: $input.attr('name'),
        //    value: $input.val()
        //});

    });

    // Implements JS validation for SKU price field
    $('#js-product-sku-section-wrapper').on('wa_before_save', function(event) {
        //
        // Look for SKU price value in form data, check value if found
        //
        $.each(event.form_data || [], function(i, name_value) {
            var name = name_value.name,
                value = name_value.value;
            if (name.match(/\[additional_prices\]\[editorhooks_price_1\]$/)) {
                if (parseFloat(value) > 100) {
                    event.form_errors.push({
                        "id": name,
                        "text": 'This price must not exceed 100 (JS plugin validation test)'
                    });
                }
            }
        });
    });

    // Implements JS validation for additional SKU field
    $('#js-product-sku-section-wrapper').on('wa_before_save', function(event) {
        //
        // Look for additional SKU field value in form data, check value if found
        //
        $.each(event.form_data || [], function(i, name_value) {
            var name = name_value.name,
                value = name_value.value;
            if (name.match(/\[additional_fields\]\[editorhooks_input_zzzz\]$/)) {
                if (value && !value.match(/^z+$/)) {
                    event.form_errors.push({
                        "id": name,
                        "text": 'This field must only contain z characters (JS plugin validation test)'
                    });
                }
            }
        });
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