<div class="span9">
	{if !empty($message) }
		{foreach from=$message item=msg}
			<h2>{$msg}</h2>
		{/foreach}
	{/if}
	{if empty($fatal) }
		<div>
			Choose payment method for account <b>
				<a href="{$USERSROOTURL}/admin/account.php?id={$account_id}">{$account_name}</a></b> (ID: {$account_id}):
			<div>
				<form action="{$USERSROOTURL}/admin/controller/choose_engine.php" method="POST">
					<input type="hidden" name="account_id" value="{$account_id}" />
					<ul style="list-style-type: none">
						{foreach from=$engines item=engine}
							<li><input type="radio" name="engine" value="{$engine.id}" id="engine_radio_{$engine.id}" {if $engine.current}checked{/if} />
								<label for="engine_radio_{$engine.id}">{$engine.title}</label>
							</li>
						{/foreach}
					</ul>
					<input type="submit" value="Ok" />
					{$CSRFNonce}
				</form>
			</div>
		</div>
	{/if}
</div>