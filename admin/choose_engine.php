<?php
require_once(__DIR__.'/admin.php');

$ADMIN_SECTION = 'payment_method';

include(__DIR__.'/view/choose_engine.php');

require_once(__DIR__.'/header.php');

StartupAPI::$template->display('@admin/choose_engine.html.twig', $template_data);

require_once(__DIR__.'/footer.php');
