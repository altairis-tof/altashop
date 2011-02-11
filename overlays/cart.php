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
class Cart extends Overlay {

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
	 * display a live description
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_description($host=NULL, $options=NULL) {
    global $context;
    $text = str_replace('[cart]', '[cart.full,multishop]', $host['description']);
		$text = Codes::beautify($text);
		return $text;
	}

	/**
	 * display a live title
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_title($host=NULL, $options=NULL) {
		$text = 'Voici le contenu de votre panier';
		return $text;
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
		$text = '';

		$text = Codes::beautify($text);
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