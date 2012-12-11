<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'payment_method';

include(dirname(__FILE__).'/view/choose_engine.php');

require_once(dirname(__FILE__).'/header.php');

StartupAPI::$template->display('@admin/choose_engine.html.twig', $template_data);

require_once(dirname(__FILE__).'/footer.php');
