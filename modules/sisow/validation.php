<?php
include_once(dirname(__FILE__).'/../../config/config.inc.php');
include(_PS_MODULE_DIR_.'sisow/sisow.cls5.php');

$paymentname = $_GET['paymentname'];
$payment = $_GET['payment'];

include(_PS_MODULE_DIR_.$paymentname.'/'.$paymentname.'.php');

$paymentclass = new $paymentname();

$returnid = $_GET['ec'];

class validation extends PaymentModule
{
	function validate($paymentclass, $returnid)
	{
		$merchantid = Configuration::get('SISOW'.strtoupper($paymentclass->paymentcode).'_MERCHANTID');
		$merchantkey = Configuration::get('SISOW'.strtoupper($paymentclass->paymentcode).'_MERCHANTKEY');
		$shopid = Configuration::get('SISOW'.strtoupper($paymentclass->paymentcode).'_SHOPID');
		$createorder = Configuration::get('SISOW'.strtoupper($paymentclass->paymentcode).'_ORDERBEFORE');
		
		$db = Db::getInstance();
        $orderquery = $db->ExecuteS("
		SELECT * FROM `" . _DB_PREFIX_ . "sisow`
		WHERE `id_order` = '" . intval($returnid) . "'");

		if($orderquery && isset($orderquery['0']) )
		{
			$orderquery = $orderquery['0'];
			$trxid = $orderquery['trxid'];
		}
		else
		{
			$orderquery = false;
			$trxid = $_GET['trxid'];
		}	
		
		$sisow = new Sisow($merchantid, $merchantkey, $shopid);
		if (($ex = $sisow->StatusRequest($trxid)) < 0) 
		{
			$message = 'StatusRequest error: ex(' . $ex . '), errorcode(' . $sisow->errorCode . '), errormessage(' . $sisow->errorMessage . ')';
			PrestaShopLogger::addLog($message, 3, '0000001', 'Sisow', intval($orderid));
			exit;
		}
		
		if ($sisow->status == 'Open'|| $sisow->status == 'Pending')
			exit;
		
		if($createorder == "after")
		{
			if ($sisow->status == 'Success') {
				$st = _PS_OS_PAYMENT_;

				if (floatval(substr(_PS_VERSION_, 0, 3)) < 1.4) {
					$paymentclass->validateOrder($returnid, $st, $sisow->amount, $paymentclass->displayName); //, NULL, NULL, $currency->id);
				}
				else {
					$cart = new Cart((int)$returnid);
					$paymentclass->validateOrder($returnid, $st, $sisow->amount, $paymentclass->displayName, NULL, NULL, NULL, false, $cart->secure_key);
				}
				
				$this->updateSisowTable($paymentclass->currentOrder, $sisow->trxId, $paymentclass->paymentcode, $sisow->status, $sisow->consumerBic, $sisow->consumerIban, $sisow->consumerName);
			}
		}
		else
		{
			$order = new Order($returnid);
			
			if($order->current_state != _PS_OS_PAYMENT_ || $sisow->status == 'Reversed' || $sisow->status == 'Refunded')
			{
				$status = "";
				if($sisow->status == "Success")
				{
					$status = _PS_OS_PAYMENT_;
				}
				else if($sisow->status == 'Reversed' || $sisow->status == 'Refunded')
				{
					$status = _PS_OS_REFUND_;
				}
				else
				{
					$status = _PS_OS_CANCELED_;
				}
				
				//$order->setCurrentState($st);
				
				$oh = new OrderHistory();
				$oh->id_order = $order->id;
				$oh->changeIdOrderState($status, $order->id);
				$oh->add();
				
				$this->updateSisowTable($returnid, $sisow->trxId, $paymentclass->paymentcode, $sisow->status, $sisow->consumerBic, $sisow->consumerIban, $sisow->consumerName);
			}
		}
		exit;
	}
	
	function updateSisowTable($orderid, $trxId, $payment, $Status = "", $Bic = "", $Iban = "", $Name = "")
	{
		$db = Db::getInstance();
		
		$db = Db::getInstance();
				$result = $db->Execute("
				INSERT INTO `" . _DB_PREFIX_ . "sisow`
				(`id_order`, `trxid`, `status` ,`consumeraccount`,`consumername`, `payment`)
				VALUES
				(" . $orderid . ", '" . $trxId . "', '".$Status."', '".$Bic . " / " . $Iban."', '".$Name."', '".$payment."')");
		
		return true;
	}
	
	function redirect($paymentclass, $returnid)
	{
		$createorder = Configuration::get('SISOW'.strtoupper($paymentclass->paymentcode).'_ORDERBEFORE');
		
		if($createorder == "after")
		{
			if ($_GET['status'] == 'Success') {
				$order = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'orders WHERE id_cart = '.(int)$_GET['ec']);
				Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$_GET['ec'].'&id_module='.$paymentclass->id.'&id_order='.$order['id_order'].'&key='.$order['secure_key']);
			}
			else {
				Tools::redirectLink(__PS_BASE_URI__.'order.php?step=3');
				echo '<p>Payment failed</p>';
			}
		}
		else
		{
			$order = new Order($returnid);
			if ($_GET['status'] == 'Success') {
				Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$order->id_cart.'&id_module='.$paymentclass->id.'&id_order='.$order->id.'&key='.$order->secure_key);
				exit;
			}
			else 
			{
				$oldCart = new Cart(Order::getCartIdStatic($order->id, $this->context->customer->id));
				$duplication = $oldCart->duplicate();
				if (!$duplication || !Validate::isLoadedObject($duplication['cart']))
					$this->errors[] = Tools::displayError('Sorry. We cannot renew your order.');
				else if (!$duplication['success'])
					$this->errors[] = Tools::displayError('Some items are no longer available, and we are unable to renew your order.');
				else
				{
					$this->context->cookie->id_cart = $duplication['cart']->id;
					$this->context->cookie->write();
					if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1)
						Tools::redirect('index.php?controller=order-opc');
					Tools::redirect('index.php?controller=order');
				}

				Tools::redirectLink(__PS_BASE_URI__.'order.php?step=3');
				exit;
			}
		}
	}
}

if(isset($_GET['notify']) || isset($_GET['callback']))
{
	$validation = new validation();
	$validation->validate($paymentclass, $returnid);
}
else
{
	$validation = new validation();
	$validation->redirect($paymentclass, $returnid);
}
?>