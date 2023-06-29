/* global jQuery */
// eslint-disable-next-line func-names
(function ($) {
  // eslint-disable-next-line no-shadow
  $.entwine('ss', ($) => {
    $('.external-links-report__create-report').entwine({
      PollTimeout: null,
      ButtonIsLoading: false,
      ReloadContent: false,

      onclick(e) {
        e.preventDefault();

        this.buttonLoading();
        this.start();
      },

      onmatch() {
        // poll the current job and update the front end status
        this.poll();
      },

      start() {
        const self = this;
        // initiate a new job
        $('.external-links-report__report-progress')
          .empty()
          .text('Running report 0%');

        $.ajax({
          url: 'admin/externallinks/start',
          async: true,
          timeout: 3000,
          success() {
            self.setReloadContent(true);
            self.poll();
          },
          error(e) {
            if (typeof console !== 'undefined') {
              // eslint-disable-next-line no-console
              console.error(e);
            }
          },
        });
      },

      /**
       * Get the "create report" button selector
       *
       * @return {Object}
       */
      getButton() {
        return $('.external-links-report__create-report');
      },

      /**
       * Sets the button into a loading state. See LeftAndMain.js.
       */
      buttonLoading() {
        if (this.getButtonIsLoading()) {
          return;
        }
        this.setButtonIsLoading(true);

        const $button = this.getButton();

        // set button to "submitting" state
        $button.addClass('btn--loading loading');
        $button.attr('disabled', true);

        if ($button.is('button')) {
          $button.append($(
            '<div class="btn__loading-icon">' +
              '<span class="btn__circle btn__circle--1" />' +
              '<span class="btn__circle btn__circle--2" />' +
              '<span class="btn__circle btn__circle--3" />' +
            '</div>'));

          $button.css(`${$button.outerWidth()}px`);
        }
      },

      /**
       * Reset the button back to its original state after loading. See LeftAndMain.js.
       */
      buttonReset() {
        this.setButtonIsLoading(false);

        const $button = this.getButton();

        $button.removeClass('btn--loading loading');
        $button.attr('disabled', false);
        $button.find('.btn__loading-icon').remove();
        $button.css('width', 'auto');
      },

      poll() {
        const self = this;
        this.buttonLoading();

        $.ajax({
          url: 'admin/externallinks/getJobStatus',
          async: true,
          success(data) {
            // No report, so let user create one
            if (!data) {
              self.buttonReset();
              return;
            }

            // Parse data
            const completed = data.Completed ? data.Completed : 0;
            const total = data.Total ? data.Total : 0;

            // If complete status
            if (data.Status === 'Completed') {
              if (self.getReloadContent()) {
                $('.cms-container').loadPanel(document.location.href, null, {}, true, false);
                self.setReloadContent(false);
              }
              $('.external-links-report__report-progress')
                .text(`Report finished ${completed}/${total}`);

              self.buttonReset();
              return;
            }

            // If incomplete update status
            if (completed < total) {
              const percent = (completed / total) * 100;
              $('.external-links-report__report-progress')
                .text(`Running report  ${completed}/${total} (${percent.toFixed(2)}%)`);
            }

            // Ensure the regular poll method is run
            // kill any existing timeout
            if (self.getPollTimeout() !== null) {
              clearTimeout(self.getPollTimeout());
            }

            self.setPollTimeout(setTimeout(() => {
              $('.external-links-report__create-report').poll();
            }, 1000));
          },
          error(e) {
            if (typeof console !== 'undefined') {
              // eslint-disable-next-line no-console
              console.error(e);
            }
            self.buttonReset();
          },
        });
      },
    });
  });
}(jQuery));
