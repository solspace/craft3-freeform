# Setup Guide

This guide assumes you have a [Stripe](https://stripe.com) account already.

<span class="note warning">Please refer to the [Freeform Stripe integration documentation](https://docs.solspace.com/craft/freeform/v5/integrations/stripe-payments/) for a complete setup guide.</span>

## Compatibility

Uses OAuth flow on `v4` of the REST API.

### Endpoints
Maps data to Stripe via the **Payment Element API**.

### Fields
Maps Freeform fields to Stripe fields to populate more customer information for the transaction/customer profile in Stripe.

## Setup Instructions

### 1. Prepare Freeform

- Select *Stripe* from the **Service Provider** select dropdown.
- Enter a name and handle for the integration.
- Copy the URL in the **Webhook URL** field to your clipboard.
- Leave this page open.

### 2. Prepare Stripe

Open up another browser tab and go to your Stripe account:

- On the top menu, click on the **Developers** button. On the next page click **API Keys**.
- Copy the token for **Publishable key** (e.g. `pk_test_fs7f6f8g8dfg68g68d76dgd8`) and paste into the **Public Key** field inside Freeform.
- Copy the token (click *Reveal test key* button to reveal) for **Secret key** (e.g. `sk_test_af7fa7gfdo78g6ddfg6d8d87`) and paste into the **Secret Key** field inside Freeform.

<span class="note tip">Stripe can be run in **Live** mode or **Test** mode. To toggle between Live and Test mode, click the **Test mode** toggle at the top of the page. When doing this, the _Publishable key_ and _Secret key_ will switch between live and test as well. We strongly recommend switching Stripe to **Test** mode first, and testing your site with those keys instead.

Consider using an **Environment Variable** for these settings instead. Then, store _Live_ keys on production and _Test_ keys on local dev environments.</span>

### 3. Configure the Stripe Webhook

- Click on the **Webhooks** tab inside the Stripe **Developers** page.
- On the next page, click the **+ Add endpoint** button.
- Configure the webhook:
    - Copy the **Webhook URL** field value in Freeform (e.g. `http://my-precio.us/freeform/payment-webhooks/stripe?id=1`) and paste it into the **Endpoint URL** setting.
    - For the **Listen to** setting, choose **Events on your account**.
    - Click on the **Select events** button under the **Select events to listen to** setting. Add the following 3 events Freeform requires:
        - `payment_intent.succeeded`
        - `payment_intent.payment_failed`
        - `payment_intent.canceled`
    - Click the **Add endpoint** button to save it.
- On the next page inside Stripe account, click on the newly created endpoint URL.
- At the top of the next page, you'll see an item called **Signing secret**.
- Click on the **Reveal** button below it, and then copy the token (e.g. `whsec_dsf87d876sdf7g876fd8fasd9f7dsasd`).

### 4. Complete the Connection

- Switch back to the Payment integration inside Freeform, and paste the Stripe **Signing secret** token into the **Webhook Secret** setting.
- Click the **Save** button.
- You will then be redirected back to the **Freeform Integration** page.
- Confirm that there is a green circle with **Authorized** in the middle of the page.

### 5. Configure the Form

To use this integration on your form(s), you'll need to configure each form individually.

- Visit the form inside the form builder.
- Click on the **Integrations** tab.
- Click on **Stripe** in the list of available integrations.
- Toggle the _Enable_ setting ON.
- Map your Freeform fields to Stripe's fields as necessary.

<span class="note warning">Please refer to the [Freeform Stripe integration documentation](https://docs.solspace.com/craft/freeform/v5/integrations/stripe-payments/) for a complete setup guide.</span>

---

<small>Do you need more from this integration? Is the integration you're looking for not here? Solspace offers [custom software development services](https://docs.solspace.com/support/premium/) to build any feature or change you need.</small>

<style type="text/css">ol{padding-left:20px!important}ol>li{font-weight:600}ol>li>ul>li{font-weight:400}.warning {display:block;padding:10px 15px;border:1px solid var(--warning-color);border-radius:5px;}.tip {color:#1f5fea;display:block;padding:10px 15px;border:1px solid #1f5fea;border-radius:5px;}</style>