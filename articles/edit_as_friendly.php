<?php
/**
 * create a new article or edit an existing one
 *  - options tab only for associates
 *  - no media tab on creation
 *
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// process uploaded data
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// update an existing page
	if(isset($_REQUEST['id'])) {

		// remember the previous version
		if($item['id'] && Versions::are_different($item, $_REQUEST))
			Versions::save($item, 'article:'.$item['id']);

		// overlay has been inserted or updated
		if(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type'])
			$action = 'insert';
		else
			$action = 'update';

		// stop on error
		if(!Articles::put($_REQUEST) || (is_object($overlay) && !$overlay->remember($action, $_REQUEST))) {
			$item = $_REQUEST;
			$with_form = TRUE;

		// else display the updated page
		} else {

			// touch the related anchor, but only if the page has been published
			if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
				$anchor->touch('article:update', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

			// cascade changes on access rights
			if($_REQUEST['active'] != $item['active'])
				Anchors::cascade('article:'.$item['id'], $_REQUEST['active']);

			// add this page to poster watch list
			if(Surfer::get_id())
				Members::assign('article:'.$item['id'], 'user:'.Surfer::get_id());

			// display the updated page
			Safe::redirect($context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item));
		}


	// create a new page
	} elseif(!$_REQUEST['id'] = Articles::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// successful post
	} else {

		// allow back-referencing from overlay
		$_REQUEST['self_reference'] = 'article:'.$_REQUEST['id'];
		$_REQUEST['self_url'] = $context['url_to_root'].Articles::get_permalink($_REQUEST);

		// post an overlay, with the new article id --don't stop on error
		if(is_object($overlay))
			$overlay->remember('insert', $_REQUEST);

		// increment the post counter of the surfer
		if(Surfer::get_id())
			Users::increment_posts(Surfer::get_id());

		// touch the related anchor, but only if the page has been published
		if(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE)) {
			$anchor->touch('article:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

			// advertise public pages
			if(isset($_REQUEST['active']) && ($_REQUEST['active'] == 'Y')) {

				// pingback, if any
				Links::ping($_REQUEST['description'], 'article:'.$_REQUEST['id']);

				// ping servers
				Servers::notify($anchor->get_url());

			}

			// 'publish' hook
			if(is_callable(array('Hooks', 'include_scripts')))
				Hooks::include_scripts('publish', $_REQUEST['id']);

		}

		// get the new item
		$article =& Anchors::get('article:'.$_REQUEST['id'], TRUE);

		// page title
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the page has been published
		if(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE))
			$context['text'] .= i18n::s('<p>The new page has been successfully published. Please review it now to ensure that it reflects your mind.</p>');

		// remind that the page has to be published
		elseif(Surfer::is_empowered())
			$context['text'] .= i18n::s('<p>Don\'t forget to publish the new page someday. Review the page, enhance it and then click on the Publish command to make it publicly available.</p>');

		// section ask for auto-publish, but the surfer has posted a draft document
		elseif((isset($context['users_with_auto_publish']) && ($context['users_with_auto_publish'] == 'Y')) || (is_object($anchor) && $anchor->has_option('auto_publish')))
			$context['text'] .= i18n::s('<p>Don\'t forget to publish the new page someday. Review the page, enhance it and then click on the Publish command to make it publicly available.</p>');

		// reward regular members
		else
			$context['text'] .= i18n::s('<p>The new page will now be reviewed before its publication. It is likely that this will be done within the next 24 hours at the latest.</p>');

		// list persons that have been notified
		$context['text'] .= Mailer::build_recipients(i18n::s('Persons that have been notified of your post'));

		// list endpoints that have been notified
		$context['text'] .= Servers::build_endpoints(i18n::s('Servers that have been notified of your post'));

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($article->get_url() => i18n::s('View the page')));
		if(Surfer::may_upload()) {
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Upload a file')));
		}
		$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Add a link')));
		if((!isset($_REQUEST['publish_date']) || ($_REQUEST['publish_date'] <= NULL_DATE)) && Surfer::is_empowered())
			$menu = array_merge($menu, array(Articles::get_url($_REQUEST['id'], 'publish') => i18n::s('Publish the page')));
		if(Surfer::get_email_address() && isset($context['with_email']) && ($context['with_email'] == 'Y'))
			$menu = array_merge($menu, array(Articles::get_url($_REQUEST['id'], 'invite') => i18n::s('Invite participants')));
		if(is_object($anchor) && Surfer::is_empowered())
			$menu = array_merge($menu, array('articles/edit.php?anchor='.urlencode($anchor->get_reference()) => i18n::s('Add another page')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the creation of a new page
		if(!Surfer::is_empowered())
			$label = sprintf(i18n::c('New submission: %s'), strip_tags($article->get_title()));
		else
			$label = sprintf(i18n::c('New page: %s'), strip_tags($article->get_title()));

		// poster and target section
		if(is_object($anchor))
			$description = sprintf(i18n::s('Sent by %s in %s'), Surfer::get_name(), $anchor->get_title())."\n\n";
		else
			$description = sprintf(i18n::s('Sent by %s'), Surfer::get_name())."\n\n";

		// title and link
		if($title = $article->get_title())
			$description .= $title."\n";
		$description = $context['url_to_home'].$context['url_to_root'].$article->get_url()."\n\n";

		// teaser
		if($teaser = $article->get_teaser('basic'))
			$description .= "\n\n".$teaser."\n\n";

		// notify sysops
		Logger::notify('articles/edit.php', $label, $description);

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit an article
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';
	$fields = array();

	//
	// panels
	//
	$panels = array();

	//
	// information tab
	//
	$text = '';

	// additional fields for anonymous surfers
	if(!Surfer::is_logged()) {

		// splash
		if(isset($item['id']))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php?id='.$item['id']);
		elseif(is_object($anchor))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php?anchor='.$anchor->get_reference());
		else
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php');
		$text .= '<p>'.sprintf(i18n::s('If you have previously registered to this site, please %s. Then the server will automatically put your name and address in following fields.'), Skin::build_link($login_url, 'authenticate'))."</p>\n";

		// the name, if any
		$label = i18n::s('Your name');
		$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" value="'.encode_field(Surfer::get_name(' ')).'" />';
		$hint = i18n::s('Let us a chance to know who you are');
		$fields[] = array($label, $input, $hint);

		// the address, if any
		$label = i18n::s('Your e-mail address');
		$input = '<input type="text" name="edit_address" size="45" maxlength="128" accesskey="a" value="'.encode_field(Surfer::get_email_address()).'" />';
		$hint = i18n::s('Put your e-mail address to receive feed-back');
		$fields[] = array($label, $input, $hint);

		// stop robots
		if($field = Surfer::get_robot_stopper())
			$fields[] = $field;

	}

	// the title
	if(!is_object($overlay) || !($label = $overlay->get_label('title', isset($item['id'])?'edit':'new')))
		$label = i18n::s('Title').' *';
	$value = '';
	if(isset($item['title']) && $item['title'])
		$value = $item['title'];
	elseif(isset($_SESSION['pasted_title']))
		$value = $_SESSION['pasted_title'];
	$input = '<textarea name="title" id="title" rows="2" cols="50" accesskey="t">'.encode_field($value).'</textarea>';
	if(!is_object($overlay) || !($hint = $overlay->get_label('title_hint', isset($item['id'])?'edit':'new')))
		$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the introduction
	if(!is_object($overlay) || !($label = $overlay->get_label('introduction', isset($item['id'])?'edit':'new')))
		$label = i18n::s('Introduction');
	$value = '';
	if(isset($item['introduction']) && $item['introduction'])
		$value = $item['introduction'];
	elseif(isset($_SESSION['pasted_introduction']))
		$value = $_SESSION['pasted_introduction'];
	$input = '<textarea name="introduction" rows="3" cols="50" accesskey="i">'.encode_field($value).'</textarea>';
	if(!is_object($overlay) || !($hint = $overlay->get_label('introduction_hint', isset($item['id'])?'edit':'new')))
		$hint = i18n::s('Also complements the title in lists featuring this page');
	$fields[] = array($label, $input, $hint);

	// include overlay fields, if any
	if(is_object($overlay))
		$fields = array_merge($fields, $overlay->get_fields($item));

	// the description label
	if(!is_object($overlay) || !($label = $overlay->get_label('description', isset($item['id'])?'edit':'new')))
		$label = i18n::s('Description');

	// use the editor if possible
	$value = '';
	if(isset($item['description']) && $item['description'])
		$value = $item['description'];
	elseif(isset($_SESSION['pasted_text']))
		$value = $_SESSION['pasted_text'];
	$input = Surfer::get_editor('description', $value);
	if(!is_object($overlay) || !($hint = $overlay->get_label('description_hint', isset($item['id'])?'edit':'new')))
		$hint = '';
	$fields[] = array($label, $input, $hint);

	// tags
	$label = i18n::s('Tags');
	$input = '<input type="text" name="tags" id="tags" value="'.encode_field(isset($item['tags'])?$item['tags']:'').'" size="45" maxlength="255" accesskey="t" /><div id="tags_choices" class="autocomplete"></div>';
	$hint = i18n::s('A comma-separated list of keywords');
	$fields[] = array($label, $input, $hint);

	// end of regular fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// trailer information
	$label = i18n::s('Trailer');
	$input = Surfer::get_editor('trailer', isset($item['trailer'])?$item['trailer']:'');
	$hint = i18n::s('Text to be appended at the bottom of the page, after all other elements attached to this page.');
	$fields[] = array($label, $input, $hint);

	// extra information
	$label = i18n::s('Extra');
	$input = '<textarea name="extra" rows="6" cols="50">'.encode_field(isset($item['extra']) ? $item['extra'] : '').'</textarea>';
	$hint = i18n::s('Text to be inserted in the panel aside the page. Use [box.extra=title]content[/box] or plain HTML.');
	$fields[] = array($label, $input, $hint);

	// add a folded box
	$text .= Skin::build_box(i18n::s('More content'), Skin::build_form($fields), 'folded');
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('information', i18n::s('Information'), 'information_panel', $text);

	//
	// append tabs from the overlay, if any
	//
	if(is_object($overlay) && ($more_tabs = $overlay->get_tabs('edit', $item)))
 		$panels = array_merge($panels, $more_tabs);

	//
	// embedded tab
	//
	$text = '';
	
	// if we are editing an existing article
	if (isset($item['id'])) {

  	// the icon url may be set after the page has been created
  	if(Surfer::is_empowered() && Surfer::is_member()) {
  		$label = i18n::s('Image');

  		// show the current icon
  		if(isset($item['icon_url']) && $item['icon_url']) {
  			$input = '<img src="'.preg_replace('/\/images\/article\/[0-9]+\//', "\\0thumbs/", $item['icon_url']).'" alt="" />';
  			$command = i18n::s('Change');
  		} else {
  			$input = '<span class="details">'.i18n::s('Upload an image to be displayed at page top').'</span>';
  			$command = i18n::s('Add an image');
  		}

  		$value = '';
  		if(isset($item['icon_url']) && $item['icon_url'])
  			$value = $item['icon_url'];
  		$input .= BR.'<input type="text" name="icon_url" size="55" value="'.encode_field($value).'" maxlength="255" />';
  		if(Surfer::may_upload())
  			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=icon', $command, 'button').'</span>';
  		$fields[] = array($label, $input);

  	// the thumbnail url may be set after the page has been created
  		$label = i18n::s('Thumbnail');

  		// show the current thumbnail
  		if(isset($item['thumbnail_url']) && $item['thumbnail_url']) {
  			$input = '<img src="'.$item['thumbnail_url'].'" alt="" />';
  			$command = i18n::s('Change');
  		} else {
  			$input = '<span class="details">'.i18n::s('Upload a small image to illustrate this page when it is listed into parent page.').'</span>';
  			$command = i18n::s('Add an image');
  		}

  		$input .= BR.'<input type="text" name="thumbnail_url" size="55" value="'.encode_field(isset($item['thumbnail_url']) ? $item['thumbnail_url'] : '').'" maxlength="255" />';
  		if(Surfer::may_upload())
  			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=thumbnail', $command, 'button').'</span>';
  		$fields[] = array($label, $input);
  	}

  	// end of regular fields
  	$text .= Skin::build_form($fields);
   	$fields = array();

  	// the list of images
  	if($items = Images::list_by_date_for_anchor('article:'.$item['id']))
  		$text .= Skin::build_box(i18n::s('Images'), Skin::build_list($items, 'decorated'), 'unfolded', 'edit_images');

		// files are reserved to authenticated members
		if($items = Files::list_embeddable_for_anchor('article:'.$item['id'], 0, 50))
			$text .= Skin::build_box(i18n::s('Files'), Skin::build_list($items, 'decorated'), 'unfolded');

		// locations are reserved to authenticated members
		if(Locations::allow_creation($anchor, $item)) {
			$menu = array( 'locations/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add a location') );
			$items = Locations::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'article:'.$item['id']);
			$text .= Skin::build_box(i18n::s('Locations'), Skin::build_list($menu, 'menu_bar').Skin::build_list($items, 'decorated'), 'folded');
		}

		// tables are reserved to associates
		if(Tables::allow_creation($anchor, $item)) {
			$menu = array( 'tables/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add a table') );
			$items = Tables::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'article:'.$item['id']);
			$text .= Skin::build_box(i18n::s('Tables'), Skin::build_list($menu, 'menu_bar').Skin::build_list($items, 'decorated'), 'folded');
		}

  	// add a folded box
  	if(count($fields))
  		$text .= Skin::build_box(i18n::s('Other attachments'), Skin::build_form($fields), 'folded');
  	$fields = array();

  	// display in a separate panel
  	if($text)
  		$panels[] = array('media', i18n::s('Media'), 'media_panel', $text);
  }
  
	//
	// options tab
	//
	$text = '';
	
	// provide information to section owner
	if(isset($item['id'])) {

		// owner
		$label = i18n::s('Owner');
		$input = '';
		if(isset($item['owner_id'])) {
			if($owner = Users::get($item['owner_id']))
				$input = Users::get_link($owner['full_name'], $owner['email'], $owner['id']);
			else
				$input = i18n::s('No owner has been found.');
		}

		// change the owner
		if(Articles::is_owned($item, $anchor) || Surfer::is_associate())
			$input .= ' <span class="details">'.Skin::build_link(Articles::get_url($item['id'], 'own'), i18n::s('Change'), 'button').'</span>';

		$fields[] = array($label, $input);

		// editors
		$label = i18n::s('Editors');
		if($items =& Members::list_editors_for_member('article:'.$item['id'], 0, USERS_LIST_SIZE, 'comma'))
			$input =& Skin::build_list($items, 'comma');
		else
			$input = i18n::s('Nobody has been assigned to this page.');

		// manage participants and editors
 		if(Articles::is_owned($item, $anchor, TRUE) || Surfer::is_associate()) {

			$input .= ' <span class="details">'.Skin::build_link(Articles::get_url($item['id'], 'invite'), i18n::s('Invite participants'), 'button').'</span>';

			$input .= ' <span class="details">'.Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), i18n::s('Manage editors'), 'button').'</span>';

		}

		$fields[] = array($label, $input);
	}

// the active flag: Yes/public, Restricted/logged, No/associates --we don't care about inheritance, to enable security changes afterwards
	$label = i18n::s('Access');

	// maybe a public page
	$input = '<input type="radio" name="active_set" value="Y" accesskey="v"';
	if(!isset($item['active_set']) || ($item['active_set'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Public - Access is granted to anonymous surfers').BR;

	// maybe a restricted page
	$input .= '<input type="radio" name="active_set" value="R"';
	if(isset($item['active_set']) && ($item['active_set'] == 'R'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Community - Access is restricted to authenticated members').BR;

	// or a hidden page
	$input .= '<input type="radio" name="active_set" value="N"';
	if(isset($item['active_set']) && ($item['active_set'] == 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Private - Access is restricted to selected persons')."\n";

	// combine this with inherited access right
	if(is_object($anchor) && $anchor->is_hidden())
		$hint = i18n::s('Parent is private, and this will be re-enforced anyway');
	elseif(is_object($anchor) && !$anchor->is_public())
		$hint = i18n::s('Parent is not public, and this will be re-enforced anyway');
	else
		$hint = i18n::s('Who is allowed to access?');

	// expand the form
	$fields[] = array($label, $input, $hint);

	// locked: Yes / No
	$label = i18n::s('Locker');
	$input = '<input type="radio" name="locked" value="N"';
	if(!isset($item['locked']) || ($item['locked'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Contributions are accepted').' '
		.BR.'<input type="radio" name="locked" value="Y"';
	if(isset($item['locked']) && ($item['locked'] == 'Y'))
		$input .= ' checked="checked"';
	if(isset($item['active']) && ($item['active'] == 'N'))
		$input .= '/> '.i18n::s('Only owners and associates can add content');
	else
		$input .= '/> '.i18n::s('Only assigned persons, owners and associates can add content');
	$fields[] = array($label, $input);

	// append fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// the rank
	// the default value
	if(!isset($item['rank']))
		$item['rank'] = 10000;

	$label = i18n::s('Rank');
	$input = '<input type="text" name="rank" id="rank" size="10" value="'.encode_field($item['rank']).'" maxlength="255" />';
	$hint = sprintf(i18n::s('For %s pages; regular pages are ranked at %s.'),
		'<a href="#" onclick="$(\'rank\').value=10; return false;">'.i18n::s('sticky').'</a>',
		'<a href="#" onclick="$(\'rank\').value=10000; return false;">'.i18n::s('10000').'</a>');
	$fields[] = array($label, $input, $hint);

	// the publication date
	$label = i18n::s('Publication date');
	if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
		$input = Surfer::from_GMT($item['publish_date']);
  elseif(isset($item['id'])) {
		Skin::define_img('ARTICLES_PUBLISH_IMG', 'articles/publish.gif');
		$input = Skin::build_link(Articles::get_url($item['id'], 'publish'), ARTICLES_PUBLISH_IMG.i18n::s('Publish'), 'basic');
	} else {
		Skin::define_img('ARTICLES_UNPUBLISH_IMG', 'articles/unpublish.gif');
		$input = ARTICLES_UNPUBLISH_IMG.i18n::s('not published');
  }
  $fields[] = array($label, $input);

	// the expiry date
	$label = i18n::s('Expiry date');
	if(isset($item['expiry_date']) && ($item['expiry_date'] > NULL_DATE))
		$input = Surfer::from_GMT($item['expiry_date']);
	else
		$input = i18n::s('never');
	$fields[] = array($label, $input);

	// the parent section
	if(is_object($anchor)) {

		if(isset($item['id']) && $anchor->is_assigned()) {
			$label = i18n::s('Section');
			$input =& Skin::build_box(i18n::s('Select parent container'), Sections::get_radio_buttons($anchor->get_reference()), 'folded');
			$fields[] = array($label, $input);
		} else
			$text .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	}

	// append fields
	if(is_object($anchor))
		$label = sprintf(i18n::s('Contribution to "%s"'), $anchor->get_title());
	else
		$label = i18n::s('Contribution to parent container');
	$text .= Skin::build_box($label, Skin::build_form($fields), 'folded');
	$fields = array();

	// the nick name
	if(Articles::is_owned($item, $anchor) || Surfer::is_associate()) {
		$label = i18n::s('Nick name');
		$value = '';
		if(isset($item['nick_name']) && $item['nick_name'])
			$value = $item['nick_name'];
		elseif(isset($_SESSION['pasted_name']))
			$value = $_SESSION['pasted_name'];
		$input = '<input type="text" name="nick_name" size="32" value="'.encode_field($value).'" maxlength="64" accesskey="n" />';
		$hint = sprintf(i18n::s('To designate a page by its name in the %s'), Skin::build_link('go.php', 'page selector', 'help'));
		$fields[] = array($label, $input, $hint);
	}

	// the source
	$label = i18n::s('Source');
	$value = '';
	if(isset($item['source']) && $item['source'])
		$value = $item['source'];
	elseif(isset($_SESSION['pasted_source']))
		$value = $_SESSION['pasted_source'];
	$input = '<input type="text" name="source" value="'.encode_field($value).'" size="45" maxlength="255" accesskey="e" />';
	$hint = i18n::s('Mention your source, if any. Web link (http://...), internal reference ([user=tom]), or free text.');
	$fields[] = array($label, $input, $hint);

	// rendering options
	$label = i18n::s('Rendering');
	$input = '<input type="text" name="options" id="options" size="55" value="'.encode_field(isset($item['options']) ? $item['options'] : '').'" maxlength="255" accesskey="o" />'
		.JS_PREFIX
		.'function append_to_options(keyword) {'."\n"
		.'	var target = $("options");'."\n"
		.'	target.value = target.value + " " + keyword;'."\n"
		.'}'."\n"
		.JS_SUFFIX;
	$keywords = array();
	$keywords[] = '<a onclick="append_to_options(\'anonymous_edit\')" style="cursor: pointer;">anonymous_edit</a> - '.i18n::s('Allow anonymous surfers to edit content');
	$keywords[] = '<a onclick="append_to_options(\'members_edit\')" style="cursor: pointer;">members_edit</a> - '.i18n::s('Allow members to edit content');
	$keywords[] = '<a onclick="append_to_options(\'comments_as_wall\')" style="cursor: pointer;">comments_as_wall</a> - '.i18n::s('Allow easy interactions between people');
	$keywords[] = '<a onclick="append_to_options(\'no_comments\')" style="cursor: pointer;">no_comments</a> - '.i18n::s('Prevent the addition of comments');
	$keywords[] = '<a onclick="append_to_options(\'files_by_title\')" style="cursor: pointer;">files_by_title</a> - '.i18n::s('Sort files by title (and not by date)');
	$keywords[] = '<a onclick="append_to_options(\'no_files\')" style="cursor: pointer;">no_files</a> - '.i18n::s('Prevent the upload of new files');
	$keywords[] = '<a onclick="append_to_options(\'links_by_title\')" style="cursor: pointer;">links_by_title</a> - '.i18n::s('Sort links by title (and not by date)');
	$keywords[] = '<a onclick="append_to_options(\'no_links\')" style="cursor: pointer;">no_links</a> - '.i18n::s('Prevent the addition of related links');
	$keywords[] = '<a onclick="append_to_options(\'view_as_chat\')" style="cursor: pointer;">view_as_chat</a> - '.i18n::s('Real-time collaboration');
	$keywords[] = '<a onclick="append_to_options(\'view_as_tabs\')" style="cursor: pointer;">view_as_tabs</a> - '.i18n::s('Tabbed panels');
	$keywords[] = '<a onclick="append_to_options(\'view_as_wiki\')" style="cursor: pointer;">view_as_wiki</a> - '.i18n::s('Discussion is separate from content');
	$keywords[] = 'view_as_foo_bar - '.sprintf(i18n::s('Branch out to %s'), 'articles/view_as_foo_bar.php');
	$keywords[] = 'edit_as_simple - '.sprintf(i18n::s('Branch out to %s'), 'articles/edit_as_simple.php');
	$keywords[] = 'skin_foo_bar - '.i18n::s('Apply a specific theme (in skins/foo_bar)');
	$keywords[] = 'variant_foo_bar - '.i18n::s('To load template_foo_bar.php instead of the regular template');
	$hint = i18n::s('You may combine several keywords:').Skin::finalize_list($keywords, 'compact');
	$fields[] = array($label, $input, $hint);

	// language of this page
	$label = i18n::s('Language');
	$input = i18n::get_languages_select(isset($item['language'])?$item['language']:'');
	$hint = i18n::s('Select the language used for this page');
	$fields[] = array($label, $input, $hint);

	// meta information
	$label = i18n::s('Meta information');
	$input = '<textarea name="meta" rows="10" cols="50">'.encode_field(isset($item['meta']) ? $item['meta'] : '').'</textarea>';
	$hint = i18n::s('Type here any XHTML tags to be put in page header.');
	$fields[] = array($label, $input, $hint);

	// associates can change the overlay --complex interface
	if(Surfer::has_all()) {

		// current type
		$overlay_type = '';
		if(is_object($overlay))
			$overlay_type = $overlay->get_type();

		// list overlays available on this system
		$label = i18n::s('Change the overlay');
		$input = '<select name="overlay_type">';
		if($overlay_type) {
			$input .= '<option value="none">('.i18n::s('none').")</option>\n";
			$hint = i18n::s('If you change the overlay you may loose some data.');
		} else {
			$hint = i18n::s('No overlay has been selected yet.');
			$input .= '<option value="" selected="selected">'.i18n::s('none')."</option>\n";
		}
		if ($dir = Safe::opendir($context['path_to_root'].'overlays')) {

			// every php script is an overlay, except index.php, overlay.php, and hooks
			while(($file = Safe::readdir($dir)) !== FALSE) {
				if(($file[0] == '.') || is_dir($context['path_to_root'].'overlays/'.$file))
					continue;
				if($file == 'index.php')
					continue;
				if($file == 'overlay.php')
					continue;
				if(preg_match('/hook\.php$/i', $file))
					continue;
				if(!preg_match('/(.*)\.php$/i', $file, $matches))
					continue;
				$overlays[] = $matches[1];
			}
			Safe::closedir($dir);
			if(@count($overlays)) {
				natsort($overlays);
				foreach($overlays as $overlay_name) {
					$selected = '';
					if($overlay_name == $overlay_type)
						$selected = ' selected="selected"';
					$input .= '<option value="'.$overlay_name.'"'.$selected.'>'.$overlay_name."</option>\n";
				}
			}
		}
		$input .= '</select>';
		$fields[] = array($label, $input, $hint);

	// remember overlay type
	} elseif(is_object($overlay))
		$text .= '<input type="hidden" name="overlay_type" value="'.encode_field($overlay->get_type()).'" />';

	// add a folded box
	$text .= Skin::build_box(i18n::s('More options'), Skin::build_form($fields), 'folded');
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('options', i18n::s('Options'), 'options_panel', $text);

	//
	// assemble all tabs
	//
	$context['text'] .= Skin::build_tabs($panels);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(isset($item['id']))
		$menu[] = Skin::build_link(Articles::get_permalink($item), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// several options to check
	$input = array();

	// keep as draft
	if(!isset($item['id']))
		$input[] = '<input type="checkbox" name="option_draft" value="Y" /> '.i18n::s('This is a draft document. Do not publish the page, even if auto-publish has been enabled.');

	// do not remember changes on existing pages -- complex command
	if(isset($item['id']) && Surfer::is_empowered() && Surfer::has_all())
		$input[] = '<input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date.');

	// hardcoded
	if(!isset($item['id']))
		$input[] = '<input type="checkbox" name="option_hardcoded" value="Y" /> '.i18n::s('Preserve carriage returns and newlines.');

	// do not apply implicit transformations
	if(!isset($item['id']))
		$input[] = '<input type="checkbox" name="option_formatted" value="Y" /> '.i18n::s('Avoid implicit transformations (links, lists, ...), but process yacs codes as usual.');

	// validate page content
	$input[] = '<input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.');

	// append post-processing options
	if($input)
		$context['text'] .= '<p>'.implode(BR, $input).'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['page_footer'] .= JS_PREFIX
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// title is mandatory'."\n"
		.'	if(!Yacs.trim(container.title.value)) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		$("title").focus();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		.'	// extend validation --used in overlays'."\n"
		.'	if(typeof validateOnSubmit == "function") {'."\n"
		.'		return validateOnSubmit(container);'."\n"
		.'	}'."\n"
		."\n";

	// warning on jumbo size, but only on first post
	if(!isset($item['id']))
		$context['page_footer'] .= '	if(container.description.value.length > 64000){'."\n"
			.'		return confirm("'.i18n::s('Page content exceeds 64,000 characters. Do you confirm you are intended to post a jumbo page?').'");'."\n"
			.'	}'."\n"
			."\n";

	$context['page_footer'] .= '	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// detect changes in form'."\n"
		.'func'.'tion detectChanges() {'."\n"
		."\n"
		.'	var nodes = $$("form#main_form input");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		Event.observe(node, "change", function() { $("preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		."\n"
		.'	nodes = $$("form#main_form textarea");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		Event.observe(node, "change", function() { $("preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		.'}'."\n"
		."\n"
		.'// observe changes in form'."\n"
		.'Event.observe(window, "load", detectChanges);'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'Event.observe(window, "load", function() { $("title").focus() });'."\n"
		."\n"
		.'// enable tags autocompletion'."\n"
		.'Event.observe(window, "load", function() { new Ajax.Autocompleter("tags", "tags_choices", "'.$context['url_to_root'].'categories/complete.php", { paramName: "q", minChars: 1, frequency: 0.4, tokens: "," }); });'."\n";
    if (!Surfer::is_associate())
      $context['page_footer'] .= '$("_options").hide();';
    $context['page_footer'] .= JS_SUFFIX;

	// clear session data now that we have populated the form
	unset($_SESSION['anchor_reference']);
	unset($_SESSION['pasted_blogid']);
	unset($_SESSION['pasted_introduction']);
	unset($_SESSION['pasted_name']);
	unset($_SESSION['pasted_section']);
	unset($_SESSION['pasted_source']);
	unset($_SESSION['pasted_text']);
	unset($_SESSION['pasted_title']);

	// content of the help box
	$help = '';

	// capture help messages from the overlay, if any
	if(is_object($overlay))
		$help .= $overlay->get_label('help', isset($item['id'])?'edit':'new');

	// splash message for new pages
	if(!isset($item['id']))
		$help .= '<p>'.i18n::s('Please type the text of your new page and hit the submit button. You will then be able to post images, files and links on subsequent forms.').'</p>';

	// html and codes
	$help .= '<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';

 	// locate mandatory fields
 	$help .= '<p>'.i18n::s('Mandatory fields are marked with a *').'</p>';

 	// change to another editor
	$help .= '<form action=""><p><select name="preferred_editor" id="preferred_editor" onchange="Yacs.setCookie(\'surfer_editor\', this.value); window.location = window.location;">';
	$selected = '';
	if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'tinymce'))
		$selected = ' selected="selected"';
	$help .= '<option value="tinymce"'.$selected.'>'.i18n::s('TinyMCE')."</option>\n";
	$selected = '';
	if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'fckeditor'))
		$selected = ' selected="selected"';
	$help .= '<option value="fckeditor"'.$selected.'>'.i18n::s('FCKEditor')."</option>\n";
	$selected = '';
	if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'yacs'))
		$selected = ' selected="selected"';
	$help .= '<option value="yacs"'.$selected.'>'.i18n::s('Textarea')."</option>\n";
	$help .= '</select></p></form>';

	// in a side box
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();

?>