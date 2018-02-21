<?php
/**
 * notification.php
 *
 * Main file of the module
 *
 * @author  Bibek M Acharya <manibibek@gmail.com>
 * @version 1.0.0
 * @see     PaymentModuleCore
 */

/**
 * This class receives a POST request from the PSP and creates the PrestaShop
 * order according to the request parameters
 **/
class KhaltiNotificationModuleFrontController extends ModuleFrontController
{
    /*
     * Handles the Instant Payment Notification
     *
     * @todo You probably have to register the following notification URL on
     * the PSP side : http://yourstore.com/index.php?fc=module&module=khalti&controller=notification
     * @return bool
     */
    public function postProcess()
    {
        // We don't do anything if the module has been disabled by the merchant
        if ($this->module->active == false) {
            die;
        }

        //Restore the context to process the order validation properly
        $context = Context::getContext();
        $context->cart = new Cart((int)$cart_id);
        $context->customer = new Customer((int)$customer_id);
        $context->currency = new Currency((int)$context->cart->id_currency);
        $context->language = new Language((int)$context->customer->id_lang);

        $secure_key = $context->customer->secure_key;
        $module_name = $this->module->displayName;
        $currency_id = (int)$context->currency->id;

        if ($this->isValidOrder() === true) {
            $payment_status = Configuration::get('PS_OS_PAYMENT');
            $message = null;
        } else {
            $payment_status = Configuration::get('PS_OS_ERROR');

            // Add a message to explain why the order has not been validated
            $message = $this->getErrorMessage();
        }

        return $this->module->validateOrder(
            $cart_id,
            $payment_status,
            $amount,
            $module_name,
            $message,
            array(),
            $currency_id,
            false,
            $secure_key
        );
    }

    /**
     * Process the IPN data to find out if the transaction has been
     * approved or not
     *
     * @todo   implement the actual check
     * @return bool
     */
    protected function isValidOrder()
    {
        return true;
    }

    /**
     * Build the right error message according to the data in the IPN. This
     * message will be associated with the order
     *
     * @todo implement the function
     * @return string
     */
    protected function getErrorMessage()
    {
        return $this->module->l('An error occurred while processing payment');
    }
}
