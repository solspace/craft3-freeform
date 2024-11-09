# Setup Guide

This guide assumes you have a [Jira / Atlassian](https://www.atlassian.com/software/jira) account already.

## Compatibility

Uses OAuth flow on `v3` of the REST API.

### Endpoints
Maps data to Jira **Cards**. Works for _Software_ and _Business_ project types.

<span class="note warning"><b>Important:</b> Please note that because of the complexity of the Jira integration, you need to create a separate Jira integration for each additional Jira project you want to map.</span>

### Fields
Maps Freeform submission data to standard Jira Card field types.

## Setup Instructions

### 1. Prepare your site's end for Integration

- Select *Jira (v3)* from the **Service Provider** select dropdown.
- Enter a name and handle for the integration.
- In the **Instance URL** setting, enter your Jira account's URL, e.g. `https://mycompany.atlassian.net`.
- In the **Project Key** setting, enter the project key of the Jira project you want to interact with, e.g. `TST`.
- Copy the URL in the **OAuth 2.0 Return URI** field to your clipboard.
- Leave this page open.

### 2. Set up Jira app for Integration

- Open up a new browser tab and go to the [Jira Developer Console](https://developer.atlassian.com/console) and log into your account.
- It should automatically bring you to a list of your current apps.
- Click on the **Create** button and choose **OAuth 2.0 integration**.
    - Give your app a **Name**, agree to the terms and click _Create_.
- On the next page, click on the **Permissions** menu item on the left.
    - For the **Jira API** row, click on the **Add** button.
    - Then click the **Configure** button.
    - Stay on the **Classic Scopes** tab.
    - On the **Jira platform REST API** section, click the **Edit Scopes** button on the right.
    - On the modal that loads, search for and enable the following permissions:
        - `write:jira-work`
        - `read:jira-work`
        - `read:jira-user`
    - Click the **Save** button.
- Click on the **Authorization** menu item on the left.
    - For the **OAuth 2.0 (3LO)** row, click the **Add** button.
    - Paste the URL you saved from the Freeform **OAuth 2.0 Return URI** into the app's **Callback URL** setting.
    - Click the **Save changes** button.
- Click on the **Settings** menu item on the left.
    - Copy the following newly created credentials:
        - **Client ID**
        - **Secret**

### 3. Prepare the Connection

- Flip back to the Freeform CP browser tab.
- Paste the Jira app's **Client ID** value into the **Client ID** field in Freeform.
- Paste the Jira app's **Secret** value into the **Client Secret** field in Freeform.

### 4. Finish the Connection

- Click the **Save** button.
- You will be redirected to a Jira OAuth page to allow permissions.
    - If not currently logged in, fill in your credentials.
    - Click **Accept** when asked for permissions.
- You will then be redirected back to the **Freeform Integration** page.
- Confirm that there is a green circle with **Authorized** in the middle of the page.

### 5. Configure the Form

To use this integration on your form(s), you'll need to configure each form individually.

- Visit the form inside the form builder.
- Click on the **Integrations** tab.
- Click on **Jira** in the list of available integrations.
- On the right side of the page:
    - Enable the integration.
    - Set the **Google Sheets Spreadsheet ID**, e.g. `4hzvcabRd6yZwux7vK80-NK02zSDD7U-X8MePslAiHvc`
    - Select the Freeform fields to be mapped to the applicable Jira Card columns.

<span class="note warning"><b>Important:</b> Please note that if you set this up initially on a development environment, you will need to update your callback URL and reauthorize the connection on your production environment. However, your settings and field mappings will remain intact.</span>

---

<small>Do you need more from this integration? Is the integration you're looking for not here? Solspace offers [custom software development services](https://docs.solspace.com/support/premium/) to build any feature or change you need.</small>

<style type="text/css">ol{list-style-type:upper-alpha;padding-left:20px!important}ol>li{font-weight:600}ol>li>ul>li{font-weight:400}.warning {display:block;padding:10px 15px;border:1px solid var(--warning-color);border-radius:5px;}</style>