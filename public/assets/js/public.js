(function ( $ ) {
	"use strict";

	$(function () {

        var xhr = null;

        $('.rplus-rating-dorating').on('click', function(e) {
            e.preventDefault();

            // don't execute this when a request is in progress
            if ( xhr !== null ) {
                return;
            }

            var $this = $(this),
                data = {
                    post_id: $this.data('post'),
                    type: $this.data('type'),
                    action: 'rplus_wp_rating_ajax_dorating',
                    _token: RplusWpRatingAjax.nonce_vote
                };

            xhr = $.post( RplusWpRatingAjax.ajaxurl, data, function( response ) {

                // do it!

            });

        });

	});

}(jQuery));