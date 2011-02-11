<?php
/**
 * check apache options
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../../shared/global.php';

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// page title
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Apache .htaccess'));

if(is_callable('apache_get_modules')) {
	if(in_array('mod_expires', apache_get_modules()))
		$context['text'] .= '<p>'.i18n::s('Cache by expiration is also available.').'</p>';
	else
		$context['text'] .= '<p>'.sprintf(i18n::s('Activate the following Apache module to allow cache by expiration: %s'), 'mod_expires').'</p>';
}

// details
$context['text'] .= '<p>'.i18n::s('The default handler will be set to index.php.').'</p>';

// good news
$context['text'] .= '<p>'.sprintf(i18n::s('Please ensure that Apache has been configured with %s.'), 'AllowOverride Indexes').'</p>';

// follow-up commands
$follow_up = Skin::build_link('control/htaccess/', i18n::s('Done'), 'button');
$context['text'] .= Skin::build_block($follow_up, 'bottom');

// remember capability in session context
if(!isset($_SESSION['htaccess']))
	$_SESSION['htaccess'] = array();
$_SESSION['htaccess']['indexes'] = TRUE;

// render the page according to the loaded skin
render_skin();

?>