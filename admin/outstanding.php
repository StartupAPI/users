<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'outstanding';

include(dirname(__FILE__).'/view/outstanding.php');

require_once(dirname(__FILE__).'/header.php');

StartupAPI::$template->display('@admin/outstanding.html.twig', $template_data);

require_once(dirname(__FILE__).'/footer.php');
