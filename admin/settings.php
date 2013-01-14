<?php
/**
 * This page displays current state of the settings in the system
 *
 * Eventually it'll be converted to settings wizard
 *
 * @todo Add type and value validation and report wrongly-configured values, (e.g. non-existing folders or type mismatches)
 *
 * @package StartupAPI
 * @subpackage Admin
 */
require_once(__DIR__ . '/admin.php');

function phpType($type) {
	if (substr($type, -2) == '[]') {
		return phpType(substr($type, 0, -2)) . '[]';
	}

	if ($type == 'seconds') {
		return 'int';
	}
	if ($type == 'minutes') {
		return 'int';
	}
	if ($type == 'days') {
		return 'int';
	}
	if ($type == 'path') {
		return 'string';
	}
	if ($type == 'url') {
		return 'string';
	}
	if ($type == 'cookie-key') {
		return 'string';
	}
	if ($type == 'secret') {
		return 'string';
	}
	if ($type == 'user-id') {
		return 'int';
	}

	return $type;
}

/**
 * Prints PHP value to be included in a code snippen
 *
 * @param string $type PHP variable type: 'boolean', 'int', 'string' and special type 'secret' for variables to not be shown in UI
 * @param mixed $value Value of the variable
 */
function codeValue($type, $value, $options = array()) {
	if (substr($type, -2) == '[]' && is_array($value)) {
		?>array(<?php
		$first = true;
		foreach ($value as $val) {
			if (!$first) {
				?>, <?php
			}
			codeValue(substr($type, 0, -2), $val);
			$first = false;
		}
		?>)<?php
		return;
	}

	if (is_null($value)) {
		?><span class="startupapi-admin-code-value null">null</span><?php
	} else if ($type == 'boolean') {
		?><span class="startupapi-admin-code-value boolean"><?php echo $value ? 'true' : 'false' ?></span><?php
	} else if ($type == 'int' || $type == 'user-id' || $type == 'seconds' || $type == 'minutes') {
		?><span class="startupapi-admin-code-value int"><?php echo UserTools::escape($value) ?></span><?php
	} else if ($type == 'secret') {
		?>'<span class="startupapi-admin-code-value secret" title="Actual value is not shown for security reasons">****************</span>'<?php
	} else if ($type == 'string' || $type == 'path' || $type == 'url' || $type == 'cookie-key') {
		?>'<span class="startupapi-admin-code-value string"><?php echo UserTools::escape($value) ?></span>'<?php
	} else if ($type == 'callable') {
		if (is_string($value)) {
			?>'<span class="startupapi-admin-code-value callable"><?php echo UserTools::escape($value) ?></span>'<?php
		} else if (is_array($value)) {
			?><span class="startupapi-admin-code-value callable">array('<?php echo UserTools::escape($value[0]) ?>', '<?php echo UserTools::escape($value[1]) ?>')</span><?php
		} else if (get_class($value) == 'Closure') {
			$arguments = is_array($options) && array_key_exists('arguments', $options) ? $options['arguments'] : array();
			$var_names = array_map(function($argument) {
						return '$' . $argument;
					}, $arguments);
			?><span class="startupapi-admin-code-value callable">function(<?php echo join(', ', $var_names) ?>) { &hellip; }</span><?php
		}
	} else {
		?><span class="startupapi-admin-code-value unknown"><?php echo UserTools::escape(var_export($value, true)) ?></span><?php
	}
}

/**
 * Prints setting value in a way appropriate for a type of the variable
 *
 * @param string $type Setting type: 'boolean', 'seconds', 'path', 'url', 'secret', 'cookie-key'
 * @param mixed $value Value of the setting
 * @param mixed[] $options Array of additional options that might be needed by some types
 */
function value($type, $value, $options = array()) {
	if (is_null($value)) {
		?><span class="startupapi-admin-setting-value null">&mdash;</span><?php
	} else if ($type == 'boolean') {
		$true_string = is_array($options) && array_key_exists('true_string', $options) ? $options['true_string'] : 'yes';
		$false_string = is_array($options) && array_key_exists('false_string', $options) ? $options['false_string'] : 'no';
		?><span class="badge<?php echo $value ? ' badge-success' : '' ?>"><?php echo $value ? $true_string : $false_string ?></span><?php
	} else if ($type == 'seconds') {
		?><span class="startupapi-admin-setting-value seconds"><?php echo UserTools::escape($value) ?></span> seconds
		(<?php echo UserTools::escape(intval($value / 60 / 60 / 24)) ?> days)<?php
	} else if ($type == 'minutes') {
		?><span class="startupapi-admin-setting-value minutes"><?php echo UserTools::escape($value) ?></span> minutes<?php
	} else if ($type == 'days') {
		?><span class="startupapi-admin-setting-value days"><?php echo UserTools::escape($value) ?></span> days<?php
	} else if ($type == 'path') {
		?><span class="startupapi-admin-setting-value path"><?php echo UserTools::escape($value) ?></span><?php
	} else if ($type == 'url') {
		?><a class="startupapi-admin-setting-value url" target="_blank" href="<?php echo UserTools::escape($value) ?>"><?php echo UserTools::escape($value) ?><i class="icon-share-alt"></i></a><?php
	} else if ($type == 'secret') {
		?><span class="startupapi-admin-setting-value secret">hidden</span><?php
	} else if ($type == 'cookie-key') {
		?><span class="badge badge-warning"><i class="icon-tags icon-white"></i> <?php echo UserTools::escape($value) ?></span><?php
	} else if ($type == 'int') {
		?><span class="startupapi-admin-setting-value int"><?php echo UserTools::escape($value) ?></span><?php
	} else if ($type == 'string') {
		?>'<span class="startupapi-admin-setting-value string"><?php echo UserTools::escape($value) ?></span>'<?php
	} else if ($type == 'callable') {
		$arguments = is_array($options) && array_key_exists('arguments', $options) ? $options['arguments'] : array();
		$var_names = array_map(function($argument) {
					return '$' . $argument;
				}, $arguments);
		if (is_string($value)) {
			?><span class="startupapi-admin-setting-value callable"><?php echo UserTools::escape($value) ?>(<?php echo join(', ', $var_names) ?>)</span><?php
		} else if (is_array($value)) {
			?><span class="startupapi-admin-setting-value callable"><?php echo UserTools::escape($value[0]) ?>::<?php echo UserTools::escape($value[1]) ?>(<?php echo join(', ', $var_names) ?>)</span><?php
		} else if (get_class($value) == 'Closure') {
			?><span class="startupapi-admin-setting-value callable">function(<?php echo join(', ', $var_names) ?>) { &hellip; }</span><?php
		}
	} else if ($type == 'user-id') {
		?><a href="user.php?id=<?php echo $value ?>"><i class="icon-user"></i> <?php echo UserTools::escape(User::getUser($value)->getName()) ?></a><?php
	} else {
		?><pre class="t8-toggle startupapi-admin-setting-value unknown"><?php echo UserTools::escape(var_export($value, true)) ?></pre><?php
	}
}

