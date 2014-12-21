<?php

class SisowInstall
{
	public function createTables()
	{
		$db = Db::getInstance();

		if (!$db->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'sisow` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`id_order` INT NOT NULL ,
			`trxid` TEXT NOT NULL ,
			`consumeraccount` TEXT NOT NULL ,
			`consumername` TEXT NOT NULL ,
			`payment` TEXT NOT NULL,
			`status` VARCHAR(16) NOT NULL,
			`invoice` VARCHAR(32) NOT NULL,
			`invoicedate` DATETIME NOT NULL,
			`document` INT NOT NULL,
			`invoiceurl` TEXT NOT NULL,
			`credit` VARCHAR(32) NOT NULL,
			`creditdate` DATETIME NOT NULL,
			`crediturl` TEXT NOT NULL,
			PRIMARY KEY (`id`),
			INDEX `id_order`(`id_order`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1'))
			return false;	
		
		$colums = $db->ExecuteS('SHOW COLUMNS FROM `'._DB_PREFIX_.'sisow`');
		
		$old_fields = array();
		
		if(isset($colums) && is_array($colums))
			foreach($colums as $colum)
				$old_fields[] = $colum['Field'];

		
		$new_fields = array();
		//new fields version 3.5
		$new_fields['invoiceurl'] = 'TEXT NOT NULL';
		$new_fields['credit'] = 'VARCHAR(32) NOT NULL';
		$new_fields['creditdate'] = 'DATETIME NOT NULL';
		$new_fields['crediturl'] = 'TEXT NOT NULL';	
		
		
		foreach ($new_fields as $fieldname => $fieldtype)
			if(!in_array($fieldname, $old_fields))
				$db->Execute("ALTER TABLE `" . _DB_PREFIX_ . "sisow` ADD (`".$fieldname."` ".$fieldtype." )");

		return true;	
	}

	public function updateConfiguration($paymentcode = '')
	{
		if($paymentcode == '')
			return false;
			
		$paymentcode = strtoupper($paymentcode);	
		
		Configuration::updateValue('SISOW'.$paymentcode.'_MERCHANTID', '');
		Configuration::updateValue('SISOW'.$paymentcode.'_MERCHANTKEY', '');
		Configuration::updateValue('SISOW'.$paymentcode.'_SHOPID', '');
		Configuration::updateValue('SISOW'.$paymentcode.'_TEST', 0);
		Configuration::updateValue('SISOW'.$paymentcode.'_ORDERBEFORE', 0);
		Configuration::updateValue('SISOW'.$paymentcode.'_PREFIX', '');
		
		if($paymentcode == 'klarna' || $paymentcode == 'klarnaacc')
		{
			Configuration::updateValue('SISOW'.$paymentcode.'_KLARNAID', '');
		}
		
		if($paymentcode == 'ebill' || $paymentcode == 'overboeking')
		{
			Configuration::updateValue('SISOW'.$paymentcode.'_INCLUDE', 0);
			Configuration::updateValue('SISOW'.$paymentcode.'_DAYS', '30');
		}
		return true;
	}
	
	public function deleteConfiguration($paymentcode = '')
	{
		if($paymentcode == '')
			return false;
			
		$paymentcode = strtoupper($paymentcode);	

		Configuration::deleteByName('SISOW'.$paymentcode.'_MERCHANTID');
		Configuration::deleteByName('SISOW'.$paymentcode.'_MERCHANTKEY');
		Configuration::deleteByName('SISOW'.$paymentcode.'_SHOPID');
		Configuration::deleteByName('SISOW'.$paymentcode.'_TEST');
		Configuration::deleteByName('SISOW'.$paymentcode.'_ORDERBEFORE');
		Configuration::deleteByName('SISOW'.$paymentcode.'_PREFIX');
		
		if($paymentcode == 'ebill' || $paymentcode == 'overboeking')
		{
			Configuration::deleteByName('SISOW'.$paymentcode.'_INCLUDE');
			Configuration::deleteByName('SISOW'.$paymentcode.'_DAYS');
		}
		return true;
	}
	
	public function createOrderState()
	{
		if (!Configuration::get('SISOW_PENDING'))
		{
			$orderState = new OrderState();
			$orderState->name = array();

			foreach (Language::getLanguages() as $language)
			{
				if (Tools::strtolower($language['iso_code']) == 'nl')
					$orderState->name[$language['id_lang']] = 'Wachten op betaling';
				else
					$orderState->name[$language['id_lang']] = 'Waiting for payment';
			}

			$orderState->send_email = false;
			$orderState->color = '#9f00a7';
			$orderState->hidden = false;
			$orderState->delivery = false;
			$orderState->logable = false;
			$orderState->invoice = false;
			$orderState->paid = false;

			if(!$orderState->add())
				return false;
			
			Configuration::updateValue('SISOW_PENDING', (int)$orderState->id);
		}
		
		if (!Configuration::get('SISOW_PAYMENTFAIL'))
		{
			$orderState = new OrderState();
			$orderState->name = array();

			foreach (Language::getLanguages() as $language)
			{
				if (Tools::strtolower($language['iso_code']) == 'nl')
					$orderState->name[$language['id_lang']] = 'Betaling mislukt';
				else
					$orderState->name[$language['id_lang']] = 'Payment Failed';
			}

			$orderState->send_email = false;
			$orderState->color = '#FF0000';
			$orderState->hidden = false;
			$orderState->delivery = false;
			$orderState->logable = false;
			$orderState->invoice = false;
			$orderState->paid = false;

			if(!$orderState->add())
				return false;
			
			Configuration::updateValue('SISOW_PAYMENTFAIL', (int)$orderState->id);
		}
		
		return true;
	}
}

?>