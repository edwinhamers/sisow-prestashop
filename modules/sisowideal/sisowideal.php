<?php 
 error_reporting(E_ALL);
 ini_set("display_errors", 1);

if (!defined('_PS_VERSION_'))
	exit;
	
class SisowIdeal extends PaymentModule
{
	public function __construct()
	{
		$this->paymentcode = 'ideal';
		$this->name = 'sisowideal';
		$this->paymentname = 'iDEAL';
		$this->tab = 'payments_gateways';
		$this->version = '3.5.0';
		$this->author = 'Sisow';
		
		$this->bootstrap = true;

		$this->currencies = true;
		$this->currencies_mode = 'radio';

		parent::__construct();

		$this->displayName = 'Sisow ' . $this->paymentname;
		$this->description = sprintf($this->l('Processing %s transactions with Sisow.'), $this->paymentname);
		$this->confirmUninstall = sprintf($this->l('Are you sure you want to delete Sisow %s?'), $this->paymentname);

		$this->page = basename(__FILE__, '.php');

		if (version_compare(_PS_VERSION_, '1.5', '<'))
		{
			$mobile_enabled = (int)Configuration::get('PS_MOBILE_DEVICE');
			require(_PS_MODULE_DIR_.'/sisow/backward_compatibility/backward.php');
		}
		else
			$mobile_enabled = (int)Configuration::get('PS_ALLOW_MOBILE_DEVICE');
	}
	
	/**
	*	Function install()
	*	Is called when 'Install' in on this module within administration page
	*/
	public function install()
	{
		require_once(_PS_MODULE_DIR_.'/sisow/install.php');
		$sisow_install = new SisowInstall();
	
		if (!parent::install()
			|| !$sisow_install->updateConfiguration($this->paymentcode)
			|| !$sisow_install->createTables()
			|| !$sisow_install->createOrderState()
			|| !$this->registerHook('payment')
			|| !$this->registerHook('paymentReturn')
			|| !$this->registerHook('adminOrder')
			)
			return false;
			
		return true;
	}
	
	public function uninstall()
	{
		require_once(_PS_MODULE_DIR_.'/sisow/install.php');
		$sisow_install = new SisowInstall();
		
		$sisow_install->deleteConfiguration($this->paymentcode)	;
		return parent::uninstall();
	}
	
