<?php
require_once(__DIR__.'/admin.php');

$ADMIN_SECTION = 'outstanding';

include(__DIR__.'/view/outstanding.php');

require_once(__DIR__.'/header.php');

StartupAPI::$template->display('@admin/outstanding.html.twig', $template_data);

require_once(__DIR__.'/footer.php');
