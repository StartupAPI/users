<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'accounts';

include(dirname(__FILE__).'/view/account.php');

require_once(dirname(__FILE__).'/header.php');

StartupAPI::$template->display('@admin/account.html.twig', $template_data);

require_once(dirname(__FILE__).'/footer.php');