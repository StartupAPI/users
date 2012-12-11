<?php

require_once(dirname(dirname(__FILE__)) . '/admin.php');

# Getting users one by one is quite ineffective, so building query to do this
$db = UserConfig::getDB();

if (!($stmt = $db->prepare('SELECT a.id, a.name, a.plan_slug, a.schedule_slug, SUM(c.amount) AS debt FROM ' . UserConfig::$mysql_prefix .
		'accounts AS a JOIN ' . UserConfig::$mysql_prefix . 'account_charge AS c ON a.id = c.account_id GROUP BY c.account_id HAVING debt < 0'))) {
	throw new Exception("Can't prepare statement: " . $db->error);
}

if (!$stmt->execute()) {
	throw new Exception("Can't execute statement: " . $stmt->error);
}

if (!$stmt->bind_result($id, $name, $plan, $schedule, $debt)) {
	throw new Exception("Can't bind result: " . $stmt->error);
}

$debtors = array();
while ($stmt->fetch() === TRUE) {
	$debtors[] = array('id' => $id, 'name' => $name, 'plan' => $plan, 'schedule' => $schedule, 'debt' => sprintf("%.2f", abs($debt)));
}

$stmt->close();

$template_data['debtors'] = $debtors;
$template_data['USERSROOTURL'] = UserConfig::$USERSROOTURL;
