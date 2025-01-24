!function(){"use strict";var e,t={ready:"freeform-ready",reset:"freeform-on-reset",submit:"freeform-on-submit",removeMessages:"freeform-remove-messages",fieldRemoveMessages:"freeform-remove-field-messages",renderSuccess:"freeform-render-success",renderFieldErrors:"freeform-render-field-errors",renderFormErrors:"freeform-render-form-errors",ajaxBeforeSuccess:"freeform-before-ajax-success",ajaxSuccess:"freeform-ajax-success",ajaxError:"freeform-ajax-error",ajaxBeforeSubmit:"freeform-ajax-before-submit",ajaxAfterSubmit:"freeform-ajax-after-submit",afterFailedSubmit:"freeform-after-failed-submit",handleActions:"freeform-handle-actions"},n={applied:"freeform-rules-applied"},r=function(e,t,n,r){o("add",{elements:e,type:t,callback:n,options:r})},o=function(e,t){var n=t.type,r=t.elements,o=t.callback,a=t.options,i=Array.isArray(n)?n:[n],c=Array.isArray(r)?r:[r];Array.from(c).forEach((function(t){i.forEach((function(n){"add"===e?t.addEventListener(n,o,a):t.removeEventListener(n,o,a)}))}))},a="freeform-stripe",i={load:"".concat(a,"-load"),render:{appearance:"".concat(a,"-appearance")}},c="https://js.stripe.com/v3",u=/^https:\/\/js\.stripe\.com\/v3\/?(\?.*)?$/,l="loadStripe.setLoadParameters was called but an existing Stripe.js script already exists in the document; existing script parameters will be used",s=function(e){var t=e&&!e.advancedFraudSignals?"?advancedFraudSignals=false":"",n=document.createElement("script");n.src="".concat(c).concat(t);var r=document.head||document.body;if(!r)throw new Error("Expected document.body not to be null. Stripe.js requires a <body> element.");return r.appendChild(n),n},f=null,p=null,d=null,m=!1,v=function(){return e||(e=(t=null,null!==f?f:(f=new Promise((function(e,n){if("undefined"!=typeof window&&"undefined"!=typeof document)if(window.Stripe&&t&&console.warn(l),window.Stripe)e(window.Stripe);else try{var r=function(){for(var e=document.querySelectorAll('script[src^="'.concat(c,'"]')),t=0;t<e.length;t++){var n=e[t];if(u.test(n.src))return n}return null}();if(r&&t)console.warn(l);else if(r){if(r&&null!==d&&null!==p){var o;r.removeEventListener("load",d),r.removeEventListener("error",p),null===(o=r.parentNode)||void 0===o||o.removeChild(r),r=s(t)}}else r=s(t);d=function(e,t){return function(){window.Stripe?e(window.Stripe):t(new Error("Stripe.js not available"))}}(e,n),p=function(e){return function(){e(new Error("Failed to load Stripe.js"))}}(n),r.addEventListener("load",d),r.addEventListener("error",p)}catch(e){return void n(e)}else e(null)}))).catch((function(e){return f=null,Promise.reject(e)}))).catch((function(t){return e=null,Promise.reject(t)})));var t};Promise.resolve().then((function(){return v()})).catch((function(e){m||console.warn(e)}));var y,h=function(){for(var e=arguments.length,t=new Array(e),n=0;n<e;n++)t[n]=arguments[n];m=!0;var r=Date.now();return v().then((function(e){return function(e,t,n){if(null===e)return null;var r=e.apply(void 0,t);return function(e,t){e&&e._registerWrapper&&e._registerWrapper({name:"stripe-js",version:"2.4.0",startTime:t})}(r,n),r}(e,t,r)}))},b=function(){return b=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var o in t=arguments[n])Object.prototype.hasOwnProperty.call(t,o)&&(e[o]=t[o]);return e},b.apply(this,arguments)},w=new Map,g=function(e){var t=e.querySelector("[data-freeform-stripe-card][data-config]");if(t){var n=JSON.parse(t.dataset.config);return b(b({},n),{getStripeInstance:function(){return w.get(n.apiKey)},loadStripe:function(){return e=void 0,t=void 0,o=function(){var e;return function(e,t){var n,r,o,a={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]},i=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return i.next=c(0),i.throw=c(1),i.return=c(2),"function"==typeof Symbol&&(i[Symbol.iterator]=function(){return this}),i;function c(c){return function(u){return function(c){if(n)throw new TypeError("Generator is already executing.");for(;i&&(i=0,c[0]&&(a=0)),a;)try{if(n=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return a.label++,{value:c[1],done:!1};case 5:a.label++,r=c[1],c=[0];continue;case 7:c=a.ops.pop(),a.trys.pop();continue;default:if(!((o=(o=a.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){a=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){a.label=c[1];break}if(6===c[0]&&a.label<o[1]){a.label=o[1],o=c;break}if(o&&a.label<o[2]){a.label=o[2],a.ops.push(c);break}o[2]&&a.ops.pop(),a.trys.pop();continue}c=t.call(e,a)}catch(e){c=[6,e],r=0}finally{n=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}}(this,(function(t){switch(t.label){case 0:return w.has(n.apiKey)?[3,2]:[4,h(n.apiKey)];case 1:e=t.sent(),w.set(n.apiKey,e),t.label=2;case 2:return[2,w.get(n.apiKey)]}}))},new((r=void 0)||(r=Promise))((function(n,a){function i(e){try{u(o.next(e))}catch(e){a(e)}}function c(e){try{u(o.throw(e))}catch(e){a(e)}}function u(e){var t;e.done?n(e.value):(t=e.value,t instanceof r?t:new r((function(e){e(t)}))).then(i,c)}u((o=o.apply(e,t||[])).next())}));var e,t,r,o}})}},E=(y=function(e,t){return y=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(e,t){e.__proto__=t}||function(e,t){for(var n in t)Object.prototype.hasOwnProperty.call(t,n)&&(e[n]=t[n])},y(e,t)},function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Class extends value "+String(t)+" is not a constructor or null");function n(){this.constructor=e}y(e,t),e.prototype=null===t?Object.create(t):(n.prototype=t.prototype,new n)}),S=function(){return S=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var o in t=arguments[n])Object.prototype.hasOwnProperty.call(t,o)&&(e[o]=t[o]);return e},S.apply(this,arguments)},O=function(e,t,n,r){return new(n||(n=Promise))((function(o,a){function i(e){try{u(r.next(e))}catch(e){a(e)}}function c(e){try{u(r.throw(e))}catch(e){a(e)}}function u(e){var t;e.done?o(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(i,c)}u((r=r.apply(e,t||[])).next())}))},j=function(e,t){var n,r,o,a={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]},i=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return i.next=c(0),i.throw=c(1),i.return=c(2),"function"==typeof Symbol&&(i[Symbol.iterator]=function(){return this}),i;function c(c){return function(u){return function(c){if(n)throw new TypeError("Generator is already executing.");for(;i&&(i=0,c[0]&&(a=0)),a;)try{if(n=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return a.label++,{value:c[1],done:!1};case 5:a.label++,r=c[1],c=[0];continue;case 7:c=a.ops.pop(),a.trys.pop();continue;default:if(!((o=(o=a.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){a=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){a.label=c[1];break}if(6===c[0]&&a.label<o[1]){a.label=o[1],o=c;break}if(o&&a.label<o[2]){a.label=o[2],a.ops.push(c);break}o[2]&&a.ops.pop(),a.trys.pop();continue}c=t.call(e,a)}catch(e){c=[6,e],r=0}finally{n=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}},x=function(e,t,n){if(n||2===arguments.length)for(var r,o=0,a=t.length;o<a;o++)!r&&o in t||(r||(r=Array.prototype.slice.call(t,0,o)),r[o]=t[o]);return e.concat(r||Array.prototype.slice.call(t))},k=function(e,t,n,r,o){var a=new XMLHttpRequest;return a.open(e,t),a.setRequestHeader("Cache-Control","no-cache"),a.setRequestHeader("X-Requested-With","XMLHttpRequest"),a.setRequestHeader("HTTP_X_REQUESTED_WITH","XMLHttpRequest"),I(a,null==o?void 0:o.headers),a.onload=function(){var e=a.response;try{e=JSON.parse(a.response)}catch(e){}var t=a.status;t<200||t>=300?r(new T("Request failed with status ".concat(a.statusText),a,e)):n({status:a.status,data:e})},a.onerror=function(){r(new Error("Network error"))},a.onabort=function(){r(new Error("Request aborted"))},o.onUploadProgress&&(a.upload.onprogress=function(e){o.onUploadProgress(e)}),o.cancelToken&&o.cancelToken._setCancelFn((function(){a.abort()})),a},I=function(e,t){t&&Object.entries(t).forEach((function(t){var n=t[0],r=t[1];e.setRequestHeader(n,String(r))}))},P=function(e,t){return new Promise((function(n,r){var o=k((null==t?void 0:t.method)||"GET",e,n,r,t),a=null==t?void 0:t.data;a instanceof FormData?o.send(a):(o.setRequestHeader("Content-Type","application/json"),o.send(JSON.stringify(a)))}))};P.get=function(e){for(var t=[],n=1;n<arguments.length;n++)t[n-1]=arguments[n];return O(void 0,x([e],t,!0),void 0,(function(e,t){return void 0===t&&(t={}),j(this,(function(n){return[2,new Promise((function(n,r){var o=k("GET",e,n,r,t);o.open("GET",e),o.send()}))]}))}))},P.post=function(e,t){for(var n=[],r=2;r<arguments.length;r++)n[r-2]=arguments[r];return O(void 0,x([e,t],n,!0),void 0,(function(e,t,n){return void 0===n&&(n={}),j(this,(function(r){return[2,new Promise((function(r,o){var a=k("POST",e,r,o,n);t instanceof FormData?a.send(t):(a.setRequestHeader("Content-Type","application/json"),a.send(JSON.stringify(t)))}))]}))}))},function(){function e(){}e.prototype.cancel=function(){this.cancelFn&&(this.cancelFn(),this.cancelFn=null)},e.prototype._setCancelFn=function(e){this.cancelFn=e}}();var T=function(e){function t(n,r,o){var a=e.call(this,n)||this;return a.response=S(S({},r),{data:o}),a.status=r.status,Object.setPrototypeOf(a,t.prototype),a}return E(t,e),t}(Error),F=function(e,t,n,r){return new(n||(n=Promise))((function(o,a){function i(e){try{u(r.next(e))}catch(e){a(e)}}function c(e){try{u(r.throw(e))}catch(e){a(e)}}function u(e){var t;e.done?o(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(i,c)}u((r=r.apply(e,t||[])).next())}))},L=function(e,t){var n,r,o,a={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]},i=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return i.next=c(0),i.throw=c(1),i.return=c(2),"function"==typeof Symbol&&(i[Symbol.iterator]=function(){return this}),i;function c(c){return function(u){return function(c){if(n)throw new TypeError("Generator is already executing.");for(;i&&(i=0,c[0]&&(a=0)),a;)try{if(n=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return a.label++,{value:c[1],done:!1};case 5:a.label++,r=c[1],c=[0];continue;case 7:c=a.ops.pop(),a.trys.pop();continue;default:if(!((o=(o=a.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){a=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){a.label=c[1];break}if(6===c[0]&&a.label<o[1]){a.label=o[1],o=c;break}if(o&&a.label<o[2]){a.label=o[2],a.ops.push(c);break}o[2]&&a.ops.pop(),a.trys.pop();continue}c=t.call(e,a)}catch(e){c=[6,e],r=0}finally{n=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}},A=function(e){var t=new FormData(e);return t.set("method","post"),t.delete("action"),t},q=function(e,t){return F(void 0,void 0,void 0,(function(){var n;return L(this,(function(r){return n=A(t),[2,P.post("/freeform/payments/stripe/payment-intents",n,{headers:{"FF-STRIPE-INTEGRATION":e}})]}))}))},R=function(e,t,n){return F(void 0,void 0,void 0,(function(){var r;return L(this,(function(o){switch(o.label){case 0:return r=A(t),[4,P.post("/freeform/payments/stripe/payment-intents/".concat(n,"/amount"),r,{headers:{"FF-STRIPE-INTEGRATION":e}})];case 1:return[2,o.sent().data]}}))}))},_=function(e){return F(void 0,[e],void 0,(function(e){var t,n=e.integration,r=e.form,o=e.paymentIntentId;return L(this,(function(e){switch(e.label){case 0:return t=A(r),[4,P.post("/freeform/payments/stripe/payment-intents/".concat(o,"/customers"),t,{headers:{"FF-STRIPE-INTEGRATION":n}})];case 1:return[2,e.sent().status]}}))}))},M=function(e){if(null!==e.getAttribute("data-hidden"))return!0;for(var t=e.parentElement;t;){if(null!==t.getAttribute("data-hidden"))return!0;t=t.parentElement}return!1},N=function(e,t){var n=e.querySelectorAll('[data-field-type="stripe"]');return Array.from(n).filter((function(e){var n=M(e);return!(!n||!t)||!n&&!t}))},H=function(e){return N(e,!1)},C=function(){return C=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var o in t=arguments[n])Object.prototype.hasOwnProperty.call(t,o)&&(e[o]=t[o]);return e},C.apply(this,arguments)},G=[],W=function(e){return function(t){return n=void 0,r=void 0,a=function(){var n,r,o,a,c,u,l,s,f,p,d,m;return function(e,t){var n,r,o,a={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]},i=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return i.next=c(0),i.throw=c(1),i.return=c(2),"function"==typeof Symbol&&(i[Symbol.iterator]=function(){return this}),i;function c(c){return function(u){return function(c){if(n)throw new TypeError("Generator is already executing.");for(;i&&(i=0,c[0]&&(a=0)),a;)try{if(n=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return a.label++,{value:c[1],done:!1};case 5:a.label++,r=c[1],c=[0];continue;case 7:c=a.ops.pop(),a.trys.pop();continue;default:if(!((o=(o=a.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){a=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){a.label=c[1];break}if(6===c[0]&&a.label<o[1]){a.label=o[1],o=c;break}if(o&&a.label<o[2]){a.label=o[2],a.ops.push(c);break}o[2]&&a.ops.pop(),a.trys.pop();continue}c=t.call(e,a)}catch(e){c=[6,e],r=0}finally{n=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}}(this,(function(v){switch(v.label){case 0:return M(t)?[2]:(n=g(t),r=n.fieldMapping,o=n.theme,a=n.layout,c=n.floatingLabels,u=n.integration,l=n.amountFields,s=n.loadStripe,f=e.elementMap,p=e.form,[4,s()]);case 1:return d=v.sent(),m=t.querySelector("[data-freeform-stripe-card]"),f.has(m)||(p.freeform.disableSubmit("stripe.init"),f.set(m,{empty:!0,elements:null,paymentIntent:null}),m.innerHTML="Loading...",q(u,p).then((function(e){var t=e.data,n=t.id,s=t.secret;m.parentElement.querySelector("[data-freeform-stripe-intent]").value=n;var v=function(e){var t=e.theme,n=e.layout,r=void 0===n?"tabs":n,o=e.floatingLabels;return{elementOptions:{appearance:{theme:void 0===t?"stripe":t,labels:void 0!==o&&o?"floating":"above",variables:{}}},paymentOptions:{layout:{type:"tabs"===r?"tabs":"accordion",defaultCollapsed:!1,radios:"accordion-radios"===r,spacedAccordionItems:"accordion-radios"!==r}}}}({theme:o,layout:a,floatingLabels:c}),y=v.elementOptions,h=v.paymentOptions,b=function(e,t,n){var r=t||{},o=r.bubbles,a=void 0!==o&&o,i=r.cancelable,c=void 0===i||i,u=function(e,t){var n={};for(var r in e)Object.prototype.hasOwnProperty.call(e,r)&&t.indexOf(r)<0&&(n[r]=e[r]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var o=0;for(r=Object.getOwnPropertySymbols(e);o<r.length;o++)t.indexOf(r[o])<0&&Object.prototype.propertyIsEnumerable.call(e,r[o])&&(n[r[o]]=e[r[o]])}return n}(r,["bubbles","cancelable"]),l=function(e,t,n){return void 0===t&&(t=!0),void 0===n&&(n=!0),new Event(e,{bubbles:t,cancelable:n})}(e,a,c);return Object.assign(l,u),n&&(n instanceof HTMLElement?n.dispatchEvent(l):Array.from(n).forEach((function(e){return e.dispatchEvent(l)}))),l}(i.render.appearance,{bubbles:!0,elementOptions:y,paymentOptions:h},[m]),w=d.elements(C(C({},b.elementOptions),{clientSecret:s})),g=w.create("payment",b.paymentOptions);g.mount(m),g.on("change",(function(e){f.get(m).empty=e.empty&&!e.complete})),l.forEach((function(e){var t;null===(t=p.elements.namedItem(e))||void 0===t||t.addEventListener("change",(function(){G.push(e),p.freeform.disableSubmit("stripe.working"),p.freeform.disableForm();var t=f.get(m).paymentIntent.id;R(u,p,t).then((function(e){var t=e.id,n=e.client_secret;n?(g.unmount(),w=d.elements({clientSecret:n}),(g=w.create("payment",b.paymentOptions)).mount(m),g.on("change",(function(e){f.get(m).empty=e.empty&&!e.complete})),f.set(m,{empty:!0,elements:w,paymentIntent:{id:t,secret:n}})):w.fetchUpdates()})).catch((function(t){var n;p.freeform._renderFieldErrors(((n={})[e]=[t.response.data.message],n))})).finally((function(){G.pop(),G.length||(p.freeform.enableSubmit("stripe.working"),p.freeform.enableForm())}))}))}));var E=r.some((function(e){return void 0===e.target})),S=function(e){return function(t){var r=t.target.value;_({integration:u,form:p,paymentIntentId:n,key:e,value:r})}};E?p.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach((function(e){e.addEventListener("change",S(e.name))})):r.forEach((function(e){var t,n=e.source,r=e.target;null===(t=p.elements.namedItem(r))||void 0===t||t.addEventListener("change",S(n))})),f.set(m,{empty:!0,elements:w,paymentIntent:{id:n,secret:s}})})).catch((function(n){m.innerHTML="Could not load payment element.";var r={};l.forEach((function(e){r[e]=[n.response.data.message]})),p.freeform._renderFieldErrors(r);var o=function(){W(e)(t),l.forEach((function(e){var t;null===(t=p[e])||void 0===t||t.removeEventListener("change",o)}))};l.forEach((function(e){var t;null===(t=p[e])||void 0===t||t.addEventListener("change",o)}))})).finally((function(){p.freeform.enableSubmit("stripe.init")}))),[2]}}))},new((o=void 0)||(o=Promise))((function(e,t){function i(e){try{u(a.next(e))}catch(e){t(e)}}function c(e){try{u(a.throw(e))}catch(e){t(e)}}function u(t){var n;t.done?e(t.value):(n=t.value,n instanceof o?n:new o((function(e){e(n)}))).then(i,c)}u((a=a.apply(n,r||[])).next())}));var n,r,o,a}},D=function(e,t,n,r){return new(n||(n=Promise))((function(o,a){function i(e){try{u(r.next(e))}catch(e){a(e)}}function c(e){try{u(r.throw(e))}catch(e){a(e)}}function u(e){var t;e.done?o(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(i,c)}u((r=r.apply(e,t||[])).next())}))},K=function(e,t){var n,r,o,a={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]},i=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return i.next=c(0),i.throw=c(1),i.return=c(2),"function"==typeof Symbol&&(i[Symbol.iterator]=function(){return this}),i;function c(c){return function(u){return function(c){if(n)throw new TypeError("Generator is already executing.");for(;i&&(i=0,c[0]&&(a=0)),a;)try{if(n=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return a.label++,{value:c[1],done:!1};case 5:a.label++,r=c[1],c=[0];continue;case 7:c=a.ops.pop(),a.trys.pop();continue;default:if(!((o=(o=a.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){a=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){a.label=c[1];break}if(6===c[0]&&a.label<o[1]){a.label=o[1],o=c;break}if(o&&a.label<o[2]){a.label=o[2],a.ops.push(c);break}o[2]&&a.ops.pop(),a.trys.pop();continue}c=t.call(e,a)}catch(e){c=[6,e],r=0}finally{n=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}},U=new WeakMap,X=function(e){return o=void 0,a=void 0,c=function(){var o,a,i;return function(e,t){var n,r,o,a={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]},i=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return i.next=c(0),i.throw=c(1),i.return=c(2),"function"==typeof Symbol&&(i[Symbol.iterator]=function(){return this}),i;function c(c){return function(u){return function(c){if(n)throw new TypeError("Generator is already executing.");for(;i&&(i=0,c[0]&&(a=0)),a;)try{if(n=1,r&&(o=2&c[0]?r.return:c[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,c[1])).done)return o;switch(r=0,o&&(c=[2&c[0],o.value]),c[0]){case 0:case 1:o=c;break;case 4:return a.label++,{value:c[1],done:!1};case 5:a.label++,r=c[1],c=[0];continue;case 7:c=a.ops.pop(),a.trys.pop();continue;default:if(!((o=(o=a.trys).length>0&&o[o.length-1])||6!==c[0]&&2!==c[0])){a=0;continue}if(3===c[0]&&(!o||c[1]>o[0]&&c[1]<o[3])){a.label=c[1];break}if(6===c[0]&&a.label<o[1]){a.label=o[1],o=c;break}if(o&&a.label<o[2]){a.label=o[2],a.ops.push(c);break}o[2]&&a.ops.pop(),a.trys.pop();continue}c=t.call(e,a)}catch(e){c=[6,e],r=0}finally{n=o=0}if(5&c[0])throw c[1];return{value:c[0]?c[1]:void 0,done:!0}}([c,u])}}}(this,(function(c){return U.has(e)||(U.set(e,!0),o=new WeakMap,i=function(e){return function(){return D(void 0,void 0,void 0,(function(){var t;return K(this,(function(r){return t=e.form,H(t).forEach(W(e)),function(e){return N(e,!0)}(t).forEach((function(t){t.addEventListener(n.applied,(function(){W(e)(t)}))})),[2]}))}))}}(a={elementMap:o,form:e}),r(e,[t.ready,t.reset,t.ajaxAfterSubmit],i),r(e,[t.submit],function(e){return function(t){return D(void 0,void 0,void 0,(function(){return K(this,(function(n){return t.addCallback((function(){return D(void 0,void 0,void 0,(function(){var n,r,o,a,i,c,u,l,s,f,p,d,m,v,y,h,b,w,E;return K(this,(function(S){switch(S.label){case 0:if(t.isBackButtonPressed)return[2];n=e.elementMap,r=H(e.form),o=0,a=r,S.label=1;case 1:return o<a.length?(i=a[o],c=g(i),u=c.getStripeInstance,l=c.required,s=c.integration,f=i.querySelector("[data-freeform-stripe-card]"),p=n.get(f),d=p.empty,m=p.elements,v=p.paymentIntent,y=v.id,h=v.secret,d&&!l?[2]:[4,t.freeform.quickSave(h,y)]):[3,5];case 2:return!1===(b=S.sent())?[2,!0]:void 0===b?[2,!1]:((w=new URL("/freeform/payments/stripe/callback",window.location.origin)).searchParams.append("integration",s),w.searchParams.append("token",b),[4,u().confirmPayment({elements:m,confirmParams:{return_url:w.toString()}})]);case 3:return(E=S.sent().error)&&(t.freeform._renderFormErrors([E.message]),t.freeform._scrollToForm()),[2,!1];case 4:return o++,[3,1];case 5:return[2]}}))}))}),100),[2]}))}))}}(a))),[2]}))},new((i=void 0)||(i=Promise))((function(e,t){function n(e){try{u(c.next(e))}catch(e){t(e)}}function r(e){try{u(c.throw(e))}catch(e){t(e)}}function u(t){var o;t.done?e(t.value):(o=t.value,o instanceof i?o:new i((function(e){e(o)}))).then(n,r)}u((c=c.apply(o,a||[])).next())}));var o,a,i,c};document.addEventListener(i.load,(function(){document.querySelectorAll("form[data-freeform]").forEach((function(e){X(e)}))})),window.onload=function(){document.dispatchEvent(new CustomEvent(i.load))};var B=function(e){var t;"FORM"!==e.nodeName&&void 0===(null===(t=e.dataset)||void 0===t?void 0:t.freeform)||X(e),null==e||e.childNodes.forEach(B)};new MutationObserver((function(e){e.forEach((function(e){"childList"===e.type&&e.addedNodes.forEach((function(e){B(e)}))}))})).observe(document.body,{childList:!0,subtree:!0})}();