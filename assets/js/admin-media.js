/* global jQuery, wp, FectionGalleryAdmin */
(function ($) {
  function parseIds(value) {
    if (!value) return [];
    return value
      .split(',')
      .map((v) => parseInt(v.trim(), 10))
      .filter((n) => Number.isFinite(n) && n > 0);
  }

  function renderPreview(attachments) {
    const $preview = $('#fg-media-preview');
    $preview.empty();

    attachments.forEach((att) => {
      const thumb = att.sizes && (att.sizes.thumbnail || att.sizes.medium);
      const url = thumb ? thumb.url : att.icon;
      const $item = $('<div class="fg-thumb" />');
      $item.append($('<img />', { src: url, alt: att.alt || '' }));
      $preview.append($item);
    });
  }

  $(function () {
    const $ids = $('#fg-media-ids');

    $('#fg-clear-media').on('click', function () {
      $ids.val('');
      $('#fg-media-preview').empty();
    });

    $('#fg-pick-media').on('click', function (e) {
      e.preventDefault();

      const frame = wp.media({
        title: (FectionGalleryAdmin && FectionGalleryAdmin.frameTitle) || 'Select media',
        button: { text: (FectionGalleryAdmin && FectionGalleryAdmin.frameButton) || 'Use selected' },
        library: { type: ['image', 'video'] },
        multiple: true
      });

      frame.on('open', function () {
        const selection = frame.state().get('selection');
        parseIds($ids.val()).forEach((id) => {
          const attachment = wp.media.attachment(id);
          attachment.fetch();
          selection.add(attachment);
        });
      });

      frame.on('select', function () {
        const selection = frame.state().get('selection');
        const ids = [];
        const atts = [];

        selection.each(function (model) {
          ids.push(model.get('id'));
          atts.push(model.toJSON());
        });

        $ids.val(ids.join(','));
        renderPreview(atts);
      });

      frame.open();
    });
  });
})(jQuery);
