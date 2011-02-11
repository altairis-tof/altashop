<?php
Class Layout_cart_as_resume extends Layout_interface {

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

    if ($article = Articles::get('cart')) {
      $link = Articles::get_permalink($article);
      if (strpos($link, '?') > 0)
        $link .= '&variant=naked';
      else
        $link .= '?variant=naked';

      $jcart['form_action'] = $context['url_to_root'].$link;
    }

		// DISPLAY THE CART HEADER
		$text .= "<!-- BEGIN JCART -->\n<div id='jcart_resume'>\n";
		$text .= "<table>\n";

		// IF ANY ITEMS IN THE CART
		if($cart->itemcount > 0)
		{
      // DISPLAY CART RESUME
      $text .= '<tr><td style="text-align: center;" colspan=4>'.$cart->itemcount.' article'.(($cart->itemcount > 1)?'s':'') . "</td></tr>\n";

  		$text .= "<tr>\n";
  		$text .= "<td id='jcart-checkout-line' colspan='4'>\n";

      $text .= "<table><tr style='background: none;'><td id='jcart-ttc'>" . $jcart['text']['subtotal'] . " : " . number_format($cart->total_ttc,2, ',', ' ') ." ". $jcart['text']['currency_symbol'] . "</td></tr>\n";

 			if ($button['checkout']) { $input_type = 'image'; $src = ' src="' . $button['checkout'] . '" alt="' . $jcart['text']['checkout_button'] . '" title="" ';	}

      $text .= "<tr><td><input type='button' " . $src . "id='jcart-checkout' name='jcart_checkout' class='jcart-button' value='" . $jcart['text']['checkout_button'] . "' onclick='return open_window(\"".$jcart['form_action']."\", \"R&eacute;capitulatif de votre panier\");'/></td>\n";
      $text .= '</tr></table>';
      $text .= "</td>\n";
  		$text .= "</tr>\n";
  		$text .= "<tr>\n";
  		$text .= "<th id='jcart-payment' colspan='4' style='text-align: center;'>\n";
  		$text .= "</th>\n";
  		$text .= "</tr>\n";
		}

		// THE CART IS EMPTY
		else
			{
			$text .= "<tr><td colspan='4' class='empty'>" . $jcart['text']['empty_message'] . "</td></tr>\n";
			}

		$text .= "</table>";

		// HIDDEN INPUT ALLOWS US TO DETERMINE IF WE'RE ON THE CHECKOUT PAGE
		// WE NORMALLY CHECK AGAINST REQUEST URI BUT AJAX UPDATE SETS VALUE TO jcart-relay.php
		$text .= "<input type='hidden' id='jcart-variant' name='jcart_variant' value='".$variant."' />\n";

		$text .= "</div><!-- END JCART -->";
		return $text;
	}
}

?>