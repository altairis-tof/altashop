<?php
/**
 * list icons of the avatar library
 *
 * The avatar library is the directory '[code]skins/_reference/avatars[/code]' under the YACS installation directory.
 * Upload some files through FTP there, and they will become useful resources for all members of your community.
 *
 * Note that this index is restricted to image files ([code].gif[/code], [code].jpeg[/code], [code].jpg[/code], [code].png[/code]).
 *
 * This script offers to authenticated surfers to change their avatar as well.
 *
 * @see users/select_avatar.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../../shared/global.php';

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
$context['page_title'] = i18n::s('The library of profile pictures');

// logged users may change their avatar
if(Surfer::is_logged())
	$context['page_menu'] += array( 'users/select_avatar.php' => i18n::s('Your picture') );

// list available avatars, except on error
if(!count($context['error'])) {

	// where images are
	$path = 'skins/_reference/avatars';

	// browse the path to list directories and files
	if(!$dir = Safe::opendir ($context['path_to_root'].$path))
		Logger::error(sprintf(i18n::s('The directory %s does not exist.'), $path));

	// list directories and files separately
	else {

		// offers to change the avatar
		if(Surfer::is_logged())
			$context['text'] .= '<p>'.Skin::build_link('users/select_avatar.php', i18n::s('Change your own picture'), 'shortcut').'</p>'."\n";

		// splash
		$context['text'] .= '<p>'.i18n::s('This is the library of shared pictures available at this site.').'</p>'."\n";

		// build the lists
		while(($item = Safe::readdir($dir)) !== FALSE) {

			// skip some files
			if($item[0] == '.')
				continue;

			if(is_dir($context['path_to_root'].$path.'/'.$item))
				continue;

			// consider only images
			if(!preg_match('/(\.gif|\.jpeg|\.jpg|\.png)$/i', $item))
				continue;

			// display the image
			$context['text'] .= ' <img src="'.$context['url_to_root'].$path.'/'.$item.'" alt="'.$item.'" style="padding: 4px 4px 4px 4px;" /> ';

		}
		Safe::closedir($dir);
	}

}

// render the skin
render_skin();

?>