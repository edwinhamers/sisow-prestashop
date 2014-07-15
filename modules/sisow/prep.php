<?php
function _prepare($cart, $module = false) {
	$arr['ipaddress'] = $_SERVER['REMOTE_ADDR'];
	// customer
	if (!isset($customer)) {
		$customer = new Customer((int)($cart->id_customer));
	}
	$arr['customer'] = $cart->id_customer;
	
	$currency = new CurrencyCore($cart->id_currency);
	$arr['currency'] = $currency->iso_code;

	// delivery address
	if (isset($cart->id_address_delivery)) {
		$address = new Address($cart->id_address_delivery);
		$arr['shipping_firstname'] = $address->firstname;
		$arr['shipping_lastname'] = $address->lastname;
		$arr['shipping_address1'] = $address->address1;
		$arr['shipping_address2'] = $address->address2;
		$arr['shipping_phone'] = !empty($address->phone_mobile) ? $address->phone_mobile : $address->phone;
		$arr['shipping_zip'] = $address->postcode;
		$arr['shipping_city'] = $address->city;
		$arr['shipping_country'] = $address->country; //isset($this->_country[strtoupper($countryObj->iso_code)]) ? $this->_country[strtoupper($countryObj->iso_code)] : '';
		$arr['shipping_countrycode'] = CountryCore::getIsoById($address->id_country);
		//$arr['shipping_language'] = strtoupper($lang->iso_code);
	}
	
	// billing address
	if (isset($cart->id_address_invoice)) {
		$address = new Address($cart->id_address_invoice);
		$arr['billing_firstname'] = $address->firstname;
		$arr['billing_lastname'] = $address->lastname;
		$arr['billing_address1'] = $address->address1;
		$arr['billing_address2'] = $address->address2;
		$arr['billing_phone'] = !empty($address->phone_mobile) ? $address->phone_mobile : $address->phone;
		$arr['billing_zip'] = $address->postcode;
		$arr['billing_city'] = $address->city;
		$arr['billing_country'] = $address->country; //isset($this->_country[strtoupper($countryObj->iso_code)]) ? $this->_country[strtoupper($countryObj->iso_code)] : '';
		$arr['billing_countrycode'] = CountryCore::getIsoById($address->id_country);
		if (!isset($cart->id_address_delivery)) {
			$arr['shipping_firstname'] = $address->firstname;
			$arr['shipping_lastname'] = $address->lastname;
			$arr['shipping_address1'] = $address->address1;
			$arr['shipping_address2'] = $address->address2;
			$arr['shipping_phone'] = !empty($address->phone_mobile) ? $address->phone_mobile : $address->phone;
			$arr['shipping_zip'] = $address->postcode;
			$arr['shipping_city'] = $address->city;
			$arr['shipping_country'] = $address->country; //isset($this->_country[strtoupper($countryObj->iso_code)]) ? $this->_country[strtoupper($countryObj->iso_code)] : '';
			$arr['shipping_countrycode'] = CountryCore::getIsoById($address->id_country);
		}
	}

	$arr['billing_mail'] = $customer->email;
	$arr['shipping_mail'] = $customer->email;
	
	$tax = new TaxCore();
	
	// products
	$i = 1;
	$prods = $cart->getProducts();
	foreach ($prods as $k => $prod) {
		$arr['product_id_' . $i] = $prod['id_product'];
		$arr['product_description_' . $i] = $prod['name'];
		$arr['product_quantity_' . $i] = round($prod['cart_quantity'], 0);
		$arr['product_weight_' . $i] = round($prod['weight'] * 1000, 0);
		$arr['product_tax_' . $i] = round(($prod['total_wt'] - $prod['total']) * 100, 0);
		if (method_exists($tax, 'getProductTaxRate')) {
			$arr['product_taxrate_' . $i] = round($tax->getProductTaxRate((int)$prod['id_product'], (int)($cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')})) * 100, 0); //$prod['rate'];
		}
		else if (($pp = new Product($prod['id_product'], true, $cart->id_lang))) {
			$arr['product_taxrate_' . $i] = round($pp->tax_rate * 100, 0);
		}
		$arr['product_netprice_' . $i] = round($prod['price'] * 100, 0);
		$arr['product_price_' . $i] = round($prod['price_wt'] * 100, 0);
		$arr['product_nettotal_' . $i] = round($prod['total'] * 100, 0);
		$arr['product_total_' . $i] = round($prod['total_wt'] * 100, 0);
		$i++;
	}

	// shipping
	if (($shipping = $cart->getOrderShippingCost($cart->id_carrier, false)) > 0) {
		$shiptax = $cart->getOrderShippingCost($cart->id_carrier, true);
		$arr['shipping'] = round($shipping * 100, 2);
		
		$arr['product_id_' . $i] = 'shipping';
		$arr['product_description_' . $i] = 'Verzendkosten';
		$arr['product_quantity_' . $i] = 1;
		$arr['product_weight_' . $i] = 0;
		$arr['product_tax_' . $i] = round(($shiptax - $shipping) * 100, 0);
		if (method_exists($tax, 'getCarrierTaxRate')) {
			$arr['product_taxrate_' . $i] = round($tax->getProductTaxRate((int)$cart->id_carrier, (int)$cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}) * 100, 0);
		}
		else if ($cart->id_carrier && ($cc = new Carrier($cart->id_carrier)) && ($tt = new Tax($cc->id_tax)) && $tt->tax_rate) {
			$arr['product_taxrate_' . $i] = round($tt->tax_rate * 100, 0);
		}
		else { //if ($shiptax > $shipping) {
			$arr['product_taxrate_' . $i] = round(2100, 0);
		}
		$arr['product_netprice_' . $i] = round($shipping * 100, 0);
		$arr['product_price_' . $i] = round($shiptax * 100, 0);
		$arr['product_nettotal_' . $i] = round($shipping * 100, 0);
		$arr['product_total_' . $i] = round($shiptax * 100, 0);
		$i++;
	}
	
	// payment fee
	if ($module && method_exists($module, 'getFee') && ($fee = $module->getFee($cart))) {
		$feetax = $module->getFee($cart, true); // inclusief BTW
		$arr['product_id_' . $i] = 'paymentfee';
		$arr['product_description_' . $i] = 'Payment Fee';
		$arr['product_quantity_' . $i] = 1;
		$arr['product_weight_' . $i] = 0;
		$arr['product_tax_' . $i] = round(($feetax - $fee) * 100, 0);
		$arr['product_taxrate_' . $i] = $module->getFee() * 100;
		$arr['product_netprice_' . $i] = round($fee * 100, 0);
		$arr['product_price_' . $i] = round($feetax * 100, 0);
		$arr['product_nettotal_' . $i] = round($fee * 100, 0);
		$arr['product_total_' . $i] = round($feetax * 100, 0);
		$i++;
	}

	if( count($cart->getCartRules()) > 0 )
	{
		foreach($cart->getCartRules() as $discount)
		{
			$feetax = $discount['value_real'];
			$fee = $discount['value_tax_exc'];
			
			$arr['product_id_' . $i] = $discount['code'];
			$arr['product_description_' . $i] = $discount['description'];
			$arr['product_quantity_' . $i] = 1;
			$arr['product_weight_' . $i] = 0;
			$arr['product_tax_' . $i] = round(($feetax - $fee) * -100, 0);
			$arr['product_taxrate_' . $i] = round((($arr['product_tax_' . $i] * 100.0) / $fee));       //$module->getFee() * 100;
			$arr['product_netprice_' . $i] = round($fee * -100, 0);
			$arr['product_price_' . $i] = round($feetax * -100, 0);
			$arr['product_nettotal_' . $i] = round($fee * -100, 0);
			$arr['product_total_' . $i] = round($feetax * -100, 0);
			$i++;
		}		
	}
	return $arr;
}
?>