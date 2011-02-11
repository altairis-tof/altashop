<?php
/**
 * [cart]
 * [cart.layout]
 * [cart.layout,variant]
 *
 *    		-> render a cart with the altacart module
 *
 * It has been originally coded for gresivaudan.org and gresishop.com
 *
 * Author: Christophe Battarel - altairis - christophe@altairis.fr
 *
 */
 
 // merge in first place to save some cycles if at the beginning
  $pattern = array_merge(array(
				'/\[cart\]\n*/ise',						  // [cart]
				'/\[cart\.([^\]]+?)\]\n*/ise'   // [cart.layout] [cart.layout,variant]
    ), $pattern);
  $replace = array_merge(array(
				"render_cart()",						    // [cart]
				"render_cart('$1')"					    // [cart.layout] [cart.layout,variant]
    ), $replace);

	/**
	 * render cart with altacart module
	 *
	 * @param string the layout ('resume' / 'full' / 'checkout'); may have a variant, ie : [cart.full,multishop]
	 * @return string the rendered cart
	**/
	function &render_cart($layout='resume') {
		global $context;

		// we return some text;
		$text = '';
    $cart =& $_SESSION['altaCart'];
    if(!is_object($cart)) {
      require_once($context['path_to_root'].'altacart/cart.php');
      $cart = new altaCart();
    }
    $text .= $cart->render($layout);

		return $text;
  }

?>
