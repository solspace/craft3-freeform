!function(){function t(r){return t="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},t(r)}!function(){"use strict";$(".clear-logs").on({click:function(r){return r.stopPropagation(),r.preventDefault(),!!confirm("Are you sure you want to clear error logs?")&&($.ajax({url:$(this).attr("href"),data:(o={},e=Craft.csrfTokenName,n=Craft.csrfTokenValue,(e=function(r){var o=function(r){if("object"!=t(r)||!r)return r;var o=r[Symbol.toPrimitive];if(void 0!==o){var e=o.call(r,"string");if("object"!=t(e))return e;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(r)}(r);return"symbol"==t(o)?o:o+""}(e))in o?Object.defineProperty(o,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):o[e]=n,o),type:"post",dataType:"json",success:function(t){t.success&&window.location.reload(!0)}}),!1);var o,e,n}})}()}();