# Setup Guide

This guide assumes you have a [Constant Contact ](https://www.constantcontact.com) account already.

## Compatibility

Uses OAuth flow on `v3` of the REST API.

### Endpoints
Maps data to the following endpoints:

- **Contact Lists**

### Fields
Maps data to the following field types:

- **Standard**
- **Custom**

## Duplicate Check & Update

If a duplicate email address is found in _Constant Contact_, the profile data will be updated with the latest information submitted.

## Setup Instructions

### 1. Prepare your site's end for Integration

- Select *Constant Contact (v3)* from the **Service Provider** select dropdown.
- Enter a name and handle for the integration.
- Copy the URL in the **OAuth 2.0 Return URI** field to your clipboard.
- Leave this page open.

### 2. Prepare Constant Contact's end for Integration

- Open up a new browser tab and go to Constant Contact's API [*My Applications* website](https://app.constantcontact.com/pages/dma/portal/).
- Log into your Constant Contact account there.
- Go to the **My Applications** page.
- Click on the **New Application** button at top right.
    - In the modal window that pops up, enter a name for the application.
    - Leave the other 2 settings as they are defaulted.
    - Click the **Create** button.
- Once saved, it'll display a list of your applications.
- For the application you just created, click on the **Edit** button.
    - In the Constant Contact **Redirect URI** field, click on the pencil icon at the right to edit the value.
    - Paste the value you copied from Freeform's **OAuth 2.0 Return URI** field.
    - Click the **Confirm** button.
- Click the **Save** button at the top right.
- On the **Details** page of your application, copy the `API Key` value.
- Click the **Generate Client Secret** button beside the API Key field.
    - On the confirm screen that loads, click the **Generate Client Secret** button.
    - Copy the newly created **Application Secret** value from Constant Contact.

### 3. Prepare the Connection

- Go back to your Craft/Freeform browser tab.
- Paste the `API Key` value from Constant Contact into the the **Client ID** field in Freeform.
- Paste the `Application Secret` value from Constant Contact into the the **Client Secret** field in Freeform.

### 4. Finish the Connection

- Click the **Save** button.
- You will be redirected to a Constant Contact OAuth page to allow permissions.
    - If not currently logged in, fill in your credentials.
    - Click **Allow Access** when asked for permissions.
- You will then be redirected back to the **Freeform Email Marketing Integration** page.
- Confirm that there is a green circle with **Authorized** in the middle of the page.

### 5. Configure the Form

To use this integration on your form(s), you'll need to configure each form individually.

- Visit the form inside the form builder.
- Click on the **Integrations** tab.
- Click on **Constant Contact** in the list of available integrations.
- On the right side of the page:
    - Enable the integration.
    - Choose a Freeform field to be the target opt-in field.
    - Select a mailing list that new users should be subscribed to.
    - Map Freeform fields to the Constant Contact fields as you wish.

<span class="note warning"><b>Important:</b> Please note that if you set this up initially on a development environment, you will need to update your callback URL and reauthorize the connection on your production environment. However, your settings and field mappings will remain intact.</span>

---

<small>Do you need more from this integration? Is the integration you're looking for not here? Solspace offers [custom software development services](https://docs.solspace.com/support/premium/) to build any feature or change you need.</small>

<style type="text/css">ol{list-style-type:upper-alpha;padding-left:20px!important}ol>li{font-weight:600}ol>li>ul>li{font-weight:400}.warning {display:block;padding:10px 15px;border:1px solid var(--warning-color);border-radius:5px;}</style>