# Magento2-Newsman

[NewsMAN](https://www.newsman.com) Plugin for Magento 2.0. Sync Your Magento Customers and Subscribers with the NewsMAN Lists and Segments

Simplify the connection between your shop and NewsMAN platform using this straightforward method. Generate an API KEY within your NewsMAN account, install the plugin, and effortlessly synchronize your shop customers and newsletter subscribers with NewsMANlists and segments. The installation process is quick and user-friendly.

# Installation

Installation should be done only by a programmer.

## Manual installation: 
1. Copy the *"app/code/Dazoot"* directory from this repository to your "app/code/" shop directory.

2. Edit file from *"app/etc/config.php"*

- Add these lines in the array:
 'Dazoot_Newsman' => 1,
 'Dazoot_Newsmansmtp' => 1

3. We need access to the server bash shell. And apply this command:

- ("root/yourmagentodirectory") php bin/magento setup:upgrade
```
Delete Cache 
for Magento 2.0x
```
- ("root/yourmagentodirectory/var/di") - delete di folder
```
Delete Cache 
for Magento 2.3x
```
- ("root/yourmagentodirectory/generated/metadata") - delete metadata folder

- ("root/yourmagentodirectory") php bin/magento setup:di:compile
- ("root/yourmagentodirectory") php bin/magento setup:static-content:deploy ro_RO en_US
- ("root/yourmagentodirectory") php bin/magento cache:flush

## Auto installation: 
1. Copy the *"app/code/Dazoot"* directory from this repository to your "app/code/" shop directory.

2. Configuration -> Web Setup Wizard -> Module Manager -> :

- Enable dazoot/module-newsman
- Enable dazoot/newsmanmarketing
- Dazoot_Newsmansmtp
	
# After installation

Clear cache from System -> Cache Management -> Flush Magento Cache and Flush CSS/JS Cache
	
# Configuration

## Newsman Sync

1. Go to **Stores > Configuration > Newsman > General Settings**
Fill in your [Newsman](https://www.newsmanapp.com) API KEY and User ID and click the Save Config button.

  ![General Settings](https://raw.githubusercontent.com/Newsman/Magento2-Newsman/master/assets/general_settings.png)

2. After the [Newsman](https://www.newsmanapp.com) API KEY and User ID are set, you can choose a list and (optional) segment and press Save Config.

3. You do a manual synchronization by clicking "Manual Sync".

  ![Synchronization Schedule](https://raw.githubusercontent.com/Newsman/Magento2-Newsman/master/assets/synchronization_schedule.png)

4. For the automatic synchronization to work, you need to have Magento's built-in cron job functionality enabled.

## Webhooks

- Unsubscribing `Newsletter Subscribers` from Magento Admin will automatically unsubscribe in Newsman List
- Unsubscribing `Newsletter Subscribers` from Newsman will automatically unsubscribe in Magento

## Newsman SMTP Configuration

  ![SMTP Configuration](https://raw.githubusercontent.com/Newsman/Magento2-Newsman/master/assets/smtp.png)

## Newsman Remarketing

  ![SMTP Configuration](https://raw.githubusercontent.com/Newsman/Magento2-Newsman/master/assets/marketing.png)

## Description

### Subscription Forms & Pop-ups:
- Create visually appealing forms and pop-ups to capture potential leads.
- Incorporate embedded registrations for newsletters or pop-ups triggered by exit intent.
- Maintain consistency in form presentation across different devices for a seamless user experience.
- Integrate forms with automated systems for timely responses and personalized welcome emails.

### Contact Lists & Segments Management:
- Efficiently import and synchronize contact lists from various sources.
- Simplify data management through segmentation strategies based on demographics or behavior.

### Email & SMS Marketing Campaigns:
- Easily send extensive campaigns, newsletters, or promotions to a broad subscriber base.
- Tailor campaigns for individual subscribers, addressing them by name and suggesting relevant products.
- Re-engage subscribers by re-sending campaigns to those who haven't opened the initial email.

### Email & SMS Marketing Automation:
- Automate the delivery of personalized product recommendations, follow-up emails, and strategies for addressing cart abandonment.
- Strategically tackle cart abandonment or showcase related products to encourage completed purchases.
- Collect post-purchase feedback to enhance customer satisfaction.

### Ecommerce Remarketing Strategies:
- Reconnect with subscribers through targeted offers based on past interactions.
- Personalize interactions with exclusive offers or reminders tailored to user behavior or preferences.

### SMTP Transactional Emails:
- Ensure the prompt and reliable delivery of crucial messages, such as order confirmations or shipping notifications, via SMTP.

### Comprehensive Email and SMS Analytics:
- Gain insights into open rates, click-through rates, conversion rates, and overall campaign performance.

Use the NewsMAN plugin for Magento to seamlessly streamline marketing efforts and establish effective connections with the audience.