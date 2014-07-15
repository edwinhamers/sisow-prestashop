<?php
class sisowidealpaymentModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;
	public $display_column_right = false;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		$this->display_column_left = false;
		parent::initContent();

		require_once(_PS_MODULE_DIR_.'/sisow/sisow.cls5.php');
		$sisow = new Sisow( Configuration::get('SISOW'.strtoupper($this->module->paymentcode).'_MERCHANTID'), Configuration::get('SISOW'.strtoupper($this->module->paymentcode).'_MERCHANTKEY') );
		$issuers = array();
		$sisow->DirectoryRequest($issuers, false, (Configuration::get('SISOW'.strtoupper($this->module->paymentcode).'_TEST') == 'test')? true : false );
		
		$this->context->smarty->assign('issuers', $issuers);
		$this->context->smarty->assign('paymentcode', $this->module->paymentcode);
		$this->context->smarty->assign('paymentname', $this->module->name);

		$this->setTemplate('payment_execution.tpl');
	}
}
?>