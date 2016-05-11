<?php
namespace StartupAPI;

/**
 * @package StartupAPI
 */
class Setting {
  private $type;
  private $name;
  private $description;
  private $options;

  const TYPE_BOOLEAN     = 'boolean';
  const TYPE_INT         = 'int';
  const TYPE_USER_ID     = 'user-id';
  const TYPE_SECONDS     = 'seconds';
  const TYPE_MINUTES     = 'minutes';
  const TYPE_DAYS        = 'days';
  const TYPE_SECRET      = 'secret';
  const TYPE_STRING      = 'string';
  const TYPE_PATH        = 'path';
  const TYPE_URL         = 'url';
  const TYPE_COOKIE_KEY  = 'cookie-key';
  const TYPE_CALLABLE    = 'callable';

  const TYPE_BOOLEAN_ARRAY     = 'boolean[]';
  const TYPE_INT_ARRAY         = 'int[]';
  const TYPE_USER_ID_ARRAY     = 'user-id[]';
  const TYPE_SECONDS_ARRAY     = 'seconds[]';
  const TYPE_MINUTES_ARRAY     = 'minutes[]';
  const TYPE_DAYS_ARRAY        = 'days[]';
  const TYPE_SECRET_ARRAY      = 'secret[]';
  const TYPE_STRING_ARRAY      = 'string[]';
  const TYPE_PATH_ARRAY        = 'path[]';
  const TYPE_URL_ARRAY         = 'url[]';
  const TYPE_COOKIE_KEY_ARRAY  = 'cookie-key[]';
  const TYPE_CALLABLE_ARRAY    = 'callable[]';

  function __construct($type, $name, $description, $options = array()) {
    $this->type = $type;
    $this->name = $name;
    $this->description = $description;
    $this->options = $options;
  }

  public function getName() {
    return $this->name;
  }

  public function getType() {
    return $this->type;
  }

  public function getDescription() {
    return $this->description;
  }

  private static function type2PHPType($type) {
    if ($type == self::TYPE_BOOLEAN) {
      return 'boolean';
    }

    if ($type == self::TYPE_CALLABLE) {
      return 'callable';
    }

    if ($type == self::TYPE_INT
      || $type == self::TYPE_SECONDS
      || $type == self::TYPE_MINUTES
      || $type == self::TYPE_DAYS
      || $type == self::TYPE_USER_ID
    ) {
      return 'int';
    }
    if ($type == self::TYPE_STRING
      || self::TYPE_PATH
      || self::TYPE_URL
      || self::TYPE_COOKIE_KEY
      || self::TYPE_SECRET
      || self::TYPE_COOKIE_KEY
      || self::TYPE_COOKIE_KEY
    ) {
      return 'string';
    }

    return $type;
  }

  /**
   * Returns PHP types of the configuration variables
   *
   * @param string $this->type StartupAPI configuration value type
   * @return string PHP type of the variable
   */
  public function phpType() {
    if ($this->type == self::TYPE_BOOLEAN_ARRAY) { return self::type2PHPType(self::TYPE_BOOLEAN). '[]'; }
    if ($this->type == self::TYPE_INT_ARRAY) { return self::type2PHPType(self::TYPE_INT). '[]'; }
    if ($this->type == self::TYPE_USER_ID_ARRAY) { return self::type2PHPType(self::TYPE_USER_ID). '[]'; }
    if ($this->type == self::TYPE_SECONDS_ARRAY) { return self::type2PHPType(self::TYPE_SECONDS). '[]'; }
    if ($this->type == self::TYPE_MINUTES_ARRAY) { return self::type2PHPType(self::TYPE_MINUTES). '[]'; }
    if ($this->type == self::TYPE_DAYS_ARRAY) { return self::type2PHPType(self::TYPE_DAYS). '[]'; }
    if ($this->type == self::TYPE_SECRET_ARRAY) { return self::type2PHPType(self::TYPE_SECRET). '[]'; }
    if ($this->type == self::TYPE_STRING_ARRAY) { return self::type2PHPType(self::TYPE_STRING). '[]'; }
    if ($this->type == self::TYPE_PATH_ARRAY) { return self::type2PHPType(self::TYPE_PATH). '[]'; }
    if ($this->type == self::TYPE_URL_ARRAY) { return self::type2PHPType(self::TYPE_URL). '[]'; }
    if ($this->type == self::TYPE_COOKIE_KEY_ARRAY) { return self::type2PHPType(self::TYPE_COOKIE_KEY). '[]'; }
    if ($this->type == self::TYPE_CALLABLE_ARRAY) { return self::type2PHPType(self::TYPE_CALLABLE). '[]'; }

    return self::type2PHPType($this->type);
  }
  /**
   * Prints PHP value to be included in a code snippen
   *
   * @param string $this->type PHP variable type: 'boolean', 'int', 'string' and special type 'secret' for variables to not be shown in UI
   * @param mixed $value Value of the variable
   */
  function code($value, $value_only = false) {
    $setting_info = array(
      'type' => $this->type,
      'php_type' => $this->phpType(),
      'description' => $this->description,
      'name' => $this->name,
      'value' => $value
    );

  	if (substr($this->type, -2) == '[]' && is_array($value)) {
      $code_html = $value_only ? '' : rtrim(StartupAPI::$template->render(
        '@startupapi-admin/settings/code/code.html.twig',
        $setting_info
      )) . ' ';

      $code_html .= 'array(';
  		$first = true;
  		foreach ($value as $val) {
  			if (!$first) {
  				$code_html .= ', ';
  			}
        $sub_setting = new self(substr($this->type, 0, -2), $this->name, $this->description, $this->options);
  			$code_html .= rtrim($sub_setting->code($val, TRUE));
  			$first = false;
  		}
  		$code_html .= ')' . ($value_only ? '' : ';');
  		return $code_html;
  	}

    if (is_null($value)) {
      $template = '@startupapi-admin/settings/code/null.html.twig';
  	} else if ($this->type == self::TYPE_BOOLEAN) {
      $template = '@startupapi-admin/settings/code/boolean.html.twig';
  	} else if ($this->type == self::TYPE_INT
      || $this->type == self::TYPE_USER_ID
      || $this->type == self::TYPE_SECONDS
      || $this->type == self::TYPE_MINUTES
      || $this->type == self::TYPE_DAYS
    ) {
      $template = '@startupapi-admin/settings/code/value.html.twig';
  	} else if ($this->type == self::TYPE_SECRET) {
      $template = '@startupapi-admin/settings/code/secret.html.twig';
  	} else if ($this->type == self::TYPE_STRING
      || $this->type == self::TYPE_PATH
      || $this->type == self::TYPE_URL
      || $this->type == self::TYPE_COOKIE_KEY
    ) {
      $template = '@startupapi-admin/settings/code/string.html.twig';
  	} else if ($this->type == self::TYPE_CALLABLE) {
  		if (is_string($value)) {
        $template = '@startupapi-admin/settings/code/callable_string.html.twig';
  		} else if (is_array($value)) {
        $template = '@startupapi-admin/settings/code/callable_array.html.twig';
  		} else if (get_class($value) == 'Closure') {
        $setting_info['arguments'] = array_map(function($argument) {
              return '$' . $argument;
        }, is_array($this->options) && array_key_exists('arguments', $this->options) ? $this->options['arguments'] : array());

        $template = '@startupapi-admin/settings/code/callable_closure.html.twig';
  		}
  	} else {
      $setting_info['var_export'] = var_export($value, true);
      $template = '@startupapi-admin/settings/code/unknown.html.twig';
  	}

    return $code_html = ($value_only ? '' : rtrim(StartupAPI::$template->render(
      '@startupapi-admin/settings/code/code.html.twig',
      $setting_info
    )) . ' ') . rtrim(StartupAPI::$template->render(
      $template,
      $setting_info
    )) . ($value_only ? '' : ';');
  }

