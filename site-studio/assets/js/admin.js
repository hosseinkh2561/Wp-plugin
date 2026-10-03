(function ($) {
    'use strict';

    function feedback(message, error) {
        var node = document.querySelector('[data-studio-feedback]');
        if (!node) {
            return;
        }
        node.textContent = message || '';
        node.style.color = error ? '#c33' : '#2eae62';
    }

    $(function () {
        $('[data-studio-media]').on('click', function (event) {
            event.preventDefault();
            var button = $(this);
            var target = $('#' + button.data('target'));
            if (!target.length || typeof wp === 'undefined' || !wp.media) {
                return;
            }
            var frame = wp.media({
                title: 'انتخاب تصویر برای استودیو',
                button: { text: 'استفاده از این تصویر' },
                multiple: false,
                library: { type: 'image' }
            });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                target.val(attachment.id).trigger('change');
                var preview = $('[data-preview-for="' + button.data('target') + '"]');
                var imageUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                preview.html('<img src="' + $('<div>').text(imageUrl).html() + '" alt=""><button type="button" class="studio-media-remove" data-remove-media="' + button.data('target') + '" aria-label="حذف تصویر">×</button>');
            });
            frame.open();
        });

        $(document).on('click', '[data-remove-media]', function () {
            var targetId = $(this).data('remove-media');
            $('#' + targetId).val('0').trigger('change');
            $('[data-preview-for="' + targetId + '"]').html('<span>هنوز تصویری انتخاب نشده است</span>');
        });

        $('[data-studio-preset]').on('click', function () {
            var button = $(this);
            if (button.hasClass('is-loading')) {
                return;
            }
            button.addClass('is-loading');
            feedback('در حال اعمال چیدمان…', false);
            $.post(ArenaSiteStudioAdmin.ajaxUrl, {
                action: 'arena_site_studio_apply_preset',
                preset: button.data('studio-preset'),
                nonce: ArenaSiteStudioAdmin.nonce
            }).done(function (response) {
                if (response && response.success) {
                    feedback(response.data.message, false);
                    window.setTimeout(function () { window.location.reload(); }, 500);
                } else {
                    feedback(response && response.data ? response.data.message : ArenaSiteStudioAdmin.errorLabel, true);
                }
            }).fail(function () {
                feedback(ArenaSiteStudioAdmin.errorLabel, true);
            }).always(function () {
                button.removeClass('is-loading');
            });
        });

        $('[data-studio-elementor]').on('click', function () {
            var button = $(this);
            if (button.hasClass('is-loading')) {
                return;
            }
            button.addClass('is-loading').text('در حال آماده‌سازی…');
            $.post(ArenaSiteStudioAdmin.ajaxUrl, {
                action: 'arena_site_studio_elementor',
                nonce: ArenaSiteStudioAdmin.nonce
            }).done(function (response) {
                if (response && response.success) {
                    window.alert(response.data.message);
                    window.location.reload();
                } else {
                    window.alert(response && response.data ? response.data.message : ArenaSiteStudioAdmin.errorLabel);
                    button.removeClass('is-loading').text('تلاش دوباره');
                }
            }).fail(function () {
                window.alert(ArenaSiteStudioAdmin.errorLabel);
                button.removeClass('is-loading').text('تلاش دوباره');
            });
        });

        $('[data-range-output]').on('input change', function () {
            var output = $($(this).data('range-output'));
            if (output.length) {
                output.text($(this).val() + 'px');
            }
        });

        // The global column selector is a quick layout control. Individual
        // product rails can still be fine-tuned afterwards.
        $('#studio-columns').on('change', function () {
            $('.studio-product-editor select[name*="[columns]"]').val($(this).val());
        });

        $('.studio-side-card a[href^="#"], .studio-admin-header a[href^="#"]').on('click', function (event) {
            var target = $($(this).attr('href'));
            if (target.length) {
                event.preventDefault();
                $('html, body').animate({ scrollTop: target.offset().top - 25 }, 260);
            }
        });

        $('.studio-settings-form').on('submit', function () {
            $('#studio-submit').addClass('is-busy').text('در حال ذخیره…').prop('disabled', true);
        });
    });
}(jQuery));
