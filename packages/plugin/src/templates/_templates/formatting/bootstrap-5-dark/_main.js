var forms=document.querySelectorAll("[data-freeform-bootstrap-dark]");forms.forEach((function(e){e.addEventListener("freeform-ready",(function(e){var r=e.freeform;r.setOption("errorClassBanner",["alert","alert-danger"]),r.setOption("errorClassList",["list-unstyled","m-0","fst-italic","text-danger"]),r.setOption("errorClassField",["is-invalid"]),r.setOption("successClassBanner",["alert","alert-success"])})),e.addEventListener("freeform-stripe-appearance",(function(e){e.elementOptions.appearance=Object.assign(e.elementOptions.appearance,{variables:{colorPrimary:"#0d6efd",fontFamily:'-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji"',fontSizeBase:"1rem",spacingUnit:"0.2em",tabSpacing:"10px",gridColumnSpacing:"20px",gridRowSpacing:"20px",colorText:"#ffffff",colorBackground:"#1d1f23",colorDanger:"#dc3545",borderRadius:"0.375rem"},rules:{".Tab, .Input":{border:"1px solid #495057",boxShadow:"none"},".Tab:focus, .Input:focus":{border:"1px solid #0b5ed7",boxShadow:"none",outline:"0",transition:"border-color .15s ease-in-out"},".Label":{fontSize:"1rem",fontWeight:"400"}}})})),e.addEventListener("freeform-on-submit",(function(e){var r=e.form.getAttribute("data-id");forms.forEach((function(e){var o=e.getAttribute("data-id");r!==o&&(e.querySelectorAll("[data-field-errors]").forEach((e=>e.remove())),e.querySelectorAll(".form-control").forEach((e=>e.classList.remove("is-invalid"))))}))}))}));