<?php
/*
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
*/

/**
 * @since 1.5.0
 */
class KhaltiValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {

        $cart = $this->context->cart;
        $total = $cart->getOrderTotal(true, Cart::BOTH);
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active || $total == 0) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'khalti') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $this->context->smarty->assign([
            'params' => $_REQUEST,
        ]);

        $customer = new Customer($cart->id_customer);
        $currency = $this->context->currency;

        $this->setTemplate('module:khalti/views/templates/front/payment_validation.tpl');

        
        $token = isset( $_GET['token'] ) ? $_GET['token'] : "";
        $amount = isset( $_GET['amount'] ) ? $_GET['amount'] : "";

        $token = strip_tags(htmlspecialchars($token));
        $amount = strip_tags(htmlspecialchars($amount));

        $validate = $this->khalti_validate($token,$amount);
        $status_code = $validate['status_code'];
        $idx = $validate['idx'];
        $amount = $amount/100;
        //var_dump($validate);die();

        if($total == $amount && $idx!=null)
        {
            $message = $this->khalti_transaction($idx);
            $message = json_encode($message);
            $this->payment_validate($amount,$message,$customer,$currency,$cart,$total);
        }
        else
        {
            $message = "There is some error while submitting the Payment";
            $amount = 0;
            $this->payment_validate($amount,$message,$customer,$currency,$cart,$total);
        }


    }

    public function getSecretKey()
    {
        if (Configuration::get('khalti_payment_mode') == 1) {
            return Configuration::get('khalti_test_secret_key');
        } else {
            return Configuration::get('khalti_live_secret_key');
        }
    }

    public function payment_validate($amount_paid,$message,$customer,$currency,$cart,$total)
    {
        $this->module->validateOrder($cart->id, Configuration::get('PS_OS_PAYMENT') , $amount_paid, $this->module->displayName, $message, array(), (int)$currency->id, false, $customer->secure_key);
        Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
    }

    public function khalti_validate($token,$amount)
    {
        $args = http_build_query(array(
            'token' => $token,
            'amount'  => $amount
           ));

        $url = "https://khalti.com/api/payment/verify/";

        # Make the call using API.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$args);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

/*      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);*/

        $headers = ['Authorization: Key '.$this->getSecretKey()];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Response
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response = json_decode($response);
        $idx = @$response->idx;
        $data = array(
            "idx" => $idx,
            "status_code" => $status_code,
            "response" => $response
        );
        curl_close($ch);
        return $data;
    }

    public function khalti_transaction($idx)
    {
        $url = "https://khalti.com/api/merchant-transaction/$idx/";

        # Make the call using API.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

/*      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);*/

        $headers = ['Authorization: Key '.Configuration::get('khalti_secret_key')];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Response
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $response;
    }

}
