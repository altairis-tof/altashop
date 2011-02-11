<?php

// USER CONFIG
include_once('jcart/jcart-config.php');

// DEFAULT CONFIG VALUES
include_once('jcart/jcart-defaults.php');

Class altaCart {
	var $total_ht = 0;
	var $total_ttc = 0;
	var $tva = 0;
	var $port = 0;
	var $itemcount = 0;
	var $items = array();
	var $itemproducts = array();
	var $itemprices = array();
	var $itemtaxes = array();
	var $itemqtys = array();
	var $itemnames = array();
	var $itemshops = array();
	var $itemarticles = array();

  function altaCart() {
    global $context;

    $_SESSION['altaCart'] = $this;

    $this->init();
  }
  
  function init() {
    global $context;
    global $jcart;

    if (!isset($context['altaCart'])) {
      $context['altaCart'] = $this;
    	$context['page_footer'] .= "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].'altacart/jcart/themes/base/jquery.ui.all.css" type="text/css" media="all" />'."\n";

    	$context['page_footer'] .= "\t".'<script type="text/javascript" src="'.$context['url_to_root'].'altacart/jcart/jquery-1.4.2.min.js"></script>'."\n";
    	$context['page_footer'] .= "\t".'<script type="text/javascript" src="'.$context['url_to_root'].'altacart/jcart/jquery-ui-1.8.2.custom.min.js"></script>'."\n";
    	$context['page_footer'] .= "\t".'<script type="text/javascript" src="'.$context['url_to_root'].'altacart/jcart/jcart-javascript.php"></script>'."\n";
    	$context['page_footer'] .= '
          <script type="text/javascript">
          jQuery.noConflict();
          jQuery(document).ready(function($) {
        		$(\'<div id="dialog_win"></div>\').appendTo(\'#main_panel\');
        		$(\'#dialog_win\').dialog({
        			autoOpen: false,
        			resizable: false,
        			width: 600,
        			modal: true
        		});

        	});
        	function open_window(url, title) { 
            jQuery(\'#dialog_win\').dialog( "option", "title", title );
            jQuery(\'#dialog_win\').load(url);
            jQuery(\'#dialog_win\').dialog(\'open\');
            return false;
        	}
          </script>
        ';
    }
  }
  
	// GET CART CONTENTS
	function get_contents()
		{
		asort($this->items);
		$items = array();
		foreach($this->items as $tmp_item)
			{
			$item = FALSE;

			$item['id'] = $tmp_item;
			$item['qty'] = $this->itemqtys[$tmp_item];
			$item['price'] = $this->itemprices[$tmp_item];
			$item['tax'] = $this->itemtaxes[$tmp_item];
			$item['name'] = $this->itemnames[$tmp_item];
			$item['shop'] = $this->itemshops[$tmp_item];
			$item['variant'] = $this->itemvariants[$tmp_item];
			$item['article'] = $this->itemarticles[$tmp_item];
			$item['section'] = $this->itemsections[$tmp_item];
			$item['category'] = $this->itemcategories[$tmp_item];
			$item['subtotal_ht'] = $item['qty'] * $item['price'];
			$item['subtotal_ttc'] = $item['subtotal_ht'] * (1 + $item['tax']);
			$items[] = $item;
			}
		return $items;
		}


	// ADD AN ITEM
	function add_item($item_id, $item_qty=1, $item_price, $item_tax, $item_name, $item_article, $item_shop=null)
		{
    global $jcart;

		// VALIDATION
		$valid_item_qty = $valid_item_price = false;

		// IF THE ITEM QTY IS AN INTEGER, OR ZERO
		if (preg_match("/^[0-9-]+$/i", $item_qty))
			{
			$valid_item_qty = true;
			}
		// IF THE ITEM PRICE IS A FLOATING POINT NUMBER
		if (is_numeric($item_price))
			{
			$valid_item_price = true;
			}

		// ADD THE ITEM
		if ($valid_item_qty !== false && $valid_item_price !== false)
			{
			// IF THE ITEM IS ALREADY IN THE CART, INCREASE THE QTY
			if($this->itemqtys[$item_id] > 0)
				{
				$this->itemqtys[$item_id] = $item_qty + $this->itemqtys[$item_id];
				$this->_update_total();
				}
			// THIS IS A NEW ITEM
			else
				{
				$this->items[] = $item_id;
				$this->itemqtys[$item_id] = $item_qty;
				$this->itemprices[$item_id] = $item_price;
				$this->itemtaxes[$item_id] = $item_tax;
				$this->itemnames[$item_id] = $item_name;
				$this->itemarticles[$item_id] = $item_article;
				$this->itemshops[$item_id] = $item_shop;
				}
			$this->_update_total();


			return true;
			}

		else if	($valid_item_qty !== true)
			{
			$error_type = 'qty';
			return $error_type;
			}
		else if	($valid_item_price !== true)
			{
			$error_type = 'price';
			return $error_type;
			}
		}


	// UPDATE AN ITEM
	function update_item($item_id, $item_qty)
		{
		// IF THE ITEM QTY IS AN INTEGER, OR ZERO
		// UPDATE THE ITEM
		if (preg_match("/^[0-9-]+$/i", $item_qty))
			{
			if($item_qty < 1)
				{
				$this->del_item($item_id);
				}
			else
				{
				$this->itemqtys[$item_id] = $item_qty;
				}
			$this->_update_total();
			return true;
			}
		}


  // update tva
  function update_tva($tva) {
    $this->tva = $tva;
  }

  // update port
  function update_port($port) {
    $this->port = $port;
  }

	// UPDATE THE ENTIRE CART
	// VISITOR MAY CHANGE MULTIPLE FIELDS BEFORE CLICKING UPDATE
	// ONLY USED WHEN JAVASCRIPT IS DISABLED
	// WHEN JAVASCRIPT IS ENABLED, THE CART IS UPDATED ONKEYUP
	function update_cart()
		{
		// POST VALUE IS AN ARRAY OF ALL ITEM IDs IN THE CART
		if (is_array($_POST['jcart_item_ids']))
			{
			// TREAT VALUES AS A STRING FOR VALIDATION
			$item_ids = implode($_POST['jcart_item_ids']);
			}

		// POST VALUE IS AN ARRAY OF ALL ITEM QUANTITIES IN THE CART
		if (is_array($_POST['jcart_item_qty']))
			{
			// TREAT VALUES AS A STRING FOR VALIDATION
			$item_qtys = implode($_POST['jcart_item_qty']);
			}

		// IF NO ITEM IDs, THE CART IS EMPTY
		if ($_POST['jcart_item_id'])
			{
			// IF THE ITEM QTY IS AN INTEGER, OR ZERO, OR EMPTY
			// UPDATE THE ITEM
			if (preg_match("/^[0-9-]+$/i", $item_qtys) || $item_qtys == '')
				{
				// THE INDEX OF THE ITEM AND ITS QUANTITY IN THEIR RESPECTIVE ARRAYS
				$count = 0;

				// FOR EACH ITEM IN THE CART
				foreach ($_POST['jcart_item_id'] as $item_id)
					{
					// GET THE ITEM QTY AND DOUBLE-CHECK THAT THE VALUE IS AN INTEGER
					$update_item_qty = intval($_POST['jcart_item_qty'][$count]);

					if($update_item_qty < 1)
						{
						$this->del_item($item_id);
						}
					else
						{
						// UPDATE THE ITEM
						$this->update_item($item_id, $update_item_qty);
						}

					// INCREMENT INDEX FOR THE NEXT ITEM
					$count++;
					}
				return true;
				}
			}
		// IF NO ITEMS IN THE CART, RETURN TRUE TO PREVENT UNNECSSARY ERROR MESSAGE
		else if (!$_POST['jcart_item_id'])
			{
			return true;
			}
		}


	// REMOVE AN ITEM
	/*
	GET VAR COMES FROM A LINK, WITH THE ITEM ID TO BE REMOVED IN ITS QUERY STRING
	AFTER AN ITEM IS REMOVED ITS ID STAYS SET IN THE QUERY STRING, PREVENTING THE SAME ITEM FROM BEING ADDED BACK TO THE CART
	SO WE CHECK TO MAKE SURE ONLY THE GET VAR IS SET, AND NOT THE POST VARS

	USING POST VARS TO REMOVE ITEMS DOESN'T WORK BECAUSE WE HAVE TO PASS THE ID OF THE ITEM TO BE REMOVED AS THE VALUE OF THE BUTTON
	IF USING AN INPUT WITH TYPE SUBMIT, ALL BROWSERS DISPLAY THE ITEM ID, INSTEAD OF ALLOWING FOR USER FRIENDLY TEXT SUCH AS 'remove'
	IF USING AN INPUT WITH TYPE IMAGE, INTERNET EXPLORER DOES NOT SUBMIT THE VALUE, ONLY X AND Y COORDINATES WHERE BUTTON WAS CLICKED
	CAN'T USE A HIDDEN INPUT EITHER SINCE THE CART FORM HAS TO ENCOMPASS ALL ITEMS TO RECALCULATE TOTAL WHEN A QUANTITY IS CHANGED, WHICH MEANS THERE ARE MULTIPLE REMOVE BUTTONS AND NO WAY TO ASSOCIATE THEM WITH THE CORRECT HIDDEN INPUT
	*/
	function del_item($item_id)
		{
		$ti = array();
		$this->itemqtys[$item_id] = 0;
		foreach($this->items as $item)
			{
			if($item != $item_id)
				{
				$ti[] = $item;
				}
			}
		$this->items = $ti;
		$this->_update_total();
		}


	// EMPTY THE CART
	function empty_cart()
		{
		$this->total_ht = 0;
		$this->total_ttc = 0;
		$this->tva = 0;
		$this->port = 0;
		$this->itemcount = 0;
		$this->items = array();
		$this->itemprices = array();
		$this->itemtaxes = array();
		$this->itemqtys = array();
		$this->itemnames = array();
		$this->itemvariants = array();
		$this->itemarticles = array();
		$this->itemsections = array();
		$this->itemcategories = array();
		$this->itemshops = array();
		}


	// INTERNAL FUNCTION TO RECALCULATE TOTAL
	function _update_total()
		{
		$this->itemcount = 0;
		$this->total_ttc =  $this->tva = $this->total_ht = 0;
		if(sizeof($this->items > 0))
			{
			foreach($this->items as $item)
				{
				$ht = $this->itemprices[$item] * $this->itemqtys[$item];
				$this->total_ht = $this->total_ht + $ht;
				$this->tva = $this->tva + round(($ht * $this->itemtaxes[$item]), 2);
				// TOTAL ITEMS IN CART (ORIGINAL wfCart COUNTED TOTAL NUMBER OF LINE ITEMS)
				$this->itemcount += $this->itemqtys[$item];
				}
			}
      $this->total_ttc = $this->total_ht + $this->tva + $this->port;
		}


  function render($variant=null) {
    global $context;
    
    $this->init();
    //$this->update();

		if (!$variant) {
      if ($_REQUEST['jcart_variant'])
        $variant = $_REQUEST['jcart_variant'];
      else
        $variant = 'resume';
    }

		// use the provided layout interface
		if(is_object($variant)) {
			$output =& $variant->layout($this);
			return $output;
		}

		// no layout yet
		$layout = NULL;

		// separate options from layout name
		$attributes = explode(',', $variant, 2);

		// instanciate the provided name
		if($attributes[0]) {
			$name = 'layout_cart_as_'.$attributes[0];
			if(is_readable($context['path_to_root'].'altacart/'.$name.'.php')) {
				include_once $context['path_to_root'].'altacart/'.$name.'.php';
				$layout = new $name;

				// provide parameters to the layout
				if(isset($attributes[1]))
					$layout->set_variant($attributes[1]);

			}
		}

		// use default layout
		if(!$layout) {
			include_once $context['path_to_root'].'altacart/layout_cart_as_full.php';
			$layout = new Layout_cart_as_full();
		}

		// do the job
		$output =& $layout->layout($this);
		return $output;
	}
	
	function update() {
		global $context;
    global $jcart;
    
    extract($jcart);

		// ASSIGN USER CONFIG VALUES AS POST VAR LITERAL INDICES
		// INDICES ARE THE HTML NAME ATTRIBUTES FROM THE USERS ADD-TO-CART FORM
		$item_id = $_POST[$item_id];
		$item_qty = $_POST[$item_qty];
		$item_price = $_POST[$item_price];
		$item_tax = $_POST[$item_tax];
		$item_name = $_POST[$item_name];
		$item_article = $_POST[$item_article];
		$item_shop = $_POST[$item_shop];

		// ADD AN ITEM
		if ($_POST[$item_add])
			{
			$item_added = $this->add_item($item_id, $item_qty, $item_price, $item_tax, $item_name, $item_article, $item_shop);
			// IF NOT TRUE THE ADD ITEM FUNCTION RETURNS THE ERROR TYPE
			if ($item_added !== true)
				{
				$error_type = $item_added;
				switch($error_type)
					{
					case 'qty':
						$this->error_message = $jcart['text']['quantity_error'];
						break;
					case 'price':
						$this->error_message = $jcart['text']['price_error'];
						break;
					}
				}
			}

		// UPDATE A SINGLE ITEM
		// CHECKING POST VALUE AGAINST $jcart['text'] ARRAY FAILS?? HAVE TO CHECK AGAINST $jcart ARRAY
		if ($_POST['jcart_update_item'] == $jcart['text']['update_button'])
			{
			$item_updated = $this->update_item($_POST['item_id'], $_POST['item_qty']);
			if ($item_updated !== true)
				{
				$this->error_message = $jcart['text']['quantity_error'];
				}
			}

		// UPDATE ALL ITEMS IN THE CART
		if($_POST['jcart_update_cart'] || $_POST['jcart_checkout'])
			{
			$this_updated = $this->update_cart();
			if ($this_updated !== true)
				{
				$this->error_message = $jcart['text']['quantity_error'];
				}
			}

		// REMOVE AN ITEM
		if($_GET['jcart_remove'] && !$_POST[$item_add] && !$_POST['jcart_update_cart'] && !$_POST['jcart_check_out'])
			{
			$this->del_item($_GET['jcart_remove']);
			}

		// EMPTY THE CART
		if($_POST['jcart_empty'])
			{
			$this->empty_cart();
			}

		// DETERMINE WHICH TEXT TO USE FOR THE NUMBER OF ITEMS IN THE CART
		if ($this->itemcount >= 0)
			{
			$jcart['text']['items_in_cart'] = $jcart['text']['multiple_items'];
			}
		if ($this->itemcount == 1)
			{
			$jcart['text']['items_in_cart'] = $jcart['text']['single_item'];
			}


  }
  
  function create_order($payment_method) {
    global $context;
    
    // recup id client
    $user_id = Surfer::get_id();
    
    // recup date commande
    $order_date = date('Y/m/d H:i:s');
    
    // creation commande
		$query = "INSERT INTO ".SQL::table_name('orders')." SET \n"
			."order_date='".$order_date."', \n"
			."user_id='".SQL::escape($user_id)."', \n"
			."payment_method='".SQL::escape($payment_method)."', \n"
			."total_ht=".SQL::escape($this->total_ht).", \n"
			."tva=".SQL::escape($this->tva).", \n"
			."port=".SQL::escape($this->port).", \n"
			."total_ttc=".SQL::escape($this->total_ttc)."\n";
			
		// actual insert
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		$commande_id = SQL::get_last_id($context['connection']);

		foreach($this->items as $tmp_item)
		{
  		$query = "INSERT INTO ".SQL::table_name('orders_lines')." SET \n"
  			."order_id='".SQL::escape($commande_id)."', \n"
  			."reference='".SQL::escape($tmp_item)."', \n"
  			."product_id='".SQL::escape($this->itemarticles[$tmp_item])."', \n"
  			."shop_id='".SQL::escape($this->itemshops[$tmp_item])."', \n"
  			."pu_ht=".SQL::escape($this->itemprices[$tmp_item]).", \n"
  			."tax=".SQL::escape($this->itemtaxes[$tmp_item]).", \n"
  			."quantity=".SQL::escape($this->itemqtys[$tmp_item])."\n";

  		// actual insert
  		if(SQL::query($query) === FALSE)
  			return FALSE;
  			
		}
		
		// recup section commandes
		$commandes_anchor = Sections::lookup('commandes');

    // cr�ation article yacs : page commande
    $article = array();
    $article['title'] = 'Commande numero '.$commande_id.' du '.$order_date;
    $article['overlay_id'] = $commande_id;
    $overlay = array();
    $overlay['overlay_type'] = 'commande';
    $article['overlay'] = serialize($overlay);
    $article['anchor'] = $commandes_anchor;
    $current_date = strftime('%Y-%m-%d %H:%M:%S', time() + ((Surfer::get_gmt_offset() - intval($context['gmt_offset'])) * 3600));
    $article['publish_date'] = $article['create_date'] = $current_date;
    $article['publish_id'] = $article['create_id'] = $user_id;
    $page_id = Articles::post($article);
    
    // mise � jour n�page sur commande
    $q = "UPDATE ".SQL::table_name('orders')." SET page_id = '".$page_id."' WHERE id = '".$commande_id."'";
    SQL::query($q);
    
    // page commande associ�e � user
    Members::toggle($page_id, $user_id);

    return $commande_id;
  }
}
?>