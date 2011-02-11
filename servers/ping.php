<?php
/**
 * ping our peers
 *
 * With a simple click you can advise other servers of your cloud that they should check your site again.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'servers.php';
include_once '../services/call.php'; // ping

// load the skin
load_skin('servers');

// the path to this page
$context['path_bar'] = array( 'servers/' => i18n::s('Servers') );

// the title of the page
$context['page_title'] = i18n::s('Ping the cloud');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// anonymous users are invited to log in or to register
} elseif(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('servers/ping.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// do the ping
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'ping')) {

	// list servers to be advertised
	if($servers = Servers::list_for_ping(0, 20, 'ping')) {

		$context['text'] .= '<p>'.i18n::s('Servers that have been notified').'</p><ul>';

		// ping each server
		foreach($servers as $server_url => $attributes) {
			list($server_ping, $server_label) = $attributes;

			$milestone = get_micro_time();
			$result = @Call::invoke($server_ping, 'weblogUpdates.ping', array(strip_tags($context['site_name']), $context['url_to_home'].$context['url_to_root']), 'XML-RPC');
			if($result[0])
				$label = round(get_micro_time() - $milestone, 2).' sec.';
			else
				$label = @$result[1];
			$context['text'] .= '<li>'.$server_label.' ('.$label.')</li>';

		}

		$context['text'] .= '</ul>';

	// no server to ping
	} else
		$context['text'] .= '<p>'.i18n::s('No server has been created yet.').'</p>';

	// back to the index of servers
	$menu = array('servers/' => i18n::s('Servers'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// remember this in log as well
	Logger::remember('servers/ping.php', 'The cloud has been pinged');

// operation has to be confirmed
} else {

	// introductory text
	$context['text'] .= '<p>'.i18n::s('This script will ping (<code>weblogUpdates.ping</code>) every server configured to be part of our cloud. Normally, the publication script does this automatically. However, no ping occurs for pages submitted by XML-RPC or by e-mail. Therefore, you should launch this script at least once per month to ensure everybody knows about this site.').'</p>';

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'
		.'<input type="hidden" name="action" value="ping" />'
		.Skin::build_submit_button(i18n::s('Yes, I want to ping the cloud'))
		.'</p></form>';

	// set the focus on the backup button
	$context['text'] .= JS_PREFIX
		.'$("go").focus();'."\n"
		.JS_SUFFIX."\n";

}

// render the skin
render_skin();

?>
