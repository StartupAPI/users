<div class="span9">
	{if !empty($message)}
		{foreach from=$message item=msg}
			<h2>{$msg}</h2>
		{/foreach}
	{/if}
	{if empty($fatal)}
		<h2>Account: {$account_name}</h2>
		<p>Status: {if $account_isActive}<b class="badge badge-success">Active</b>{else}<b class="badge badge-important">Suspended</b>{/if}</p>

		{if $useSubscriptions}
			<h3>Subscription</h3>
			<div>

				<p>Plan Name: <b><a class="label" href="plan.php?slug={$plan_slug}">{$plan_name}</a></b></p>
				<p>Plan Description: {$plan_description}</p>
				<p>Plan Details: <a href="{$plan_details_url}">{$plan_details_url}</a></p>
				{if $plan_downgrade_to}
					<p>Plan automatically downgrades to: <b>{$plan_downgrade_to}</b>
						if payment is due more than {$plan_grace_period} day(s)</p>
					{/if}
					{if isset($schedule_name)}
					<p>Payment Schedule: <b>{$schedule_name}</b></p>
					<p>Payment Schedule description: {$schedule_description}</p>
					<p>Charge Amount: <b>${$schedule_charge_amount}</b></p>
					<p>Charge Period: <b>{$schedule_charge_period}</b> days</p>
				{/if}
			</div>

			<h3>Payments</h3>
			<p>Payment Engine used: <b>{$account_engine}</b>
				<a href="{$USERSROOTURL}/admin/choose_engine.php?account_id={$account_id}">[ change ]</a>
			</p>

			<div>
				{if $balance < 0}
					<p>Amount owed</p>
					<ul>
						{$total = 0}
						{foreach from=$charges item=c}
							<li>{$c.datetime} ${$c.amount}</li>
							{$total = $total + $c.amount}
						{/foreach}
					</ul>
					<p>Total debt: <b>${sprintf("%.2f",$total)}</b></p>
				{else}
					<p>Balance: <b>${sprintf("%.2f",$balance)}</b></p>
				{/if}
			</div>

			{if isset($account_next_charge)}
				<div>
					<p>Next charge: <b>{$account_next_charge}</b></p>
				</div>
			{/if}
			{if isset($next_plan_name) && $next_plan_name != $plan_name}
				<div>
					<p>After this date following plan used:</p>
					<p>Plan Name: <b><a href="plan.php?slug={$next_plan_slug}">{$next_plan_name}</a></b></p>
					<p>Plan Description: {$next_plan_description}</p>
					<p>Plan Details: <a href="{$plan_details_url}">{$next_plan_details_url}</a></p>
				</div>
			{/if}
			{if isset($next_schedule) && $next_plan_name == $plan_name}
				<p>After this date following schedule used:</p>
			{/if}
			{if isset($next_schedule) }
				<p>Payment Schedule: <b>{$next_schedule_name}</b></p>
				<p>Payment Schedule description: {$next_schedule_description}</p>
				<p>Charge Amount: <b>${$next_schedule_charge_amount}</b></p>
				<p>Charge Period: <b>{$next_schedule_charge_period}</b> days</p>
			{/if}

			<div>
				<a href="{$USERSROOTURL}/admin/transaction_log.php?account_id={$account_id}">View account transaction log</a>
			</div>
		{/if}

		<h3>Account Users:</h3>
		<ul>
			{foreach from=$users item=user}
				<li><a href="{$USERSROOTURL}/admin/user.php?id={$user.id}">{$user.name}</a> {if $user.admin}<span class="badge badge-important">admin</span>{/if}</li>
			{/foreach}
		</ul>
	{/if}
</div>