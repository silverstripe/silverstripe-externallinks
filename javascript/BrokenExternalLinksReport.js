(function($) {
  $.entwine('ss', function($) {
    $('.external-links-report__create-report').entwine({
      PollTimeout: null,
      ButtonIsLoading: false,

      onclick: function(e) {
        e.preventDefault();

        this.buttonLoading();
        this.start();
      },

      onmatch: function() {
        // poll the current job and update the front end status
        this.poll();
      },

      start: function() {
        // initiate a new job
        $('.external-links-report__report-progress')
          .empty()
          .text('Running report 0%');

        $.ajax({
          url: "admin/externallinks/start",
          async: true,
          timeout: 3000
        });

        this.poll();
      },

      /**
       * Get the "create report" button selector
       *
       * @return {Object}
       */
      getButton: function() {
        return $('.external-links-report__create-report');
      },

      /**
       * Sets the button into a loading state. See LeftAndMain.js.
       */
      buttonLoading: function() {
        if (this.getButtonIsLoading()) {
          return;
        }
        this.setButtonIsLoading(true);

        var $button = this.getButton();

        // set button to "submitting" state
        $button.addClass('btn--loading loading');

        if ($button.is('button')) {
          $button.append($(
            '<div class="btn__loading-icon">'+
              '<span class="btn__circle btn__circle--1" />'+
              '<span class="btn__circle btn__circle--2" />'+
              '<span class="btn__circle btn__circle--3" />'+
            '</div>'));

          $button.css($button.outerWidth() + 'px');
        }
      },

      /**
       * Reset the button back to its original state after loading. See LeftAndMain.js.
       */
      buttonReset: function() {
        this.setButtonIsLoading(false);

        var $button = this.getButton();

        $button.removeClass('btn--loading loading');
        $button.find('.btn__loading-icon').remove();
        $button.css('width', 'auto');
      },

      poll: function() {
        var self = this;
        this.buttonLoading();

        $.ajax({
          url: "admin/externallinks/getJobStatus",
          async: true,
          success: function(data) {
            // No report, so let user create one
            if (!data) {
              this.buttonReset();
              return;
            }

            // Parse data
            var completed = data.Completed ? data.Completed : 0;
            var total = data.Total ? data.Total : 0;

            // If complete status
            if (data.Status === 'Completed') {
              $('.external-links-report__report-progress')
                .text('Report finished ' + completed + '/' + total);

              self.buttonReset();
              return;
            }

            // If incomplete update status
            if (completed < total) {
              var percent = (completed / total) * 100;
              $('.external-links-report__report-progress')
                .text('Running report  ' + completed + '/' +  total + ' (' + percent.toFixed(2) + '%)');
            }

            // Ensure the regular poll method is run
            // kill any existing timeout
            if (self.getPollTimeout() !== null) {
              clearTimeout(self.getPollTimeout());
            }

            self.setPollTimeout(setTimeout(function() {
              $('.external-links-report__create-report').poll();
            }, 1000));
          },
          error: function(e) {
            if (typeof console !== 'undefined') {
              console.log(e);
            }
          }
        });
      }
    });
  });
}(jQuery));
