<?php
require_once(__DIR__ . '/admin.php');

$ADMIN_SECTION = 'accounts';

include(__DIR__ . '/view/account.php');

require_once(__DIR__ . '/header.php');

StartupAPI::$template->display('@admin/account.html.twig', $template_data);

require_once(__DIR__ . '/footer.php');