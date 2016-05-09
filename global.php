<?php
namespace StartupAPI;

mb_language('uni');
mb_internal_encoding('UTF-8');
header('Content-type: text/html; charset=utf-8');

/**
 * Autoloader for StartupAPI classes to be loaded from ./classes/ folder in the project
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {
  error_log("Loading $class");

  // project-specific namespace prefix
  $prefix = 'StartupAPI\\';

  // base directory for the namespace prefix
  $base_dir = __DIR__ . '/classes/';
  $modules_dir = __DIR__ . '/modules/';

  // does the class use the namespace prefix?
  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) {
    // no, move to the next registered autoloader
    return;
  }

  // get the relative class name
  $relative_class = substr($class, $len);

  // replace the namespace prefix with the base directory, replace namespace
  // separators with directory separators in the relative class name, append
  // with .php
  $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

  // If class is in \StartupAPI\Modules namespace, load it from modules folder
  if (strpos($relative_class, 'Modules\\') === 0) {
    $module_class_name = substr($relative_class, strlen('Modules\\'));
    $module_namespace_name = preg_replace('/\\.*$/', '', $module_class_name);
    foreach (StartupAPIModule::$builtin_modules as $module_slug => $module) {
      // if this is one of the module classes, load them from modules folder instead
      if ($module['class'] == $module_namespace_name) {
        $file = $modules_dir . '/' .
          $module_slug . '/' .
          str_replace('\\', '/', $module_class_name) .
          '.php';
        break;
      }
    }
  }

  // if the file exists, require it
  if (file_exists($file)) {
    error_log("Including $class from $file");
    require_once $file;
  } else {
    error_log("Can't load class $class from $file");
  }
});

require_once(__DIR__.'/default_config.php');
require_once(dirname(__DIR__).'/users_config.php');

StartupAPI::init();
