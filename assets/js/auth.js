(function( authHelper, $, undefined ) {
	"use strict";
    var _settings = {};

	authHelper.init = function(settings) {
        _settings = $.extend(_settings, settings);
    };

    authHelper.initToggleChildItems = function(treeSelector) {
        $(treeSelector).on('click', '.toggle-auth', function(e) {
            var toggle = $(this).hasClass('fa-times');
            $('input[type=hidden]', $(this).closest('li'))
                .prop('disabled', !toggle);

            $(this).toggleChildItems();
        });
        
        $(treeSelector + ' > li > ul > li > div .toggle-auth').toggleChildItems();
    };

    $.fn.toggleChildItems = function() {
        var li_parents = $(this).parents('li');
        $('li', $(this).closest('li')).add(li_parents).each(function(){
            var has_disabled = $('input:disabled', this).length > 0;
            var has_enabled = $('input:not(:disabled)', this).length > 0;

            $(this).children('.stv-item')
                .toggleClass('auth-all-enabled', !has_disabled && has_enabled)
                .toggleClass('auth-some-enabled', has_disabled && has_enabled);

            $('span.title', $(this).children('.stv-item'))
                .toggleClass('text-muted', has_disabled && !has_enabled);

            $('.toggle-auth', $(this).children('.stv-item'))
                .toggleClass('text-danger', has_disabled && !has_enabled)
                .toggleClass('text-success', !has_disabled && has_enabled)
                .toggleClass('text-primary', has_disabled && has_enabled)
                .toggleClass('fa-times', has_disabled && !has_enabled)
                .toggleClass('fa-check', !has_disabled && has_enabled)
                .toggleClass('fa-minus', has_disabled && has_enabled);
        });
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