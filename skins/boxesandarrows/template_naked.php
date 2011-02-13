<?php
// this one has to be included
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}

	// display main content
	Page::content();

?>