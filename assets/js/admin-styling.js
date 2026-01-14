/* global jQuery */
(function ($) {
  function initColorPickers(root) {
    if (!$.fn.wpColorPicker) return;
    $(root)
      .find('input.fg-color-field')
      .each(function () {
        const $input = $(this);
        if ($input.data('fgColorPickerInit')) return;
        $input.data('fgColorPickerInit', true);
        $input.wpColorPicker();
      });
  }

  function initFilter() {
    const $filter = $('#fg-style-filter');
    if (!$filter.length) return;

    const $wrap = $filter.closest('.wrap');

    function applyFilter() {
      const term = String($filter.val() || '').trim().toLowerCase();
      const $rows = $wrap.find('table.form-table tr');

      if (!term) {
        $rows.show();
        return;
      }

      $rows.each(function () {
        const $row = $(this);
        const labelText = $row.find('th label').text().trim().toLowerCase();
        const matches = labelText.indexOf(term) !== -1;
        $row.toggle(matches);
      });
    }

    $filter.on('input', applyFilter);
    applyFilter();
  }

  $(function () {
    initColorPickers(document);
    initFilter();

    // Also support color fields inside metaboxes.
    $(document).on('widget-added widget-updated', function (e, widget) {
      initColorPickers(widget);
    });
  });
})(jQuery);