  /**
   * Prints setting value in a way appropriate for a type of the variable
   *
   * @param string $this->type Setting type: 'boolean', 'seconds', 'path', 'url', 'secret', 'cookie-key'
   * @param mixed $value Value of the setting
   * @param mixed[] $options Array of additional options that might be needed by some types
   */
  function value($value) {
  	if (is_null($value)) {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/null.html.twig',
        array('value' => $value));
  	} else if ($this->type == self::TYPE_BOOLEAN) {
  		$true_string = array_key_exists('true_string', $this->options)
        ? $this->options['true_string'] : 'yes';

  		$false_string = array_key_exists('false_string', $this->options)
        ? $this->options['false_string'] : 'no';

      return StartupAPI::$template->render('@startupapi-admin/settings/value/boolean.html.twig',
        array('value' => $value, 'true_string' => $true_string, 'false_string' => $false_string));
  	} else if ($this->type == self::TYPE_SECONDS) {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/seconds.html.twig',
        array('seconds' => $value, 'days' => intval($value / 60 / 60 / 24)));
  	} else if ($this->type == self::TYPE_MINUTES) {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/minutes.html.twig',
        array('minutes' => $value, 'days' => intval($value / 60 / 24)));
    } else if ($this->type == self::TYPE_DAYS) {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/days.html.twig',
        array('days' => $value));
    } else if ($this->type == self::TYPE_PATH) {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/path.html.twig',
        array('path' => $value));
    } else if ($this->type == self::TYPE_URL) {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/url.html.twig',
        array('url' => $value));
    } else if ($this->type == self::TYPE_SECRET) {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/secret.html.twig',
        array('url' => $value));
    } else if ($this->type == self::TYPE_COOKIE_KEY) {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/cookie_key.html.twig',
        array('key' => $value));
    } else if ($this->type == self::TYPE_INT) {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/int.html.twig',
        array('value' => $value));
    } else if ($this->type == self::TYPE_STRING) {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/string.html.twig',
        array('value' => $value));
    } else if ($this->type == self::TYPE_USER_ID) {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/user_id.html.twig',
        array('id' => $value, 'name' => User::getUser($value)->getName()));
    } else if ($this->type == self::TYPE_CALLABLE) {
      $arguments = is_array($this->options) && array_key_exists('arguments', $this->options)
          ? $this->options['arguments'] : array();

      if (is_string($value)) {
        return StartupAPI::$template->render('@startupapi-admin/settings/value/callable.html.twig',
          array('value' => $value, 'arguments' => $arguments));
      } else if (is_array($value)) {
        return StartupAPI::$template->render('@startupapi-admin/settings/value/callable.html.twig',
          array('value' => $value[0] . '::' . $value[1], 'arguments' => $arguments));
      } else if (get_class($value) == 'Closure') {
        return StartupAPI::$template->render('@startupapi-admin/settings/value/callable_closure.html.twig',
          array('arguments' => $arguments));
      }
    } else {
      return StartupAPI::$template->render('@startupapi-admin/settings/value/var_export.html.twig',
        array('var_export' => var_export($value, true)));
  	}
  }
}
