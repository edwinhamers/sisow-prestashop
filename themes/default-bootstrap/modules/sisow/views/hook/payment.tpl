<div class="sisow_error">
	<p><b>
		{if $paymentcode == "sofort"}
			{$soforterror}
		{else if $paymentcode == "ebill"}
			{$ebillerror}
		{else if $paymentcode == "maestro"}
			{$maestroerror}
		{else if $paymentcode == "mastercard"}
			{$mastercarderror}
		{else if $paymentcode == "mistercash"}
			{$mistercasherror}
		{else if $paymentcode == "overboeking"}
			{$overboekingerror}
		{else if $paymentcode == "ebill"}
			{$idealerror}
		{else if $paymentcode == "ebill"}
			{$idealerror}
		{else if $paymentcode == "ebill"}
			{$idealerror}
		{/if}	
	</b></p>
</div>

{if $smarty.const._PS_VERSION_ >= 1.6}

<div class="row">
	<div class="col-xs-12 col-md-6">
        <p class="payment_module sisow">
			<a href="javascript:void(0)" onclick="$('#sisow_{$paymentcode}_form').submit();" class="sisow" id="sisow{$paymentcode}_process_payment" title="{l s='Pay by Sisow' mod='sisowideal'}">
				<img src="{$base_dir_ssl}modules/{$paymentname}/{$paymentcode}.png" width="64" alt="{$paymenttext}" /> {$paymenttext}				
			</a>
		</p>
    </div>
</div>

<style>
	p.payment_module.sisow a 
	{ldelim}
		padding-left:17px;
	{rdelim}
</style>
{else}
<p class="payment_module">
	<a href="javascript:void(0)" onclick="$('#sisow_{$paymentcode}_form').submit();"  class="sisow" id="sisow{$paymentcode}_process_payment" title="{l s='Pay by Sisow' mod='sisowideal'}">
		<img src="{$base_dir_ssl}modules/{$paymentname}/{$paymentcode}.png" width="64" alt="{$paymenttext}" /> {$paymenttext}			
	</a>
</p>

{/if}

<form id="sisow_{$paymentcode}_form" action="{$base_dir_ssl}modules/sisow/payment.php?payment={$paymentcode}&paymentname={$paymentname}" data-ajax="false" title="{$paymenttext}" method="post">
	<input type="hidden" name="{$paymentcode}" value="true"/>
</form>