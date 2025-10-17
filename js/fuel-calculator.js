
(function ($, Drupal) {
    'use strict';

    Drupal.behaviors.fuelCalculatorValidation = {
        attach: function (context) {
            const $fields = $('#edit-distance, #edit-efficiency, #edit-price', context);
            const $form = $('#fuel-calculator-form', context);

            function validate(value)
            {
                const num = parseFloat(value);
                return {
                    valid: !isNaN(num) && num > 0,
                    message: !isNaN(num) && num > 0 ? '' : 'Must be a positive number'
                };
            }
            function showValidation($field, validation)
            {
                const $msg = $field.closest('.space-y-2').find('.validation-message');
                const $input = $field;

                if (!validation.valid && $field.val()) {
                    $msg.addClass('text-red-500').html('⚠ ' + validation.message);
                    $input.addClass('border-red-500 ring-red-500');
                } else if (validation.valid) {
                    $msg.addClass('text-green-500').html('✓ Valid');
                    $input.addClass('border-green-500');
                }
            }

            $fields.on(
                'input blur',
                function () {
                    showValidation($(this), validate($(this).val()));
                }
            );

            $form.on(
                'submit',
                function (e) {
                    let isValid = true;
                    $fields.each(
                        function () {
                            if (!validate($(this).val()).valid) {
                                isValid = false;
                            }
                        }
                    );
                }
            );

            $fields.filter('[value]').trigger('blur');
        }
    };

})(jQuery, Drupal);
