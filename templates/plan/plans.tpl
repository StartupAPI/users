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
		{/literal}
	</style>

	<form action="{$USERSROOTURL}/controller/account/plans.php" method="POST">
	<p>
	{$m = 0}
	{foreach from=$plans item=plan}
  <div class="container-{$m}">
    <p>Plan Name: <b>{$plan.name}</b></p>
    <p>Plan Description: {$plan.description}</p>
    <p>Plan Details: <a href="{$plan.details_url}">{$plan.details_url}</a></p>
    {if $plan.downgrade_to}
    <p>Plan automatically downgraded to: <b>{$plan.downgrade_to}</b> 
    	if payment is due more than {$plan.grace_period} day(s)</p>
    {/if}
    {if $plan.chosen}
    <p>You have already chosen to swtich to this plan.</p>
    {/if}
    <p>
		{if !empty($plan.schedules) && count($plan.schedules)}
		Following schedule(s) available:
			{$n = 0}
			{foreach from=$plan.schedules item=schedule}
			<div class="userbase-account-plan-container container-{$m}-{$n}">
        <div class="userbase-account-plan-element">
          <input type="radio" name="plan" value="{$plan.slug}.{$schedule.slug}" id="plan-radio-{$m}-{$n}" 
          	{if $schedule.current}checked{/if} {if !$schedule.available || $schedule.chosen}disabled{/if}/>
        </div>
        <div class="userbase-account-plan-element">
        	<label for="plan-radio-{$m}-{$n}">
          <p class="userbase-account-top-paragraph">Payment Schedule: <b>{$schedule.name}</b></p>
          <p>Payment Schedule description: {$schedule.description}</p>
          <p>Charge Amount: <b>${$schedule.charge_amount}</b></p>
          <p>Charge Period: <b>{$schedule.charge_period}</b> days</p>
          {if $schedule.chosen}<p><b>You have already chosen to switch to this schedule.</b></p>
          {elseif !$schedule.available}<p><b>Your balance of ${$balance} is not sufficient to switch to this schedule.</b></p>{/if}
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
	  	 		<input type="radio" name="plan" value="{$plan.slug}" id="plan-radio-{$m}" {if $plan.current}checked{/if} 
	  	 			{if $plan.chosen}disabled{/if} />
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
