// Use webdriverjs to create a Selenium Client
var client = require('webdriverio').remote({
    desiredCapabilities: {
        // You may choose other browsers
        // http://code.google.com/p/selenium/wiki/DesiredCapabilities
        browserName: 'chrome'
    },
    // webdriverjs has a lot of output which is generally useless
    // However, if anything goes wrong, remove this to see more details
    logLevel: 'silent'
});

var base_url = process.argv[2];
var run_images = process.argv[3];
var screenshot_prefix = 'login_page';
var screenshot_index = 0;

client.init()
	.addCommand("record", function(cb) {
		var image_file = run_images + '/' + screenshot_prefix + (screenshot_index++) + '.png';
		this.saveScreenshot(image_file).call(cb);
	})	
	.addCommand("login", function(url, user, pw, cb) {
		this.url(url)
		.record()
		.setValue('#startupapi-usernamepass-username', user)
		.setValue('#startupapi-usernamepass-pass', pw)
		.record()
		.submitForm('#startupapi-usernamepass-username')
		.pause(3000)
		.call(cb);
	})	
	.setViewportSize({width: 1280, height: 1024}, false)
	.login(base_url + '/login.php', 'sergeychernyshev', 'bogus')
	.record()
	.url(base_url + '/edit.php')
	.record()
	.end();
