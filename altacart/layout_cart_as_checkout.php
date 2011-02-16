<?php
Class Layout_cart_as_checkout extends Layout_interface {

	/**
	 * list cart resume
	 *
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout($cart) {
		global $context;
    global $jcart;

		// OVERWRITE THE CONFIG FORM ACTION TO POST TO jcart-gateway.php INSTEAD OF POSTING BACK TO CHECKOUT PAGE
		// THIS ALSO ALLOWS US TO VALIDATE PRICES BEFORE SENDING CART CONTENTS TO BANK
  	$form_action = $jcart['path'] . 'jcart-gateway.php';

    $text = '';
    
		if($cart->itemcount > 0)
			{
			// début formulaire
			$text .= '<form name="payment_form" method="post" action="'.$form_action.'">';
			
      // récupération infos client
      $user_id = Surfer::get_id();
      $user = Users::get($user_id);
      $overlay = unserialize($user['overlay']);

    }
    
		// DISPLAY THE CART HEADER
		$text .= "<!-- BEGIN JCART -->\n<div id='jcart'>\n";
		$text .= "<table>\n";

		// IF ANY ITEMS IN THE CART
		if($cart->itemcount > 0)
			{
  		$text .= "<tr id='jcart-headers'>\n";
  		$text .= "<th id='jcart-header' colspan='3'>\n";
  		$text .= "<strong id='jcart-title'>" . $jcart['text']['cart_title'] . "</strong>";
  		$text .= "</th>\n";
  		$text .= "</tr>". "\n";
  		
			$shop = null;

			// DISPLAY LINE ITEMS
			foreach($cart->get_contents() as $item)
				{
				// récup données article
				$article = Articles::get($item['article']);
				$url = $context['url_to_root'].Articles::get_permalink($article);

        // multi boutiques ?
				if ($this->layout_variant == 'multishop') {
          if (!$shop || ($item['shop'] != $shop)) {
            $shop = $item['shop'];
            $boutique = Sections::get($item['shop']);
				    $url_boutique = $context['url_to_root'].Sections::get_permalink($boutique);
            $text .= "<tr><td class='jcart-item-shop' colspan=3>\n";
            $text .= "Boutique : ";
    				$text .=  '<a href="'.$url_boutique.'" onclick="jQuery(\'#dialog_win\').remove();Yacs.startWorking();document.location=\''.$url_boutique.'\'; return false;">'.$boutique['title'].'</a>'."\n";
            $text .= "</td></tr>\n";
          }
        }

				$text .= "<tr>\n";

				// ADD THE ITEM ID AS THE INPUT ID ATTRIBUTE
				// THIS ALLOWS US TO ACCESS THE ITEM ID VIA JAVASCRIPT ON QTY CHANGE, AND THEREFORE UPDATE THE CORRECT ITEM
				// NOTE THAT THE ITEM ID IS ALSO PASSED AS A SEPARATE FIELD FOR PROCESSING VIA PHP
				$text .= "<td class='jcart-item-qty'>\n";
			  $text .= "<input type='hidden' id='jcart-item-id-" . $item['id'] . "' name='jcart_item_qty[ ]' value='" . $item['qty'] . "' />".$item['qty']."\n";
				$text .= "</td>\n";
				$text .= "<td class='jcart-item-name'>\n";
				$text .=  '<a href="'.$url.'" onclick="jQuery(\'#dialog_win\').remove();Yacs.startWorking();document.location=\''.$url.'\'; return false;">'.$item['name'].'</a><input type="hidden" name="jcart_item_name[ ]"" value="' . $item['name'] . '" />'."\n";
				$text .= "<input type='hidden' name='jcart_item_id[ ]' value='" . $item['id'] . "' />\n";
				$text .= "</td>\n";
				$text .= "<td class='jcart-item-price'>\n";
				$text .= "<span>" . number_format($item['subtotal_ttc'],2,',',' ')."&nbsp;". $jcart['text']['currency_symbol']  . "</span><input type='hidden' name='jcart_item_price[ ]' value='" . $item['price'] . "' />\n";
				$text .= "</td>\n";
        $text .= "</tr>\n";
				}
			}

		// THE CART IS EMPTY
		else
			{
			$text .= "<tr id='jcart-headers'><td colspan='3' class='empty'>" . $jcart['text']['empty_message'] . "</td></tr>\n";
			}

    if($cart->total_ttc != 0) {
  		$text .= "<tr>\n";
  		$text .= "<td id='jcart-checkout-line' colspan='3'>\n";

      $text .= "<table id='jcart-total-table'>\n";

      $cart->_update_total();

      $_SESSION['altaCart'] = $cart;

      $text .= "<tr style='background: none;'><td id='jcart-ht-label'>Total HT :</td><td id='jcart-ht'>" . number_format($cart->total_ht ,2, ',', ' ') ." ". $jcart['text']['currency_symbol'] . "</td></tr>\n";
      $text .= "<tr style='background: none;'><td id='jcart-tax-label'>TVA :</td><td id='jcart-tax'>" . number_format($cart->tva ,2, ',', ' ') ." ". $jcart['text']['currency_symbol'] . "</td></tr>\n";
      $text .= "<tr style='background: none;'><td id='jcart-ttc-label'>Total TTC :</td><td id='jcart-ttc'>" . number_format($cart->total_ttc ,2, ',', ' ') ." ". $jcart['text']['currency_symbol'] . "</td></tr>\n";

      // CGV
      $cgv = Articles::get('CGV');
      $cgv_url = $context['url_to_root'].Articles::get_permalink($cgv);
      $cgv_link = Skin::build_link($cgv_url, 'les conditions g&eacute;n&eacute;rales de vente', 'article', $href_title=NULL, $new_window=TRUE);
      $text .= '<tr><td colspan=4>';
      $text .= '<div id="cgv_accept">';
      $text .= '<span id="accept_title">J\'ai lu et accept&eacute; '.$cgv_link.' : </span>';
      $text .= '<input type=checkbox name="accept_box" id="accept_box" /> ';
      $text .= '</div>';
      $text .= '</td></tr>';
      
      // bouton continuer vos achats
      $boutique = Sections::get('boutique');
      $boutique_url = $context['url_to_root'].Sections::get_permalink($boutique);
      $text .= '<tr><td colspan=2><input type=button id="jcart-continue" onclick="document.location= \''.$boutique_url.'\';" value="Continuer vos achats">';

			// BOUTON VALIDATION COMMANDE
			$text .= "<input type='submit' " . $src ."id='jcart-paypal-checkout' name='jcart_paypal_checkout' value='" . $jcart['text']['checkout_paypal_button'] . "' onclick=\"if (!document.getElementById('accept_box').checked) { alert('Merci de lire les CGV'); return false; }\"; />";
      $text .= '</td></tr></table>';
      $text .= "</td>\n";
  		$text .= "</tr>\n";
  		$text .= "<tr>\n";
  		$text .= "<th id='jcart-payment' colspan='3' style='text-align: center;'>\n";
  		$text .= "</th>\n";
  		$text .= "</tr>\n";
   }
		// DISPLAY THE CART FOOTER
		$text .= "<tr style='background: none;'>\n";
		$text .= "<th id='jcart-footer' colspan='3' style='text-align: center;'>\n";
		$text .= "</th>\n";
		$text .= "</tr>\n";

		$text .= "</table>";

		$text .= "</div>\n<!-- END JCART -->\n";
		
		if($cart->itemcount > 0) {
      $text .= "<input type='hidden' name='amount' value='".$cart->total."' />";
      $text .= "<input type='hidden' name='currency' value='EUR' />";
      $text .= "<input type='hidden' name='language' value='FR' />";
      $text .= "<input type='hidden' name='payment_method' value='CB' />";
      $text .= '</form>';
    }
		return $text;
	}
	
}

?>
