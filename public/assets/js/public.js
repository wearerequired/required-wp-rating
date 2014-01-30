(function ( $ ) {
	"use strict";

	$(function () {

        var rplus_xhr = false;

        $('.rplus-rating-dorating').on('click', function(e) {
            e.preventDefault();

            // don't execute this when a request is in progress
            if ( rplus_xhr !== false ) {
                return false;
            }

            var $this = $(this),
                data = {
                    post_id: $this.data('post'),
                    type: $this.data('type'),
                    action: 'rplus_wp_rating_ajax_dorating',
                    _token: RplusWpRatingAjax.nonce_vote
                };

            rplus_xhr = true;

            $.post( RplusWpRatingAjax.ajaxurl, data, function( response ) {

                if ( response.success == true ) {

                    // update counts
                    $('.rplus-rating-positive span').text( response.data.positives );
                    $('.rplus-rating-negative span').text( response.data.negatives );

                    var info = $('<span class="rplus-rating-success">' + response.data.message + '</span>').hide();
                    $('.rplus-rating-controls').append( info.fadeIn() );

                    window.setTimeout( function() { $('.rplus-rating-success').fadeOut(); }, 2000 );

                } else {

                    var info = $('<span class="rplus-rating-error">' + response.data + '</span>').hide();
                    $('.rplus-rating-controls').append( info.fadeIn() );

                    window.setTimeout( function() { $('.rplus-rating-error').fadeOut(); }, 2000 );

                }

                rplus_xhr = false;
            });

            return false;
        });

	});

}(jQuery));