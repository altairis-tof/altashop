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
class Produit extends Overlay {

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
		$input = '<input type="text" name="ht_price" value ="'.encode_field($this->attributes['ht_price']).'" />';
		$hint = i18n::s('saisissez le prix HT de l\'article');
		$fields[] = array('Prix HT', $input, $hint);

		// tva
		$input = '<input type="text" name="tva_rate" value ="'.(isset($this->attributes['tva_rate'])?encode_field($this->attributes['tva_rate']):'19.6').'" />';
		$hint = i18n::s('taux de TVA appliqu&eacute; &agrave; l\'article');
		$fields[] = array('Taux de TVA', $input, $hint);

		// prix ttc
		$input = '<input type="text" name="ttc_price" value ="'.encode_field($this->attributes['ttc_price']).'" />';
		$hint = i18n::s('saisissez le prix TTC de l\'article');
		$fields[] = array('Prix TTC', $input, $hint);

		// qté disponible
		$input = '<input type="text" name="available_qty" value ="'.encode_field($this->attributes['available_qty']).'" />';
		$hint = i18n::s('quantit&eacute; disponible (facultatif)');
		$fields[] = array('Dispo.', $input, $hint);
/*
		// frais de port
		$input = '<input type="text" name="shipping_cost" value ="'.encode_field($this->attributes['shipping_cost']).'" />';
		$hint = i18n::s('frais d\'exp&eacute;dition (facultatif)');
		$fields[] = array('Montant du port', $input, $hint);
*/
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
      case 'title': return 'Nom du produit';
      case 'introduction': return 'R&eacute;sum&eacute;';
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

		// text to return
		if ($this->attributes['available_qty'] != '' && $this->attributes['available_qty'] <= 0)
      $text .= 'Produit en rupture de stock';
    else
  		$text = $this->add_to_cart($host, 'list');
		
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
	 * display a live description
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_description($host=NULL, $options=NULL) {
		$text = '<div style="float: left; width: 50%;">'.$host['description'].'</div>';
		if ($this->attributes['available_qty'] != '' && $this->attributes['available_qty'] <= 0)
      $text .= 'Produit en rupture de stock';
    else
		  $text .= $this->add_to_cart($host, 'view');
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
	 * display the content of one recipe
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &add_to_cart($host=NULL, $variant) {
		global $context;

    if ($this->attributes['ttc_price'] != 0) {
      $price = number_format($this->attributes['ttc_price'],2, ',', ' ').'  &euro;';
      $price .= ' TTC';
      $ttc_price = $this->attributes['ttc_price'];
    }
    elseif ($this->attributes['ht_price'] != 0) {
      $price = number_format($this->attributes['ht_price'],2, ',', ' ').'  &euro;';
      $price .= ' HT';
      $ttc_price = round(($this->attributes['ht_price'] * (1 + $this->attributes['tva_rate']) / 100), 2);
    }
    else {
      $price = 'Gratuit !';
      $ttc_price = 0;
    }
    
		// text to return
		$text = '';

    $cart =& $_SESSION['altaCart'];
    if(!is_object($cart)) {
      require_once($context['path_to_root'].'altacart/cart.php');
      $cart = new altaCart();
    }
    
    $boutique_id = $this->get_boutique_id($host['anchor']);
    $id = $boutique_id.'_'.$host["id"]; // pour tri des articles par boutique

    // get cart page
    if ($article = Articles::get('cart')) {
      $link = Articles::get_permalink($article);
      if (strpos($link, '?') > 0)
        $link .= '&variant=naked';
      else
        $link .= '?variant=naked';

      $form_action = $context['url_to_home'].$context['url_to_root'].$link;
    }

		$text .=  '
				<form style="margin: 0; padding:0;" method="post" action="'.$form_action.'" class="jcart" />
						<input type="hidden" name="my-item-shop" value="'.$boutique_id.'" />
						<input type="hidden" name="my-item-id" value="'.$id.'" />
						<input type="hidden" name="my-item-article" value="'.$host['id'].'" />
						<input type="hidden" name="my-item-name" value="'.str_replace('"', '', $host["title"]).'" />
						<input type="hidden" name="my-item-tax" value="'.($this->attributes['tva_rate'] / 100).'" />
						<input type="hidden" name="my-item-price" value="'.$this->attributes['ht_price'].'" />';

    if ($variant == 'list') {
			// the url to view this item
			$url = $context['url_to_root'].Articles::get_permalink($host);
      $text .=  '
              <div class="product_box">
              <h3><span>Commandez</span></h3>
              <div class="product_body">
						  <table style="border: none; width: 100%;">
  						<tr>
              <td style="border: none; text-align: center;"><span class="product_title">'.Skin::build_link($url, $host['title'], 'basic', Codes::beautify($host['introduction'])).'</span></td>
              </tr>
  						<tr>
              <td style="border: none; text-align: center;">'.Skin::build_image('center', $host['thumbnail_url'], Codes::beautify($host['introduction']), $url).'</td>
              </tr>
              <tr>
              <td style="border: none; text-align: center;">'.Skin::build_link($url, 'Plus d\'infos', 'basic', 'Cliquez pour voir plus d\'informations sur le produit.').'</td>
              </tr>';
    }
		else {
      $text .= '[sidebar=Commandez]
						  <table style="border: none; width: 100%;">
  						<tr>
              <td style="border: none; text-align: center;">'.Skin::build_image('center', $host['thumbnail_url'], $host['title'], $host['icon_url']).'</td>
              </tr>';
    }
		$text .=  '
              <tr style="border: none;">
  							<td style="border: none; text-align: center;">Prix&nbsp;:&nbsp;'.$price.'</td>
  						</tr>
  						<tr style="border: none;">
                <td style="border: none; text-align: center;">Quantit&eacute;&nbsp;:&nbsp;<input type="text" name="my-item-qty" value="1" size="3" /></td>
              </tr>
  						<tr style="border: none;">
              </tr>
  						<tr style="border: none;">
  						  <td style="border: none; text-align: center;"><input type="submit" name="my-add-button" value="Ajoutez au panier" class="button" /></td>
              </tr>
						</table>';
    if ($variant == 'list') {
      $text .= '</div>';
      $text .= '</div>';
    }
    else
      $text .= '[/sidebar]';

    $text .= '</form> ';

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

		$this->attributes['ht_price'] = isset($fields['ht_price']) ? str_replace(',', '.', $fields['ht_price']) : '';
		$this->attributes['tva_rate'] = isset($fields['tva_rate']) ? str_replace(',', '.', $fields['tva_rate']) : '';
		$this->attributes['ttc_price'] = isset($fields['ttc_price']) ? str_replace(',', '.', $fields['ttc_price']) : '';
		$this->attributes['available_qty'] = isset($fields['available_qty']) ? str_replace(',', '.', $fields['available_qty']) : '';
		$this->attributes['shipping_cost'] = isset($fields['shipping_cost']) ? str_replace(',', '.', $fields['shipping_cost']) : '';

		return $this->attributes;
	}
	
	function get_boutique_id($anchor) {
    global $context;
    
    $id = str_replace('section:', '', $anchor);
    
    if ($boutique_root = Sections::lookup('boutique')) {
      while ($anchor != $boutique_root) {
        $id = str_replace('section:', '', $anchor);
        $section = Sections::get($id);
        $anchor = $section['anchor'];
      }
    }

    return $id;
  }

}

?>