!function(){function t(o){return t="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},t(o)}!function(){"use strict";$(".clear-logs").on({click:function(o){return o.stopPropagation(),o.preventDefault(),!!confirm("Are you sure you want to clear this log?")&&($.ajax({url:$(this).attr("href"),data:(r={},e=Craft.csrfTokenName,n=Craft.csrfTokenValue,(e=function(o){var r=function(o){if("object"!=t(o)||!o)return o;var r=o[Symbol.toPrimitive];if(void 0!==r){var e=r.call(o,"string");if("object"!=t(e))return e;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(o)}(o);return"symbol"==t(r)?r:r+""}(e))in r?Object.defineProperty(r,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):r[e]=n,r),type:"post",dataType:"json",success:function(t){t.success&&window.location.reload(!0)}}),!1);var r,e,n}})}()}();