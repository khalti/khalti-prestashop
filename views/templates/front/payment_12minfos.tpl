{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<script src="https://khalti.com/static/khalti-checkout.js"></script>
<script type="text/javascript">
function termsCheck()
{
    var checkBox = document.getElementById("conditions_to_approve[terms-and-conditions]");
    if(checkBox.checked == true)
    {
        document.getElementById("payment-button").click();
    }
    else
    {
        alert("Please Agree to the Terms & Service before clicking on Khalti Button");
        //return false;
    }
}
</script>
<section>
	<p><button id="custom-payment-button" onclick="termsCheck();" style="background-color: #773292; color: #fff; border: none; padding: 5px 10px; border-radius: 2px">Pay with Khalti</button>
        <button id="payment-button" style="visibility:hidden"></button>
    </p>
	<p>{l s='Click the Button Above to Pay With Khalti' mod='khalti'}</p>
</section>

	    <script type="text/javascript">
        var amount_total = {$amount_total|escape:'htmlall':'UTF-8'};
        var public_key = "{$public_key|escape:'htmlall':'UTF-8'}";
        var order_id = {$order_id|escape:'htmlall':'UTF-8'};
        var validation_url = "{$validation_url|escape:'htmlall':'UTF-u'}";
        {literal}
        var config = {
            // replace the publicKey with yours
            "publicKey": public_key,
            "productIdentity": order_id,
            "productName": "Product",
            "productUrl": "url",
            "eventHandler": {
                onSuccess (payload) {
                    // hit merchant api for initiating verfication
                    var token = payload.token;
                    var amount = payload.amount;
                    window.location.href = validation_url+'?token='+token+'&amount='+amount;
                },
                onError (error) {
                    console.log(error);
                }
            }
        };
        var checkout = new KhaltiCheckout(config);
        var btn = document.getElementById("payment-button");
        btn.onclick = function () {
           checkout.show({amount: amount_total});
        }
        //checkout.show({amount: amount_total});
        {/literal}
    </script>