/* Getting $config_variables from settings.inc file */
require_once(__DIR__ . '/settings.inc');

$ADMIN_SECTION = 'systemsettings';
$BREADCRUMB_EXTRA = $plan->name;
require_once(__DIR__ . '/header.php');
?>
<div class="span9">
	<p>All available Startup API settings and their current values of are shown below.</p>
	<p>To make changes to your configuration, click "code" button and copy the code show to <tt>users_config.php</tt> file in your application folder.</p>

<ul>
	<?php foreach ($config_variables as $section) {
		?>
		<li><a href="#<?php echo $section['id'] ?>"><?php echo $section['name'] ?></a></li>
		<?php
	}
	?>
</ul>

<?php
foreach ($config_variables as $section) {
	?>
	<h2 id="<?php echo $section['id'] ?>"><?php echo $section['name'] ?> <a href="#" title="go to top of the page"><i class="icon-th-list"></i></a></h2>
	<p><?php echo $section['description']; ?></p>
	<?php
	foreach ($section['groups'] as $group) {
		if (array_key_exists('description', $group)) {
			?>
			<p><?php echo $group['description'] ?></p>
			<?php
		}

		$id_html = '';
		if (array_key_exists('id', $group)) {
			$id_html = ' id="' . $group['id'] . '"';
		}
		?>
		<table<?php echo $id_html ?> class="table">
			<tr>
				<th>Setting description</th>
				<th>Current Value(s)</th>
			</tr>
			<?php
			foreach ($group['settings'] as $setting) {
				$var_name = $setting['name'];

				$code = "";
				?>
				<tr class="startupapi-admin-setting">
					<td>
						<p><?php echo $setting['description'] ?></p>
						<p>
							<span class="btn btn-mini">
								<i class="icon-cog"></i>
								<span class="calltoaction">code</span>
							</span>
							<span class="variable-name">UserConfig::$<?php echo $setting['name'] ?> (<?php echo phpType($setting['type']) ?>)</span>
						</p>
					</td>

					<td>
						<?php
						if (substr($setting['type'], -2) == '[]' && is_array(UserConfig::$$var_name)) {
							if (count(UserConfig::$$var_name) == 0) {
								?><span class="startupapi-admin-setting-value null">&mdash;</span><?php
				}
				foreach (UserConfig::$$var_name as $value) {
								?>
								<div>
									<?php
									value(substr($setting['type'], 0, -2), $value, $setting['options']);
									?>
								</div>
								<?php
							}
						} else {
							value($setting['type'], UserConfig::$$var_name, $setting['options']);
						}
						?>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<pre>
/**
 * @var <?php echo phpType($setting['type']) ?> <?php echo $setting['description'] ?>.
 */
UserConfig::$<?php echo $setting['name'] ?> = <?php codeValue($setting['type'], UserConfig::$$var_name, $setting['options']) ?>;</pre>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php
	}
}
?>
</div>
<script src="<?php echo UserConfig::$USERSROOTURL ?>/trunk8/trunk8.js"></script>

<script>
	$('.startupapi-admin-setting .btn').click(function() {
		var tr = $(this).parent().parent().parent();
		tr.next().toggle(75);
		tr.find('.btn').toggleClass('btn-inverse');
		tr.find('.icon-cog').toggleClass('icon-white');
	});

	$('.t8-toggle').trunk8({
		fill: '&hellip; <a class="read-more" href="#">see all</a>'
	});

	$('.read-more').live('click', function (event) {
		$(this).parent().trunk8('revert').append(' <a class="read-less" href="#">see less</a>');

		return false;
	});

	$('.read-less').live('click', function (event) {
		$(this).parent().trunk8();

		return false;
	});
</script>

<?php
require_once(__DIR__ . '/footer.php');