<?php
/**
 * redirect.php
 *
 * Main file of the module
 *
 * @author  Bibek M Acharya <manibibek@gmail.com>
 * @version 1.0.0
 * @see     PaymentModuleCore
 */

/*
 * This front controller builds the payment request and then redirects the
 * customer to the PSP website so that he can pay safely
 */
class KhaltiRedirectModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * Initialize common front page content
     *
     * @see    FrontControllerCore::initContent()
     * @return void
     */
    public function initContent()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;
        parent::initContent();
    }

    /**
     *
     */
    public function postProcess()
    {
        require(dirname(__FILE__) . '/../../classes/Khalti.php');

        /**
         * TODO : Here you have to build all the parameters required by the PSP
         */
        $this->context->smarty->assign(
            array(
                'paymenturl' => Khalti::getPaymentUrl(),
            )
        );
        return $this->setTemplate('redirect.tpl');
    }
}
