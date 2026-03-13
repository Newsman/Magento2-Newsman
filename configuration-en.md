# Newsman Extension for Magento 2 - Configuration Guide

This guide walks you through every setting in the Newsman extension for Magento 2 so you can connect your store to your Newsman account and start collecting subscribers, sending newsletters, and tracking customer behavior.

---

## Where to Find the Extension Settings

After installing the extension, go to **Stores > Settings > Configuration** in your Magento admin panel. In the left sidebar you will see a **Newsman** tab with two sections:

- **General** - API connection, subscriber sync, data export, and developer settings
- **Remarketing** - Visitor tracking and remarketing pixel

All settings can be configured per **Store View**, per **Website**, or as a **Default** for all stores. Use the scope selector at the top of the page to choose which level you are configuring.

---

## Getting Started - Connecting to Newsman

Before you can use any feature, you need to connect the extension to your Newsman account. There are two ways to do this:

### Option A: Quick Setup with OAuth (Recommended)

1. Go to **Stores > Configuration > Newsman > General**.
2. In the **About** section, click the **Configure with Newsman Login** button.
3. You will be taken to the Newsman website. Log in if needed and grant access.
4. You will be redirected back to a page in Magento where you choose your email list from a dropdown. Select the list you want to use and click **Save**.
5. That's it - your API Key, User ID, and List are all configured.

### Option B: Manual Setup

1. Log in to your Newsman account at newsman.app.
2. Go to your account settings and copy your **API Key** and **User ID**.
3. In Magento, go to **Stores > Configuration > Newsman > General**.
4. Open the **API (Credentials)** section.
5. Paste your **User ID** and **API Key** in the corresponding fields.
6. Click **Test Credentials** to verify the connection. If successful, a confirmation message will appear.
7. Click **Synchronize Lists and Segments** to load your Newsman lists into the dropdown.
8. Select your **List ID** from the dropdown and optionally a **Segment ID**.
9. Click **Save Config**.

---

## Reconfigure with Newsman OAuth

If you need to reconnect the extension to a different Newsman account, or if your credentials have changed, go to **Stores > Configuration > Newsman > General** and click the **Configure with Newsman Login** button in the About section. This will take you through the same OAuth flow described above - you will be redirected to the Newsman website to authorize access, then back to Magento to select your email list. Your API Key, User ID, and List will be updated with the new credentials.

---

## General Section

Go to **Stores > Configuration > Newsman > General** to configure the core extension behavior.

### About

This section shows the extension version and provides the **Configure with Newsman Login** button for OAuth setup.

### General Settings

- **Active** - Enable or disable all Newsman features. When set to "No", the extension is completely inactive. Enabled by default.

- **Send User IP Address** - When a visitor subscribes, the extension can send their IP address to Newsman. This can help with analytics and compliance. Disabled by default.

- **Server IP Address** - A fallback IP address used when "Send User IP Address" is turned off. You can usually leave this empty.

### API (Credentials)

- **User ID** - Your Newsman User ID. Filled automatically if you used OAuth.

- **API Key** - Your Newsman API Key. This value is stored encrypted. Filled automatically if you used OAuth.

- **Test Credentials** - Click this button to verify that your User ID and API Key are correct. A success or error message will appear.

- **API Timeout** - How many seconds the extension waits for a response from Newsman before giving up. The default of 60 seconds works well for most setups. The minimum allowed value is 5 seconds.

- **Synchronize Lists and Segments** - Click this button to fetch all your lists and segments from Newsman. You need to do this before you can select a list or segment below.

- **List ID** - Select the Newsman email list that will receive your subscribers. You must click "Synchronize Lists and Segments" first to populate this dropdown.

- **Segment ID** - Optionally select a segment within the chosen list. Segments let you organize subscribers into groups. If you don't use segments, leave this empty.

### Export

These settings control how data is shared between your Magento store and Newsman.

- **Authorization Header Name / Key** - This is a legacy option for protecting your data exports with custom security credentials. If you connected via OAuth, you do not need to set these - the extension handles authentication automatically. You only need to fill these in if you set up the connection manually and want to add an extra layer of security to data exports.

- **Customer Attributes Map** - A dynamic table where you can map Magento customer attributes to Newsman fields. For example, you could map "date_of_birth" to a custom field in Newsman. Click **Add** to create a new mapping row. This is optional and only needed if you want to send extra customer data to Newsman beyond the standard fields.

- **Product Attributes Map** - A dynamic table where you can map Magento product attributes to Newsman fields. For example, you could map "manufacturer" or "color" to custom fields in Newsman. This is optional and only needed if you want to send extra product data.

- **Send customer telephone number** - When enabled, customer phone numbers from billing/shipping addresses are included in data exports to Newsman. Disabled by default.

