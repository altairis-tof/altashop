<?php
/**
 * describe one recipe
 *
 * @see overlays/overlay.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Thanks extends Overlay {

	/**
	 * build the list of fields for one overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host) {
		global $context;
		
    $fields = array();

    return $fields;
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

			case 'view':
			default:
				// use the article title as the page title
				return NULL;
		}

		// no match
		return NULL;
	}

	/**
	 * display the content of one recipe
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		global $context;

		// text to return
    $section = Sections::get('commandes');
		$commande_url = $context['url_to_root'].Sections::get_permalink($section);
		$text = Skin::build_link($commande_url, 'Lien vers vos commandes', 'article', 'Cliquez pour voir vos commandes');
		
    $cart =& $_SESSION['altaCart'];
    if(!is_object($cart)) {
      require_once($context['path_to_root'].'altacart/cart.php');
      $cart = new altaCart();
    }

    // EMPTY THE CART
	  $cart->empty_cart();

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

		return $this->attributes;
	}

}

?>