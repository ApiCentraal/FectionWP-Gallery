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

    if (!attachments || !attachments.length) {
      $preview.append(
        $('<div class="fg-empty" />', {
          text: (FectionGalleryAdmin && FectionGalleryAdmin.empty) || 'No media selected yet.'
        })
      );
      return;
    }

    attachments.forEach((att) => {
      const thumb = att.sizes && (att.sizes.thumbnail || att.sizes.medium);
      const url = thumb ? thumb.url : att.icon;

      const $item = $('<div class="fg-thumb" />');
      $item.attr('data-id', String(att.id));
      $item.append(
        $('<div class="fg-thumb-handle" aria-hidden="true" />').append(
          $('<span class="dashicons dashicons-move" />')
        )
      );
      $item.append($('<img />', { src: url, alt: att.alt || '' }));

      const editUrl = att.editLink || (att.id ? 'post.php?post=' + String(att.id) + '&action=edit' : '');
      const $actions = $('<div class="fg-thumb-actions" />');
      if (editUrl) {
        $actions.append(
          $('<a />', {
            href: editUrl,
            target: '_blank',
            rel: 'noopener noreferrer',
            text: (FectionGalleryAdmin && FectionGalleryAdmin.openInLibrary) || 'Open in Media Library'
          })
        );
      }
      $actions.append(
        $('<button />', {
          type: 'button',
          class: 'button-link fg-remove-media',
          'data-id': String(att.id),
          text: (FectionGalleryAdmin && FectionGalleryAdmin.remove) || 'Remove'
        })
      );

      $item.append($actions);
      $preview.append($item);
    });
  }

  function updateIdsFromPreview() {
    const ids = [];
    $('#fg-media-preview .fg-thumb').each(function () {
      const id = parseInt($(this).attr('data-id'), 10);
      if (Number.isFinite(id) && id > 0) ids.push(id);
    });
    $('#fg-media-ids').val(ids.join(','));

    const $count = $('#fg-media-count');
    if ($count.length) {
      const label = (FectionGalleryAdmin && FectionGalleryAdmin.selected) || 'Selected:';
      $count.text(label + ' ' + String(ids.length));
    }

    // Let other admin scripts (e.g. live preview) react.
    $(document).trigger('fg_media_ids_updated', [ids]);
  }

  $(function () {
    const $ids = $('#fg-media-ids');

    const $preview = $('#fg-media-preview');
    if ($preview.length && $preview.sortable) {
      $preview.sortable({
        items: '.fg-thumb',
        handle: '.fg-thumb-handle',
        tolerance: 'pointer',
        update: function () {
          updateIdsFromPreview();
        }
      });
    }

    // Initialize count from existing state.
    updateIdsFromPreview();

    $(document).on('click', '.fg-remove-media', function (e) {
      e.preventDefault();
      const $item = $(this).closest('.fg-thumb');
      $item.remove();
      updateIdsFromPreview();
    });

    $('#fg-clear-media').on('click', function () {
      $ids.val('');
      $('#fg-media-preview')
        .empty()
        .append(
          $('<div class="fg-empty" />', {
            text: (FectionGalleryAdmin && FectionGalleryAdmin.empty) || 'No media selected yet.'
          })
        );

      updateIdsFromPreview();

      $(document).trigger('fg_media_ids_updated', [[]]);
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
          const json = model.toJSON();
          json.editLink = model.get('editLink');
          atts.push(json);
        });

        $ids.val(ids.join(','));
        renderPreview(atts);

        updateIdsFromPreview();

        $(document).trigger('fg_media_ids_updated', [ids]);
      });

      frame.open();
    });
  });
})(jQuery);
