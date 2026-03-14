(function ($) {
    function applyConditionalVisibility() {
        $('[data-conditional-field]').each(function () {
            var $field = $(this);
            var sourceName = $field.data('conditionalField');
            var expectedValue = String($field.data('conditionalValue'));
            var $source = $('[name="' + sourceName + '"]');
            var actualValue = '';

            if (!$source.length) {
                return;
            }

            if ($source.is(':checkbox')) {
                actualValue = $source.is(':checked') ? $source.val() : '0';
            } else {
                actualValue = String($source.val());
            }

            $field.toggleClass('is-hidden', actualValue !== expectedValue);
        });
    }

    $(function () {
        $('.event-o-color-picker').wpColorPicker();

        $(document).on('change', '[name="event_o_dark_mode"]', applyConditionalVisibility);

        applyConditionalVisibility();
    });
})(jQuery);