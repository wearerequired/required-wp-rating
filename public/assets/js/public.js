(function ($) {
  "use strict";

  $(function () {

    var rplus_xhr = false;

    $('.rplus-rating-dorating').on('click', function (e) {
      e.preventDefault();

      // don't execute this when a request is in progress
      if (rplus_xhr !== false || ( $(this).data('voted') == true )) {
        return false;
      }

      var $this = $(this),
          data = {
            post_id: $this.data('post'),
            type   : $this.data('type'),
            action : 'rplus_wp_rating_ajax_dorating',
            _token : RplusWpRatingAjax.nonce_vote
          };

      rplus_xhr = true;

      $.post(RplusWpRatingAjax.ajaxurl, data, function (response) {

        if (response.success == true) {

          $this.addClass('active');
          // prevent double execution
          $this.removeClass('rplus-rating-dorating');
          $this.data('voted', true);

          // check if we have to display the feedbackform
          if (response.data.feedback == true) {

            var $form = $(response.data.feedbackform).hide();
            $('.rplus-rating-controls .button-group').after($form);
            $form.fadeIn();
            $form.find('form[name="rplusfeedback"]').data('token', response.data.token).data('post', $this.data('post'));

          }

          // update counts
          $('.rplus-rating-controls .rating-count-positive').text(response.data.positives);
          $('.rplus-rating-controls .rating-count-negative').text(response.data.negatives);

        } else {

          var info = $('<span class="rplus-rating-error">' + response.data + '</span>').hide();
          $('.rplus-rating-controls').append(info.fadeIn());

          window.setTimeout(function () {
            $('.rplus-rating-error').fadeOut();
          }, 5000);

        }

        rplus_xhr = false;
      });

      return false;
    });

    $(document).on('submit', 'form[name="rplusfeedback"]', function (e) {
      e.preventDefault();

      // don't execute this when a request is in progress
      if (rplus_xhr !== false || ( $(this).data('voted') == true )) {
        return false;
      }

      var $this = $(this),
          data = {
            post_id  : $this.data('post'),
            rating_id: $this.data('rating_id'),
            action   : 'rplus_wp_rating_ajax_dofeedback',
            feedback : $('.rplus-rating-controls textarea.feedback').val(),
            reply    : $('.rplus-rating-controls input.reply').val(),
            _token   : $this.data('token')
          };

      // stop when no feedback is given
      if (data.feedback.length == 0) {
        $('.rplus-rating-controls textarea.feedback').focus();
        return false;
      }

      rplus_xhr = true;

      $.post(RplusWpRatingAjax.ajaxurl, data, function (response) {

        if (response.success == true) {

          var $controls = $this.parents('.rplus-rating-controls'),
              $info = $('<span class="rplus-rating-success">' + response.data + '</span>').hide();

          $this.parent('.feedback-form').fadeOut(400, function () {
            $(this).remove();
            $controls.append($info.fadeIn());
          });

          window.setTimeout(function () {
            $('.rplus-rating-success').fadeOut();
          }, 5000);

        } else {

          var $info = $('<span class="rplus-rating-error">' + response.data + '</span>').hide();
          $this.parent('.rplus-rating-controls').append($info.fadeIn());

          window.setTimeout(function () {
            $('.rplus-rating-error').fadeOut();
          }, 5000);

        }

        rplus_xhr = false;
      });

      return false;
    });

  });

}(jQuery));