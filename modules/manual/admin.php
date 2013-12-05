<?php

  # Ensure we are serving admin
  require_once(dirname(dirname(__DIR__)).'/admin/admin.php');

  $ADMIN_SECTION = 'payment_method';
  $BREADCRUMB_EXTRA = 'Manual';
  require_once(dirname(dirname(__DIR__)).'/admin/header.php');

  $action = isset($_REQUEST['action']) ? htmlspecialchars($_REQUEST['action']) : '';
  switch($action) {

    case 'ProcessAddPayment':
    case 'ProcessRefund':

      $account_id = isset($_REQUEST['account_id']) ? intval(htmlspecialchars($_REQUEST['account_id'])) : NULL;
      $amount = isset($_REQUEST['howmuch']) ? sprintf("%.2f",htmlspecialchars($_REQUEST['howmuch'])) : NULL;

      $subject = $action == 'ProcessRefund' ? 'Refund' : 'Payment';
      if (is_null($account = Account::getByID($account_id))) {
      ?>
        <p>Can't find account with ID <?php echo $account_id ?></p>
      <?php
        break;
      } elseif(is_null($amount) || !is_numeric($amount)) {
      ?>
        <p>Please enter numeric value for amount.</p>
      <?php
      } else {
        $engine = $account->getPaymentEngine();
        if(is_null($engine)) {
        ?>
        <p>Can't find Payment Engine for account <?php echo $account_id ?>. Should be 'manual'.</p>
        <?php
          break;
        } else {
          $data = array('account_id' => $account_id, 'amount' => $amount);

          $operator = User::require_login();

          if($action == 'ProcessRefund') {
            $tr_id = $engine->refund($data);
          }
          else {
            $tr_id = $engine->paymentReceived($data);
          }

          if($tr_id) {

            $engine->storeTransactionDetails($tr_id, array(
              'operator_id' => $operator->getID(),
              'funds_source' => htmlspecialchars($_REQUEST['funds_source']),
              'comment' => htmlspecialchars($_REQUEST['comment'])
            ));

            echo "<p>", $subject, " of ", $amount, " recorded.</p>\n";
          } else {
            echo "<p>",$subject," can not be recorded - please check server logs.</p>";
          }
        }
      }


    case 'DisplayAddPayment':
    case 'DisplayMakeRefund':

      $hint = $action == 'DisplayMakeRefund' ? 'Make refund' : 'Add funds';
      $process = $action == 'DisplayMakeRefund' ? 'ProcessRefund' : 'ProcessAddPayment';
      $source = $action == 'DisplayMakeRefund' ? 'Reason' : 'Funds source';
      $account_id = isset($_REQUEST['account_id']) ? intval(htmlspecialchars($_REQUEST['account_id'])) : NULL;
      if (is_null($account = Account::getByID($account_id))) {
      ?>
        <p>Can't find account with ID <?php echo $account_id ?></p>
      <?php
        break;
      } else {
        $balance = preg_replace("/(^|-)/","$$1",sprintf("%.2f",$account->getBalance()),1);
      ?>
      <div>
        <form action="">
        <input type="hidden" name="action" value="<?php echo $process ?>" />
        <input type="hidden" name="account_id" value="<?php echo $account_id ?>" />
        <p><?php echo $hint ?> for account '<b><?php echo $account->getName() ?></b>' (Current Balance: <b><?php echo $balance ?></b>
          ID: <b><?php echo $account_id?>)</b></p>
        <table>
        <tr><td>Amount:</td><td><input type="text" id="howmuch" name="howmuch" /></td></tr>
        <tr><td><?php echo $source ?>:</td><td><input type="text" id="funds_source" name="funds_source" /></td></tr>
        <tr><td>Comment:</td><td><input type="text" id="comment" name="comment" /></td></tr>
        </table>
        <p><input type="submit" value="Ok" /></p>
        </form>
      </div>

      <?php
      }
      break;

    default:

      # Display account list and filter form
      ?>
		<div class="span9">
<table cellpadding="5" cellspacing="0" border="1" width="100%">
<tr><th>ID</th><th>Name</th><th>Plan</th><th>Schedule</th><th>Active</th><th>Balance</th><th>&nbsp;</th><th>&nbsp;</th></tr>
<?php
      $perpage = 20;
      $pagenumber = 0;

      if (array_key_exists('page', $_GET))
        $pagenumber = $_GET['page'];

      $search = null;
      if (array_key_exists('q', $_GET)) {
        $search = trim($_GET['q']);
        if ($search == '')
          $search = null;
      }

      $sort_by = array(
        'id' => 'ID',
        'name' => 'Account Name',
        'plan_slug' => 'Payment Plan',
        'schedule_slug' => 'Payment Schedule',
        'active' => 'Account status',
        'balance' => 'Account balance');

      if (array_key_exists('sort', $_GET) && in_array($_GET['sort'],array_keys($sort_by)))
        $sortby = $_GET['sort'];
      else
        $sortby = 'id';

      $db = UserConfig::getDB();

      if (!($stmt = $db->prepare('SELECT id,name,plan_slug,schedule_slug,active,COALESCE(SUM(amount),0) AS balance FROM '.
        UserConfig::$mysql_prefix.'accounts AS a LEFT JOIN '.UserConfig::$mysql_prefix.'account_charge AS c '.
        'ON c.account_id = a.id WHERE engine_slug = "manual" '.(is_null($search) ? '' : 'AND name like ? ').
        'GROUP BY a.id ORDER BY '.$sortby.' LIMIT '.$perpage.' OFFSET '.$pagenumber * $perpage))) {
			throw new DBPrepareStmtException($db);
		}

      if(!is_null($search)) {
        $search_like = '%'.$search.'%';
        if (!$stmt->bind_param('s',$search_like)) {
			throw new DBBindParamException($db, $stmt);
		}
      }

      if (!$stmt->execute()) {
		throw new DBExecuteStmtException($db, $stmt);
	  }

      if (!$stmt->bind_result($id, $name, $plan_slug, $schedule_slug, $active, $balance)) {
		throw new DBBindResultException($db, $stmt);
	  }

      $accounts = array();
      while($stmt->fetch() === TRUE)
        $accounts[] = array(
          'id' => $id,
          'name' => $name,
          'plan_slug' => $plan_slug,
          'schedule_slug' => $schedule_slug,
          'active' => $active,
          'balance' => $balance);

      $stmt->close();

      ?>
      <tr><td colspan="8" valign="middle">
      <?php
      if (count($accounts) == $perpage) {
        ?><a style="float: right" href="?page=<?php echo $pagenumber+1; echo is_null($search) ? '' : '&q='.urlencode($search)?>">next &gt;&gt;&gt;<a><?php
      }
      else {
        ?><span style="color: silver; float: right">next &gt;&gt;&gt;</span><?php
      }
      if ($pagenumber > 0) {
        ?><a style="float: left" href="?page=<?php echo $pagenumber-1; echo is_null($search) ? '' : '&q='.urlencode($search) ?>">&lt;&lt;&lt;prev</a><?php
      }
      else {
        ?><span style="color: silver; float: left">&lt;&lt;&lt;prev</span><?php
      }
      ?>
      <span style="float: left; margin: 0 2em 0 1em;">Page <?php echo $pagenumber+1?></span>
      <form action="" id="search" name="search">
      <input type="text" id="q" name="q"<?php echo is_null($search) ? '' : ' value="'.htmlspecialchars($search).'"'?>/><input type="submit" value="search"/><input type="button" value="clear" onclick="document.getElementById('q').value=''; document.search.submit()"/>
      Sort by
      <select name="sort" onchange="document.search.submit();">
      <?php
        foreach($sort_by as $k => $v)
          echo "<option value=\"".$k."\" ".($sortby == $k ? ' selected="yes"' : '')." >".$v."</option>\n";
      ?>
      </select>
      </form>
      </td></tr>
      <?php
      foreach($accounts as $a) {

        echo "<tr valign=\"top\">\n";
        echo "<td><a href=\"".UserConfig::$USERSROOTURL."/admin/account.php?id=".$a['id']."\">".$a['id']."</a></td>\n";
        echo "<td><a href=\"".UserConfig::$USERSROOTURL."/admin/account.php?id=".$a['id']."\">".$a['name']."</a></td>\n";
        echo "<td>".$a['plan_slug']."</td><td>".$a['schedule_slug']."</td><td>".($a['active'] ? 'Active' : 'Suspended')."</td>\n";
        $a['balance'] = preg_replace("/(^|-)/","$$1",sprintf("%.2f",$a['balance']),1);
        echo "<td>".$a['balance']."</td><td><a href=\"?action=DisplayAddPayment&account_id=".$a['id']."\">Add funds</a></td>";
        echo "<td><a href=\"?action=DisplayMakeRefund&account_id=".$a['id']."\">Refund</a></td></tr>\n";
      }
      ?>
      <tr><td colspan="8">
      <?php
      if (count($accounts) == $perpage) {
        ?><a style="float: right" href="?page=<?php echo $pagenumber+1; echo is_null($search) ? '' : '&q='.urlencode($search)?>">next &gt;&gt;&gt;</a><?php
      }
      else {
        ?><span style="color: silver; float: right">next &gt;&gt;&gt;</span><?php
      }

      if ($pagenumber > 0) {
        ?><a style="float: left" href="?page=<?php echo $pagenumber-1; echo is_null($search) ? '' : '&q='.urlencode($search)?>">&lt;&lt;&lt;prev</a><?php
      }
      else {
        ?><span style="color: silver; float: left">&lt;&lt;&lt;prev</span><?php
      }
      ?>
      <span style="float: left; margin-left: 2em">Page <?php echo $pagenumber+1?></span>

      </td></tr>
      </table>
</div>
      <?php

      # end of default
  }

  if($action != '')
    echo "<p><a href=\"".UserConfig::$USERSROOTURL."/modules/manual/admin.php\">Back to list</a></p>\n";
  require_once(dirname(dirname(__DIR__)).'/admin/footer.php');
