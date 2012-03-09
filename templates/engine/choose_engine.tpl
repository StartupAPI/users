  {if !empty($message) }
    {foreach from=$message item=msg}
    <h2>{$msg}</h2>
    {/foreach}
  {/if}
  {if empty($fatal) }
	<div>
		Choose your payment method:
	<div>
	<form action="{$USERSROOTURL}/controller/engine/choose_engine.php" method="POST">
	<ul style="list-style-type: none">
	{foreach from=$engines item=engine}
		<li><input type="radio" name="engine" value="{$engine.id}" id="engine_radio_{$engine.id}" {if $engine.current}checked{/if} />
			<label for="engine_radio_{$engine.id}">{$engine.title}</label>
		</li>
	{/foreach}
	</ul>
	<input type="submit" value="Ok" />
	</form>
	{/if}
