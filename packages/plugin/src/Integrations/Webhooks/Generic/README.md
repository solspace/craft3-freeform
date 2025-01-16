# Setup Guide
Freeform's Generic Webhooks feature allows you to generate and POST the JSON from the Freeform submission data off to your own custom endpoint. It also contains a few form identifying entries.

This guide assumes you are familiar with what is necessary to connect Freeform data with a webhook from an external service, whether it's third party service or your own custom one, etc. For that matter, we cannot provide any assistance with setting this up, aside from the basics, and letting you know what kind of data to expect.

## Setup Instructions

### 1. Set up the Webhook on your site

- For *Service Provider*, select **Generic**.
- Enter a name for the **Name** field.
- Add the webhook URL from your external source into the **Webhook URL** field in Freeform.
- Click the **Save** button.

### 2. Configure the Form

To use this integration on your form(s), you'll need to configure each form individually.

- Visit the form inside the form builder.
- Click on the **Integrations** tab.
- Click on your **Generic** webhook in the list of available integrations.
- On the right side of the page:
    - Enable the integration.

### 3. Verify the Webhook

- Try submitting one of your forms that uses this webhook, and check if Freeform posts successfully to your custom endpoint.

## Example Output

``` json
{
  "form": {
    "id": 7,
    "name": "My Form",
    "handle": "myForm",
    "color": "#807447",
    "description": "",
    "returnUrl": ""
  },
  "id": 618,
  "dateCreated": {
    "date": "2025-07-09 08:05:22.000000",
    "timezone_type": 3,
    "timezone": "America\/Los_Angeles"
  },
  "uid": "1880fb76-d616-4c37-ac02-fc0c1765f349",
  "token": "nzkfa6VCY0YzOwnIkHMyJ8pEEHx2qu8llRCdL9u8wGLr7iMrokPypRp8F0lQwfm6zLibTXKKihdCJ1V4Iq5uprtyajz7MGtXVUq2",
  "firstName": "Bob",
  "lastName": "Smith",
  "email": "bob@smith.test",
  "acceptTerms": "1"
}
```

---

<small>Do you need more from this integration? Is the integration you're looking for not here? Solspace offers [custom software development services](https://docs.solspace.com/support/premium/) to build any feature or change you need.</small>