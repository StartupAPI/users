<!-- footer starts -->
<footer class="footer startupapi-footer">
	<div class="container">
		<div class="pull-right">
			<a href="http://www.startupapi.com/" target="_blank">
				<img class="startupapi-logo" width="20" height="20" src="<?php echo UserConfig::$USERSROOTURL ?>/images/header_icon.png"/>
				Powered by Startup API
			</a>
		</div>
		<div>
			<?php
			if (!is_null(UserConfig::$appName)) {
				?>
				<?php echo date('Y') ?>
				&copy;
				<a href="<?php echo UserConfig::$SITEROOTURL ?>">
					<?php echo UserConfig::$appName; ?>
				</a>
				<?php
			}
			?>
		</div>
	</div>
</footer>
</body>
</html>