	public function getContent()
	{
		$output = null;
		
		if (Tools::isSubmit('submit'.$this->name))
		{
			// get settings from post because post can give errors and you want to keep values
			$merchantid = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_MERCHANTID');
			$merchantkey = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_MERCHANTKEY');
			$shopid = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_SHOPID');
			$test = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_TEST');
			$createorder = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_ORDERBEFORE');
			$orderprefix = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_PREFIX');

			// no errors so update the values
			Configuration::updateValue('SISOW'.strtoupper($this->paymentcode).'_MERCHANTID', $merchantid);
			Configuration::updateValue('SISOW'.strtoupper($this->paymentcode).'_MERCHANTKEY', $merchantkey);
			Configuration::updateValue('SISOW'.strtoupper($this->paymentcode).'_SHOPID', $shopid);
			Configuration::updateValue('SISOW'.strtoupper($this->paymentcode).'_TEST', $test);
			Configuration::updateValue('SISOW'.strtoupper($this->paymentcode).'_ORDERBEFORE', $createorder);
			Configuration::updateValue('SISOW'.strtoupper($this->paymentcode).'_PREFIX', $orderprefix);
			
			$output .= $this->displayConfirmation($this->l('Settings updated'));
		}
			
		return $output.$this->displayForm();
	}
	public function displayForm()
	{
		$fields_form[0]['form'] = array (
			'legend' => array (
				'title' => $this->l('General Settings'),
				'image' => '../img/admin/edit.gif'
			),
			'input' => array (
				array (
					'type' => 'text',
					'label' => $this->l('Merchant ID'),
					'name' => 'SISOW'.strtoupper($this->paymentcode).'_MERCHANTID',
					'size' => 20,
					'required' => true,
					'hint' => $this->l('The Sisow Merchant ID, you can find this in your Sisow profile on www.sisow.nl')
				),
				array (
					'type' => 'text',
					'label' => $this->l('Merchant Key'),
					'name' => 'SISOW'.strtoupper($this->paymentcode).'_MERCHANTKEY',
					'size' => 64,
					'required' => true,
					'hint' => $this->l('The Sisow Merchant Key, you can find this in your Sisow profile on www.sisow.nl')
				),
				array (
					'type' => 'text',
					'label' => $this->l('Shop ID'),
					'name' => 'SISOW'.strtoupper($this->paymentcode).'_SHOPID',
					'size' => 20,
					'required' => true,
					'hint' => $this->l('The Sisow Shop ID, you can find this in your Sisow profile on www.sisow.nl')
				),
				array (
					'type' => 'radio',
					'label' => $this->l('Test/Production Mode'),
					'name' => 'SISOW'.strtoupper($this->paymentcode).'_TEST',
					'class' => 't',
					'values' => array (
						array (
							'id' => 'live',
							'value' => 'live',
							'label' => $this->l('Live Mode')
						),
						array (
							'id' => 'test',
							'value' => 'test',
							'label' => $this->l('Test Mode')
						)
					),
					'required' => true
				),
				array (
					'type' => 'radio',
					'label' => $this->l('Create order'),
					'name' => 'SISOW'.strtoupper($this->paymentcode).'_ORDERBEFORE',
					'class' => 't',
					'values' => array (
						array (
							'id' => 'before',
							'value' => 'before',
							'label' => $this->l(' Before payment, all orders are visible and orderId is sent to Sisow')
						),
						array (
							'id' => 'after',
							'value' => 'after',
							'label' => $this->l('After payment, only succesfull orders are visible and cartId is sent to Sisow')
						)
					),
					'required' => true
				),
				array (
					'type' => 'text',
					'label' => $this->l('Order prefix'),
					'name' => 'SISOW'.strtoupper($this->paymentcode).'_PREFIX',
					'size' => 28,
					'required' => true,
					'hint' => $this->l('The order prefix, this is visible on the bank statement of the customer.')
				)
			),
			'submit' => array (
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right'
				)
		);
		
		$helper = new HelperForm();
		
		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		
		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true; // false -> remove toolbar
		$helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = array (
			'save' => array (
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules')
			),
			'back' => array (
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);
		
		if (Tools::isSubmit('submit'.$this->name))
		{
			// get settings from post because post can give errors and you want to keep values
			$merchantid = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_MERCHANTID');
			$merchantkey = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_MERCHANTKEY');
			$shopid = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_SHOPID');
			$test = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_TEST');
			$createorder = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_ORDERBEFORE');
			$orderprefix = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_PREFIX');
		}
		else
		{
			$merchantid = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_MERCHANTID');
			$merchantkey = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_MERCHANTKEY');
			$shopid = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_SHOPID');
			$test = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_TEST');
			$createorder = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_ORDERBEFORE');
			$orderprefix = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_PREFIX');
		}
		
		// Load current value
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_MERCHANTID'] = $merchantid;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_MERCHANTKEY'] = $merchantkey;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_SHOPID'] = $shopid;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_TEST'] = $test;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_ORDERBEFORE'] = $createorder;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_PREFIX'] = $orderprefix;
		
		return $helper->generateForm($fields_form);
	}
	
	/**
	*	hookPayment($params)
	*	Called in Front Office at Payment Screen - displays user this module as payment option
	*/
	function hookPayment($params)
	{
		$error = '';
		$error = (isset($_GET[$this->paymentcode.'error'])) ? $_GET[$this->paymentcode.'error'] : '';				 
			
		$this->context->smarty->assign($this->paymentcode.'error', $error);
		$this->context->smarty->assign('paymentcode', $this->paymentcode);
		$this->context->smarty->assign('paymentname', $this->name);
		$this->context->smarty->assign('paymenttext', $this->l('Pay with') . ' ' . $this->paymentname);
				
		return $this->display(__FILE__, 'views/hook/payment.tpl');
		
	}
	
	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return '';
		$this->context->smarty->assign('successline1', $this->l('Your order on %s is complete.'));
		$this->context->smarty->assign('successline2', $this->l('Your payment is processed with %s.'));
		
		$this->context->smarty->assign('paymentcode', $this->paymentcode);
		$this->context->smarty->assign('paymentname', $this->paymentname);
		
		return $this->display(__FILE__, '../sisow/views/hook/confirmation.tpl');
	}

	public function hookAdminOrder($params)
	{	
		$id_order = $params['id_order'];

		$db = Db::getInstance();
        $result = $db->ExecuteS("
		SELECT * FROM `" . _DB_PREFIX_ . "sisow`
		WHERE `id_order` = '" . intval($id_order) . "' AND `payment` = '".$this->paymentcode."'");

		if (!$result) return '';
		
		$sisow_order = $result['0'];
		$this->_html = "";
		$this->_html .= '
		<br />
		<fieldset style="width:400px;">
			<legend><img src="'._MODULE_DIR_.$this->name.'/logo.gif" alt="" /> Sisow iDEAL</legend>
			<p><b>'.$this->l('Transaction ID:').'</b> '.$sisow_order['trxid'].'</p>
			<p><b>'.$this->l('Consumer name:').'</b> '.$sisow_order['consumername'].'</p>
			<p><b>'.$this->l('Consumer account:').'</b> '.$sisow_order['consumeraccount'].'</p>
			<p><b>'.$this->l('Sisow status:').'</b> '.$sisow_order['status'].'</p>';
		$sisow_order = $this->_postProcess($sisow_order);
		if ($sisow_order['status'] == 'Success') {
			$this->_html .= '
				<form method="post" action="'.$_SERVER['REQUEST_URI'].'">
					<p class="center">
						<input type="submit" class="button" name="submitSisowRefund" value="'.$this->l('Refund transaction').'" onclick="if(!confirm(\''.$this->l('Are you sure?').'\'))return false;" />
					</p>
				</form>';
		}
		else if ($sisow_order['status'] == 'Refund') {
			$this->_html .= '
				<p><b>'.$this->l('Refunded').'</b></p>';
		}
		$this->_html .= '
			</fieldset>';
		
		return $this->_html;
	}
	
	private function _postProcess($sisow_order)
	{
		global $currentIndex, $cookie;
		
		if (Tools::isSubmit('submitSisowRefund'))
		{
			if (!$sisow_order['trxid'])
				return $sisow_order;
			include_once(_PS_MODULE_DIR_.'sisow/sisow.cls5.php');
			$sisow = new Sisow(Configuration::get('SISOWIDEAL_MERCHANTID'), Configuration::get('SISOWIDEAL_MERCHANTKEY'));
			
			$message = $this->l('Refund operation result:').'<br>';
			if (($id = $sisow->RefundRequest($sisow_order['trxid'])) < 0) {
				$message .= $this->l('Transaction error!');
				PrestaShopLogger::addLog($message, 3, '0000001', 'Sisow', intval($paymentclass->currentOrder));
			}
			else
			{
				$message .= $this->l('Sisow refund successful!');
				$sisow_order['status'] = 'Refund';
				Db::getInstance()->Execute("UPDATE `"._DB_PREFIX_."sisow` SET `status` = 'Refund', `document` = ".$id." WHERE `id` = ".(int)($sisow_order['id']));
					//die(Tools::displayError('Error when updating Sisow database'));*/
				$history = new OrderHistory();
				$history->id_order = (int)($sisow_order['id_order']);
				$history->changeIdOrderState(_PS_OS_REFUND_, (int)($sisow_order['id_order']));
				$history->addWithemail();
			}

			$msg = new Message();
			$message = strip_tags($message, '<br>');
			if (!Validate::isCleanHtml($message))
				$message = $this->l('Payment message is not valid, please check your module.');
			$msg->message = $message;
			$msg->id_order = (int)($sisow_order['id_order']);
			$msg->private = 1;
			$msg->add();
			Tools::redirectAdmin($currentIndex.'&id_order='.(int)(Tools::getValue('id_order')).'&vieworder&token='.Tools::getAdminToken('AdminOrders'.(int)(Tab::getIdFromClassName('AdminOrders')).(int)($cookie->id_employee)));
			return $sisow_order;
		}
		return $sisow_order;
	}
	
}