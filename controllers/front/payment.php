<?php
/**
 * universalpay
 *
 * @author    0RS <admin@prestalab.ru>
 * @link http://prestalab.ru/
 * @copyright Copyright &copy; 2009-2016 PrestaLab.Ru
 * @license   http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version 2.2.1
 */
class KhaltiPaymentModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;
    public $display_column_right = false;
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

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

        if(!Configuration::get('khalti_public_key') OR !Configuration::get('khalti_secret_key'))
         {
            die($this->module->l('Configuration Not Complete. Please Make Sure You have Entered the Public and Secret Key in the Khalti Configuration Panel','payment'));
         }

        
        $public_key = Configuration::get('khalti_public_key');
        $order_id = $cart->id;
        $validation_url = $this->context->link->getModuleLink('khalti', 'validation', array(), true);
        $this->context->smarty->assign(
            array(
                'amount_total' => $total*100,
                'public_key' => $public_key,
                'order_id' => $order_id,
                'validation_url' => $validation_url
            )
        );
        $this->setTemplate('module:khalti/views/templates/front/payment_khalti.tpl');

    }
}
