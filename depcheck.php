<?php
/**
 * Testing for all dependencies StartupAPI has
 * http://startupapi.org/Startup_API/Installation#Prerequisites
 */
$required_php_version = '5.4.0';
$required_extensions = array('mysqli', ['mcrypt', 'openssl'], 'curl', 'mbstring', 'json');

$current_php_version = phpversion();
if (version_compare($current_php_version, $required_php_version, '>=')) {
	echo "Using PHP version $current_php_version ... OK\n";
} else {
	echo("[Missing Dependency] You're using PHP version lower then required $required_php_version");
	exit(1);
}

$current_php_extensions = get_loaded_extensions();

foreach ($required_extensions as $requirement) {
	if (is_array($requirement)) {
		// check for one of the alternatives
		$requirement_met = false;
		foreach ($requirement as $extension) {
			if (in_array($extension, $current_php_extensions)) {
				echo "PHP $extension extension is loaded ... OK\n";
				$requirement_met = true;
				break;
			}
		}

		if (!$requirement_met) {
			echo("[Missing Dependency] You're using PHP installation which does not have at least one of the following extension enabled: " . implode(' or ', $requirement). "\n");
			exit(1);
		}
	} else {
		// check for required extension
		if (in_array($requirement, $current_php_extensions)) {
			echo "PHP $requirement extension is loaded ... OK\n";
		} else {
			echo("[Missing Dependency] You're using PHP installation which does not have '$extension' extension enabled\n");
			exit(1);
		}
	}
}
