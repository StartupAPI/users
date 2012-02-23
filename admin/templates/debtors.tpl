  {if !empty($message) }
    {foreach from=$message item=msg}
    <h2>{$msg}</h2>
    {/foreach}
  {/if}
  {if empty($fatal) }
  <div>
	Accounts with debts:
	</div>
	<div class="debtors">
    <table class="debtors">
    <tr><th>Account</th><th>Payment Plan</th><th>Payment Schedule</th><th>Debt</th></tr>
    {foreach from=$debtors item=debtor}
      <tr><td><a href="{$USERSROOTURL}/admin/account.php?account_id={$debtor.id}">{$debtor.name}</a></td>
      <td>{$debtor.plan}</td><td>{$debtor.schedule}</td><td>-${$debtor.debt}</td></tr>
    {/foreach}
    </table>
  </div>
  {/if}
