!function(){"use strict";var e={ready:"freeform-ready",reset:"freeform-on-reset",submit:"freeform-on-submit",removeMessages:"freeform-remove-messages",fieldRemoveMessages:"freeform-remove-field-messages",renderSuccess:"freeform-render-success",renderFieldErrors:"freeform-render-field-errors",renderFormErrors:"freeform-render-form-errors",ajaxBeforeSuccess:"freeform-before-ajax-success",ajaxSuccess:"freeform-ajax-success",ajaxError:"freeform-ajax-error",ajaxBeforeSubmit:"freeform-ajax-before-submit",ajaxAfterSubmit:"freeform-ajax-after-submit",afterFailedSubmit:"freeform-after-failed-submit",handleActions:"freeform-handle-actions"},n={applied:"freeform-rules-applied"},t=function(e,n,t,o){r("add",{elements:e,type:n,callback:t,options:o})},r=function(e,n){var t=n.type,r=n.elements,o=n.callback,a=n.options,i=Array.isArray(t)?t:[t],c=Array.isArray(r)?r:[r];Array.from(c).forEach((function(n){i.forEach((function(t){"add"===e?n.addEventListener(t,o,a):n.removeEventListener(t,o,a)}))}))},o="freeform-stripe",a={load:"".concat(o,"-load"),render:{appearance:"".concat(o,"-appearance")}},i="https://js.stripe.com/v3",c=/^https:\/\/js\.stripe\.com\/v3\/?(\?.*)?$/,u="loadStripe.setLoadParameters was called but an existing Stripe.js script already exists in the document; existing script parameters will be used",l=function(e){var n=e&&!e.advancedFraudSignals?"?advancedFraudSignals=false":"",t=document.createElement("script");t.src="".concat(i).concat(n);var r=document.head||document.body;if(!r)throw new Error("Expected document.body not to be null. Stripe.js requires a <body> element.");return r.appendChild(t),t},s=null,f=null,d=null,p=Promise.resolve().then((function(){return e=null,null!==s?s:(s=new Promise((function(n,t){if("undefined"!=typeof window&&"undefined"!=typeof document)if(window.Stripe&&e&&console.warn(u),window.Stripe)n(window.Stripe);else try{var r=function(){for(var e=document.querySelectorAll('script[src^="'.concat(i,'"]')),n=0;n<e.length;n++){var t=e[n];if(c.test(t.src))return t}return null}();if(r&&e)console.warn(u);else if(r){if(r&&null!==d&&null!==f){var o;r.removeEventListener("load",d),r.removeEventListener("error",f),null===(o=r.parentNode)||void 0===o||o.removeChild(r),r=l(e)}}else r=l(e);d=function(e,n){return function(){window.Stripe?e(window.Stripe):n(new Error("Stripe.js not available"))}}(n,t),f=function(e){return function(){e(new Error("Failed to load Stripe.js"))}}(t),r.addEventListener("load",d),r.addEventListener("error",f)}catch(e){return void t(e)}else n(null)}))).catch((function(e){return s=null,Promise.reject(e)}));var e})),m=!1;p.catch((function(e){m||console.warn(e)}));var v=function(){for(var e=arguments.length,n=new Array(e),t=0;t<e;t++)n[t]=arguments[t];m=!0;var r=Date.now();return p.then((function(e){return function(e,n,t){if(null===e)return null;var r=e.apply(void 0,n);return function(e,n){e&&e._registerWrapper&&e._registerWrapper({name:"stripe-js",version:"2.2.2",startTime:n})}(r,t),r}(e,n,r)}))},h=function(){return h=Object.assign||function(e){for(var n,t=1,r=arguments.length;t<r;t++)for(var o in n=arguments[t])Object.prototype.hasOwnProperty.call(n,o)&&(e[o]=n[o]);return e},h.apply(this,arguments)},y=new Map,b=function(e){var n=e.querySelector("[data-freeform-stripe-card][data-config]");if(n){var t=JSON.parse(n.dataset.config);return h(h({},t),{getStripeInstance:function(){return y.get(t.apiKey)},loadStripe:function(){return e=void 0,n=void 0,o=function(){var e;return function(e,n){var t,r,o,a,i={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]};return a={next:c(0),throw:c(1),return:c(2)},"function"==typeof Symbol&&(a[Symbol.iterator]=function(){return this}),a;function c(c){return function(u){return function(c){if(t)throw new TypeError("Generator is already executing.");for(;a&&(a=0,c[0]&&(i=0)),i;)try{if(t=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return i.label++,{value:c[1],done:!1};case 5:i.label++,r=c[1],c=[0];continue;case 7:c=i.ops.pop(),i.trys.pop();continue;default:if(!((o=(o=i.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){i=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){i.label=c[1];break}if(6===c[0]&&i.label<o[1]){i.label=o[1],o=c;break}if(o&&i.label<o[2]){i.label=o[2],i.ops.push(c);break}o[2]&&i.ops.pop(),i.trys.pop();continue}c=n.call(e,i)}catch(e){c=[6,e],r=0}finally{t=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}}(this,(function(n){switch(n.label){case 0:return y.has(t.apiKey)?[3,2]:[4,v(t.apiKey)];case 1:e=n.sent(),y.set(t.apiKey,e),n.label=2;case 2:return[2,y.get(t.apiKey)]}}))},new((r=void 0)||(r=Promise))((function(t,a){function i(e){try{u(o.next(e))}catch(e){a(e)}}function c(e){try{u(o.throw(e))}catch(e){a(e)}}function u(e){var n;e.done?t(e.value):(n=e.value,n instanceof r?n:new r((function(e){e(n)}))).then(i,c)}u((o=o.apply(e,n||[])).next())}));var e,n,r,o}})}},w=function(e,n,t,r){return new(t||(t=Promise))((function(o,a){function i(e){try{u(r.next(e))}catch(e){a(e)}}function c(e){try{u(r.throw(e))}catch(e){a(e)}}function u(e){var n;e.done?o(e.value):(n=e.value,n instanceof t?n:new t((function(e){e(n)}))).then(i,c)}u((r=r.apply(e,n||[])).next())}))},g=function(e,n){var t,r,o,a,i={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]};return a={next:c(0),throw:c(1),return:c(2)},"function"==typeof Symbol&&(a[Symbol.iterator]=function(){return this}),a;function c(c){return function(u){return function(c){if(t)throw new TypeError("Generator is already executing.");for(;a&&(a=0,c[0]&&(i=0)),i;)try{if(t=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return i.label++,{value:c[1],done:!1};case 5:i.label++,r=c[1],c=[0];continue;case 7:c=i.ops.pop(),i.trys.pop();continue;default:if(!((o=(o=i.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){i=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){i.label=c[1];break}if(6===c[0]&&i.label<o[1]){i.label=o[1],o=c;break}if(o&&i.label<o[2]){i.label=o[2],i.ops.push(c);break}o[2]&&i.ops.pop(),i.trys.pop();continue}c=n.call(e,i)}catch(e){c=[6,e],r=0}finally{t=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}},E=function(e,n,t,r,o){var a=new XMLHttpRequest;return a.open(e,n),a.setRequestHeader("Cache-Control","no-cache"),a.setRequestHeader("X-Requested-With","XMLHttpRequest"),a.setRequestHeader("HTTP_X_REQUESTED_WITH","XMLHttpRequest"),S(a,null==o?void 0:o.headers),a.onload=function(){var e=a.response;try{e=JSON.parse(a.response)}catch(e){}t({status:a.status,data:e})},a.onerror=function(){r(new Error("Network error"))},a.onabort=function(){r(new Error("Request aborted"))},o.onUploadProgress&&(a.upload.onprogress=function(e){o.onUploadProgress(e)}),o.cancelToken&&o.cancelToken._setCancelFn((function(){a.abort()})),a},S=function(e,n){n&&Object.entries(n).forEach((function(n){var t=n[0],r=n[1];e.setRequestHeader(t,String(r))}))},x=function(e,n){return new Promise((function(t,r){var o=E((null==n?void 0:n.method)||"GET",e,t,r,n),a=null==n?void 0:n.data;a instanceof FormData?o.send(a):(o.setRequestHeader("Content-Type","application/json"),o.send(JSON.stringify(a)))}))};x.get=function(e,n){return void 0===n&&(n={}),w(void 0,void 0,void 0,(function(){return g(this,(function(t){return[2,new Promise((function(t,r){var o=E("GET",e,t,r,n);o.open("GET",e),o.send()}))]}))}))},x.post=function(e,n,t){return void 0===t&&(t={}),w(void 0,void 0,void 0,(function(){return g(this,(function(r){return[2,new Promise((function(r,o){var a=E("POST",e,r,o,t);n instanceof FormData?a.send(n):(a.setRequestHeader("Content-Type","application/json"),a.send(JSON.stringify(n)))}))]}))}))},function(){function e(){}e.prototype.cancel=function(){this.cancelFn&&(this.cancelFn(),this.cancelFn=null)},e.prototype._setCancelFn=function(e){this.cancelFn=e}}();var k=function(e,n,t,r){return new(t||(t=Promise))((function(o,a){function i(e){try{u(r.next(e))}catch(e){a(e)}}function c(e){try{u(r.throw(e))}catch(e){a(e)}}function u(e){var n;e.done?o(e.value):(n=e.value,n instanceof t?n:new t((function(e){e(n)}))).then(i,c)}u((r=r.apply(e,n||[])).next())}))},O=function(e,n){var t,r,o,a,i={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]};return a={next:c(0),throw:c(1),return:c(2)},"function"==typeof Symbol&&(a[Symbol.iterator]=function(){return this}),a;function c(c){return function(u){return function(c){if(t)throw new TypeError("Generator is already executing.");for(;a&&(a=0,c[0]&&(i=0)),i;)try{if(t=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return i.label++,{value:c[1],done:!1};case 5:i.label++,r=c[1],c=[0];continue;case 7:c=i.ops.pop(),i.trys.pop();continue;default:if(!((o=(o=i.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){i=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){i.label=c[1];break}if(6===c[0]&&i.label<o[1]){i.label=o[1],o=c;break}if(o&&i.label<o[2]){i.label=o[2],i.ops.push(c);break}o[2]&&i.ops.pop(),i.trys.pop();continue}c=n.call(e,i)}catch(e){c=[6,e],r=0}finally{t=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}},T=function(e){var n=new FormData(e);return n.set("method","post"),n.delete("action"),n},j=function(e,n){return k(void 0,void 0,void 0,(function(){var t;return O(this,(function(r){return t=T(n),[2,x.post("/freeform/payments/stripe/payment-intents",t,{headers:{"FF-STRIPE-INTEGRATION":e}})]}))}))},P=function(e,n,t){return k(void 0,void 0,void 0,(function(){var r;return O(this,(function(o){switch(o.label){case 0:return r=T(n),[4,x.post("/freeform/payments/stripe/payment-intents/".concat(t,"/amount"),r,{headers:{"FF-STRIPE-INTEGRATION":e}})];case 1:return[2,o.sent().data]}}))}))},F=function(e){var n=e.integration,t=e.form,r=e.paymentIntentId;return k(void 0,void 0,void 0,(function(){var e;return O(this,(function(o){switch(o.label){case 0:return e=T(t),[4,x.post("/freeform/payments/stripe/payment-intents/".concat(r,"/customers"),e,{headers:{"FF-STRIPE-INTEGRATION":n}})];case 1:return[2,o.sent().status]}}))}))},L=function(e){if(null!==e.getAttribute("data-hidden"))return!0;for(var n=e.parentElement;n;){if(null!==n.getAttribute("data-hidden"))return!0;n=n.parentElement}return!1},I=function(e,n){var t=e.querySelectorAll('[data-field-type="stripe"]');return Array.from(t).filter((function(e){var t=L(e);return!(!t||!n)||!t&&!n}))},q=function(e){return I(e,!1)},A=function(){return A=Object.assign||function(e){for(var n,t=1,r=arguments.length;t<r;t++)for(var o in n=arguments[t])Object.prototype.hasOwnProperty.call(n,o)&&(e[o]=n[o]);return e},A.apply(this,arguments)},R=[],M=function(e){return function(n){return t=void 0,r=void 0,i=function(){var t,r,o,i,c,u,l,s,f,d,p,m;return function(e,n){var t,r,o,a,i={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]};return a={next:c(0),throw:c(1),return:c(2)},"function"==typeof Symbol&&(a[Symbol.iterator]=function(){return this}),a;function c(c){return function(u){return function(c){if(t)throw new TypeError("Generator is already executing.");for(;a&&(a=0,c[0]&&(i=0)),i;)try{if(t=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return i.label++,{value:c[1],done:!1};case 5:i.label++,r=c[1],c=[0];continue;case 7:c=i.ops.pop(),i.trys.pop();continue;default:if(!((o=(o=i.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){i=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){i.label=c[1];break}if(6===c[0]&&i.label<o[1]){i.label=o[1],o=c;break}if(o&&i.label<o[2]){i.label=o[2],i.ops.push(c);break}o[2]&&i.ops.pop(),i.trys.pop();continue}c=n.call(e,i)}catch(e){c=[6,e],r=0}finally{t=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}}(this,(function(v){switch(v.label){case 0:return L(n)?[2]:(t=b(n),r=t.fieldMapping,o=t.theme,i=t.layout,c=t.floatingLabels,u=t.integration,l=t.amountFields,s=t.loadStripe,f=e.elementMap,d=e.form,[4,s()]);case 1:return p=v.sent(),m=n.querySelector("[data-freeform-stripe-card]"),f.has(m)||(d.freeform.disableSubmit("stripe.init"),f.set(m,{empty:!0,elements:null,paymentIntent:null}),m.innerHTML="Loading...",j(u,d).then((function(e){var n=e.data,t=n.id,s=n.secret;m.parentElement.querySelector("[data-freeform-stripe-intent]").value=t;var v=function(e){var n=e.theme,t=e.layout,r=void 0===t?"tabs":t,o=e.floatingLabels;return{elementOptions:{appearance:{theme:void 0===n?"stripe":n,labels:void 0!==o&&o?"floating":"above",variables:{}}},paymentOptions:{layout:{type:"tabs"===r?"tabs":"accordion",defaultCollapsed:!1,radios:"accordion-radios"===r,spacedAccordionItems:"accordion-radios"!==r}}}}({theme:o,layout:i,floatingLabels:c}),h=v.elementOptions,y=v.paymentOptions,b=function(e,n,t){var r=n||{},o=r.bubbles,a=void 0!==o&&o,i=r.cancelable,c=void 0===i||i,u=function(e,n){var t={};for(var r in e)Object.prototype.hasOwnProperty.call(e,r)&&n.indexOf(r)<0&&(t[r]=e[r]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var o=0;for(r=Object.getOwnPropertySymbols(e);o<r.length;o++)n.indexOf(r[o])<0&&Object.prototype.propertyIsEnumerable.call(e,r[o])&&(t[r[o]]=e[r[o]])}return t}(r,["bubbles","cancelable"]),l=function(e,n,t){return void 0===n&&(n=!0),void 0===t&&(t=!0),new Event(e,{bubbles:n,cancelable:t})}(e,a,c);return Object.assign(l,u),t&&(t instanceof HTMLElement?t.dispatchEvent(l):Array.from(t).forEach((function(e){return e.dispatchEvent(l)}))),l}(a.render.appearance,{bubbles:!0,elementOptions:h,paymentOptions:y},[m]),w=p.elements(A(A({},b.elementOptions),{clientSecret:s})),g=w.create("payment",b.paymentOptions);g.mount(m),g.on("change",(function(e){f.get(m).empty=e.empty&&!e.complete})),l.forEach((function(e){var n;null===(n=d.elements.namedItem(e))||void 0===n||n.addEventListener("change",(function(){R.push(e),d.freeform.disableSubmit("stripe.working"),d.freeform.disableForm();var n=f.get(m).paymentIntent.id;P(u,d,n).then((function(e){var n=e.id,t=e.client_secret;t?(g.unmount(),w=p.elements({clientSecret:t}),(g=w.create("payment",b.paymentOptions)).mount(m),g.on("change",(function(e){f.get(m).empty=e.empty&&!e.complete})),f.set(m,{empty:!0,elements:w,paymentIntent:{id:n,secret:t}})):w.fetchUpdates()})).catch((function(n){var t;d.freeform._renderFieldErrors(((t={})[e]=[n.response.data.message],t))})).finally((function(){R.pop(),R.length||(d.freeform.enableSubmit("stripe.working"),d.freeform.enableForm())}))}))}));var E=r.some((function(e){return void 0===e.target})),S=function(e){return function(n){var r=n.target.value;F({integration:u,form:d,paymentIntentId:t,key:e,value:r})}};E?d.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach((function(e){e.addEventListener("change",S(e.name))})):r.forEach((function(e){var n,t=e.source,r=e.target;null===(n=d.elements.namedItem(r))||void 0===n||n.addEventListener("change",S(t))})),f.set(m,{empty:!0,elements:w,paymentIntent:{id:t,secret:s}})})).catch((function(t){m.innerHTML="Could not load payment element.";var r={};l.forEach((function(e){r[e]=[t.response.data.message]})),d.freeform._renderFieldErrors(r);var o=function(){M(e)(n),l.forEach((function(e){var n;null===(n=d[e])||void 0===n||n.removeEventListener("change",o)}))};l.forEach((function(e){var n;null===(n=d[e])||void 0===n||n.addEventListener("change",o)}))})).finally((function(){d.freeform.enableSubmit("stripe.init")}))),[2]}}))},new((o=void 0)||(o=Promise))((function(e,n){function a(e){try{u(i.next(e))}catch(e){n(e)}}function c(e){try{u(i.throw(e))}catch(e){n(e)}}function u(n){var t;n.done?e(n.value):(t=n.value,t instanceof o?t:new o((function(e){e(t)}))).then(a,c)}u((i=i.apply(t,r||[])).next())}));var t,r,o,i}},N=function(e,n,t,r){return new(t||(t=Promise))((function(o,a){function i(e){try{u(r.next(e))}catch(e){a(e)}}function c(e){try{u(r.throw(e))}catch(e){a(e)}}function u(e){var n;e.done?o(e.value):(n=e.value,n instanceof t?n:new t((function(e){e(n)}))).then(i,c)}u((r=r.apply(e,n||[])).next())}))},H=function(e,n){var t,r,o,a,i={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]};return a={next:c(0),throw:c(1),return:c(2)},"function"==typeof Symbol&&(a[Symbol.iterator]=function(){return this}),a;function c(c){return function(u){return function(c){if(t)throw new TypeError("Generator is already executing.");for(;a&&(a=0,c[0]&&(i=0)),i;)try{if(t=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return i.label++,{value:c[1],done:!1};case 5:i.label++,r=c[1],c=[0];continue;case 7:c=i.ops.pop(),i.trys.pop();continue;default:if(!((o=(o=i.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){i=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){i.label=c[1];break}if(6===c[0]&&i.label<o[1]){i.label=o[1],o=c;break}if(o&&i.label<o[2]){i.label=o[2],i.ops.push(c);break}o[2]&&i.ops.pop(),i.trys.pop();continue}c=n.call(e,i)}catch(e){c=[6,e],r=0}finally{t=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}},_=new WeakMap,C=function(r){return o=void 0,a=void 0,c=function(){var o,a,i;return function(e,n){var t,r,o,a,i={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]};return a={next:c(0),throw:c(1),return:c(2)},"function"==typeof Symbol&&(a[Symbol.iterator]=function(){return this}),a;function c(c){return function(u){return function(c){if(t)throw new TypeError("Generator is already executing.");for(;a&&(a=0,c[0]&&(i=0)),i;)try{if(t=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return i.label++,{value:c[1],done:!1};case 5:i.label++,r=c[1],c=[0];continue;case 7:c=i.ops.pop(),i.trys.pop();continue;default:if(!((o=(o=i.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){i=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){i.label=c[1];break}if(6===c[0]&&i.label<o[1]){i.label=o[1],o=c;break}if(o&&i.label<o[2]){i.label=o[2],i.ops.push(c);break}o[2]&&i.ops.pop(),i.trys.pop();continue}c=n.call(e,i)}catch(e){c=[6,e],r=0}finally{t=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}}(this,(function(c){return _.has(r)||(_.set(r,!0),o=new WeakMap,i=function(e){return function(){return N(void 0,void 0,void 0,(function(){var t;return H(this,(function(r){return t=e.form,q(t).forEach(M(e)),function(e){return I(e,!0)}(t).forEach((function(t){t.addEventListener(n.applied,(function(){M(e)(t)}))})),[2]}))}))}}(a={elementMap:o,form:r}),t(r,[e.ready,e.reset,e.ajaxAfterSubmit],i),t(r,[e.submit],function(e){return function(n){return N(void 0,void 0,void 0,(function(){return H(this,(function(t){return n.addCallback((function(){return N(void 0,void 0,void 0,(function(){var t,r,o,a,i,c,u,l,s,f,d,p,m,v,h,y,w,g,E;return H(this,(function(S){switch(S.label){case 0:if(n.isBackButtonPressed)return[2];t=e.elementMap,r=q(e.form),o=0,a=r,S.label=1;case 1:return o<a.length?(i=a[o],c=b(i),u=c.getStripeInstance,l=c.required,s=c.integration,f=i.querySelector("[data-freeform-stripe-card]"),d=t.get(f),p=d.empty,m=d.elements,v=d.paymentIntent,h=v.id,y=v.secret,p&&!l?[2]:[4,n.freeform.quickSave(y,h)]):[3,5];case 2:return!1===(w=S.sent())?[2,!0]:void 0===w?[2,!1]:((g=new URL("/freeform/payments/stripe/callback",window.location.origin)).searchParams.append("integration",s),g.searchParams.append("token",w),[4,u().confirmPayment({elements:m,confirmParams:{return_url:g.toString()}})]);case 3:return(E=S.sent().error)&&(n.freeform._renderFormErrors([E.message]),n.freeform._scrollToForm()),[2,!1];case 4:return o++,[3,1];case 5:return[2]}}))}))}),100),[2]}))}))}}(a))),[2]}))},new((i=void 0)||(i=Promise))((function(e,n){function t(e){try{u(c.next(e))}catch(e){n(e)}}function r(e){try{u(c.throw(e))}catch(e){n(e)}}function u(n){var o;n.done?e(n.value):(o=n.value,o instanceof i?o:new i((function(e){e(o)}))).then(t,r)}u((c=c.apply(o,a||[])).next())}));var o,a,i,c};document.addEventListener(a.load,(function(){document.querySelectorAll("form[data-freeform]").forEach((function(e){C(e)}))})),window.onload=function(){document.dispatchEvent(new CustomEvent(a.load))};var G=function(e){var n;"FORM"!==e.nodeName&&void 0===(null===(n=e.dataset)||void 0===n?void 0:n.freeform)||C(e),null==e||e.childNodes.forEach(G)};new MutationObserver((function(e){e.forEach((function(e){"childList"===e.type&&e.addedNodes.forEach((function(e){G(e)}))}))})).observe(document.body,{childList:!0,subtree:!0})}();