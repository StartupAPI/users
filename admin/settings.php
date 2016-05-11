<?php
namespace StartupAPI;

/**
 * This page displays current state of the settings in the system
 *
 * Eventually it'll be converted to settings wizard
 *
 * @package StartupAPI
 * @subpackage Admin
 */
require_once(__DIR__ . '/admin.php');

/* Getting $config_variables from settings.inc file */
require_once(__DIR__ . '/settings.inc');

$ADMIN_SECTION = 'systemsettings';
require_once(__DIR__ . '/header.php');
?>
<div class="span9">
	<p>All available Startup API settings and their current values of are shown below.</p>
	<p>To make changes to your configuration, click <span class="btn btn-mini disabled"><i class="icon-cog"></i> code</span> button and copy the code to <tt>users_config.php</tt> file in your application folder.</p>

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
	<?php if (isset($section['description'])) { ?>
		<p><?php echo $section['description']; ?></p>
	<?php } ?>
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
				if (!is_object($setting) || get_class($setting) != 'StartupAPI\Setting') {
					continue;
				}

				?>
				<tr class="startupapi-admin-setting">
					<td>
						<h4><?php echo $setting->getDescription() ?></h4>
						<p>
							<span class="btn btn-mini">
								<i class="icon-cog"></i>
								<span class="calltoaction">code</span>
							</span>
							<span class="variable-name">UserConfig::$<?php echo $setting->getName() ?> (<?php echo $setting->phpType() ?>)</span>
						</p>
					</td>

					<td>
						<?php
						$options = array_key_exists('options', $setting) ? $setting['options'] : array();

						if (substr($setting->getType(), -2) == '[]' && is_array(UserConfig::${$setting->getName()})) {
							if (count(UserConfig::${$setting->getName()}) == 0) {
								?>
								<span class="startupapi-admin-setting-value null">&mdash;</span>
								<?php
							}
							foreach (UserConfig::${$setting->getName()} as $value) {
								?>
								<div>
									<?php
									echo $setting->value($value);
									?>
								</div>
								<?php
							}
						} else {
							echo $setting->value(UserConfig::${$setting->getName()});
						}
						?>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<pre><?php echo $setting->code(UserConfig::${$setting->getName()})?></pre>
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
