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

  hljs.highlightAll();
})();
