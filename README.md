=== Paydock for WooCommerce ===

Contributors: paydock
Donate link: https://paydock.com/
Tags: paydock, woocommerce, payment, gateways, payment gateways
Requires PHP: 7.4
Requires at least: 6.0
Tested up to: 6.5.3
Stable tag: 3.0.5
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Accept more payment methods with Paydock. Connect multiple payment gateways with a central interface to manage the transactions.

== Description ==

Accept more payment methods with Paydock. Connect multiple payment gateways with a central interface to manage the transactions.

Use Paydock to add a payment gateway for credit cards, bank transfers, PayPal or even Buy now pay later gateways.

For a full list of payment gateways Paydock is connected to, visit [Paydock] (https://paydock.com/features/api/ "PayDock gateways")

== Installation ==

To install and configure the Paydock plugin, you need:

* Web Server - Nginx
* PHP 8.1
* MySQL version 8.0 or greater OR MariaDB 11.1 
* Support for HTTPS (SSL certificate)
* PHP memory limit of 256MB
* PHP Requirements (curl, gd2, mbstring, xml, json, and zip)

### Step-by-Step Installation

1. **Download the Plugin**
   - Download the zip file with the plugin:  
     [Paydock WooCommerce Plugin] (https://github.com/PayDock/e-commerce-woo/blob/main/paydock_v3.0.5.zip)

2. **Install the Plugin**
   - Go to WordPress -> Plugins -> Add new Plugin -> Upload Plugin

3. **Upload the zip file and activate the plugin**

4. **Watch the Tutorial**
   - Watch the video tutorial with step by step guidance: [Video Tutorial] (https://www.loom.com/share/e3baad357d4444c6967ef4b96377784b?sid=4f21b0af-43f2-4081-9ce7-76bf946fa535)

5. **Obtain Admin Credentials**
   - Press [here] (https://jetsoftpro.atlassian.net/wiki/spaces/Paydoc/pages/2607448306/Installing+plugin+the+first+time) to obtain your WordPress admin user credentials.

To download the latest version of Paydock's WooCommerce plugin, you can manually trigger a build and download the generated artefact directly from GitHub:

1. **Trigger the Build**
   - Visit the Actions tab in Paydock's GitHub repository: [Paydock GitHub] (https://github.com/PayDock/e-commerce-woo)
   - Under Workflows, find the workflow named "Build and upload the Paydock plugin"
   - Click on "Run workflow"
   - Select the branch and click the green "Run workflow" button

2. **Download the Plugin**
   - Once the workflow completes, click on the run that you triggered in the previous step
   - Scroll down to the Artifacts at the bottom of the page
   - Click on the link to download the ZIP file

== Screenshots ==

1. Frontend
2. Admin side settings
3. API side

== Changelog ==

= 1.0.19 =
* Initial release

= 1.0.28 =
* Bug fixes

= 1.4.0 =
* First release on the plugins store

= 1.5.7 =
* Bug fixes

= 2.0.37 =
* Completely new plugin. This version includes new code, supports new versions of PHP, MySQL, WordPress, WooCommerce, and v2 API of the Paydock app.

= 2.0.44 =
* New features, readme, changelog, etc.

= 2.0.46 =
* Patch, small fixes

= 2.0.53 =
* Min-max feature

= 3.0.4 =
* Fixes, updates, tweaks

= 3.0.5 =
* Statuses, openssl, paths
