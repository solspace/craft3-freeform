var forms=document.querySelectorAll("[data-freeform-foundation]");forms.forEach((function(e){e.addEventListener("freeform-ready",(function(e){var o=e.freeform;o.setOption("errorClassBanner",["callout","alert"]),o.setOption("errorClassList",["errors"]),o.setOption("errorClassField","has-error"),o.setOption("successClassBanner",["callout","success"])})),e.addEventListener("freeform-stripe-appearance",(function(e){e.elementOptions.appearance=Object.assign(e.elementOptions.appearance,{variables:{colorPrimary:"#0d6efd",fontFamily:'-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji"',fontSizeBase:"16px",spacingUnit:"0.2em",tabSpacing:"10px",gridColumnSpacing:"20px",gridRowSpacing:"20px",colorText:"#eaeaea",colorBackground:"#1d1f23",colorDanger:"#dc3545",borderRadius:"5px"},rules:{".Tab, .Input":{border:"1px solid #6c757d",boxShadow:"none"},".Tab:focus, .Input:focus":{border:"1px solid #0b5ed7",boxShadow:"none",outline:"0",transition:"border-color .15s ease-in-out"},".Label":{fontSize:"16px",fontWeight:"400"}}})})),e.addEventListener("freeform-on-submit",(function(e){var o=e.form.getAttribute("data-id");forms.forEach((function(e){var r=e.getAttribute("data-id");o!==r&&(e.querySelectorAll("[data-field-errors]").forEach((e=>e.remove())),e.querySelectorAll(".freeform-field").forEach((e=>e.classList.remove("has-error"))))}))}))}));