jQuery(function() {
    'use strict';
    
    jQuery('li.open, li.close').find('> div.li').each(function (index, element){
        var link = jQuery(element).find('a').attr('href');
        var $arrowSpan = jQuery('<span></span>').click(function (event) {
            window.location = link;
        });
        $arrowSpan.addClass('arrowUnderlay');
        jQuery(element).append($arrowSpan);
    });
});
