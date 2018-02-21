<?php
/**
 * khalti.php
 *
 * Main file of the module
 *
 * @author  Bibek M Acharya <manibibek@gmail.com>
 * @version 1.0.0
 * @see     PaymentModuleCore
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}
/**
 * Main class of the module
 */
class Khalti extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

        /* tab section shape */
    private $section_shape = 1;

    // public $addons_track;

    public $errors = array();
    public $warnings = array();
    public $infos = array();
    public $success = array();

        /* status */
    const _FLAG_NULL_ = 0;

    const _FLAG_ERROR_ = 1;
    const _FLAG_WARNING_ = 2;
    const _FLAG_SUCCESS_ = 4;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->name = 'khalti';
        $this->tab = 'payments_gateway';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Bibek M Acharya';
        $this->need_instance = 1;
        $this->controllers = array('validation','payment');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();



        $this->displayName = $this->l('Khalti');
        $this->description = $this->l('Payment Gateway Module for Khalti. Start accepting payments from Khalti. Check all transactions and refund payments right from the dashboard');
        $this->confirmUninstall = $this->l('You will no longer be able to accept payments from Khalti. Are you sure you want to uninstall this module? ');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    /**
     * Install the module on the store
     *
     * @see    Module::install()
     * @todo   bootstrap the configuration requirements of Khalti
     * @throws PrestaShopException
     * @return bool
     */
    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn')) {
            return false;
        }
        return true;
    }

    /**
     * Uninstall the module
     *
     * @see    Module::uninstall()
     * @todo   remove the configuration requirements of Khalti
     * @throws PrestaShopException
     * @return bool
     */
    public function uninstall()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    /**
     * Entry point to the module configuration page
     *
     * @see Module::getContent()
     * @return string
     */
    public function getContent()
    {
        /* Do Log In  */
        $this->contentLogIn();

        if (!Configuration::get('khalti_live_secret_key') && !Configuration::get('khalti_live_public_key')
            && !Configuration::get('khalti_live_secret_key') && !Configuration::get('khalti_test_public_key')) {
            $this->warnings['connection'] = false;
        }

        $this->context->smarty->assign(array('module_dir' => $this->_path));

        $html = $this->loadRessources();
        $this->showHeadMessages($html);

        return $html;
    }

       private function hasErrors()
    {
        return !!$this->errors;
    }

    private function hasWarnings()
    {
        return !!$this->warnings;
    }

    private function hasInfos()
    {
        return !!$this->infos;
    }

    private function hasSuccess()
    {
        return !!$this->success;
    }

    public static function arrayAsHtmlList(array $ar = array())
    {
        if (!empty($ar)) {
            return '<ul><li>'.implode('</li><li>', $ar).'</li></ul>';
        }
        return '';
    }

        /*
     ** @method: showHeadMessages
     ** @description: show errors
     **
     ** @arg: $key
     ** @return: key if configuration has key else throw new exception
     */
    public function showHeadMessages(&$terror = '')
    {
        $msgs_list = array_map('array_filter', array(
            'displayInfos' => $this->infos,
            'displayWarning' => $this->warnings,
            'displayError' => $this->errors,
            'displayConfirmation' => $this->success,
        ));

        foreach ($msgs_list as $display => $msgs) {
            if (!empty($msgs)) {
                $terror = call_user_func(array($this, $display), '<p>Khalti</p>'.self::arrayAsHtmlList($msgs)).$terror;
            }
        }

        return (!empty($terror) ? $terror : ($terror = $this->displayError('Unknow error(s)')));
    }


    /**
     * Retrieve the current configuration values.
     *
     * @see $this->renderForm
     *
     * @return array
     */
    protected function getConfigFormValues()
    {
        return array(
            'khalti_test_public_key' => Configuration::get('khalti_test_public_key'),
            'khalti_test_secret_key' => Configuration::get('khalti_test_secret_key'),
            'khalti_live_public_key' => Configuration::get('khalti_live_public_key'),
            'khalti_live_secret_key' => Configuration::get('khalti_live_secret_key'),
            'khalti_payment_mode' => Configuration::get('khalti_payment_mode')
        );
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $currency = $this->context->currency;
        if($currency->iso_code != 'NPR')
        {
            return;
        }

        //check if the configuration is missing
        if(!$this->getSecretKey() OR !$this->getPublicKey())
         {
            die('<b>Khalti Payment Gatewy Error:</b> Configuration Not Complete. Please Make Sure You have Entered the Public and Secret Key in the Khalti Configuration Panel');
         }

        $payment_options = [
            $this->getExternalPaymentOption(),
        ];

        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getSecretKey()
    {
        if (Configuration::get('khalti_payment_mode') == 1) {
            return Configuration::get('khalti_test_secret_key');
        } else {
            return Configuration::get('khalti_live_secret_key');
        }
    }

    public function getPublicKey()
    {
        if (Configuration::get('khalti_payment_mode') == 1) {
            return Configuration::get('khalti_test_public_key');
        } else {
            return Configuration::get('khalti_live_public_key');
        }
    }

    public function getExternalPaymentOption()
    {
        $cart = $this->context->cart;
        $public_key = $this->getPublicKey();
        $order_id = $cart->id;
        $validation_url = $this->context->link->getModuleLink('khalti', 'validation', array(), true);
        $total = $cart->getOrderTotal(true, Cart::BOTH);
        $this->context->smarty->assign(
            array(
                'amount_total' => $total*100,
                'public_key' => $public_key,
                'order_id' => $order_id,
                'validation_url' => $validation_url
            )
        );

        $externalOption = new PaymentOption();
        $externalOption->setCallToActionText($this->l('Pay Using Khalti'))
                       ->setAdditionalInformation($this->context->smarty->fetch('module:khalti/views/templates/front/payment_12minfos.tpl'))
                       ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/payment.jpg'));

        return $externalOption;
    }

    private function loadRessources()
    {
        $content = array(
            $this->displayStarted(),
            $this->displayForm(),
            $this->displayTransaction(),
            $this->displayFAQ()
        );

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
            $domain = Tools::getShopDomainSsl(true, true);
        } else {
            $domain = Tools::getShopDomain(true, true);
        }

        $tab_contents = array(
            'title' => $this->l('Khalti'),
            'contents' => array(
                array(
                    'name' => $this->l('Get Started'),
                    'icon' => 'icon-book',
                    'value' => $content[0],
                    'badge' => $this->getBadgesClass(),
                ),
                array(
                    'name' => $this->l('Key Configuration'),
                    'icon' => 'icon-power-off',
                    'value' => $content[1],
                    'badge' => $this->getBadgesClass(),
                ),
                array(
                    'name' => $this->l('Payments'),
                    'icon' => 'icon-credit-card',
                    'value' => $content[2],
                    'badge' => $this->getBadgesClass(),
                ),
                array(
                    'name' => $this->l('Contact and FAQ'),
                    'icon' => 'icon-question',
                    'value' => $content[3],
                    'badge' => $this->getBadgesClass(),
                ),
            ),
            'logo' => $domain.__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/views/img/Khalti-logo.png'
        );


        $this->context->smarty->assign('tab_contents', $tab_contents);
        $this->context->smarty->assign('ps_version', _PS_VERSION_);
        $this->context->smarty->assign('new_base_dir', $this->_path);
        $this->context->controller->addJs($this->_path.'/views/js/faq.js');
        $this->context->controller->addCss($this->_path.'/views/css/started.css');
        $this->context->controller->addCss($this->_path.'/views/css/tabs.css');
        $this->context->controller->addJs($this->_path.'/views/js/back.js');

        //load databales
        $this->context->controller->addCss($this->_path.'/views/css/dataTables.bootstrap.css');
        $this->context->controller->addJs($this->_path.'/views/js/jquery.dataTables.min.js');
        $this->context->controller->addJs($this->_path.'/views/js/dataTables.bootstrap.min.js');

        return $this->display($this->_path, 'views/templates/admin/main.tpl');
    }

    public function contentLogIn()
    {

        if (Tools::isSubmit('submit_login')) {
                $khalti_test_secret_key = Tools::getValue('khalti_test_secret_key');
                $khalti_test_public_key = Tools::getValue('khalti_test_public_key');
                $khalti_live_secret_key = Tools::getValue('khalti_live_secret_key');
                $khalti_live_public_key = Tools::getValue('khalti_live_public_key');
                if (!empty($khalti_test_secret_key) && !empty($khalti_test_public_key) && !empty($khalti_live_secret_key) && !empty($khalti_live_public_key)) {  
                        Configuration::updateValue('khalti_test_secret_key', Tools::getValue('khalti_test_secret_key'));
                        Configuration::updateValue('khalti_test_public_key', Tools::getValue('khalti_test_public_key'));                 
                        Configuration::updateValue('khalti_live_secret_key', Tools::getValue('khalti_live_secret_key'));
                        Configuration::updateValue('khalti_live_public_key', Tools::getValue('khalti_live_public_key'));

                        $this->success[''] = 'Successfully Updated';
                } else {
                    $this->errors['empty'] = 'Client ID and Secret Key fields are mandatory';
                }
                Configuration::updateValue('khalti_payment_mode', Tools::getValue('khalti_payment_mode'));
            }
    }
    /*
     ** Display Form for the Key Configuration
     */
    public function displayForm()
    {
        $fields_form = array();
        $fields_value = array();
        $type = 'switch';

        $fields_form[0]['form'] = array(
            'input' => array(
                        array(
                        'type' => 'select',
                        'label' => $this->l('Khalti Mode'),
                        'hint' => $this->l('Choose Between Live Mode or Test Mode'),
                        'name' => 'khalti_payment_mode',
                        'options' => array(
                            'query' => $options = array(
                                        array(
                                          'id_option' => 1,      
                                          'name' => 'Test Mode'  
                                        ),
                                        array(
                                          'id_option' => 2,
                                          'name' => 'Live Mode'
                                        ),
                            ),
                            'id' => 'id_option',
                            'name' => 'name' 
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Live Public Key'),
                        'name' => 'khalti_live_public_key',
                        'id' => 'khalti_live_public_key',
                        'class' => 'fixed-width-xxl',
                        'size' => 50,
                        'required' => true,
                        'desc' => $this->l('Enter the Live Public Key Provided by Khalti')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Live Secret Key'),
                        'name' => 'khalti_live_secret_key',
                        'size' => 50,
                        'id' => 'khalti_live_secret_key',
                        'class' => 'fixed-width-xxl',
                        'required' => true,
                        'desc' => $this->l('Enter the Live Secret Key Provided by Khalti')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Test Public Key'),
                        'name' => 'khalti_test_public_key',
                        'id' => 'khalti_test_public_key',
                        'class' => 'fixed-width-xxl',
                        'size' => 50,
                        'required' => true,
                        'desc' => $this->l('Enter the Test Public Key Provided by Khalti')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Test Secret Key'),
                        'name' => 'khalti_test_secret_key',
                        'id' => 'khalti_test_secret_key',
                        'size' => 50,
                        'class' => 'fixed-width-xxl',
                        'required' => true,
                        'desc' => $this->l('Enter the Test Secret Key Provided by Khalti')
                    ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-default pull-right button',
                    ),
                );

        $submit_action = 'submit_login';
        $fields_value = array_merge($fields_value, array(
            'khalti_payment_mode' => Configuration::get('khalti_payment_mode'),
            'khalti_test_public_key' => Configuration::get('khalti_test_public_key'),
            'khalti_test_secret_key' => Configuration::get('khalti_test_secret_key'),
            'khalti_live_public_key' => Configuration::get('khalti_live_public_key'),
            'khalti_live_secret_key' => Configuration::get('khalti_live_secret_key'),
        ));

        $html = $this->display($this->_path, 'views/templates/admin/configuration.tpl');
        return $this->renderGenericForm($fields_form, $fields_value, $this->getSectionShape(), $submit_action).$html;
    }

    /*
     ** Display All Khalti transactions
     */
    public function displayTransaction()
    {
            $transaction_id = Tools::getValue('transaction_id');
            $action = Tools::getValue('action');
            if($action == 'refund' && $transaction_id)
            {
                $refund = $this->khaltiRefund($transaction_id);
                $status_code = $refund['StatusCode'];
                $detail = json_decode($refund['Response']);
                $detail = $detail->detail;
                if($status_code == 200)
                {
                    $this->success[''] = $detail;
                }
                else
                {
                    $this->errors['empty'] = $detail;
                }
            }
            $payment_info = NULL;
            $transaction_detail_array = array();
            if($transaction_id)
            {
                $payment_info = 1;
                $transaction_detail = json_decode($this->getTransactionDetail($transaction_id)['Response']);
                //
                $transaction_detail_array = array(
                    "idx" => $transaction_detail->idx,
                    "source" => $transaction_detail->source->name,
                    "mobile" => $transaction_detail->source->mobile,
                    "amount" => $transaction_detail->amount/100,
                    "fee_amount" => $transaction_detail->fee_amount/100,
                    "date" => date("Y/m/d H:m:s", strtotime($transaction_detail->created_on)),
                    "state" => $transaction_detail->refunded == true ? "Refunded" : $transaction_detail->state->name,
                    "refunded" => $transaction_detail->refunded,
                );
            }
            $transaction = array();
            $html = '';
            $getTransaction = $this->getTransaction()['Response'];
            $getTransaction = json_decode($getTransaction);
            //var_dump($getTransaction);die();
            foreach($getTransaction->records as $t)
            {
                array_push($transaction, array(
                    'idx' => $t->idx,
                    'source' => $t->source->name,
                    'amount' => $t->amount/100,
                    'fee' => $t->fee_amount/100,
                    'date' => date("Y/m/d H:m:s", strtotime($t->created_on)),
                    'type' => $t->type->name,
                    'state' => $t->refunded == true ? "Refunded" : $t->state->name,
                    'refunded' => $t->refunded
                ));
            }

            $path = "?controller=AdminModules&configure=khalti&token=".Tools::getAdminTokenLite('AdminModules', $this->context);
            $this->context->smarty->assign('payment_info',$payment_info);
            $this->context->smarty->assign('transaction_id',$transaction_id);
            $this->context->smarty->assign('transaction',$transaction);
            $this->context->smarty->assign('transaction_detail_array',$transaction_detail_array);
            $this->context->smarty->assign('path',$path);

            $html .= $this->display($this->_path, 'views/templates/admin/transaction.tpl');

            return $html;
    }

    public function getTransaction()
    {
        $url = "https://khalti.com/api/merchant-transaction/";

        # Make the call using API.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $headers = ['Authorization: Key '.$this->getSecretKey()];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Response
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array(
            "Response" => $response,
            "StatusCode" => $status_code
        );
    }

    public function getTransactionDetail($idx)
    {
        $url = "https://khalti.com/api/merchant-transaction/{$idx}/";

        # Make the call using API.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $headers = ['Authorization: Key '.$this->getSecretKey()];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Response
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
            return array(
                "Response" => $response,
                "StatusCode" => $status_code
            );
    }

    public function khaltiRefund($idx)
    {
        $url = "https://khalti.com/api/merchant-transaction/{$idx}/refund/";
        # Make the call using API.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = ['Authorization: Key '.$this->getSecretKey()];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Response
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
            return array(
                "Response" => $response,
                "StatusCode" => $status_code
            );
    }

    /*
     ** Generate Contact
     */
    public function displayFaq()
    {
        return $this->display($this->_path, 'views/templates/admin/faq.tpl');
    }


    /*
     ** @Method: displayStarted
     ** @description: Display the first page on Khalti Configuration
     **
     ** @arg: (none)
     ** @return: (none)
     */
    public function displayStarted()
    {
        $return_url = '';
        if (Configuration::get('PS_SSL_ENABLED')) {
            $domain = Tools::getShopDomainSsl(true);
        } else {
            $domain = Tools::getShopDomain(true);
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $return_url = urlencode($domain.$_SERVER['REQUEST_URI'].'#khalti_step_2');
        }
        $this->context->smarty->assign('return_url', $return_url);
        return $this->display($this->_path, 'views/templates/admin/started.tpl');
    }

    /*
     ** @Method: renderGenericForm
     ** @description: render generic form for prestashop
     **
     ** @arg: $fields_form, $fields_value, $submit = false, array $tpls_vars = array()
     ** @return: (none)
     */
    public function renderGenericForm($fields_form, $fields_value = array(), $fragment = false, $submit = false, array $tpl_vars = array())
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        if ($fragment !== false) {
            $helper->token .= '#'.$fragment;
        }

        if ($submit) {
            $helper->submit_action = $submit;
        }

        $helper->tpl_vars = array(
            'fields_value' => $fields_value,
            'id_language' => $this->context->language->id,
            'back_url' => $this->context->link->getAdminLink('AdminModules')
            .'&configure='.$this->name
            .'&tab_module='.$this->tab
            .'&module_name='.$this->name.($fragment !== false ? '#'.$fragment : '')
        );

        return $helper->generateForm($fields_form);
    }

        /*
     ** @Method: getSectionShape
     ** @description: get section shape fragment
     **
     ** @arg:
     ** @return: (none)
     */
    private function getSectionShape()
    {
        return 'khalti_step_'.(int)$this->section_shape++;
    }

    public function getBadgesClass(array $keys = array())
    {
        $class = self::_FLAG_NULL_;

        if (!empty($keys)) {
            foreach ($keys as $key) {
                if (isset($this->errors[$key])) {
                    $class |= self::_FLAG_ERROR_;
                } elseif (isset($this->warnings[$key])) {
                    $class |= self::_FLAG_WARNING_;
                } else {
                    $class |= self::_FLAG_SUCCESS_;
                }
            }

            if ($class & self::_FLAG_ERROR_) {
                return 'tab-error';
            } elseif ($class & self::_FLAG_WARNING_) {
                return 'tab-warning';
            } elseif ($class & self::_FLAG_SUCCESS_) {
                return 'tab-success';
            }
        }

        return false;
    }

    /**
     * Loads asset resources
     */
    public function loadAssetCompatibility()
    {
        $css_compatibility = $js_compatibility = array();

        $css_compatibility = array(
            $this->_path.'/views/css/compatibility/font-awesome.min.css',
            $this->_path.'/views/css/compatibility/bootstrap-select.min.css',
            $this->_path.'/views/css/compatibility/bootstrap-responsive.min.css',
            $this->_path.'/views/css/compatibility/bootstrap.min.css',
            $this->_path.'/views/css/tabs15.css',
            $this->_path.'/views/css/compatibility/bootstrap.extend.css'
        );
        $this->context->controller->addCSS($css_compatibility, 'all');

        // Load JS
        $js_compatibility = array(
            $this->_path.'/views/js/compatibility/bootstrap-select.min.js',
            $this->_path.'/views/js/compatibility/bootstrap.min.js'
        );

        $this->context->controller->addJS($js_compatibility);
    }

}
