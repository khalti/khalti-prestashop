# Khalti Payment Module for Prestashop

Tested and working upto `Prestashop 1.7.6.7`

### Installation
- Download the latest `khalti.zip` file from the [release](https://github.com/manibibek/khalti-prestashop/releases) section
- Go to Prestashop Backend > Module Manager > Upload a Module and Upload the zip file downloaded on the first part
- Add Public & Private keys provided by [Khalti](https://www.khalti.com) and you can start receiving payments via Khalti Payment Gateway

### Troubleshooting
- Directly downloading the zip file from the Repository will not recognize this plugin as a valid Plugin and will show `Invalid Zip file`. Always download from the [release](https://github.com/manibibek/khalti-prestashop/releases) section
- This plugin only supports `NPR` as a currency, so it won't show up on the payment options if the currency is different than NPR
- Some settings such as `Carrier Restrictions`, `Country Restrictions` might disable this Payment Gateway so go to `Improve > Payment > Preferences` and check for the necessary settings

### Contributing
- To make a contribution on this plugin, clone this repository on the `Modules` > `khalti` folder and make the necessary changes. 