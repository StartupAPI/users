<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'transactions';

include(dirname(__FILE__).'/view/transaction_log.php');

require_once(dirname(__FILE__).'/header.php');

StartupAPI::$template->display('@admin/transaction_log.html.twig', $template_data);

require_once(dirname(__FILE__).'/footer.php');
