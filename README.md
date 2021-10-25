The RoutiGo extension for Magento 2

## Installation using Composer
<pre>composer require routigo/routigo-magento2</pre>

## Installation without using Composer
_Clone_ or _download_ the contents of this repository into `app/code/RoutiGo/RoutiGo`.

### Development Mode
After installation, run `bin/magento setup:upgrade` to make the needed database changes and remove/empty Magento 2's generated files and folders.

### Production Mode
After installation, run:
1. `bin/magento setup:upgrade`
2. `bin/magento setup:di:compile`
3. `bin/magento setup:static-content:deploy [locale-codes, e.g. nl_NL en_US]`
4. `bin/magento cache:flush`

Done!

## Configuration
To use this module you need to fill in your API key under Stores->Configuration->Sales->RoutiGo.
