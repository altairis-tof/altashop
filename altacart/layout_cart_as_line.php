<?php
Class Layout_cart_as_line extends Layout_interface {

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
		$text = "<!-- BEGIN JCART -->\n<div id='jcart_line'>\n";

		// IF ANY ITEMS IN THE CART
		if($cart->itemcount > 0)
		{
      // DISPLAY CART RESUME
      $text .= "<a href='#' onclick='return open_window(\"".$jcart['form_action']."\", \"R&eacute;capitulatif de votre panier\");'>Votre panier (".$cart->itemcount." article".(($cart->itemcount > 1)?"s":"") . ")</a>\n";
    }
		// THE CART IS EMPTY
		else
			{
			$text .= "Votre panier est vide.\n";
			}

		$text .= "</div>\n<!-- END JCART -->\n";
		return $text;
	}
}

?>