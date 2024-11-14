var forms = document.querySelectorAll("[data-freeform-multipage]");
forms.forEach(function (form) {
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
                    colorDanger: "#dc3545",
                    borderRadius: "5px",
                },
                rules: {
                    '.Tab, .Input': {
                        border: '1px solid #cbced0',
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
                        fontWeight: '400',
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
                otherForm.querySelectorAll(".freeform-input").forEach(element => element.classList.remove("freeform-has-errors"));
            }
        });
    });
});
