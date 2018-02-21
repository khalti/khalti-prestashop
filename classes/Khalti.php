<?php
/**
 * Khalti.php
 *
 * Main file of the module
 *
 * @author  Bibek M Acharya <manibibek@gmail.com>
 * @version 1.0.0
 * @see     PaymentModuleCore
 */

/**
 * Payment Provider Class
 */
class Khalti
{
    /**
     * Create an array of the transaction parameters required by the PSP
     *
     * @todo   fetch the parameters according to the payment provider API
     * @return array
     */
    public static function getParameters()
    {
        $module = Context::getContext()->controller->module;
        $cart = Context::getContext()->cart;
        $customer = Context::getContext()->customer;

        return (array());
    }

    /**
     * Get the payment URL
     *
     * @return string
     */
    public static function getPaymentUrl()
    {
        return '';
    }
}
