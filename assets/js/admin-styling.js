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

  function initSectionNav() {
    const $nav = $('#fg-style-nav');
    if (!$nav.length) return;

    const $root = $('#fg-styling-page');
    const $wrap = $root.length ? $root : $nav.closest('.wrap');
    const $form = $wrap.find('form');
    if (!$form.length) return;

    const $headings = $form.find('h2');
    if (!$headings.length) return;

    // Wrap each settings section (h2 + following elements until next h2) into a card.
    $headings.each(function () {
      const $h2 = $(this);
      if ($h2.closest('.fg-style-section').length) return;

      const $section = $('<div class="fg-style-section" />');
      $h2.before($section);

      // Move h2 and all following siblings until the next h2 into this section.
      const $toMove = $h2.nextUntil('h2').addBack();
      $section.append($toMove);
    });

    const $sections = $form.find('.fg-style-section');
    if (!$sections.length) return;

    const labels = (window.FectionGalleryStyling || {});
    const allLabel = labels.all || 'All';

    let activeKey = 'all';
    function setActive(key, opts) {
      activeKey = key;
      $nav.find('.fg-style-nav-btn').removeClass('is-active');
      $nav.find('.fg-style-nav-btn[data-key="' + key + '"]').addClass('is-active');

      if (key === 'all') {
        $sections.show();
        return;
      }

      $sections.each(function () {
        const $s = $(this);
        $s.toggle($s.data('fgKey') === key);
      });
    }

    // Build nav buttons.
    $nav.empty();

    const $allBtn = $('<button type="button" class="fg-style-nav-btn" />')
      .attr('data-key', 'all')
      .text(allLabel)
      .on('click', function () {
        setActive('all');
      });
    $nav.append($allBtn);

    $sections.each(function (idx) {
      const $s = $(this);
      const title = String($s.find('h2').first().text() || '').trim();
      const key = 's' + String(idx);
      $s.attr('data-fg-key', key);
      $s.data('fgKey', key);

      const $btn = $('<button type="button" class="fg-style-nav-btn" />')
        .attr('data-key', key)
        .text(title)
        .on('click', function () {
          setActive(key);
          // Scroll to section in main column.
          if (window.scrollTo) {
            const top = $s.offset() ? $s.offset().top - 40 : null;
            if (typeof top === 'number') window.scrollTo({ top, behavior: 'smooth' });
          }
        });

      $nav.append($btn);
    });

    setActive('all');

    return {
      setActive: setActive,
      getActiveKey: function () {
        return activeKey;
      },
      $sections: $sections,
      $nav: $nav,
    };
  }

  function initFilter(navApi) {
    const $root = $('#fg-styling-page');
    if (!$root.length) return;

    const $filter = $root.find('#fg-style-filter');
    if (!$filter.length) return;

    const $noResults = $root.find('#fg-styling-no-results');

    let restoreKey = (navApi && typeof navApi.getActiveKey === 'function') ? navApi.getActiveKey() : 'all';

    function updateSectionVisibility() {
      if (!navApi || !navApi.$sections || !navApi.$sections.length) return;

      navApi.$sections.each(function () {
        const $section = $(this);
        const $rows = $section.find('table.form-table tr');
        const hasVisible = $rows.filter(':visible').length > 0;
        $section.toggle(hasVisible);

        const key = String($section.data('fgKey') || '');
        if (key) {
          const $btn = navApi.$nav.find('.fg-style-nav-btn[data-key="' + key + '"]');
          $btn.toggleClass('is-disabled', !hasVisible);
          $btn.prop('disabled', !hasVisible);
        }
      });
    }

    function applyFilter() {
      const term = String($filter.val() || '').trim().toLowerCase();
      const $rows = $root.find('table.form-table tr');

      if (!term) {
        $rows.show();
        if ($noResults && $noResults.length) $noResults.prop('hidden', true);

        // Re-enable all nav buttons.
        if (navApi && navApi.$nav) {
          navApi.$nav.find('.fg-style-nav-btn').removeClass('is-disabled').prop('disabled', false);
        }

        // Restore current nav selection.
        if (navApi && typeof navApi.getActiveKey === 'function') {
          navApi.setActive(restoreKey, { skipScroll: true });
        }
        return;
      }

      // When filtering, force "All" so matches across sections are visible.
      if (navApi && typeof navApi.setActive === 'function') {
        if (typeof navApi.getActiveKey === 'function' && navApi.getActiveKey() !== 'all') {
          restoreKey = navApi.getActiveKey();
        }
        navApi.setActive('all', { skipScroll: true });
      }

      // While filtering, keep navigation locked to "All".
      if (navApi && navApi.$nav) {
        navApi.$nav.find('.fg-style-nav-btn').each(function () {
          const $btn = $(this);
          const key = String($btn.attr('data-key') || '');
          const isAll = key === 'all';
          $btn.prop('disabled', !isAll);
          $btn.toggleClass('is-disabled', !isAll);
        });
      }

      $rows.each(function () {
        const $row = $(this);
        const labelText = $row.find('th label').text().trim().toLowerCase();
        const matches = labelText.indexOf(term) !== -1;
        $row.toggle(matches);
      });

      updateSectionVisibility();

      const anyVisible = $rows.filter(':visible').length > 0;
      if ($noResults && $noResults.length) {
        $noResults.prop('hidden', anyVisible);
      }
    }

    $filter.on('input', applyFilter);
    applyFilter();
  }

  $(function () {
    initColorPickers(document);
    const navApi = initSectionNav();
    initFilter(navApi);

    // Also support color fields inside metaboxes.
    $(document).on('widget-added widget-updated', function (e, widget) {
      initColorPickers(widget);
    });
  });
})(jQuery);
