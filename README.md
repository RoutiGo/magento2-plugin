<p align="center">
<img src="view/adminhtml/web/images/routigo-logo-large.png" alt="PostNL Logo" style="width:300px">
</p>

# RoutiGo Magento 2

## Requirements
- Magento version 2.4.5, 2.4.4, 2.4.3
- PHP 7.4+

## Installation
We strongly recommend that you use a Staging Environment for the installation, and to also make a backup of your environment.

### Installation using composer (recommended)
To install the extension login to your environment using SSH. Then navigate to the Magento 2 root directory and run the following commands in the same order as described:

Enable maintenance mode:
~~~~shell
php bin/magento maintenance:enable
~~~~

1. Install the extension:
~~~~shell
composer require tig/routigo-magento2
~~~~

2. Enable the RoutiGo Magento 2 extension
~~~~shell
php bin/magento module:enable TIG_RoutiGo
~~~~

3. Update the Magento 2 environment:
~~~~shell
php bin/magento setup:upgrade
~~~~

When your Magento environment is running in production mode, you also need to run the following comands:

4. Compile DI:
~~~~shell
php bin/magento setup:di:compile
~~~~

5. Deploy static content:
~~~~shell
php bin/magento setup:static-content:deploy
~~~~

6. Disable maintenance mode:
~~~~shell
php bin/magento maintenance:disable
~~~~

### Installation manually
1. Download the extension directly from github by clicking on *Code* and then *Download ZIP*.
2. Create the directory *app/code/TIG/RoutiGo* (Case-sensitive)
3. Extract the zip and upload the code into *app/code/TIG/RoutiGo*
4. Enable the RoutiGo Magento 2 extension
~~~~shell
php bin/magento module:enable TIG_RoutiGo
~~~~

5. Update the Magento 2 environment:
~~~~shell
php bin/magento setup:upgrade
~~~~

## Configuration
After installing the RoutiGo Magento 2 extension it can be found within the Magento backend under *Stores > Configuration > Sales > RoutiGo*.

### Account settings
To activate the plugin it needs to be set on Test or Live mode and a working Api Key needs to be filled. The key can be tested using the button "Validate Webhook", this needs to be done after saving the settings for the Api key.

### Settings
Here the cutoff time can be defined, when an order is placed after this time an extra day will be added to the shipping duration. Also the shipment days can be setup here.

### Timeframes
By enabling timeframes you can give the customers the option to select a preferred time within the checkout of the webshop.

### Upload
Orders placed with a RoutiGo shipping method can be send to RoutiGo based on the order status. When the order is send to RoutiGo, it is possible to give the order a specific status to keep an overview which ones have been send to RoutiGo.

### Shipping Costs
The shipping costs can be defined within the RoutiGo shipping method. This can be found in the backend under *Stores > Configuration > Sales > Deliver Methods > RoutiGo".