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
class Boutique extends Overlay {

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

		// prix ht
		$input = '<input type="text" name="email" value ="'.encode_field($this->attributes['email']).'" />';
		$hint = i18n::s('saisissez l\'adresse de messagerie electronique du vendeur');
		$fields[] = array('E-mail', $input, $hint);

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
		
		switch($name) {
      case 'title': return 'Nom de la boutique';
      case 'introduction': return 'Pr&eacute;sentation';
      case 'description': return 'D&eacute;tails';
    }

		// no match
		return NULL;
	}

	/**
	 * display the content of one overlay in a list
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_list_text($host=NULL, $options=NULL) { 
		global $context;

    $text = '';

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

    $text = '';

		return $text;
	}
	
	/**
	 * text to be inserted aside
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_extra_text($host=NULL, $options=NULL) {
    $text = '';
    // affichage panier si pas composant global
    // sinon mettre en commentaires
    //$text = Codes::beautify_extra('[box.extra=Votre panier][cart.resume][/box]');
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

		$this->attributes['email'] = isset($fields['email']) ? str_replace(',', '.', $fields['email']) : '';

		return $this->attributes;
	}
	

}

?>