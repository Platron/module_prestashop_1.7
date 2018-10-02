<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once 'PG_Signature.php';
include('OfdReceiptItem.php');
include('OfdReceiptRequest.php');

class platron extends PaymentModule
{
    private $_html = '';
    private $_postErrors = array();
	public $pl_merchant_id;
	public $pl_secret_key;
	public $pl_lifetime;
    public $pl_testmode;
	public $pl_ofd_check;
	public $pl_ofd_vat;

    public function __construct()
    {
        $this->name = 'platron';        
        $this->tab = 'Payment';
        $this->version = 1.0;
		$this->author = 'Platron';
        
        $this->currencies = true;
        $this->currencies_mode = 'radio';
        
        $config = Configuration::getMultiple(array('PL_MERCHANT_ID', 'PL_SECRET_KEY', 'PL_LIFETIME', 'PL_TESTMODE', 'PL_OFDCHECK', 'PL_OFD_VAT')); 
        if (isset($config['PL_MERCHANT_ID']))
            $this->pl_merchant_id = $config['PL_MERCHANT_ID'];
        if (isset($config['PL_SECRET_KEY']))
            $this->pl_secret_key = $config['PL_SECRET_KEY'];
        if (isset($config['PL_LIFETIME']))
            $this->pl_lifetime = $config['PL_LIFETIME'];
        if (isset($config['PL_TESTMODE']))
            $this->pl_testmode = $config['PL_TESTMODE'];
        if (isset($config['PL_OFDCHECK']))
            $this->pl_ofd_check = $config['PL_OFDCHECK'];
        if (isset($config['PL_OFD_VAT']))
            $this->pl_ofd_vat = $config['PL_OFD_VAT'];
        parent::__construct();
        
        /* The parent construct is required for translations */
        $this->page = basename(__FILE__, '.php');
        $this->displayName = 'Platron';
        $this->description = $this->l('Accept payments with Platron');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
    }        

    public function install()
    {
		if (!parent::install() OR !$this->registerHook('paymentOptions') OR !$this->registerHook('paymentReturn'))
			return false;
		
		Configuration::updateValue('PL_MERCHANT_ID', '');
		Configuration::updateValue('PL_SECRET_KEY', '');
		Configuration::updateValue('PL_LIFETIME', '');
        Configuration::updateValue('PL_TESTMODE', '1');
		Configuration::updateValue('PL_OFDCHECK', '1');
		Configuration::updateValue('PL_OFD_VAT', 'none');
		
        return true;
    }
    
    public function uninstall()
    {
		Configuration::deleteByName('PL_MERCHANT_ID');
		Configuration::deleteByName('PL_SECRET_KEY');
		Configuration::deleteByName('PL_LIFETIME');
        Configuration::deleteByName('PL_TESTMODE');
		Configuration::deleteByName('PL_OFDCHECK');
		Configuration::deleteByName('PL_OFD_VAT');
		
		parent::uninstall();
    }
    
    private function _postValidation()
    {
        if (isset($_POST['btnSubmit']))
        {
            if (empty($_POST['pl_merchant_id']))
                $this->_postErrors[] = $this->l('Merchant ID is required');
            elseif (empty($_POST['pl_secret_key']))
                $this->_postErrors[] = $this->l('Secret key is required');
        }
    }

    private function _postProcess()
    {
        if (isset($_POST['btnSubmit']))
        {
            if(!isset($_POST['pl_testmode']))
                $_POST['pl_testmode'] = 0;			

            if(!isset($_POST['pl_ofd_check']))
				$_POST['pl_ofd_check'] = 0;

			Configuration::updateValue('PL_MERCHANT_ID', $_POST['pl_merchant_id']);
            Configuration::updateValue('PL_SECRET_KEY', $_POST['pl_secret_key']);
            Configuration::updateValue('PL_LIFETIME', $_POST['pl_lifetime']);
            Configuration::updateValue('PL_TESTMODE', $_POST['pl_testmode']);
			Configuration::updateValue('PL_OFDCHECK', $_POST['pl_ofd_check']);
			Configuration::updateValue('PL_OFD_VAT', $_POST['pl_ofd_vat']);
        }
        $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('OK').'" /> '.$this->l('Settings updated').'</div>';
    }
    
    private function _displayRb()
    {
        $this->_html .= '<img src="../modules/platron/platron.png" style="float:left; margin-right:15px;"><b>'.$this->l('This module allows you to accept payments by Platron.').'</b><br /><br />
        '.$this->l('You need to register on the site').' <a href="https://platron.ru/join.php" target="blank">platron.ru</a> <br /><br /><br />';
    }
    
