<?php
/**
 * layout articles
 *
 * This is the default layout for articles.
 *
 * @see articles/index.php
 * @see articles/articles.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_boutique extends Layout_interface {

	/**
	 * list articles
	 *
	 * Accept following variants:
	 * - 'hits', compact plus the number of hits
	 * - 'no_author', for articles in the user page
	 * - 'category:xxx', if the list is displayed at categories/view.php
	 * - 'section:xxx', if the list is displayed at sections/view.php
	 *
	 * @param resource the SQL result
	 * @return array of resulting items, or NULL
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// insert overlay data, if any
			if(is_object($overlay))
				$text .= $overlay->get_text('list', $item);

		}

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>