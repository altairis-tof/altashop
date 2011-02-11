<?php
/**
 * share screen of this user
 *
 * This script generates a .ULS file to drive remote access to NetMeeting
 *
 * @link http://www.meetingbywire.com/HyperlinkstoNetMeeting.htm
 *
 * Restrictions apply on this page:
 * - target member has activated desktop sharing with NetMeeting
 * - associates are allowed to move forward
 * - this is the page of the authenticated surfer
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - share.php/12
 * - share.php?id=12
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the article id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
elseif(Surfer::is_logged())
	$id = Surfer::get_id();
$id = strip_tags($id);

// get the item from the database
$item =& Users::get($id);

// Netmeeting is not allowed
if(!isset($item['with_sharing']) || ($item['with_sharing'] != 'M'))
	$permitted = FALSE;

// associates can do what they want
elseif(Surfer::is_associate())
	$permitted = TRUE;

// the page of the authenticated surfer
elseif(isset($item['id']) && Surfer::is($item['id']))
	$permitted = TRUE;

// access is restricted to authenticated member
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif(isset($item['active']) && ($item['active'] == 'Y'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if(isset($item['nick_name']))
	$context['page_title'] = $item['nick_name'];
elseif(isset($item['full_name']))
	$context['page_title'] = $item['full_name'];

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Users::get_url($item['id'], 'describe')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// describe the article
} else {

	// use either explicit or implicit address
	if(isset($item['proxy_address']) && $item['proxy_address'])
		$address = $item['proxy_address'];
	elseif(isset($item['login_address']) && $item['login_address'])
		$address = $item['login_address'];
	else
		$address = $context['host_name'];

	// prepare the response
	$text = '<IULS><'."\n"
		.'[res]'."\n"
		.'hr=0'."\n"
		.'ip='.$address."\n"
		.'port=1720'."\n"
		.'mt=text/iuls'."\n"
		.'uid='.$item['nick_name']."\n"
		.'url=action=resolve;uid='.$item['nick_name'].';appid=ms-netmeeting;protid=h323'."\n"
		."\n"
		.'></IULS>'."\n";

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('text/iuls; charset='.$context['charset']);

	// suggest a name on download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($context['page_title'], 20).'.uls');
		Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');
	}

	// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
	http::expire(1800);

	// strong validator
	$etag = '"'.md5($text).'"';

	// manage web cache
	if(http::validate(NULL, $etag))
		return;

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $text;

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// render the skin on error
render_skin();

?>
