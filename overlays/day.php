<?php
/**
 * describe one specific day
 *
 * This overlay accepts following parameters:
 *
 * - layout_as_list - When this parameter is used, dates are just listed.
 * Else dates are laid out in monthly calendars.
 *
 * - with_past_dates - When this parameter is provided, all dates are listed.
 * When the parameter is absent, only future dates are included, and a
 * separate list shows past dates to associates and to section editors.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Day extends Overlay {

	/**
	 * get form fields to change the day
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host) {
		global $context;

		// the target day
		$label = i18n::s('Day');
		$input = Skin::build_input('date_stamp', isset($this->attributes['date_stamp'])?$this->attributes['date_stamp']:gmstrftime('%Y-%m-%d'), 'date');
		$hint = i18n::s('Use format YYYY-MM-DD');
		$fields[] = array($label, $input, $hint);

		// ensure that we do have a date
		$context['page_footer'] .= JS_PREFIX
			.'// ensure that some overlay fields are not empty'."\n"
			.'func'.'tion validateOnSubmit(container) {'."\n"
			."\n"
			.'	if(!Yacs.trim(container.date_stamp.value)) {'."\n"
			.'		alert("'.i18n::s('Please provide a date.').'");'."\n"
			.'		container.date_stamp.focus();'."\n"
			.'		Yacs.stopWorking();'."\n"
			.'		return false;'."\n"
			.'	}'."\n\n"
			.'	return true;'."\n"
			.'}'."\n"
			."\n"
			.JS_SUFFIX;

		// job done
		return $fields;
	}

	/**
	 * identify one instance
	 *
	 * This function returns a string that identify uniquely one overlay instance.
	 * When this information is saved, it can be used later on to retrieve one page
	 * and its content.
	 *
	 * @returns a unique string, or NULL
	 *
	 * @see articles/edit.php
	 */
	function get_id() {
		if(isset($this->attributes['date_stamp']))
			return 'day:'.$this->attributes['date_stamp'];
		return NULL;
	}

	/**
	 * get an overlaid label
	 *
	 * Accepted action codes:
	 * - 'edit' the modification of an existing object
	 * - 'delete' the deleting form
	 * - 'new' the creation of a new object
	 * - 'view' a displayed object
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the target label
	 * @param string the on-going action
	 * @return the label to use
	 */
	function get_label($name, $action='view') {
		global $context;

		// the target label
		switch($name) {

		// edit command
		case 'edit_command':
			return i18n::s('Edit this event');
			break;

		// new command
		case 'new_command':
			return i18n::s('Add an event');
			break;

		// page title
		case 'page_title':

			switch($action) {

			case 'edit':
				return i18n::s('Edit one day');

			case 'delete':
				return i18n::s('Delete one day');

			case 'new':
				return i18n::s('New day');

			case 'view':
			default:
				// use article title as the page title
				return NULL;

			}
			break;
		}

		// no match
		return NULL;
	}

	/**
	 * display a live title
	 *
	 * Add the actual date to page title
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_title($host=NULL) {

		$text = $host['title'];

		if(isset($this->attributes['date_stamp']) && ($this->attributes['date_stamp'] > NULL_DATE))
			$text .= ' ['.Skin::build_date($this->attributes['date_stamp'], 'day').']';

		return $text;
	}

	/**
	 * display the content of one instance
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		global $context;

		$text = '';
		return $text;
	}

	/**
	 * retrieve the content of one modified overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 * @return the updated fields
	 */
	function parse_fields($fields) {

		$this->attributes['date_stamp'] = isset($fields['date_stamp']) ? $fields['date_stamp'] : '';

		return $this->attributes;
	}

	/**
	 * remember an action once it's done
	 *
	 * To be overloaded into derivated class
	 *
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the hosting record
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($variant, $host) {
		global $context;

		// remember the id of the master record
		$id = $host['id'];

		// set default values for this editor
		Surfer::check_default_editor($this->attributes);

		// we use the existing back-end for dates
		include_once $context['path_to_root'].'dates/dates.php';

		// build the update query
		switch($variant) {

		case 'delete':

			// delete dates for this anchor
			Dates::delete_for_anchor('article:'.$id);
			break;

		case 'insert':

			// bind one date to this record
			if(isset($this->attributes['date_stamp']) && $this->attributes['date_stamp']) {

				$fields = array();
				$fields['anchor'] = 'article:'.$id;
				$fields['date_stamp'] = $this->attributes['date_stamp'];

				// update the database
				if(!$fields['id'] = Dates::post($fields)) {
					Logger::error(i18n::s('Impossible to add an item.'));
					return FALSE;
				}

			}
			break;

		case 'update':

			// bind one date to this record
			if(isset($this->attributes['date_stamp']) && $this->attributes['date_stamp']) {

				$fields = array();
				$fields['anchor'] = 'article:'.$id;
				$fields['date_stamp'] = $this->attributes['date_stamp'];

				// there is an existing record
				if($date =& Dates::get_for_anchor('article:'.$id)) {

					// update the record
					$fields['id'] = $date['id'];
					if(!$id = Dates::post($fields)) {
						Logger::error(sprintf(i18n::s('Impossible to update date %s'), $this->attributes['date_stamp']));
						return FALSE;
					}

				// create a record instead of raising an error, we are smart y'a'know
				} else {
					if(!$fields['id'] = Dates::post($fields)) {
						Logger::error(i18n::s('Impossible to add an item.'));
						return FALSE;
					}
				}

			}
			break;
		}

		return TRUE;
	}

	/**
	 * list dates at some anchor
	 *
	 * @param string type of replaced items (e.g., 'articles')
	 * @param string the anchor to consider (e.g., 'section:123')
	 * @param int page index
	 * @return string to be inserted in resulting web page, or NULL
	 */
	function render($type, $anchor, $page=1) {
		global $context;

		// instead of articles
		if($type != 'articles')
			return NULL;

		// get the containing page
		$container =& Anchors::get($anchor);

		// handle dates
		include_once $context['path_to_root'].'dates/dates.php';

		// the maximum number of articles per page
		if(!defined('DATES_PER_PAGE'))
			define('DATES_PER_PAGE', 20);

		// where we are
		$offset = ($page - 1) * DATES_PER_PAGE;

		// should we display all dates, or not?
		$with_past_dates = FALSE;
		if(preg_match('/\bwith_past_dates\b/i', $this->attributes['overlay_parameters']))
			$with_past_dates = TRUE;

		// menu to be displayed at the top
		$menu = array();

		// empowered users can contribute
		if(Articles::allow_creation(NULL, $container)) {
			Skin::define_img('ARTICLES_ADD_IMG', 'articles/add.gif');
			$menu[] = '<div style="display: inline">'.Skin::build_link('articles/edit.php?anchor='.urlencode($anchor), ARTICLES_ADD_IMG.i18n::s('Add an event'), 'span').'</div>';
		}

		// ensure access to past dates
		if(!$with_past_dates && ($items = Dates::list_past_for_anchor($anchor, $offset, DATES_PER_PAGE, 'compact'))) {

			// turn an array to a string
			if(is_array($items))
				$items =& Skin::build_list($items, 'compact');

			// navigation bar
			$bar = array();

			// count the number of dates in this section
			$stats = Dates::stat_past_for_anchor($anchor);
			if($stats['count'] > DATES_PER_PAGE)
				$bar = array_merge($bar, array('_count' => sprintf(i18n::ns('%d date', '%d dates', $stats['count']), $stats['count'])));

			// navigation commands for dates
			$section = Sections::get(str_replace('section:', '', $anchor));
			$home =& Sections::get_permalink($section);
			$prefix = Sections::get_url($section['id'], 'navigate', 'articles');
			$bar = array_merge($bar, Skin::navigate($home, $prefix, $stats['count'], DATES_PER_PAGE, $page));

			// display the bar
			if(is_array($bar))
				$items = Skin::build_list($bar, 'menu_bar').$items;

			// in a separate box
			$menu[] = Skin::build_sliding_box(i18n::s('Past dates'), $items, 'past_dates', TRUE);

		}

		// menu displayed towards the top of the page
		$text = Skin::finalize_list($menu, 'menu_bar');

		// build a list of events
		if(preg_match('/\blayout_as_list\b/i', $this->attributes['overlay_parameters'])) {

			// list all dates
			if($with_past_dates) {

				// navigation bar
				$bar = array();

				// count the number of dates in this section
				$stats = Dates::stat_for_anchor($anchor);
				if($stats['count'] > DATES_PER_PAGE)
					$bar = array_merge($bar, array('_count' => sprintf(i18n::ns('%d date', '%d dates', $stats['count']), $stats['count'])));

				// navigation commands for dates
				$section = Sections::get($anchor);
				$home =& Sections::get_permalink($section);
				$prefix = Sections::get_url($section['id'], 'navigate', 'articles');
				$bar = array_merge($bar, Skin::navigate($home, $prefix, $stats['count'], DATES_PER_PAGE, $page));

				// display the bar
				if(count($bar))
					$text .= Skin::build_list($bar, 'menu_bar');

				// list one page of dates
				if($items = Dates::list_for_anchor($anchor, $offset, DATES_PER_PAGE, 'family'))
					$text .= $items;

			// display only future dates to regular surfers
			} else {

				// show future dates on first page
				if(($page == 1) && ($items = Dates::list_future_for_anchor($anchor, 0, 500, 'family', TRUE)))
					$text .= $items;

			}

		// deliver a calendar view for current month, plus months around
		} else {

			// show past dates as well
			if($with_past_dates)
				$items = Dates::list_for_anchor($anchor, 0, 500, 'links');

			// only show future dates, and trackback to first of current month
			else
				$items = Dates::list_future_for_anchor($anchor, 0, 500, 'links', TRUE);

			// layout all these dates
			if($items)
				$text .= Dates::build_months($items);
		}

		// integrate this into the page
		return $text;
	}

	/**
	 * a compact list of dates at some anchor
	 *
	 * @param string the anchor to consider (e.g., 'section:123')
	 * @param int maximum number of items
	 * @return array of ($prefix, $label, $suffix, $type, $icon, $hover)
	 */
	function render_list_for_anchor($anchor, $count=7) {
		global $context;

		// we will build a list of dates
		include_once $context['path_to_root'].'dates/dates.php';

		// list past dates as well
		if(preg_match('/\bwith_past_dates\b/i', $this->attributes['overlay_parameters']))
			$items = Dates::list_for_anchor($anchor, 0, $count, 'compact');

		// list only future dates
		else
			$items = Dates::list_future_for_anchor($anchor, 0, $count, 'compact');

		// we return an array
		return $items;
	}
}

?>