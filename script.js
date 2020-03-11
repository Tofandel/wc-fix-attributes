jQuery(function ($) {
    // The woocommerce select2 is initializing
    $(document.body).on('wc-enhanced-select-init', function () {
        //We need to wait a bit for it to be fully inited
        setTimeout(function() {
            $('select.attribute_values').each(function () {
                var $that = $(this);
                $that.select2({ //Add an ajax option to it, to search the terms dynamically
                    ajax: {
                        url: WCFixAttributes.ajaxurl,
                        dataType: 'json',
                        type: "GET",
                        quietMillis: 400,
                        data: function (term) {
                            return {
                                action: 'wc_fix_search_terms',
                                _wpnonce: WCFixAttributes.nonce,
                                // Get the taxonomy printed from our action
                                taxonomy: $that.parent().children('.attribute_taxonomy_getter').data('taxonomy'),
                                term: term.term
                            };
                        },
                    }
                });
            });
        }, 100);
    })
});
