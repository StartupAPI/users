<html>
<body>
  <div>
    <p>Account Name: <b>{$account_name}</b></p>
    <p>Account Role: <b>{$account_role}</b></p>
    <p>Account Status: <b>{if $account_isActive}Active{else}Suspended{/if}</b></p>
    <p>Payment Engine used: <b>{if $account_engine}{$account_engine}{else}None{/if}<b></p>
  </div>

  <div>
    <p><b>Plan Name: <b>{$plan_name}</b></p>
    <p>Plan Description: {$plan_description}</p>
    <p>Plan Details: <a href="{$plan_details_url}">{$plan_details_url}</a></p>
    {if $plan_downgrade_to}
    <p>Plan automatically downgrades to: <b>{$plan_downgrade_to}</b> 
    	if payment is due more than {$plan_grace_period} day(s)</p>
    {/if}
    <p>Payment Schedule: <b>{$schedule_name}</b></p>
    <p>Payment Schedule description: {$schedule_description}</p>
    <p>Charge Amount: <b>${$schedule_charge_amount}</b></p>
    <p>Charge Period: <b>{$schedule_charge_period}</b> days</p>
  </div>
  
  {if count($charges)}
  <div>
  	<p>Account Debts</p>
  	<ul>
  	{foreach $charges as $c}
  		<li>$c.datetime $c.amount</li>
  	{/foreach}
  	</ul>
  </div>
  {/if}
</body>
</html>
