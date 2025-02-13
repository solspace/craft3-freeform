(function () {
  'use strict';

  $('.clear-logs').on({
    click: function (event) {
      event.stopPropagation();
      event.preventDefault();

      const msg = 'Are you sure you want to clear this log?';
      if (!confirm(msg)) {
        return false;
      }

      $.ajax({
        url: $(this).attr('href'),
        data: {
          [Craft.csrfTokenName]: Craft.csrfTokenValue,
        },
        type: 'post',
        dataType: 'json',
        success: (json) => {
          if (json.success) {
            window.location.reload(true);
          }
        },
      });

      return false;
    },
  });

  $('.paginator').each(function () {
    const paginator = $(this);
    const baseUrl = paginator.data('baseUrl');
    const currentPage = paginator.data('page');
    const totalPages = paginator.data('total');

    $('a', paginator).each(function () {
      const isPrev = $(this).attr('data-prev') !== undefined;
      const isMax = $(this).attr('data-max') !== undefined;

      if (isPrev && currentPage === 1) {
        $(this)
          .attr('disabled', true)
          .on('click', (event) => {
            event.preventDefault();
            console.log('No more prev');
          });
      }

      if (!isPrev && currentPage === totalPages) {
        $(this)
          .attr('disabled', true)
          .on('click', (event) => {
            event.preventDefault();
            console.log('No more next');
          });
      }

      let page = currentPage;
      if (isPrev) {
        page = isMax ? 1 : currentPage - 1;
      } else {
        page = isMax ? totalPages : currentPage + 1;
      }

      const url = Craft.getCpUrl(baseUrl, { page });
      $(this).attr('href', url);
    });
  });

  hljs.highlightAll();
})();
