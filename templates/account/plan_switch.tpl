<html>
<body>
	{if !empty($message) } 
		{foreach from=$message item=msg}
		<h2>{$msg}</h2>
		{/foreach}
	{/if}
	{if empty($fatal) }
  <div>
		Choose your payment plan:
  </div>
	<style>
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
	</style>

	<form action="{UserConfig::$USERSROOTURL}/controller/account/plan_switch.php" method="POST">
	<p>
	{$m = 0}
	{foreach from=$plans item=plan}
  <div class="container-{$m}">
    <p>Plan Name: <b>{$plan.name}</b></p>
    <p>Plan Description: {$plan.description}</p>
    <p>Plan Details: <a href="{$plan.details_url}">{$plan.details_url}</a></p>
    {if $plan.downgrade_to}
    <p>Plan automatically downgrades to: <b>{$plan.downgrade_to}</b> 
    	if payment is due more than {$plan.grace_period} day(s)</p>
    {/if}
    <p>
		{if !empty($plan.schedules) && count($plan.schedules)}
		Following schedule(s) available:
			{$n = 0}
			{foreach from=$plan.schedules item=schedule}
			<div class="userbase-account-plan-container container-{$m}-{$n}">
        <div class="userbase-account-plan-element">
          <input type="radio" name="plan" value="{$plan.id}.{$schedule.id}" id="plan-radio-{$m}-{$n}" {if $schedule.current}checked{/if} />
        </div>
        <div class="userbase-account-plan-element">
        	<label for="plan-radio-{$m}-{$n}">
          <p class="userbase-account-top-paragraph">Payment Schedule: <b>{$schedule.name}</b></p>
          <p>Payment Schedule description: {$schedule.description}</p>
          <p>Charge Amount: <b>${$schedule.charge_amount}</b></p>
          <p>Charge Period: <b>{$schedule.charge_period}</b> days</p>
          </label>
        </div>
       </div>
       <div class="userbase-account-spacer"></div>
       {$n = $n + 1}
  	  {/foreach}
  	 {else}
  	 Plan does not use payment schedules
  	 	<div class="userbase-account-plan-container">
  	 		<div class="userbase-account-plan-element">
	  	 		<input type="radio" name="plan" value="{$plan.id}" id="plan-radio-{$m}" {if $plan.current}checked{/if} />
	  	 	</div>
	  	 	<div class="userbase-account-plan-element">
	  	 		<label for="plan-radio-{$m}">
	  	 		<p class="userbase-account-top-paragraph">Choose this plan</p>
	  	 		</label>
	  	 	</div>
  	 	</div>
  	 	<div class="userbase-account-spacer"></div>
  	 {/if}
  	</p>
  </div>
  {/foreach}
	</p>
	<input type="submit" value="Switch" />
	</form>  
	{/if}
</body>
</html>
