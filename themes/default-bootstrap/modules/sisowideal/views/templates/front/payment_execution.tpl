{*
* 2007-2014 PrestaShop
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
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{capture name=path}{l s='iDEAL payment' mod='sisowideal'}{/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='sisowideal'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<form name="sisowideal_form" id="sisowideal_form" action="{$base_dir_ssl}modules/sisow/payment.php?payment={$paymentcode}&paymentname={$paymentname}" method="post">
	<div class="box cheque-box">
		<h3 class="page-subheading">
			{l s='iDEAL payment' mod='sisowideal'}
		</h3>
		<p class="cheque-indent">
			<strong class="dark">
			<img src="https://www.sisow.nl/Sisow/images/ideal/idealklein.gif" alt="iDEAL" style="float:left; margin: 0px 10px 5px 0px;" />
				{l s='You have chosen to pay with iDEAL.' mod='sisowideal'}
			</strong>
		</p>
		<p>{l s='Choose your bank:' mod='sisowideal'}
			<select name="issuerid" id="issuerid">
				<option value="">{l s='Choose your bank....' mod='sisowideal'}</option>
					{foreach from=$issuers key=id item=issuer}
						<option value="{$id}">{$issuer}</option>
				{/foreach}	
			</select>
		</p>
	</div><!-- .cheque-box -->
	<p class="cart_navigation clearfix" id="cart_navigation">
		<a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
			<i class="icon-chevron-left"></i>{l s='Other payment methods' mod='sisowideal'}
		</a>
		<button class="button btn btn-default button-medium" type="submit">
			<span>{l s='I confirm my order' mod='sisowideal'}<i class="icon-chevron-right right"></i></span>
		</button>
	</p>
</form>
<script type="text/javascript">
	var mess_sisow_error = "{l s='Choose your bank!' mod='sisowideal' js=1}";
	{literal}
		$(document).ready(function(){

			$('#sisowsubmit').click(function()
				{
				if ($('#issuerid').val() == '')
				{
					alert(mess_sisow_error);
				}
				else
				{
					$('#sisowideal_form').submit();
				}
				return false;
			});
		});
	{/literal}
</script>