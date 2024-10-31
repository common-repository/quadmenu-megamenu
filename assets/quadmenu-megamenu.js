+function ($) {
    'use strict';

    $(document).on('click.quadmenu.import', '.max_mega_menu_import', function (e) {
        e.preventDefault();


        var $box = $(this).closest('.theme'),
                $spinner = $box.find('.spinner');

        if (!$box.data('importing')) {
            $.ajax({
                type: 'GET',
                url: ajaxurl,
                data: {
                    action: 'quadmenu_import_megamenu',
                    nonce: quadmenu.nonce,
                },
                beforeSend: function () {
                    $spinner.addClass('is-active');
                    $box.addClass('importing').data('importing', true);
                },
                complete: function () {
                    $spinner.removeClass('is-active');
                    $box.removeClass('importing').removeData('importing');
                },
                success: function (response) {

                    if (response.success !== true) {
                        console.log(response.data);
                        return;
                    }

                    window.location.href = response.data;
                }
            });
        }
    });

}(jQuery);