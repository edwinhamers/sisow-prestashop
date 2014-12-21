<?php 
if (!defined('_PS_VERSION_'))
	exit;
	
class SisowEb extends PaymentModule
{
	public function __construct()
	{
		$this->paymentcode = 'ebill';
		$this->paymentname = 'Digitale Acceptgiro';
		$this->name = 'sisoweb';
		$this->tab = 'payments_gateways';
		$this->version = '3.6.8';
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
			Configuration::updateValue('SISOW'.strtoupper($this->paymentcode).'_INCLUDE', Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_INCLUDE'));
			Configuration::updateValue('SISOW'.strtoupper($this->paymentcode).'_DAYS', Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_DAYS'));
			
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
				),
				array (
					'type' => 'radio',
					'label' => $this->l('Include'),
					'name' => 'SISOW'.strtoupper($this->paymentcode).'_INCLUDE',
					'size' => 20,
					'required' => true,
					'values' => array (
						array (
							'id' => 'yes',
							'value' => 'yes',
							'label' => $this->l('Yes')
						),
						array (
							'id' => 'no',
							'value' => 'no',
							'label' => $this->l('No')
						)
					),
					'hint' => $this->l('Include the bank account information in the Sisow ebill')
				),
				array (
					'type' => 'text',
					'label' => $this->l('Days'),
					'name' => 'SISOW'.strtoupper($this->paymentcode).'_DAYS',
					'size' => 2,
					'required' => false,
					'hint' => $this->l('The days an Ebill is valid.')
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
			$days = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_DAYS');
			$include = (string)Tools::getValue('SISOW'.strtoupper($this->paymentcode).'_INCLUDE');
		}
		else
		{
			$merchantid = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_MERCHANTID');
			$merchantkey = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_MERCHANTKEY');
			$shopid = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_SHOPID');
			$test = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_TEST');
			$createorder = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_ORDERBEFORE');
			$orderprefix = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_PREFIX');
			$days = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_DAYS');
			$include = Configuration::get('SISOW'.strtoupper($this->paymentcode).'_INCLUDE');
		}
		
		// Load current value
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_MERCHANTID'] = $merchantid;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_MERCHANTKEY'] = $merchantkey;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_SHOPID'] = $shopid;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_TEST'] = $test;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_ORDERBEFORE'] = $createorder;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_PREFIX'] = $orderprefix;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_DAYS'] = $days;
		$helper->fields_value['SISOW'.strtoupper($this->paymentcode).'_INCLUDE'] = $include;
		
		return $helper->generateForm($fields_form);
	}
	
	
	
	/**
	*	hookPayment($params)
	*	Called in Front Office at Payment Screen - displays user this module as payment option
	*/
	function hookPayment($params)
	{
		require_once(_PS_MODULE_DIR_.'/sisow/sisow.cls5.php');
		$error = '';
		$error = (isset($_GET[$this->paymentcode.'error'])) ? $_GET[$this->paymentcode.'error'] : '';				 
			
		$this->context->smarty->assign($this->paymentcode.'error', $error);
		$this->context->smarty->assign('paymentcode', $this->paymentcode);
		$this->context->smarty->assign('paymentname', $this->name);
		$this->context->smarty->assign('paymenttext', $this->l('Pay with') . ' ' . $this->paymentname);
				
		return $this->display(__FILE__, '../sisow/views/hook/payment.tpl');
		
	}
		
	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return '';
		
		$this->context->smarty->assign('successline1', $this->l('You placed an order on %s.'));
		$this->context->smarty->assign('successline2', $this->l('You have chosen to pay in advance by %s.'));
		$this->context->smarty->assign('successline3', $this->l('The processing is outsourced to Sisow B.V.'));
		$this->context->smarty->assign('successline4', $this->l('You will receive an e-mail with information on how to complete your payment.'));
		
		$this->context->smarty->assign('paymentcode', $this->paymentcode);
		$this->context->smarty->assign('paymentname', $this->paymentname);
		
		return $this->display(__FILE__, '../sisow/views/hook/confirmation.tpl');
	}
	
	private function fetchTemplate($name)
	{
		if (version_compare(_PS_VERSION_, '1.4', '<'))
			$this->context->smarty->currentTemplate = $name;
		elseif (version_compare(_PS_VERSION_, '1.5', '<'))
		{
			$views = 'views/';
			if (@filemtime(dirname(__FILE__).'/'.$name))
				return $this->display(__FILE__, $name);
			elseif (@filemtime(dirname(__FILE__).'/'.$views.'hook/'.$name))
				return $this->display(__FILE__, $views.'hook/'.$name);
			elseif (@filemtime(dirname(__FILE__).'/'.$views.'front/'.$name))
				return $this->display(__FILE__, $views.'front/'.$name);
			elseif (@filemtime(dirname(__FILE__).'/'.$views.'admin/'.$name))
				return $this->display(__FILE__, $views.'admin/'.$name);
		}
		
		return $this->display(__FILE__, $name);
	}
	
	
	
}