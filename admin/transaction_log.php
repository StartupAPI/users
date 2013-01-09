<?php
require_once(__DIR__.'/admin.php');

$ADMIN_SECTION = 'transactions';

include(__DIR__.'/view/transaction_log.php');

require_once(__DIR__.'/header.php');

StartupAPI::$template->display('@admin/transaction_log.html.twig', $template_data);

require_once(__DIR__.'/footer.php');
