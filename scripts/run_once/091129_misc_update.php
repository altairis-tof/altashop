<?php
/**
 * update misc. files
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Update miscellaneous files';
$local['label_fr'] = 'Mise &agrave; jour compl&eacute;mentaire';
echo i18n::user('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yacs.fr';

// files to fetch, from root path
$files = array();
$files[] = 'codes/edit.js';
$files[] = 'control/htaccess/basic/.htaccess';
$files[] = 'control/htaccess/indexes/.htaccess';
$files[] = 'control/htaccess/options/.htaccess';
$files[] = 'forms/forms.js';
$files[] = 'included/browser/carrousel.swf';
$files[] = 'included/browser/open-flash-chart.swf';
$files[] = 'included/browser/player_flv_maxi.swf';
$files[] = 'included/browser/library.js';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/blank.html';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/server-scripts/spellchecker.php';
$files[] = 'included/fckeditor/fckeditor.php';
$files[] = 'included/fckeditor/fckeditor_php4.php';
$files[] = 'included/fckeditor/fckeditor_php5.php';
$files[] = 'included/simplepie.inc';
$files[] = 'included/tiny_mce/themes/advanced/skins/default/content.css';
$files[] = 'included/tiny_mce/themes/advanced/skins/default/ui.css';
$files[] = 'readme.txt';
$files[] = 'shared/yacs.js';
$files[] = 'smileys/edit.js';
$files[] = 'tools/build_i18n.bat';
$files[] = 'tools/srcfiles.txt';
$files[] = 'tools/transform.xsl';

// process every file
$count = 0;
foreach($files as $file) {

	// content of the updated file
	$content = '';

	// expected location in staging repository
	$local_reference = $context['path_to_root'].'scripts/staging/'.$file;

	// expected link from reference server
	include_once $context['path_to_root'].'links/link.php';

	// don't execute PHP scripts, just get them
	if(preg_match('/\.php$/i', $file))
		$remote_reference = 'http://'.$context['reference_server'].'/scripts/fetch.php?script='.urlencode($file);

	// fetch other files from remote reference store
	else
		$remote_reference = 'http://'.$context['reference_server'].'/scripts/reference/'.$file;

	// get the file locally
	if(file_exists($local_reference))
		$content = Safe::file_get_contents($local_reference);

	// or get the file from reference server
	elseif(($content = Link::fetch($remote_reference)) === FALSE) {
		$local['error_en'] = 'Unable to get '.$file;
		$local['error_fr'] = 'Impossible d\'obtenir '.$file;
		echo i18n::user('error')."<br />\n";
	}

	// we have something in hand
	if($content) {

		// create missing directories where applicable
		Safe::make_path(dirname($file));

		// create backups, if possible
		if(file_exists($context['path_to_root'].$file)) {
			Safe::unlink($context['path_to_root'].$file.'.bak');
			Safe::rename($context['path_to_root'].$file, $context['path_to_root'].$file.'.bak');
		}

		// update the target file
		if(!Safe::file_put_contents($file, $content)) {
			$local['label_en'] = 'Impossible to write to the file '.$file.'.';
			$local['label_fr'] = 'Impossible d\'&eacute;crire le fichier '.$file.'.';
			echo i18n::user('label')."<br />\n";
		} else {
			$local['label_en'] = 'has been updated';
			$local['label_fr'] = 'a &eacute;t&eacute; mis &agrave; jour';
			echo $file.' '.i18n::user('label')."<br />\n";
		}

	}

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'files have been processed';
$local['label_fr'] = 'fichiers ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.i18n::user('label')."<br />\n";
?>