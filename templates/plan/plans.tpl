{if !empty($message) }
	{foreach from=$message item=msg}
		<h2>{$msg}</h2>
	{/foreach}
{/if}
{if empty($fatal) }
	<h2>Choose Subscription Plan for {$account.name}</h2>
	<style>
		{literal}
			.userbase-account-plan-container {
				float: left;
			}
			.userbase-account-plan-element {
				float: left;
			}
			.userbase-account-spacer {
				clear: both;
			}
			.userbase-account-top-paragraph {
				margin-top: 0px;
			}
			.userbase-account-plan-title {
				font-size: x-large;
				margin-bottom: 0;
			}
			.userbase-account-plan-base-price {
				margin-top: 0;
				font-weight: normal;
			}
			label.userbase-account-current-schedule .userbase-account-schedule-name {
				font-weight: bold;
			}
			label.userbase-account-current-plan .userbase-account-plan-name {
				font-weight: bold;
			}
			table.userbase-account-plans-table  {
				border-width: 2px;
				border-spacing: 0px;
				border-style: solid;
				border-color: black;
				border-collapse: separate;
			}
			table.userbase-account-plans-table th {
				border-width: 1px;
				padding: 1em;
				border-style: solid;
				border-color: gray;
			}
			table.userbase-account-plans-table td {
				border-width: 1px;
				padding: 1em;
				border-style: solid;
				border-color: gray;
			}
			td.userbase-account-pay-button {
				text-align: center;
			}
			table.userbase-account-plans-table th.userbase-account-plan-current {
				background-color: silver;
			}
			table.userbase-account-plans-table td.userbase-account-plan-current {
				background-color: silver;
			}
			table.userbase-account-plans-table tr.userbase-account-schedule-selector {
				vertical-align: top;
			}

			table.userbase-account-plan-schedules {
			}
			table.userbase-account-plan-schedules td {
				vertical-align: top;
				padding: 0.2em;
				border: 0;
			}
			.userbase-account-schedule-name {
				font-size: large;
			}
			.userbase-account-schedule-details {
				color: gray;
			}
			.userbase-account-until-label, .userbase-account-starting-label {
				font-weight: bold;
				color: white;
				background-color: gray;
				padding: 0 0.2em;
				border-radius: 5px;
				-moz-border-radius: 5px;
				-webkit-border-radius: 5px;
			}
		{/literal}
	</style>

	<form action="{$USERSROOTURL}/controller/account/plans.php" method="POST">
		{$next_chosen = false}
		{$current_plan_col = null}
		{$col = 0}
		{foreach from=$plans item=plan}
			{if $plan.chosen}
				{$next_chosen = true}
			{/if}
			{if isset($plan.schedules) }
				{foreach from=$plan.schedules item=schedule}
					{if $schedule.chosen}
						{$next_chosen = true}
					{/if}
				{/foreach}
			{/if}
		{/foreach}
		{foreach from=$plans item=plan}
			{if !empty($current_plan_col)}
				{break}
			{/if}
			{if $plan.current}
				{$current_plan_col = $col}
				{break}
			{/if}
			{$col = $col + 1}
		{/foreach}
		<table class="userbase-account-plans-table">
			{$col = 0}
			<tr>
				{foreach from=$plans item=plan}
					<th{if $col == $current_plan_col} class="userbase-account-plan-current"{/if}>
				<p class="userbase-account-plan-title">
					{if !empty($plan.details_url)}
						<a href="{$plan.details_url}">{$plan.name}</a>
					{else}
						{$plan.name}
					{/if}
				</p>
				<p class="userbase-account-plan-base-price">{if !empty($plan.base_price) } ${$plan.base_price} / {$plan.base_period}{else}free{/if}</p>
				</th>
				{$col = $col + 1}
			{/foreach}
			</tr>

			{$col = 0}
			<tr>
				{foreach from=$plans item=plan}
					<td{if $col == $current_plan_col} class="userbase-account-plan-current"{/if}>{$plan.description}</td>
					{$col = $col + 1}
				{/foreach}
			</tr>

			{$col = 0}
			<tr class="userbase-account-schedule-selector">
				{foreach from=$plans item=plan}
					<td{if $col == $current_plan_col} class="userbase-account-plan-current"{/if}>
						{if !empty($plan.schedules) && count($plan.schedules)}
							{$n = 0}
							<table class="userbase-account-plan-schedules">
								{foreach from=$plan.schedules item=schedule}
									<tr>
										<td><input type="radio" name="plan"
												   id="plan-radio-{$col}-{$n}"
												   value="{$plan.slug}.{$schedule.slug}"
											{if !$schedule.available}disabled{/if}
							{if $next_chosen}{if $schedule.chosen}checked{/if}{else}{if $schedule.current}checked{/if}{/if}
							/></td>
					<td><label for="plan-radio-{$col}-{$n}"
						{if $schedule.current} class="userbase-account-current-schedule"{/if}
						>
						<span class="userbase-account-schedule-name">{$schedule.name}</span>
						{if $schedule.current && $next_chosen}
							<span class="userbase-account-until-label">until {$next_charge}</span>
						{/if}
						{if $schedule.chosen}
							<span class="userbase-account-starting-label">starting {$next_charge}</span>
						{/if}
						<br/>
						<span class="userbase-account-schedule-details">{$schedule.description}</span>
					</label>
				</td>
			</tr>
			{$n = $n + 1}
		{/foreach}
	</table>
{else}
	<input type="radio" name="plan" value="{$plan.slug}"
		   id="plan-radio-{$col}"
		   {if $next_chosen}
		   {if $plan.chosen}checked{/if}
	{else}
	{if $plan.current}checked{/if}
{/if}
/>
<label for="plan-radio-{$col}"{if $plan.current} class="userbase-account-current-plan"{/if}><span class="userbase-account-plan-name">free</span>
	{if $next_chosen && $plan.current}
		<span class="userbase-account-until-label">until {$next_charge}</span>
	{/if}
	{if $plan.chosen}
		<span class="userbase-account-starting-label">starting {$next_charge}</span>
	{/if}
</label>
{/if}
</td>
{$col = $col + 1}
{/foreach}
</tr>

{$col = 0}
<tr>
	{foreach from=$plans item=plan}
		<td class="userbase-account-pay-button {if $col == $current_plan_col} userbase-account-plan-current{/if}"><input type="submit" value="{if is_null($current_plan_col)}{if !empty($plan.base_price) }Pay Now{else}Sign Up{/if}{else}{if $col == $current_plan_col }Update{else}Change{/if}{/if}"/></td>
			{$col = $col + 1}
		{/foreach}
</tr>

</table>

</form>
{/if}
