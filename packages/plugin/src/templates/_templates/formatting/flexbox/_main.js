var form = document.querySelector('[data-freeform-flexbox]');
if (form) {
  form.addEventListener("freeform-stripe-appearance", function (event) {
    event.elementOptions.appearance = Object.assign(
      event.elementOptions.appearance,
      {
        variables: {
          colorPrimary: "#0d6efd",
        },
      }
    );
  });
}
