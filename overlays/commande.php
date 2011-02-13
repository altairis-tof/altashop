<?php
/**
 * Overlay permettant de saisir des informations (de commande sur les articles) à partir de l'index de la section listant
 * les articles commandables.
 * Enregistrement des données saisies par les utilisateurs.
 * Bouton d'enregistrement en fin de page : crée un article "commande de l'utilisateurXXX" non publié,
 * dans la section réservée à la gestion des commandes, et bascule sur l'article en question
 * Deuxième bouton de validation de la commande : publie la commande officiellement enregistrée.
 * 
 * @author Agnès Rambaud et Christophe Battarel [link]http://www.altairis.fr[/link]
 * @tester Bénédicte et Jean Christophe Mermond [link]http://www.jardinbiodelarbonne.com[/link]
 *
 * overlay basé sur l'overlay issue 
 * ---------------------------
 *
 * describe one issue
 *
 * @todo add a field to scope the case: cosmetic (issue with the interface), behaviour (functional issue), system-wide (critical issue)  
 *
 * This overlay is aiming to track status of various kinds of issue, as per following workflow:
 * [snippet]
 * on-going:suspect (create_date)
 *	 V
 *	 + qualification --> cancelled:suspect (qualification_date)
 *	 V
 * on-going:problem (qualification_date)
 *	 V
 *	 + analysis --> cancelled:problem (analysis_date)
 *	 V
 * on-going:issue (analysis_date)
 *	 V
 *	 + resolution --> cancelled:issue (resolution_date)
 *	 V
 * on-going:solution (resolution_date)
 *	 V
 *	 + integration --> cancelled:solution (close_date)
 *	 V
 * completed:solution (close_date)
 * [/snippet]
 *
 * In the overlay itself, saved along the article, only the last status and the related date are saved.
 * More descriptive data and dates are saved into the table [code]yacs_issues[/code].
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Commande extends Overlay {

	/**
	 * build the list of fields for one overlay
	 *
	 * The current status, and the related status date are proposed
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host) {
		global $context;

		// form fields
		$fields = array();
/*
		$text = '';
		$text .= '<table>';
		$text .= '<tr>';
    $text .= '<td>Produit</td>';
    $text .= '<td>Prix unitaire</td>';
    $text .= '<td>Quantit&eacute;</td>';
		$text .= '</tr>';
    $q = "select id from ".SQL::table_name('orders')." WHERE page_id = ".$host['id'];
    $order = SQL::query_first($q);
		$query = "select * from ".SQL::table_name('orders_lines')." WHERE order_id = ".$order['id'];
		$lignes = SQL::query($query);
		while($ligne = SQL::fetch($lignes)) {
      $product = Articles::get($ligne['product_id']);
      $text .= '<tr>';
      $text .= '<td>'.$product['title'].'</td>';
      $text .= '<td>'.$ligne['pu_ht'].'<input type="hidden" name="price_'.$ligne['product_id'].'" value="'.$ligne['pu_ht'].'">'.'</td>';
      $text .= '<td>'.$ligne['quantity'].'</td>';
      $text .= '</tr>';
    }
    $text .= '</table>';
    
    $fields[] = array('D&eacute;tails de la commande', $text);
*/
		// job done
		return $fields;
	}


	/**
	 * get an overlaid label
	 *
	 * Accepted action codes:
	 * - 'edit' the title for the modification of an existing object
	 * - 'delete' the title for the deleting form
	 * - 'new' the title for the creation of a new object
	 * - 'view' the title for a displayed object
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the target label
	 * @param string the on-going action
	 * @return the title to use
	 */
	function get_label($name, $action='view') {
		global $context;

		// the target label
		switch($name) {

		// page title
		case 'page_title':

			switch($action) {

			case 'edit':
				return i18n::s('Modifier la commande');

			case 'delete':
				return i18n::s('Supprimer la commande');

			case 'new':
				return i18n::s('Cr&eacute;er une commande');

			case 'view':
			default:
				// use the article title as the page title
				return NULL;

			}
		}

		// no match
		return NULL;
	}


	/**
	 * display content of main panel
	 *
	 * Everything is in a separate panel
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
    global $context;
    
    $text = '';
    
    // status de la commande
    $q = "select * from ".SQL::table_name('orders')." WHERE page_id = ".$host['id'];
    $order = SQL::query_first($q);
    if ($order['status'] == 1)
      $text .= '<p style="color: red;">Cette commande n\'est pas r&egrave;gl&eacute;e !</p>';
    else
      $text .= '<p style="color: green;">Cette commande a &eacute;t&eacute; pay&eacute;e.</p>';
      
    // detail de la commande
		$text .= '<table>';
		$text .= '<tr>';
    $text .= '<td>Boutique</td>';
    $text .= '<td>Produit</td>';
    $text .= '<td>Prix unitaire</td>';
    $text .= '<td>Quantit&eacute;</td>';
    $text .= '<td>Total</td>';
		$text .= '</tr>';
		$total = 0;
		$query = "select ol.*, s.title as shop_title from ".SQL::table_name('orders_lines')." ol left join ".SQL::table_name('sections')." s on s.id = ol.shop_id WHERE order_id = ".$order['id']." order by shop_id, id";
		$lignes = SQL::query($query);
		$shop_id = null;
		while($ligne = SQL::fetch($lignes)) {
      $product = Articles::get($ligne['product_id']);
      $text .= '<tr>';
      if (!$shop_id || $shop_id != $ligne['shop_id']) {
        $shop_id = $ligne['shop_id'];
        $text .= '<td>'.$ligne['shop_title'].'</td>';
      }
      else
        $text .= '<td>&nbsp;</td>';
      $text .= '<td>'.$product['title'].'</td>';
      $text .= '<td>'.$ligne['pu_ht'].'&nbsp;&euro;</td>';
      $text .= '<td>'.$ligne['quantity'].'</td>';
      $total_ligne = $ligne['pu_ht']*$ligne['quantity'];
      $total = $total + $total_ligne;
      $text .= '<td>'.$total_ligne.'&nbsp;&euro;</td>';
      $text .= '</tr>';
    }
    //$text .= '<tr><td colspan=3>Prix donn&eacute; &agrave; titre indicatif</td><td>'.$total.'&nbsp;&euro;</td></tr>';
    $text .= '</table>';
    
    // totaux
    $text .= BR.'<div style="text-align: center;">';
    $text .= '<p>Total HT : '.$order['total_ht'].'&euro;</p>';
    $text .= '<p>TVA : '.$order['tva'].'&euro;</p>';
    $text .= '<p>Total TTC : '.$order['total_ttc'].'&euro;</p>';
    $text .= '</div>';
		return $text;
	}

	/**
	 * retrieve the content of one modified overlay
	 *
	 * These are data saved into the piggy-backed overlay field of the hosting record.
	 *
	 * If change is the status affects a previous step of the process, then this is either a simple date
	 * update or some steps have to be cancelled.
	 *
	 * Current and previous step are computed using following table:
	 * - 'on-going:suspect': step 1 - creation
	 * - 'cancelled:suspect': step 2 - qualification
	 * - 'on-going:problem': step 2 - qualification
	 * - 'cancelled:problem': step 3 - analysis
	 * - 'on-going:issue': step 3 - analysis
	 * - 'cancelled:issue': step 4 - resolution
	 * - 'on-going:solution': step 4 - resolution
	 * - 'cancelled:solution': step 5 - close
	 * - 'completed:solution': step 5 - close
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 * @return the updated fields
	 */
	function parse_fields($fields) {


		return $this->attributes;
	}

	/**
	 * remember an action once it's done
	 *
	 * This function saves data into the table [code]yacs_issues[/code].
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the hosting record
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($variant, $host) {
		global $context;

		// build the update query
		switch($variant) {

		case 'delete':
      $q = "select id from ".SQL::table_name('orders')." WHERE page_id = ".$host['id'];
      $order = SQL::query_first($q);
			$query = "DELETE FROM ".SQL::table_name('orders_lines')." WHERE order_id LIKE '".$order['id']."'";
			SQL::query($query);
			break;


		}

		return TRUE;
	}

	/**
	 * create tables for issues
	 *
	 * @see control/setup.php
	 */
	function setup() {
		global $context;

    // création table orders
		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['order_date'] = "DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL";
		$fields['user_id'] = "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['payment_method']	= "VARCHAR(5) DEFAULT '' NOT NULL";
		$fields['total_ht'] = "double DEFAULT 0 NOT NULL";
		$fields['tva'] = "double DEFAULT 0 NOT NULL";
		$fields['port'] = "double DEFAULT 0 NOT NULL";
		$fields['total_ttc'] = "double DEFAULT 0 NOT NULL";
		$fields['status'] = "smallint(6) DEFAULT 1 NOT NULL";
		$fields['page_id'] = "MEDIUMINT DEFAULT 0 NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX user_id'] = "(user_id)";
		$indexes['INDEX page_id'] = "(page_id)";

		$ret = SQL::setup_table('orders', $fields, $indexes);

    // création table orders_lines
		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['order_id'] = "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['shop_id'] = "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['product_id']	= "INT(11) DEFAULT 0 NOT NULL";
		$fields['quantity'] = "real DEFAULT 0 NOT NULL";
		$fields['pu_ht'] = "double DEFAULT 0 NOT NULL";
		$fields['tax'] = "decimal(4, 2) DEFAULT 0 NOT NULL";
		$fields['reference'] = "varchar(15) DEFAULT '' NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX order_id'] = "(order_id)";
		$indexes['INDEX shop_id'] = "(shop_id)";
		$indexes['INDEX reference'] = "(reference)";
		$indexes['INDEX product_id'] = "(product_id)";

		return $ret . SQL::setup_table('orders_lines', $fields, $indexes);
	}

}

?>