    private function _displayForm()
    {
        $bTestMode = htmlentities(Tools::getValue('pl_testmode', $this->pl_testmode), ENT_COMPAT, 'UTF-8');    
		$checkOfd = htmlentities(Tools::getValue('pl_ofd_check', $this->pl_ofd_check), ENT_COMPAT, 'UTF-8');
        $checkedOfdstr = $checkOfd ? 'checked="checked"' : '';
		$checked = '';
		if($bTestMode)
			$checked = 'checked="checked"';

		$selectedVatType = Tools::getValue('pl_ofd_vat', $this->pl_ofd_vat);
		
		$this->_html .=
        '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
            <fieldset>
            <legend><img src="../img/admin/contact.gif" />'.$this->l('Contact details').'</legend>
                <table border="0" width="500" cellpadding="0" cellspacing="0" id="form">
                    <tr><td colspan="2">'.$this->l('Please specify required data').'.<br /><br /></td></tr>
                    <tr><td width="140" style="height: 35px;">'.$this->l('Merchant ID').'</td><td><input type="text" name="pl_merchant_id" value="'.htmlentities(Tools::getValue('pl_merchant_id', $this->pl_merchant_id), ENT_COMPAT, 'UTF-8').'" style="width: 300px;" /></td></tr>
                    <tr><td width="140" style="height: 35px;">'.$this->l('Secret key').'</td><td><input type="text" name="pl_secret_key" value="'.htmlentities(Tools::getValue('pl_secret_key', $this->pl_secret_key), ENT_COMPAT, 'UTF-8').'" style="width: 300px;" /></td></tr>
                    <tr><td width="140" style="height: 35px;">'.$this->l('Lifetime').'</td><td><input type="text" name="pl_lifetime" value="'.htmlentities(Tools::getValue('pl_lifetime', $this->pl_lifetime), ENT_COMPAT, 'UTF-8').'" style="width: 300px;" /></td></tr>
					<tr><td width="140" style="height: 35px;">'.$this->l('Testmode').'</td>
						<td>
							<input type="checkbox" name="pl_testmode" value="1" '.$checked.'/>
						</td>
					</tr>
                    <tr><td width="140" style="height: 35px;">'.$this->l('Создание чека').'</td>
                        <td>
                            <input type="checkbox" name="pl_ofd_check" value="1" '.$checkedOfdstr.'/>
                        </td>
                    </tr>
                    <tr>
                    	<td style="width:140px;height:35px;">' . $this->l('Ставка НДС') . '</td>
                    	<td>
                    		<select name="pl_ofd_vat">
                    			<option value="none" ' . ('none' === $selectedVatType ? 'selected="selected"' : '') . '>' . $this->l('Не облагается') . '</option>
                    			<option value="0" ' . ('0' === $selectedVatType ? 'selected="selected"' : '') . '>' . $this->l('0%') . '</option>
                    			<option value="10" ' . ('10' === $selectedVatType ? 'selected="selected"' : '') . '>' . $this->l('10%') . '</option>
                    			<option value="18" ' . ('18' === $selectedVatType ? 'selected="selected"' : '') . '>' . $this->l('18%') . '</option>
                    			<option value="110" ' . ('110' === $selectedVatType ? 'selected="selected"' : '') . '>' . $this->l('10/110') . '</option>
                    			<option value="118" ' . ('118' === $selectedVatType ? 'selected="selected"' : '') . '>' . $this->l('18/118') . '</option>
                    		</select>
                    </tr>
                    <tr><td colspan="2" align="center"><br /><input class="button" name="btnSubmit" value="'.$this->l('Update settings').'" type="submit" /></td></tr>
                </table>
            </fieldset>
        </form>';
    }

    public function getContent()
    {
        $this->_html = '<h2>'.$this->displayName.'</h2>';

        if (!empty($_POST))
        {
            $this->_postValidation();
            if (!sizeof($this->_postErrors))
                $this->_postProcess();
            else
                foreach ($this->_postErrors AS $err)
                    $this->_html .= '<div class="alert error">'. $err .'</div>';
        }
        else
            $this->_html .= '<br />';

        $this->_displayRb();
        $this->_displayForm();

        return $this->_html;
    }

	public function hookPaymentOptions($params)
	{
		$paramsQuery = $this->generateParamsQueryByTransactionFromOrder($params['cart']);

		$this->context->smarty->assign('arrFields', $paramsQuery);
		$this->context->smarty->assign('module_template_dir', Media::getMediaPath(_PS_MODULE_DIR_.$this->name));

		$newOption = new PaymentOption();
		$newOption->setCallToActionText($this->l('Pay by Platron'))
			->setAction('/modules/platron/validation.php')
			->setForm($this->context->smarty->fetch('module:platron/platron.tpl'));
		$payment_options = [
			$newOption,
		];
		return $payment_options;
	}

    /**
     * Создание списка товаров для чека
     * @param  Order $order заказ
     * @return array
     */
    public function createItemsOfOrderByCheck($order)
    {
        $ofdReceiptItems = [];
        $rate = $this->pl_ofd_vat;
        foreach($order->getProducts()as $key => $item) {
            $ofdReceiptItem           = new OfdReceiptItem();
            $ofdReceiptItem->label    = $item['name'];
            $ofdReceiptItem->amount   = round($item['price_wt'] * $item['quantity'], 2);
            $ofdReceiptItem->price    = round($item['price_wt'], 2);
            $ofdReceiptItem->quantity =  $item['quantity'];
            $ofdReceiptItem->vat      = $rate;
            $ofdReceiptItems[]        = $ofdReceiptItem;
        }
        if ($order->getPackageShippingCost() > 0) {
            $ofdReceiptItems[] = $this->addShippingByOrder($order, ($rate === 'none' ? 'none' : '18'));
        }
        $sum = 0;
        return $ofdReceiptItems;
    }

