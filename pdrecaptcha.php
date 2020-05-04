<?php
/**
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
* needs please refer to https://www.prestademia.com for more information.
*
*  @author    Prestademia <contacto@prestademia.com>
*  @copyright 2020 Prestademia
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Pdrecaptcha extends Module
{
    public function __construct()
    {
        $this->name         = 'pdrecaptcha';
        $this->tab          = 'front_office_features';
        $this->version      = '1.0.0';
        $this->author       = 'Prestademia';
        $this->website      = "prestademia.com";

        $this->bootstrap    = true;
        parent::__construct();

        $this->displayName  = $this->l('ReCaptcha v3 invisible');
        $this->description  = $this->l('Add a ReCaptcha v3 invisible to your contact form ');
        $this->ps_versions_compliancy = array('min' => '1.6.0.0', 'max' => '1.8.0.0');

        if (function_exists('curl_init') == false) {
            $this->warning  = $this->l('In order to use this module, activate cURL (PHP extension).');
        }
    }

    protected function getConfigurations()
    {
        $configurations = array(
            'PD_RECAPTCHA_SITE_KEY'             => '',
            'PD_RECAPTCHA_PRIVATE_KEY'          => '',
            'PD_RECAPTCHA_ENABLE_CONTACT'       => 0,
            'PD_RECAPTCHA_NAME_BUTTON_CONTACT'  => 'submitMessage',
            'PD_RECAPTCHA_SCORE'                => 2,
        );

        return $configurations;
    }

    public function install()
    {
        $configurations = $this->getConfigurations();

        foreach ($configurations as $name => $config) {
            Configuration::updateValue($name, $config);
        }

        return parent::install() &&
        $this->registerHook('displayHeader') &&
        $this->registerHook('actionBeforeContactSubmit');
    }

    public function uninstall()
    {
        $configurations = $this->getConfigurations();

        foreach (array_keys($configurations) as $config) {
            Configuration::deleteByName($config);
        }

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';
        $result = '';

        if ((bool)Tools::isSubmit('submitSettings')) {
            if (!$result = $this->preValidateForm()) {
                $output .= $this->postProcess();
                $output .= $this->displayConfirmation($this->l('Save all settings.'));
            } else {
                $output = $result;
                $output .= $this->renderTabForm();
            }
        }

        if (!$result) {
            $output .= $this->renderTabForm();
        }

        $out = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        return $out.$output;
    }

    protected function preValidateForm()
    {
        $errors = array();

        if (Tools::isEmpty(Tools::getValue('PD_RECAPTCHA_SITE_KEY'))) {
            $errors[] = $this->l('Site Key is required.');
        }

        if (Tools::isEmpty(Tools::getValue('PD_RECAPTCHA_PRIVATE_KEY'))) {
            $errors[] = $this->l('Private Key is required.');
        }

        if (Tools::isEmpty(Tools::getValue('PD_RECAPTCHA_NAME_BUTTON_CONTACT'))) {
            $errors[] = $this->l('Name button Contact is required.');
        }

        if (count($errors)) {
            return $this->displayError(implode('<br />', $errors));
        }

        return false;
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFieldsValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }
    
    public function getConfigFieldsValues()
    {
        $fields = array();
        $configurations = $this->getConfigurations();

        foreach (array_keys($configurations) as $config) {
            $fields[$config] = Configuration::get($config);
        }

        return $fields;
    }

    protected function renderTabForm()
    {
        $fields_form    =   array(
                                    'form'  =>  array(
                                                        'legend'        =>  array(
                                                                                    'title'     => $this->l('ReCaptcha configuration'),
                                                                                    'icon'      => 'icon-cogs'
                                                                            ),
                                                        'tabs'          =>  array(
                                                                                    'general'   => $this->l('General'),
                                                                                    'advanced'  => $this->l('Advanded')
                                                                            ),
                                                        'description'   =>  $this->l('To get your own site and private keys please click on the folowing link').'<br /><a href="https://www.google.com/recaptcha/intro/index.html" target="_blank">https://www.google.com/recaptcha/intro/index.html</a>',
                                                        'input'         =>  array(
                                                                                    array(
                                                                                            'type' => 'text',
                                                                                            'label' => $this->l('ReCaptcha site key'),
                                                                                            'name' => 'PD_RECAPTCHA_SITE_KEY',
                                                                                            'size'=> 70,
                                                                                            'required' => true,
                                                                                            'empty_message' => $this->l('Please fill the recaptcha site key'),
                                                                                            'tab' => 'general'
                                                                                    ),
                                                                                    array(
                                                                                            'type' => 'text',
                                                                                            'label' => $this->l('Captcha private key'),
                                                                                            'name' => 'PD_RECAPTCHA_PRIVATE_KEY',
                                                                                            'size'=> 70,
                                                                                            'required' => true,
                                                                                            'empty_message' => $this->l('Please fill the recaptcha private key'),
                                                                                            'tab' => 'general'
                                                                                    ),
                                                                                    array(
                                                                                        'type' => 'switch',
                                                                                        'label' => $this->l('Enable ReCaptcha for contact form'),
                                                                                        'name' => 'PD_RECAPTCHA_ENABLE_CONTACT',
                                                                                        'class' => 't',
                                                                                        'is_bool' => true,
                                                                                        'tab' => 'general',
                                                                                        'values' => array(
                                                                                            array(
                                                                                                'id' => 'active_on',
                                                                                                'value'=> 1,
                                                                                                'label'=> $this->l('Enabled'),
                                                                                            ),
                                                                                            array(
                                                                                                'id' => 'active_off',
                                                                                                'value'=> 0,
                                                                                                'label'=> $this->l('Disabled'),
                                                                                            ),
                                                                                        ),
                                                                                    ),
                                                                                    array(
                                                                                            'type' => 'html',
                                                                                            'name' => 'advanced-warning',
                                                                                            'html_content' => '<div class="alert alert-warning">'
                                                                                                .$this->l('Use with caution, invalid parameters may made the module not to works properly')
                                                                                                .'</div>',
                                                                                            'tab' => 'advanced'
                                                                                    ),
                                                                                    array(
                                                                                            'type' => 'text',
                                                                                            'label' => $this->l('Custom name button contact form'),
                                                                                            'hint' => $this->l('Use with caution, invalid parameters may made the module not to works properly'),
                                                                                            'desc' => $this->l('For specific theme you can edit name od the recaptcha submit button'),
                                                                                            'name' => 'PD_RECAPTCHA_NAME_BUTTON_CONTACT',
                                                                                            'required' => false,
                                                                                            'tab' => 'advanced'
                                                                                    ),
                                                                                    array(
                                                                                        'type'      => 'select',
                                                                                        'label'     =>  $this->l('Score'),
                                                                                        'name'      => 'PD_RECAPTCHA_SCORE',
                                                                                        'options'   => array(
                                                                                                            'query' => array(
                                                                                                                array('id' => 1, 'name' => $this->l('High (0.9)')),
                                                                                                                array('id' => 2, 'name' => $this->l('Medium (0.6)')),
                                                                                                                array('id' => 3, 'name' => $this->l('Low (0.3)')),
                                                                                                            ),
                                                                                                            'id' => 'id',
                                                                                                            'name' => 'name'
                                                                                                        ),
                                                                                        'desc'      => $this->l('Select score for validation recaptcha'),
                                                                                        'tab'       => 'advanced'
                                                                                    )
                                                                            ),
                                                        'submit'        =>  array(
                                                                                    'title' => $this->l('Save'),
                                                                                    'class' => 'button btn btn-default pull-right'
                                                                            )
                                                )
                            );
        
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSettings';
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    public function hookDisplayHeader($params)
    {
        if ($this->context->controller instanceof ContactController && Configuration::get('PD_RECAPTCHA_ENABLE_CONTACT') == 1) {
            $html   = ' <script src="https://www.google.com/recaptcha/api.js?render='.Configuration::get('PD_RECAPTCHA_SITE_KEY').'"></script>';
            $html  .= ' <script type="text/javascript">
                            grecaptcha.ready(function() {
                                grecaptcha.execute("'.Configuration::get('PD_RECAPTCHA_SITE_KEY').'", {action: "homepage"}).then(function(token) {
                                    $("[name=\''.Configuration::get('PD_RECAPTCHA_NAME_BUTTON_CONTACT').'\']").before($(\'<input type="hidden" name="g-token">\').attr(\'value\', token));
                                });
                            });
                        </script>
                      ';

            return $html;
        }
    }

    public function hookActionBeforeContactSubmit()
    {
        if ($this->context->controller instanceof ContactController &&
            Configuration::get('PD_RECAPTCHA_ENABLE_CONTACT') == 1 &&
            Tools::isSubmit('submitMessage') ) {
            $this->validateRecaptcha();
        }
    }


    public function validateRecaptcha()
    {
        //$context = Context::getContext();
        $data = array(
            'secret'    => Tools::getValue('PD_RECAPTCHA_PRIVATE_KEY', Configuration::get('PD_RECAPTCHA_PRIVATE_KEY')),
            'response'  => Tools::getValue('g-token'),
            'remoteip'  => Tools::getRemoteAddr()
        );

        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($verify);
        curl_close($verify);
        
        $decode = json_decode($response, true);

        if (!$decode['success'] == true) {
            switch ($decode['error-codes'][0]) {
                case 'missing-input-secret':
                    $errors = $this->l('The secret parameter is missing. Reload page');
                    break;
                case 'invalid-input-secret':
                    $errors = $this->l('The secret parameter is invalid or malformed. Reload page');
                    break;
                case 'missing-input-response':
                    $errors = $this->l('The response parameter is missing. Reload page');
                    break;
                case 'invalid-input-response':
                    $errors = $this->l('The response parameter is invalid or malformed. Reload page');
                    break;
                case 'bad-request':
                    $errors = $this->l('The request is invalid or malformed. Reload page');
                    break;
                case 'timeout-or-duplicate':
                    $errors = $this->l('The response is no longer valid: either is too old or has been used previously. Reload page');
                    break;
                default:
                    $errors = $this->l('Error general. Reload page');
                    break;
            }
            $this->context->controller->errors[] = $errors;
            return false;
        } else {
            $score = '0.6';
            if (Tools::getValue('PD_RECAPTCHA_SCORE', Configuration::get('PD_RECAPTCHA_SCORE')) === 1) {
                $score = '0.9';
            } elseif (Tools::getValue('PD_RECAPTCHA_SCORE', Configuration::get('PD_RECAPTCHA_SCORE')) === 2) {
                $score = '0.6';
            } else {
                $score = '0.3';
            }

            if ($decode['score'] >= $score) {
                return true;
            } else {
                $errors = $this->l('The score is lower than the established, please try to send again.');
                $this->context->controller->errors[] = $errors;
                return false;
            }
        }
    }
}
