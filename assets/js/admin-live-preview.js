/* global jQuery, ajaxurl, FectionGalleryLivePreview */
(function ($) {
  function debounce(fn, wait) {
    let t = null;
    return function () {
      const ctx = this;
      const args = arguments;
      clearTimeout(t);
      t = setTimeout(function () {
        fn.apply(ctx, args);
      }, wait);
    };
  }

  function collectStyleOverrides() {
    const out = {};
    // Collect both inputs and selects from the per-gallery overrides.
    $('[name^="fg_style["]').each(function () {
      const $el = $(this);
      const name = $el.attr('name');
      if (!name) return;
      const m = name.match(/^fg_style\[(.+)\]$/);
      if (!m) return;
      const key = m[1];
      const value = String($el.val() || '').trim();
      if (value !== '') out[key] = value;
    });
    return out;
  }

  function getValue(selector, fallback) {
    const $el = $(selector);
    if (!$el.length) return fallback;
    return String($el.val() || fallback);
  }

  function getChecked(selector) {
    const $el = $(selector);
    if (!$el.length) return 0;
    return $el.is(':checked') ? 1 : 0;
  }

  function setStatus(text) {
    $('#fg-live-preview-status').text(text);
  }

  function renderPreview() {
    const $wrap = $('#fg-live-preview');
    if (!$wrap.length) return;

    const postId = parseInt($wrap.data('post-id'), 10);
    if (!Number.isFinite(postId) || postId <= 0) return;

    const mediaIdsCsv = getValue('#fg-media-ids', '');
    if (!String(mediaIdsCsv).trim()) {
      $('#fg-live-preview-output').html('<p class="description">' + (FectionGalleryLivePreview && FectionGalleryLivePreview.noMedia || 'Select media to see a preview.') + '</p>');
      return;
    }

    const payload = {
      action: 'fg_render_preview',
      nonce: FectionGalleryLivePreview && FectionGalleryLivePreview.nonce,
      id: postId,
      media_ids: mediaIdsCsv,
      layout: getValue('#fg_layout', ''),
      cards_per_slide: getValue('#fg_cards_per_slide', '3'),
      header: getValue('#fg_header_text', ''),
      footer_button: getChecked('input[name="fg_footer_button"]'),
      card_show_image: getChecked('#fg_card_show_image'),
      card_image_size: getValue('#fg_card_image_size', ''),
      autoplay: getChecked('#fg_preview_autoplay'),
      interval: getValue('#fg_preview_interval', '5000'),
      style: JSON.stringify(collectStyleOverrides())
    };

    setStatus((FectionGalleryLivePreview && FectionGalleryLivePreview.loading) || 'Loading preview…');

    $.post(ajaxurl, payload)
      .done(function (res) {
        if (res && res.success && res.data && typeof res.data.html === 'string') {
          $('#fg-live-preview-output').html(res.data.html);
          setStatus('');
        } else {
          setStatus((FectionGalleryLivePreview && FectionGalleryLivePreview.error) || 'Could not render preview.');
        }
      })
      .fail(function () {
        setStatus((FectionGalleryLivePreview && FectionGalleryLivePreview.error) || 'Could not render preview.');
      });
  }

  const schedule = debounce(renderPreview, 250);

  $(function () {
    if (!$('#fg-live-preview').length) return;

    $('#fg-refresh-preview').on('click', function (e) {
      e.preventDefault();
      renderPreview();
    });

    // React to key gallery fields.
    $('#fg_layout, #fg_cards_per_slide, #fg_header_text, #fg_card_show_image, #fg_card_image_size, #fg_preview_autoplay, #fg_preview_interval').on('change input', schedule);
    $(document).on('fg_media_ids_updated', schedule);

    // React to per-gallery styling overrides.
    $(document).on('change input', '[name^="fg_style["]', schedule);

    // Initial render.
    renderPreview();
  });
})(jQuery);
