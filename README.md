<p align="center">
  <img src="https://static1.squarespace.com/static/5cb44d76523958d6ab569d39/t/5ce2788262f60d0001cc6580/1587470398362/?format=250w">
</p>
<p align="center">
  <a href="https://packagist.org/packages/trunkrs/magento-2-carrier-plugin"><img src="https://poser.pugx.org/trunkrs/magento-2-carrier-plugin/downloads" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/trunkrs/magento-2-carrier-plugin"><img src="https://poser.pugx.org/trunkrs/magento-2-carrier-plugin/v/stable" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/trunkrs/magento-2-carrier-plugin"><img src="https://poser.pugx.org/trunkrs/magento-2-carrier-plugin/license" alt="License"></a>
</p>

## Trunkrs Shipping Method Plugin for Magento 2

The official Trunkrs Shipping method plugin for Magento 2. 
This plugin allows you to integrate your Magento 2 webshop to [Trunkrs Shipping Portal](https://lightspeed.trunkrs.app/) and start managing your shipments.

## WHAT IS REQUIRED?

A client can install and use the plugin using API credentials for authentication. The API is protected by static header values in the form of:

A **client ID** provided by the header **X-API-ClientID**  
A **client secret** provided by the header **X-API-ClientSecret**

The clientId and secret combination identifies a client as an authorized client and gives them access to their client-specific records.
To obtain a clientId and secret, please contact Trunkrs at **sales@trunkrs.nl**

## TECHNICAL INFORMATION

Compatible with Magento 2.3.0 CE or later  
PHP Version 7.1.24 or later

## INSTALLATION

Run the following command: 
```bash
composer require trunkrs/magento-2-carrier-plugin
```

```bash
bin/magento setup:upgrade  
bin/magento setup:static-content:deploy -f  
bin/magento cache:flush
```  

Done.

## WORKING WITH THE PLUGIN

[click here...](https://trunkrs.atlassian.net/wiki/spaces/CUS/pages/55836676/Magento+2+Plugin+integration+manual#WORKING-WITH-OUR-MAGENTO-2-PLUGIN)

## License
The Trunkrs Shipping method plugin for Magento 2 is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
