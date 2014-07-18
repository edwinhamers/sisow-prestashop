{if $paymentcode == 'overboeking' || $paymentcode == 'ebill'}
	<b>{$successline1|sprintf:$shop_name}<b>
	<br/><br/>
	{$successline2|sprintf:$paymentname}	
	<br/>
	{$successline3}
	<br/>
	{$successline4}
	<br/><br/>
	<img src="https://www.sisow.nl/Sisow/images/mail/sisowklein.jpg" alt="Sisow OverBoeking" />
{elseif $paymentcode == 'klarna' || $paymentcode == 'klarnaacc'}
	<b>{$successline1|sprintf:$shop_name}<b>
	<br/><br/>
	{$successline2|sprintf:$paymentname}	
	{if $pendingklarna=='true'}
		<br/>
		{$successline3}
	{/if}
	{if $image_url != ''}
		</br></br>
		<img src="{$image_url}" alt="{$paymentname}"/>
	{/if}
{else}
	<b>{$successline1|sprintf:$shop_name}<b>
	<br/><br/>
	{$successline2|sprintf:$paymentname}	
{/if}	