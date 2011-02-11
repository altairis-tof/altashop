<?php
/**
 * the index page for decisions
 *
 * For a comprehensive description of decisions, you should check the database abstraction script
 * at [script]decisions/decisions.php[/script].
 *
 * This page list threads, based on recent decisions.
 * Therefore, this index page is a very convenient place to look for most recent decisions of your community.
 *
 * A list of most recent articles is displayed as a sidebar.
 *
 * Accept following invocations:
 * - index.php (view the 20 top decisions)
 * - index.php/2 (view decisions 41 to 60)
 * - index.php?page=2 (view decisions 41 to 60)
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'decisions.php';

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
else
	$page = 1;
$page = max(1,intval($page));

// load the skin
load_skin('decisions');

// the title of the page
$context['page_title'] = i18n::s('Decisions');

// count decisions in the database
$stats = Decisions::stat_threads();
if($stats['count'])
	$context['page_menu'] += array('_count' => sprintf(i18n::ns('%d page', '%d pages', $stats['count']), $stats['count']));

// stop hackers
if(($page > 1) && (($page - 1) * THREADS_PER_PAGE > $stats['count'])) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

} else {

	// navigation commands for decisions, if necessary
	if($stats['count'] > THREADS_PER_PAGE) {
		$home = 'decisions/';
		if($context['with_friendly_urls'] == 'Y')
			$prefix = $home.'index.php/';
		elseif($context['with_friendly_urls'] == 'R')
			$prefix = $home;
		else
			$prefix = $home.'?page=';
		$context['page_menu'] += Skin::navigate($home, $prefix, $stats['count'], THREADS_PER_PAGE, $page);
	}

	// page main content
	$cache_id = 'decisions/index.php#text#'.$page;
	if(!$text =& Cache::get($cache_id)) {

		// the first decision to list
		$offset = ($page - 1) * THREADS_PER_PAGE;

		// query the database and layout that stuff
		if(!$text = Decisions::list_threads_by_date($offset, THREADS_PER_PAGE, 'decorated'))
			$text = '<p>'.i18n::s('No decision has been recorded yet.').'</p>';

		// we have an array to format
		elseif(is_array($text))
			$text =& Skin::build_list($text, 'rows');

		// cache, whatever change, for 1 minute
		Cache::put($cache_id, $text, 'stable', 60);
	}
	$context['text'] .= $text;

}

// page tools
if(Surfer::is_associate())
	$context['page_tools'][] = Skin::build_link('decisions/check.php', i18n::s('Maintenance'));

// page extra content
$cache_id = 'decisions/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// side bar with the list of most recent pages
	if($items =& Articles::list_by('publication', 0, COMPACT_LIST_SIZE, 'compact'))
		$text =& Skin::build_box(i18n::s('Recent pages'), Skin::build_list($items, 'compact'), 'boxes');

	Cache::put($cache_id, $text, 'articles');
}
$context['components']['boxes'] = $text;

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('decisions/index.php');

// render the skin
render_skin();

?>
