# Setup Guide

This guide assumes you have a [Salesforce](http://salesforce.com) account already.

<span class="note warning"><b>Important:</b> This integration will not work with the _Salesforce **Essentials**_ plan as it does not have access to the API.</span>

## Compatibility

Uses OAuth flow on `v58` of the REST API.

### Endpoints
Maps data to the following endpoints:

- **Leads**
- **Opportunity**
- **Account**
- **Contact**
- **Files** (see below)

### Fields
Maps data to the following field types:

- **Text** fields: String, Encrypted String, Textarea, Email, URL, Address, Picklist, Multipicklist, Date/Time fields
- **Numeric** fields: Number, Phone, Currency
- **Other** fields: Reference/relationship
- **Files**: map uploaded files to Salesforce and relate them to **Leads**, **Opportunities**, **Accounts**, and **Contacts** .
- There are some limitations to types of fields that can be mapped, such as **Lookup** fields.

## Duplicate Check & Update

The following duplicate logic applies to the **Opportunity**/**Account**/**Contact** approach (not **Leads**).

### Default Logic

A duplicate check on records is done in the following way:

1. Check whether the *Account* already exists:
    - Check against the **Name** field as the identifier.
        - Freeform will create an *Account* with the **First Name** and **Last Name** mapped to the *Contact* if you leave the *Account* **Name** field empty. This is helpful if you are dealing with customers not necessarily belonging to a company or organization, and just wish to have a Salesforce Account matching the Contact.
    - If no match, create a new *Account*.
2. Check whether the *Contact* already exists:
    - Check against the **Email address** as the identifier.
    - If email address exists in Salesforce *Contact*, update the existing Contact with other details.
    - If no email address match, check if **first name** and **last name** exist in Salesforce *Contact*, then update the existing *Contact* with all other values including the new email address.
    - If no matches at all, create a new Salesforce *Contact*.
    - If *Account* **Name** matched, assign the new Contact to the existing Salesforce Account.
3. Create new *Opportunity*.
    - Attach *Opportunity* to existing or newly created Salesforce *Account*.

### Alternate Logic

An alternate duplicate check on records is also available. When the **Check Contact email address and Account website when checking for Duplicates** setting is enabled, the following logic will happen instead:

1. Check whether the *Contact* already exists:
    - Check against **Email address** as the identifier.
    - If there's a match, update the *Contact* with new values, where supplied.
    - If no match, create a new *Contact*.
2. Check whether the *Account* already exists:
    - Check against the **Website** field domain. If you are not mapping a domain to the **Website** field, Freeform will automatically sniff the *Contact* email address, take the domain from it and pass it off to the Salesforce *Account* as the **Website** field value.
    - If there's a match, update the *Account* with new values, where supplied.
    - If no match, create a new Salesforce *Account*.
    - If a new *Contact* was created, link the contact to the *Account*.
3. Create new *Opportunity*.
    - Attach *Opportunity* to existing or newly created Salesforce *Account*.

## Setup Instructions

### 1. Prepare Freeform's end for Integration

- Select *Salesforce (v58)* from the **Service Provider** select dropdown.
- Enter a name and handle for the integration. e.g. `My Salesforce Integration`
- Copy the URL value generated in the **OAuth 2.0 Return URI** field, e.g. `https://mysite.net/admin/freeform/oauth/authorize`.
- Leave this page open and open a new tab to go to the Salesforce site...

### 2. Prepare Salesforce's end for Integration

- Open another browser tab and go to [Salesforce website](https://login.salesforce.com) and log into your account.
- On the left navigation menu, click on **Apps**, then click **App Manager**.
- At the top right corner of the page, click the **New Connected App** button.
- Fill out the fields in the **Basic Information** section.
- In the **API (Enable OAuth Settings)** section, click the **Enable OAuth Settings** checkbox. More fields will appear...
- In the **Callback URL** field, paste the value from the **OAuth 2.0 Return URI** field in Freeform's settings for the integration.
- In the **Selected OAuth Scopes** field, select the following permissions from the list and click **Add** arrow button:
    - **Manage user data via APIs (api)**
    - **Perform requests on your behalf at any time (refresh_token, offline_access)**
- You shouldn't need to fill out any further fields, and then click **Save** button.
- You will be taken to a new page that lists info about your newly created app, including **Consumer Key** and **Consumer Secret** values. You will need to copy each of these values.
    - Salesforce gets tricky to navigate, so do yourself a favor and copy and paste these 2 values into a text editor for now, being sure to label each too. You'll save yourself some extra steps later on.
- At the top middle of the page, click on the **Manage** button.
- At the top middle of the next page, click the **Edit Policies** button.
- Under the **OAuth policies** section, adjust the following settings:
    - In the **Permitted Users** field, be sure that it is set to **All users may self-authorize**.
    - In the **IP Relaxation** field, change the setting to **Relaxed IP restrictions**.
    - Click **Save** button at bottom of page.
- If you copy and pasted the **Consumer Key** and **Consumer Secret** values in a text editor, you can skip these next couple steps:
    - To go back to your app to see these values, click on the **App Manager** navigation item (under **Apps**)
    - Find your app in the list. Then in the right column, click the down arrow, and then click **View**.

### 3. Continue the Integration on your site

- Flip back to the Freeform CP browser tab.
- Paste the Salesforce **Consumer Key** value into the **Client ID** field in Freeform.
- Paste the Salesforce **Consumer Secret** value into the **Client Secret** field in Freeform.

### 4. Additional Configuration

- **LEADS**
    - **Assign Lead Owner?** - Enabling this will make Salesforce assign a lead owner based on lead owner assignment rules in Salesforce.
    - **Sandbox Mode** - Enable this if your Salesforce account is in Sandbox mode.
    - **Using custom URL?** - Enable this if you connect to your Salesforce account with a custom company URL such as `mycompany.my.salesforce.com`.
- **OPPORTUNITY**
    - **Close Date** - Enter a relative textual date string for the Close Date of the newly created Opportunity (e.g. `7 days`).
    - **Stage Name** - Enter the Stage Name the newly created Opportunity should be assigned to (e.g. `Prospecting`).
    - **Sandbox Mode** - Enable this if your Salesforce account is in Sandbox mode.
    - **Append checkboxes field values on Contact update?** - If a Contact already exists in Salesforce, enabling this will append additional checkboxes field values to the Contact inside Salesforce, instead of overwriting the options.
    - **Append checkboxes field values on Account update?** - If an Account already exists in Salesforce, enabling this will append additional checkboxes field values to the Account inside Salesforce, instead of overwriting the options.
    - **Check Contact email address and Account website when checking for Duplicates** - By default, Freeform checks the Contact's first name, last name, email address, and Account name. If enabled, it will check only the Contact's email address and the Account's website. If no website is provided, Freeform will use the domain from the Contact's email address.

### 5. Finish the Integration

- Click the **Save** button.
- You will be redirected to a Salesforce OAuth page to allow permissions.
    - If not currently logged in, fill in your credentials.
    - Click **Allow** when asked for permissions.
- You will then be redirected back to the **Freeform CRM Integration** page.

### 6. Verify Authorization

- After the integration is saved, it'll return you to the list of CRM integrations.
- Click into the newly created integration.
- Confirm that there is green circle with **Authorized** in the middle of the page.

### 7. Configure the Form

To use this integration on your form(s), you'll need to configure each form individually.

- Visit the form inside the form builder.
- Click on the **Integrations** tab.
- Click on **Salesforce** in the list of available integrations.
- On the right side of the page:
    - Enable the integration.
    - Select the API endpoints you wish to map to.
    - Map Freeform fields to the Salesforce fields as needed.

<span class="note warning"><b>Important:</b> Please note that if you set this up initially on a development environment, you will need to update your callback URL and reauthorize the connection on your production environment. However, your settings and field mappings will remain intact.</span>

---

<small>Do you need more from this integration? Is the integration you're looking for not here? Solspace offers [custom software development services](https://docs.solspace.com/support/premium/) to build any feature or change you need.</small>

<style type="text/css">ol{padding-left:20px!important}ol>li{font-weight:600}ol>li>ul>li{font-weight:400}.warning {display:block;padding:10px 15px;border:1px solid var(--warning-color);border-radius:5px;}</style>