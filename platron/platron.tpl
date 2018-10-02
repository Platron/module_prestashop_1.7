<form id="platron" name="payment" action="/modules/platron/validation.php" method="post" enctype="application/x-www-form-urlencoded" accept-charset="utf-8">
	{foreach $arrFields as $key => $val}
		<input type="hidden" name="{$key}" value="{$val}">
	{/foreach}
</form>
