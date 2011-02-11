<?php
Class Layout_cart_as_full extends Layout_interface {

	/**
	 * list cart 
	 *
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout($cart) {
		global $context;
    global $jcart;

    // OVERWRITE THE CONFIG FORM ACTION TO POST TO jcart-gateway.php INSTEAD OF POSTING BACK TO CHECKOUT PAGE
		// THIS ALSO ALLOWS US TO VALIDATE PRICES BEFORE SENDING CART CONTENTS TO PAYPAL
//			$form_action = $path . 'jcart-gateway.php';
    // get gateway page
    if ($article = Articles::get('checkout')) {
      $link = Articles::get_permalink($article);

      $form_action = $context['url_to_root'].$link;
    }

		// OUTPUT THE CART

		// DISPLAY THE CART HEADER
		$text .= "<!-- BEGIN JCART -->\n<div id='jcart'>\n";
		$text .= "<form method='post' action='$form_action'>\n";
		$text .= "<fieldset>\n";
		$text .= "<table>\n";
		// IF ANY ITEMS IN THE CART
		if($cart->itemcount > 0)
			{
  		$text .= "<tr id='jcart-headers'>\n";
  		$text .= "<th id='jcart-header' colspan='4'>\n";
		  $text .= "<strong id='jcart-title'>" . $jcart['text']['cart_title'] . "</strong>";
		  $text .= "</th>\n";
		  $text .= "</tr>". "\n";

			$shop = null;
			
			// DISPLAY LINE ITEMS
			foreach($cart->get_contents() as $item)
				{
				// récup données article
				$article = Articles::get($item['article']);
				$url_article = $context['url_to_root'].Articles::get_permalink($article);

        // multi boutiques ?
				if ($this->layout_variant == 'multishop') {
          if (!$shop || ($item['shop'] != $shop)) {
            $shop = $item['shop'];
            $boutique = Sections::get($item['shop']);
				    $url_boutique = $context['url_to_root'].Sections::get_permalink($boutique);
            $text .= "<tr><td class='jcart-item-shop' colspan=4>\n";
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
			  $text .= "<input type='text' size=1 id='jcart-item-id-" . $item['id'] . "' name='jcart_item_qty[ ]' value='" . $item['qty'] . "' />\n";
				$text .= "</td>\n";
				$text .= "<td class='jcart-item-name'>\n";
				$text .=  '<a href="'.$url_article.'" onclick="jQuery(\'#dialog_win\').remove();Yacs.startWorking();document.location=\''.$url_article.'\'; return false;">'.$item['name'].'</a><input type="hidden" name="jcart_item_name[ ]"" value="' . $item['name'] . '" />'."\n";
				$text .= "<input type='hidden' name='jcart_item_id[ ]' value='" . $item['id'] . "' />\n";
				$text .= "</td>\n";
				$text .= "<td class='jcart-item-price'>\n";
        $text .= "<span>" . number_format($item['subtotal_ttc'],2,',',' ')."&nbsp;". $jcart['text']['currency_symbol']  . "</span><input type='hidden' name='jcart_item_price[ ]' value='" . $item['price'] . "' />\n";
        $text .= "</td>\n";
        $text .= "<td class='jcart-remove-item'>\n";
		    $text .= "<a class='jcart-remove' href='?jcart_remove=" . $item['id'] . "'>" . $jcart['text']['remove_link'] . "</a>\n";
				$text .= "</td>\n";
				$text .= "</tr>\n";
				}
        $_SESSION['altaCart'] = $cart;
			}

		// THE CART IS EMPTY
		else
			{
			$text .= "<tr id='jcart-headers'><td colspan='4' class='empty'>" . $jcart['text']['empty_message'] . "</td></tr>\n";
			}

    if($cart->itemcount > 0) {
  		$text .= "<tr>\n";
  		$text .= "<td id='jcart-checkout-line' colspan='4'>\n";

      $text .= "<table id='jcart-total-table'>";
      $text .= "<tr style='background: none;'><td id='jcart-ttc-label'>Total TTC:</td><td id='jcart-ttc'>" . number_format($cart->total_ttc,2, ',', ' ') ." ". $jcart['text']['currency_symbol'] . "</td></tr>\n";
      $text .= "<tr><td colspan=2><input type='button' id='jcart-continue' onclick='jQuery(\"#dialog_win\").dialog(\"close\");' value='Continuer vos achats'>";
			// CHECKOUT BUTTON
			if ($button['yacs_checkout'])	{ $input_type = 'image'; $src = ' src="' . $button['yacs_checkout'] . '" alt="' . $jcart['text']['checkout_yacs_button'] . '" title="" '; }
			$text .= "<input type='button' " . $src ."id='jcart-paypal-checkout' name='jcart_paypal_checkout' value='" . $jcart['text']['checkout_yacs_button'] . "' onclick=\"document.location='".$form_action."';\" />";
      $text .= '</td></tr></table>';
      $text .= "</td>\n";
  		$text .= "</tr>\n";
  		$text .= "<tr>\n";
  		$text .= "<th id='jcart-payment' colspan='4' style='text-align: center;'>\n";
  		$text .= "</th>\n";
  		$text .= "</tr>\n";
   }
		// DISPLAY THE CART FOOTER
		$text .= "<tr style='background: none;'>\n";
		$text .= "<th id='jcart-footer' colspan='4' style='text-align: center;'>\n";
		$text .= "</th>\n";
		$text .= "</tr>\n";

		$text .= "</table>";

		// HIDDEN INPUT ALLOWS US TO DETERMINE IF WE'RE ON THE CHECKOUT PAGE
		// WE NORMALLY CHECK AGAINST REQUEST URI BUT AJAX UPDATE SETS VALUE TO jcart-relay.php
		$text .= "<input type='hidden' id='jcart-variant' name='jcart_variant' value='".$variant."' />\n";

		$text .= "</fieldset>\n";
		$text .= "</form>\n";

		// IF UPDATING AN ITEM, FOCUS ON ITS QTY INPUT AFTER THE CART IS LOADED (DOESN'T SEEM TO WORK IN IE7)
		if ($_POST['jcart_update_item'])
			{
			$text .= "" . '<script type="text/javascript">jQuery.noConflict();jQuery(document).ready(function($){$("#jcart-item-id-' . $_POST['item_id'] . '").focus()});</script>' . "\n";
			}

		$text .= "</div>\n<!-- END JCART -->\n";

		return $text;
	}
}

?>