    public function createCreateOfdCheck()
    {
        return !is_null($this->pl_check);
    }

    protected function addShippingByOrder($order, $rate)
    {
        $ofdReceiptItem           = new OfdReceiptItem();
        $ofdReceiptItem->label    = 'Доставка';
        $ofdReceiptItem->amount   = round($order->getPackageShippingCost(), 2);
        $ofdReceiptItem->price    = round($order->getPackageShippingCost(), 2);
        $ofdReceiptItem->vat      = $rate;
        $ofdReceiptItem->quantity = 1;
        return $ofdReceiptItem;
    }

    /**
     * Проверка, сделать ли чек 
     * @param  array настройки 
     * @return boolean [description]
     */
    public function isCreateOfdCheck($pmconfigs)
    {
        return (int) $this->pl_ofd_check;
    }

    /**
     * Проверка все ли удачно прошло, при создании транзакции
     * @param  bool $checkResponse
     * @param  SimpleXMLElement $responseElement 
     * @return bool 
     */
    public function checkResponseFromCreateTransaction($checkResponse,$responseElement)
    {
        return $checkResponse && (string) $responseElement->pg_status === 'ok';
    }


    /**
     * Создание http запроса  
     * @param  string $action url относительный url платрон
     * @param  array $params
     * @return xml
     */
    public function createQuery($action, $params = []) 
    {
        //Инициализирует сеанс
        $connection = curl_init();
        $url = $this->getServiseUrl().'/'. $action;
        if (count($params)) {
            $url = $url.'?'.http_build_query($params);
        }

        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_URL, $url);
        $response = curl_exec($connection);
        curl_close($connection);
        return $response;
    }


    protected function getServiseUrl()
    {
        return 'https://platron.ru';
    }
    /**
     * генерация пареметров запроса для создания транзакции 
     * @param  Order $order     заказ
     * @param  array $pmconfigs настройки платежной системы
     * @return array параметры запроса           
     */
    private function generateParamsQueryByTransactionFromOrder($order)
    {
        $cookie = $this->context->cookie;
        $objLang = new LanguageCore($cookie->id_lang);
        
        $callbackUrl = Tools::getShopDomainSsl(true, true) . _MODULE_DIR_ . $this->name . '/callback.php';

        $arrReq   = [];
        /* Обязательные параметры */
        $arrReq['pg_merchant_id']  = $this->pl_merchant_id; // Идентификатор магазина
        $arrReq['pg_order_id']     = $order->id;  // Идентификатор заказа в системе магазина
        $arrReq['pg_amount']       = $order->getOrderTotal(true, 3); // Сумма заказа
        $arrReq['pg_description']  = "Оплата заказа ".$_SERVER['HTTP_HOST']; // Описание заказа (показывается в Платёжной системе)
        $arrReq['pg_site_url']     = $_SERVER['HTTP_HOST']; // Для возврата на сайт
        $arrReq['pg_lifetime']     = $this->pl_lifetime ? $this->pl_lifetime*60 : 0; // Время жизни в секундах
        $arrReq['pg_check_url']    = $callbackUrl; // Проверка заказа
        $arrReq['pg_result_url']   = $callbackUrl; // Оповещение о результатах
        $arrReq['pg_language']     = ($objLang->iso_code == 'ru') ? 'ru': 'en';
        $arrReq['pg_success_url'] = Tools::getShopDomainSsl(true, true) . '/order-history';
        $arrReq['pg_failure_url'] = Tools::getShopDomainSsl(true, true) . '/order-history';
        // $arrReq['pg_user_ip']   = $_SERVER['REMOTE_ADDR'];
        $arrReq['pg_testing_mode'] =  $this->pl_testmode;
        $arrReq['pg_currency']     = $this->getCurrency()->iso_code;
        $arrReq['pg_salt'] =  rand(21,43433);
        $arrReq['cms_payment_module'] = 'PRESTASHOP';
        $arrReq['pg_user_email'] = $cookie->email;
        $arrReq['pg_user_contact_email'] = $cookie->email;

        $arrReq['pg_sig'] = PG_Signature::make('init_payment.php', $arrReq, $this->pl_secret_key);

        return $arrReq;
    }

    public function getL($key)
    {
        $translations = array(
            'success'=> 'Platron transaction is carried out successfully.',
            'fail'=> 'Platron transaction is refused.'
        );
        return $translations[$key];
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active)
            return ;

        return $this->display(__FILE__, 'confirmation.tpl');
    }
    
}

?>