- **Send telephone number from order** - When enabled, phone numbers from order billing/shipping addresses are included in data exports to Newsman. Disabled by default.

### Newsletter

- **Send Subscribe/Unsubscribe Emails From Newsman** - When set to "Yes" (the default), Newsman handles sending subscription confirmation and unsubscribe emails instead of Magento. This gives you more control over the email design through your Newsman account. Set to "No" if you want Magento to send these emails using its built-in templates.

### Developer Settings

These settings are intended for advanced users and developers. In most cases, you should leave them at their default values.

- **Logging Mode** - Controls how much detail the extension writes to its log file. The default is **Error**, which only logs problems. Set to **Debug** if you are troubleshooting an issue (but remember to set it back afterwards, as Debug mode creates large log files). Set to **None** to disable logging entirely. Available levels: None, Error (default), Warning, Info, Debug.

- **Log Clean** - Automatically deletes log files older than this number of days. The default is 90 days.

- **Activate Test User IP / Test User IP address** - For development and testing only. Lets you simulate a specific visitor IP address. Leave these off in production.

---

## Remarketing Section

Go to **Stores > Configuration > Newsman > Remarketing** to set up visitor tracking.

Remarketing lets Newsman track what pages and products your visitors view, so you can send them personalized emails (e.g., abandoned cart reminders, product recommendations).

### General Settings

- **Active** - Enable or disable the remarketing tracking pixel on your store. Enabled by default.

- **Newsman Remarketing ID** - This identifies your store in the Newsman tracking system. It is filled in automatically if you used OAuth. You can also find it in your Newsman account under remarketing settings.

- **Use Proxy** - When enabled (the default), all tracking requests are routed through your Magento server instead of being sent directly from the visitor's browser to Newsman. This improves privacy and can help with ad blockers. When disabled, tracking scripts are loaded directly from Newsman's servers.

- **Anonymize IP Address** - When turned on, visitor IP addresses are anonymized before being sent to Newsman. Recommended for GDPR compliance. Disabled by default.

- **Brand Attribute** - Select which Magento product attribute is used as the brand name in remarketing data. The default is "manufacturer". If your store uses a different attribute for brands, select it here.

- **Script** - The JavaScript tracking code used by the remarketing pixel. This is generated automatically and should not be edited manually unless instructed by Newsman support.

### What Gets Tracked

The remarketing pixel automatically tracks visitor activity on your store:

- **Product pages** - Records which products visitors view, including brand information
- **Category pages** - Records which categories visitors browse
- **Search results** - Records what visitors search for
- **Shopping cart** - Records cart contents and value
- **Order confirmation** - Records completed purchases with order value and items

### Developer Settings

- **Log Proxy Requests** - When enabled, logs all proxy/tunnel HTTP requests for debugging purposes. Leave this off unless you are troubleshooting remarketing issues.

---

## Frequently Asked Questions

### How do I know if the connection is working?

Go to **Stores > Configuration > Newsman > General > API (Credentials)** and click the **Test Credentials** button. If your User ID and API Key are correct, you will see a success message and your lists will be shown.

### I clicked "Synchronize Lists and Segments" but the dropdown is empty. What should I do?

Make sure your API Key and User ID are correct first by clicking **Test Credentials**. Every Newsman account has at least one list by default, so if the credentials are correct the lists will appear after synchronization.

### What is the difference between Customer Attributes Map and Product Attributes Map?

These are optional mapping tables that let you send extra data from Magento to Newsman. The Customer Attributes Map sends additional customer profile fields (like date of birth or customer group), and the Product Attributes Map sends additional product fields (like color or manufacturer). You only need these if you want to use this extra data in your Newsman campaigns or segments.

### What does "Use Proxy" do in Remarketing?

When enabled, the tracking scripts and data are routed through your Magento server rather than being loaded directly from Newsman's servers by the visitor's browser. This means ad blockers are less likely to block the tracking, and visitor browsers don't make direct connections to third-party servers, which is better for privacy.

### Where are the extension logs?

The extension writes logs to its own log files within Magento's `var/log/` directory. The logging level is controlled in Developer Settings. Log files older than the configured number of days (default: 90) are automatically cleaned up weekly.

### Can I configure different lists for different store views?

Yes. All settings support Magento's scope system. Use the **Store View** selector at the top of the configuration page to configure different lists, segments, or remarketing IDs for each store view.

### What happens when a customer subscribes to the newsletter?

When a customer subscribes through Magento's newsletter form, the extension automatically sends the subscription to Newsman using the configured list and segment. If "Send Subscribe/Unsubscribe Emails From Newsman" is enabled, Newsman will send the confirmation email instead of Magento.
