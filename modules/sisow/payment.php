<?php

include_once(dirname(__FILE__).'/../../config/config.inc.php');
include_once(dirname(__FILE__).'/../../init.php');
include_once(dirname(__FILE__).'/../sisow/prep.php');
include_once(dirname(__FILE__).'/../sisow/sisow.cls5.php');

class Payment extends PaymentModule
{
	function pay($payment, $paymentname)
	{
		if(file_exists(_PS_MODULE_DIR_.$paymentname.'/'.$paymentname.'.php'))
		{
			include_once(_PS_MODULE_DIR_.$paymentname.'/'.$paymentname.'.php');
			$paymentclass = new $paymentname();
		}
		else
			exit('payment class not found!');
			
		$merchantid = Configuration::get('SISOW'.strtoupper($payment).'_MERCHANTID');
		$merchantkey = Configuration::get('SISOW'.strtoupper($payment).'_MERCHANTKEY');	
		$shopid = Configuration::get('SISOW'.strtoupper($payment).'_SHOPID');			
		$testmode = Configuration::get('SISOW'.strtoupper($payment).'_TEST');
		$createorder = Configuration::get('SISOW'.strtoupper($payment).'_ORDERBEFORE');
		$prefix = Configuration::get('SISOW'.strtoupper($payment).'_PREFIX');
		
		$total = floatval(number_format($this->context->cart->getOrderTotal(true, 3), 2, '.', ''));
				
		$arr = _prepare($this->context->cart);
			
		if($testmode == "test")
			$arr['testmode'] = 'true';

		if($payment == 'klarna' || $payment == 'klarnaacc')
		{	
			$method = ($payment == 'klarna') ? 'sisow' : 'sisow_acc';
			
			$arr['gender'] = $_POST[$method . '_gender'];
			$arr['birthdate'] = $_POST[$method . '_day'] . $_POST[$method . '_month'] . $_POST[$method . '_year'];
			$arr['billing_phone'] = $_POST[$method . '_phone'];
			
			if($payment == 'klarnaacc')
			{
				$arr['pclass'] = $_POST[$method . '_pclass'];
			}
		}	
	
		if($createorder == 'before')
		{
			$st = Configuration::get('SISOW_PENDING');
			
			if (floatval(substr(_PS_VERSION_, 0, 3)) < 1.4)
				$paymentclass->validateOrder($this->context->cart->id, $st, 0, $paymentclass->displayName); //, NULL, NULL, $currency->id);
			else 
				$paymentclass->validateOrder($this->context->cart->id, $st, 0, $paymentclass->displayName, NULL, NULL, NULL, false, $this->context->cart->secure_key);
				
			$orderid = $paymentclass->currentOrder;
		}
		else
		{
			$orderid = $this->context->cart->id;
		}
		
		$sisow = new Sisow($merchantid, $merchantkey, $shopid);
		$sisow->amount = $total;
		$sisow->payment = $payment;
		$sisow->setPayPalLocale($arr['billing_countrycode']);
		
		if($payment == 'ideal')
			$sisow->issuerId = $_POST['issuerid'];
			
		$sisow->purchaseId = $orderid;
		$sisow->description = ($prefix == "") ? $this->context->shop->name .' ' . $orderid : $prefix . ' ' . $orderid;
		$sisow->notifyUrl = (Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/sisow/validation.php?id_cart='.$this->context->cart->id . '&payment='.$payment.'&paymentname='.$paymentname;
		$sisow->returnUrl = $sisow->notifyUrl;

		if (($ex = $sisow->TransactionRequest($arr)) < 0) {
			if($payment != "klarna" && $payment != "klarnaacc" && $createorder == 'before')
			{
				$message = 'TransactionRequest error: ex(' . $ex . '), errorcode(' . $sisow->errorCode . '), errormessage(' . $sisow->errorMessage . ')';	
				PrestaShopLogger::addLog($message, 3, '0000001', 'Sisow', intval($paymentclass->currentOrder));
				
				$order = new Order($paymentclass->currentOrder);
				$order->setCurrentState(Configuration::get('SISOW_PAYMENTFAIL'));
				
				$oldCart = new Cart(Order::getCartIdStatic($paymentclass->currentOrder, $this->context->customer->id));
				$duplication = $oldCart->duplicate();
				if (!$duplication || !Validate::isLoadedObject($duplication['cart']))
					$this->errors[] = Tools::displayError('Sorry. We cannot renew your order.');
				else if (!$duplication['success'])
					$this->errors[] = Tools::displayError('Some items are no longer available, and we are unable to renew your order.');
				else
				{
					$this->context->cookie->id_cart = $duplication['cart']->id;
					$this->context->cookie->write();

					$error = 'Sisow error: ' . $ex . ' ' . $sisow->errorCode;
					Tools::redirectLink(__PS_BASE_URI__.'order.php?step=3&'.$payment."error=".$error);			
					exit;
				}
			}
			else
			{
				if(($sisow->payment == 'klarna'|| $sisow->payment == 'klarnaacc') && $sisow->errorMessage != '')
					$error = $sisow->errorMessage;
				else
					$error = 'Sisow error: ' . $ex . ' ' . $sisow->errorCode;
						
				Tools::redirectLink(__PS_BASE_URI__.'order.php?step=3&'.$payment."error=".$error);			
				exit;
			}
			
			//echo 'Sisow error: ' . $ex . ' ' . $sisow->errorMessage;
		}
		else {
			if($payment == 'overboeking' || $payment == 'ebill' || $payment == 'klarna' || $payment == 'klarnaacc')
			{	
				$status = 'Pending';
				$pendingklarna = '&pendingklarna=true';
				
				if(($payment == "klarna" || $payment == "klarnaacc") && !$sisow->pendingKlarna)
				{
					$orderstatus = _PS_OS_PAYMENT_;
					$status = 'Reservation';
					$pendingklarna = '';
				}
				else
				{
					$orderstatus = Configuration::get('SISOW_PENDING');
				}
				
				if($createorder == "after")
				{
					if (floatval(substr(_PS_VERSION_, 0, 3)) < 1.4)
						$paymentclass->validateOrder($this->context->cart->id, $orderstatus, $total, $paymentclass->displayName); //, NULL, NULL, $currency->id);
					else 
						$paymentclass->validateOrder($this->context->cart->id, $orderstatus, $total, $paymentclass->displayName, NULL, NULL, NULL, false, $this->context->cart->secure_key);
					
					$order = new Order($paymentclass->currentOrder);
				}
				else
				{
					$order = new Order($sisow->purchaseId);
				}
				
				$db = Db::getInstance();
				$result = $db->Execute("
				INSERT INTO `" . _DB_PREFIX_ . "sisow`
				(`id_order`, `trxid`, `status` ,`consumeraccount`,`consumername`,`consumercity`,`payment`)
				VALUES
				(" . $order->id . ", '" . $sisow->trxId . "', '".$status."', '', '', '', '".$paymentclass->paymentcode."')");
						
				Tools::redirectLink('order-confirmation.php?id_cart='.$order->id_cart.'&id_module='.$paymentclass->id.'&id_order='.$order->id.'&key='.$order->secure_key . $pendingklarna);	
			}
			else
			{	
				$order = new Order($paymentclass->currentOrder);				
				header('Location: ' . $sisow->issuerUrl);
			}
			exit;
		}
	}
}

$payment = $_GET['payment'];
$paymentname = $_GET['paymentname'];

$paymentclass = new Payment();
$paymentclass->pay($payment, $paymentname);