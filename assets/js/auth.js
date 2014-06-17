(function( authHelper, $, undefined ) {
	"use strict";
    var _settings = {};

	authHelper.init = function(settings) {
        _settings = $.extend(_settings, settings);
    };

    authHelper.initToggleChildItems = function(treeSelector) {
        $('input:not(:disabled)','#yw2').each(function(){$(this).toggleChildItems(true);});

        $(treeSelector).on('click', '.toggle-auth', function(e) {
            var toggle = !$(this).hasClass('fa-check');
            $('input[type=hidden]', $(this).closest('li'))
                .prop('disabled', !toggle);
        
            $('input[type=hidden]', $(this).closest('.stv-item'))
                .prop('disabled', !toggle);

            $(this).toggleChildItems(toggle);
        });
    };

    $.fn.toggleChildItems = function(toggle) {
        $('.toggle-auth', $(this).closest('li'))
                .toggleClass('text-primary', false)
                .toggleClass('fa-minus', false)
                .toggleClass('text-success', toggle)
                .toggleClass('text-danger', !toggle)
                .toggleClass('fa-check', toggle)
                .toggleClass('fa-times', !toggle);

        $('span.title', $(this).closest('li'))
                .toggleClass('text-muted', !toggle);

        $('.stv-item', $(this).closest('li'))
                .toggleClass('auth-all-enabled', toggle)
                .toggleClass('auth-some-enabled', false);
        

        var parent = $(this).closest('ul');
        while (parent.hasClass('stv-list') && parent.prev().hasClass('stv-item')) {
            console.info(parent, parent.prev());
            var disabled = $('input[type=hidden]:disabled', parent).length;
            var all = $('input[type=hidden]', parent).length;
            parent.prev()
                    .toggleClass('auth-all-enabled', disabled === 0)
                    .toggleClass('auth-some-enabled', all !== disabled && disabled !== 0);

            $('.toggle-auth', parent.prev())
                    .toggleClass('text-success', disabled === 0)
                    .toggleClass('text-primary', all !== disabled && disabled !== 0)
                    .toggleClass('text-danger', all === disabled)
                    .toggleClass('fa-check', disabled === 0)
                    .toggleClass('fa-minus', all !== disabled && disabled !== 0)
                    .toggleClass('fa-times', all === disabled);

            $('span.title', parent.prev())
                    .toggleClass('text-muted', all === disabled);

            $('input[type=hidden]', parent.prev())
                    .prop('disabled', disabled !== 0);

            parent = parent.parent().closest('ul');
        }
    };

    authHelper.expandSelectedBranches = function(selector) {
        $('ul:has(input:not(:disabled))', selector)
                .show()
                .each(function(){
                    $('.toggle', $(this).prev())
                        .toggleClass('fa-expand-o', false)
                        .toggleClass('fa-collapse-o', true);
                });
    };

}( window.authHelper = window.authHelper || {}, jQuery ));