/**
 * Define global namespace if it's not set yet
 */
window.STARTUPAPI = window.STARTUPAPI || {};

/*
 * gets an array of messages and if one of them was passed, then displays it
 */

STARTUPAPI.showMessages = function(pagemessages) {
	if (document.location.hash.substring(0, 9) == '#message=')
	{
		var msg = document.location.hash.substring(9);

		for (var key in pagemessages)
		{
			if (pagemessages.hasOwnProperty(key) && msg == key)
			{
				var container = $('#startupapi-message');
				var alert_box = $('#startupapi-message .alert');

				$('#startupapi-message_copy').html(pagemessages[key]['text']);

				container.show();

				alert_box.slideDown('fast').addClass('alert-' + pagemessages[key]['class']);

				alert_box.hover(function() {
					STARTUPAPI.keepMessageOpen = true;
				}, function() {
					STARTUPAPI.keepMessageOpen = false;

					setTimeout(STARTUPAPI.hideMessages, 1000);
				});

				setTimeout(STARTUPAPI.hideMessages, 2500);

				document.location.hash = '#';

				return;
			}
		}
	}
}

STARTUPAPI.hideMessages = function() {
	if (STARTUPAPI.keepMessageOpen) {
		return;
	}

	$('#startupapi-message').slideUp('fast');
};
