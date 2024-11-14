var forms = document.querySelectorAll("[data-freeform-tailwind]");
forms.forEach(function (form) {
    // Styling for AJAX responses
    form.addEventListener("freeform-ready", function (event) {
        var freeform = event.freeform;
        freeform.setOption("errorClassBanner", ["bg-red-100", "border", "border-red-400", "font-bold", "text-red-700", "px-4", "py-3", "rounded", "relative", "mb-4"]);
        freeform.setOption("errorClassList", ["errors", "text-red-500", "text-sm", "italic"]);
        freeform.setOption("errorClassField", ["freeform-input", "border-red-500"]);
        freeform.setOption("successClassBanner", ["bg-green-100", "border", "border-green-500", "font-bold", "text-green-700", "px-4", "py-3", "rounded", "relative", "mb-4"]);
    })
    // Styling for Stripe Payments field
    form.addEventListener("freeform-stripe-appearance", function (event) {
        event.elementOptions.appearance = Object.assign(
            event.elementOptions.appearance,
            {
                variables: {
                    colorPrimary: "#0d6efd",
                    fontFamily: "-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,\"Helvetica Neue\",Arial,sans-serif,\"Apple Color Emoji\",\"Segoe UI Emoji\",\"Segoe UI Symbol\",\"Noto Color Emoji\"",
                    fontSizeBase: "16px",
                    spacingUnit: "0.2em",
                    tabSpacing: "10px",
                    gridColumnSpacing: "20px",
                    gridRowSpacing: "20px",
                    colorText: "#212529",
                    colorBackground: "#ffffff",
                    colorDanger: "rgb(239 68 68)",
                    borderRadius: "5px",
                },
                rules: {
                    '.Tab, .Input': {
                        border: '1px solid rgb(148 163 184)',
                        boxShadow: 'none',
                    },
                    '.Tab:focus, .Input:focus': {
                        border: '1px solid #0b5ed7',
                        boxShadow: 'none',
                        outline: '0',
                        transition: 'border-color .15s ease-in-out',
                    },
                    '.Label': {
                        fontSize: '16px',
                        fontWeight: '500',
                    },
                },
            }
        );
    });
    // Hides other form field error messaging
    form.addEventListener("freeform-on-submit", function (event) {
        var formId = event.form.getAttribute("data-id");
        forms.forEach(function (otherForm) {
            var otherFormId = otherForm.getAttribute("data-id");
            if (formId !== otherFormId) {
                otherForm.querySelectorAll("[data-field-errors]").forEach(element => element.remove());
                otherForm.querySelectorAll(".freeform-input").forEach(element => element.classList.remove("border-red-500"));
            }
        });
    });
});
