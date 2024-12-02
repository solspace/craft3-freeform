# Setup Guide

This guide assumes you already have an endpoint to submit to.

## Setup Instructions

### 1. Enable POST Forwarding inside Freeform

- Enable POST Forwarding by toggling on the **Enabled** setting.
- If you'd like POST Forwarding to be enabled for all forms by default, toggle on the **Enabled by default** setting.
- Enter the URL where the POST request should be sent to in the **URL** field. 
- Provide a keyword or phrase Freeform should check for in the output of the external POST URL to know if and when there's an error to log, e.g. `error` or `an error occurred`, etc.
- Save the form.

### 2. Configure the Form
To use this integration on your form(s), you'll need to configure each form individually. If you toggled on the **Enabled by default** setting in the Freeform Settings, it will automatically be ON for all forms. You can disable them for each form as necessary.

- Visit the form inside the form builder.
- Click on the **Integrations** tab.
- Click on **POST Forwarding** in the list of available integrations.
- On the right side of the page:
    - Enable (or disable) the integration.
    - Adjust any of the settings as needed.
- Save the form.

---

<small>Do you need more from this integration? Is the integration you're looking for not here? Solspace offers [custom software development services](https://docs.solspace.com/support/premium/) to build any feature or change you need.</small>