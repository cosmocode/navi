jQuery(function () {
    'use strict';

    jQuery('.plugin__navi.js').find('li.close, li.open')
        .on('click', function (e) {
            jQuery(e.target).toggleClass('close open');
            e.stopPropagation();
        })
        .css('cursor', 'pointer')
    ;
});
