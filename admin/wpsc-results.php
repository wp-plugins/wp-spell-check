<?php
/* Admin Classes */
/*
	Works in the background: yes
	Pro version scans the entire website: yes
	Sends email reminders: yes
	Finds place holder text: yes
	Custom Dictionary for unusual words: yes
	Scans Password Protected membership Sites: yes
	Unlimited scans on my website: Yes


	Scans Categories: Yes WP Spell Check Pro
	Scans SEO Titles: Yes WP Spell Check Pro
	Scans SEO Descriptions: Yes WP Spell Check Pro
	Scans WordPress Menus: Yes WP Spell Check Pro
	Scans Page Titles: Yes WP Spell Check Pro
	Scans Post Titles: Yes WP Spell Check Pro
	Scans Page slugs: Yes WP Spell Check Pro
	Scans Post Slugs: Yes WP Spell Check Pro
	Scans Post categories: Yes WP Spell Check Pro

	Privacy URI: https://www.wpspellcheck.com/privacy-policy/
	Pro Add-on / Home Page: https://www.wpspellcheck.com/
	Pro Add-on / Prices: https://www.wpspellcheck.com/purchase-options/
*/
class sc_table extends WP_List_Table {

	function __construct() {
		global $status, $page;
		
		//Set Defaults
		parent::__construct( array(
			'singular' => 'word',
			'plural' => 'words',
			'ajax' => true
		) );
	}
	
	function column_default($item, $column_name) {
		return print_r($item,true);
	}
	
	//Set up options for words in the table
	function column_word($item) {
		//Build suggested spellings list
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_options';
		$dict_table = $wpdb->prefix . "spellcheck_dictionary";
		$language_setting = $wpdb->get_results('SELECT option_value from ' . $table_name . ' WHERE option_name="language_setting";');
		$dict_words = $wpdb->get_results('SELECT word FROM ' . $dict_table . ';');
		$pspell_link = pspell_new($language_setting[0]->option_value);
		if ($spell_link == false) {
			$pspell_config = pspell_config_create('en');
			if ($language_setting[0]->option_value == 'en_CA') {
				pspell_config_personal($pspell_config, dirname(__FILE__) . "/dict/en_CA.pws");
			} elseif ($language_setting[0]->option_value == 'en_US') {
				pspell_config_personal($pspell_config, dirname(__FILE__) . "/dict/en_US.pws");
			} elseif ($language_setting[0]->option_value == 'en_US') {
				pspell_config_personal($pspell_config, dirname(__FILE__) . "/dict/en_UK.pws");
			}
			$pspell_link = pspell_new_config($pspell_config);
		}
		$sorting = '';
		if ($_GET['orderby'] != '') $sorting .= '&orderby=' . $_GET['orderby'];
		if ($_GET['order'] != '') $sorting .= '&order=' . $_GET['order'];
		if ($_GET['paged'] != '') $sorting .= '&paged=' . $_GET['paged'];

		foreach ($dict_words as $dict_word) {
			pspell_add_to_session($pspell_link, $dict_word->option_value);
		}
		$suggestions = pspell_suggest($pspell_link, $item['word']);

		//build row actions
		if ($item['page_type'] == 'Page Slug' || $item['page_type'] == 'Post Slug') {
			$actions = array (
				'Ignore'      			=> sprintf('<a href="?page=wp-spellcheck.php&ignore=' . $item['id'] . $sorting . '">Ignore</a>'),
				'Add to Dictionary'		=> sprintf('<a href="?page=wp-spellcheck.php&add=' . $item['word'] . '">Add to Dictionary</a>')
			);
		} else {
			$actions = array (
				'Ignore'      			=> sprintf('<a href="?page=wp-spellcheck.php&ignore=' . $item['id'] . $sorting . '">Ignore</a>'),
				'Suggested Spelling'	=> sprintf('<a href="#" class="wpsc-suggest-button" suggestions="' . $suggestions[0] . '-' . $suggestions[1] . '-' . $suggestions[2] . '-' . $suggestions[3] . '">Suggested Spelling</a>'),
				'Edit'					=> sprintf('<a href="#" class="wpsc-edit-button" page_type="' . $item['page_type'] . '" id="wpsc-word-' . $item['word'] . '">Edit</a>'),
				'Add to Dictionary'		=> sprintf('<a href="?page=wp-spellcheck.php&add=' . $item['word'] . $sorting . '">Add to Dictionary</a>')
			);
		}
		
		//return the word contents
		return sprintf('%1$s<span style="color:silver"></span>%3$s',
            stripslashes($item['word']),
            $item['ID'],
            $this->row_actions($actions)
        );
	}
	
	//Set up actions for page name
	function column_page_name($item) {
		//build row actions
		//Get page URL
		global $wpdb;
		$page = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type!='revision' AND post_title = %s", $item[page_name]));
		$link = urldecode ( get_permalink( $page ) );
		$actions = array (
			'View'      			=> sprintf('<a href="' . $link . '" id="wpsc-page-name" page="' . $page . '" target="_blank">View</a>'),
		);
		
		//return the word contents
		return sprintf('%1$s <span style="color:silver"></span>%3$s',
            $item['page_name'],
            $item['ID'],
            $this->row_actions($actions)
        );
	}

	//Set up actions for page type
	function column_page_type($item) {
		//build row actions
		$actions = array ();
		
		//return the word contents
		return sprintf('%1$s <span style="color:silver"></span>%3$s',
            $item['page_type'],
            $item['ID'],
            $this->row_actions($actions)
        );
	}
	
	//Create checkbox for bulk actions
	function column_cb($item) {
		return sprintf( '<input type="checkbox" name="word[]" value="%s" />', $item['id'] );
	}

	//Create bulk actions
	function get_bulk_actions() {
		return array(
			'Ignore' => __( 'Ignore' ),
			'Add to Dictionary' => __( 'Add to Dictionary' )
		);
	}

	//Handle bulk actions
	function process_bulk_action() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		// security check!
       		if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

	        $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	        $action = 'bulk-' . $this->_args['plural'];
		$bulk_message = '';

        	if ( ! wp_verify_nonce( $nonce, $action ) )
	                wp_die( 'Nope! Security check failed!' );
        	}

		$action = $this->current_action();
		$words = isset($_REQUEST['word']) ? $_REQUEST['word'] : array();

		switch ( $action ) {
			case 'Ignore':
				for ($x = 0; $x < sizeof($words); $x++) {
					ignore_word($words[$x]);
					$word_ignored = $wpdb->get_results('SELECT word FROM ' . $table_name . ' WHERE id="'.$words[$x].'";');
					$bulk_message .= $word_ignored[0]->word . ", ";
				}
				$bulk_message .= "Have been added to the ignore list";
				break;

			case 'Add to Dictionary':
				for ($x = 0; $x < sizeof($words); $x++) {
					$results = $wpdb->get_results('SELECT word FROM ' . $table_name . ' WHERE id="'.$words[$x].'";');
					add_to_dictionary($results[0]->word);
					$bulk_message .= $results[0]->word . ", ";
				}
				$bulk_message .= "Have been added to the dictionary";
				break;
		}
		$_GET['bulk_message'] = $bulk_message;
	}
	
	//Get the titles of each column
	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'word' => 'Misspelled Words',
			'page_name' => 'Page',
			'page_type' => 'Page Type'
		);
		return $columns;
	}
	
	//Set which columns the table can be sorted by
	function get_sortable_columns() {
		$sortable_columns = array(
			'word' => array('word',false),
			'page_name' => array('page_name',false),
			'page_type' => array('page_type',false)
		);
		return $sortable_columns;
	}

	//Code for displaying a single row
	function single_row( $item ) {
		static $row_class = 'wpsc-row';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr class="wpsc-row" id="wpsc-row-' . $item['id'] . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	//Prepares table data for display
	function prepare_items() {
		global $wpdb;
		
		$per_page = 20;
		
		//Define and build an array for column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->process_bulk_action(); // Causes issues with page display
		
		//Grab and set up data
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$dictionary_table = $wpdb->prefix . 'spellcheck_dictionary';
		$results = $wpdb->get_results('SELECT id, word, page_name, page_type FROM ' . $table_name . ' WHERE ignore_word is false', OBJECT); // Query that grabs data from database
		$data = array();
		foreach($results as $word) {
			if ($word->word != '') {
				array_push($data, array('id' => $word->id, 'word' => $word->word, 'page_name' => $word->page_name, 'page_type' => $word->page_type, 'page_url' => $word->page_url));
			}
		}
		
		function usort_reorder($a, $b) {
			$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'word'; //Column to sort by, default word
			$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //Order to sort, default ascending
			
			$result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
			return ($order==='asc') ? $result : -$result;
		}
		usort($data, 'usort_reorder');
		
		//Set up pagination
		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		$this->items = $data;
		
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page,
			'total_pages' => ceil($total_items/$per_page)
		) );		
	}
}

/* Admin Functions */
function ignore_word($id) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'spellcheck_words';
	$words = $wpdb->get_results('SELECT word FROM ' . $table_name . ' WHERE id='. $id . ';');
	$word = $words[0]->word;

	$wpdb->update($table_name, array('ignore_word' => true), array('id' => $id));

	return "$word has been added to ignore list";
}

function add_to_dictionary($word) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'spellcheck_words';
	$dictionary_table = $wpdb->prefix . 'spellcheck_dictionary';
	$word = str_replace('%28', '(', $word);

	$check = $wpdb->get_results('SELECT word FROM ' . $dictionary_table . ' WHERE word = "' . $word . '"'); // Check to see if word is already in the dictionary

	if (sizeof($check) < 1)
		$wpdb->insert($dictionary_table, array('word' => $word)); // Add word to the dictionary

	$wpdb->delete($table_name, array('word' => $word)); //Delete all occurrences of the word from existing list of errors

	return "$word has been added to the dictionary";
}

function update_word_admin($old_word, $new_word, $page_name, $page_type) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'posts';
	$words_table = $wpdb->prefix . 'spellcheck_words';
	$terms_table = $wpdb->prefix . 'terms';
	$meta_table = $wpdb->prefix . 'postmeta';
	$old_word = str_replace('%28', '(', $old_word);
	$new_word = str_replace('%28', '(', $new_word);
	$old_word = str_replace('%27', "'", $old_word);
	$new_word = str_replace('%27', "'", $new_word);
	$old_word = stripslashes(stripslashes($old_word));
	$new_word = stripslashes($new_word);


	if ($page_type == 'Post Content' || $page_type == 'Page Content' || $page_type == 'Media Description') {
		//****PAGE AND POST CONTENT****
		$page_result = $wpdb->get_results('SELECT post_content, post_title FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');

		$updated_content = str_replace($old_word, $new_word, $page_result[0]->post_content);

		$old_name = $page_result[0]->post_title;
		$wpdb->update($table_name, array('post_content' => $updated_content), array('ID' => $page_name));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $old_name)); //Delete all occurrences of the word from existing list of errors for this page
	} elseif ($page_type == 'Menu Item' || $page_type == 'Post Title' || $page_type == 'Page Title' || $page_type == 'Slider Title' || $page_type == 'Media Title') {
		//****MENU ITEMS AND PAGE/POST TITLES****
		$menu_result = $wpdb->get_results('SELECT post_title FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');
		$updated_content = str_replace($old_word, $new_word, $menu_result[0]->post_title);

		$old_name = $menu_result[0]->post_title;
		$wpdb->update($table_name, array('post_title' => $updated_content), array('ID' => $page_name));
		$wpdb->update($words_table, array('page_name' => $updated_content), array('page_name' => $old_name)); //Update the title of the page/post/menu in the spellcheck database
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $updated_content)); //Delete all occurrences of the word from existing list of errors
	} elseif ($page_type == 'Slider Caption') {
		//****SLIDER CAPTIONS****
		$menu_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');
		$caption = get_post_meta($menu_result[0]->ID, 'my_slider_caption', true);
		$updated_content = str_replace($old_word, $new_word, $caption);

		update_post_meta($menu_result[0]->ID, 'my_slider_caption', $updated_content);
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $menu_result[0]->post_title)); //Delete all occurrences of the word from existing list of errors
	} elseif ($page_type == 'Huge IT Slider Caption') {
		//****SLIDER CAPTIONS****
		$it_table = $wpdb->prefix . 'wp_huge_itslider_images';
		$menu_result = $wpdb->get_results('SELECT sl_sdesc FROM ' . $it_table . ' WHERE sl_stitle="' . $page_name . '"');
		$updated_content = str_replace($old_word, $new_word, $menu_result[0]->sl_stitle);

		$wpdb->update($it_table, array('sl_sdesc' => $updated_content), array('sl_stitle' => $page_name));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $menu_result[0]->post_title)); //Delete all occurrences of the word from existing list of errors
	} elseif ($page_type == 'Huge IT Slider Title') {
		//****SLIDER CAPTIONS****
		$it_table = $wpdb->prefix . 'wp_huge_itslider_images';
		$menu_result = $wpdb->get_results('SELECT sl_stitle FROM ' . $it_table . ' WHERE sl_stitle="' . $page_name . '"');
		$updated_content = str_replace($old_word, $new_word, $menu_result[0]->sl_stitle);

		$wpdb->update($it_table, array('sl_stitle' => $updated_content), array('sl_stitle' => $page_name));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $menu_result[0]->post_title)); //Delete all occurrences of the word from existing list of errors
	} elseif ($page_type == 'Smart Slider Caption') {
		//****SLIDER CAPTIONS****
		$slider_table = $wpdb->prefix . 'wp_nextend_smartslider_slides';
		$menu_result = $wpdb->get_results('SELECT description FROM ' . $slider_table . ' WHERE title="' . $page_name . '"');
		$updated_content = str_replace($old_word, $new_word, $menu_result[0]->description);

		$wpdb->update($slider_table, array('description' => $updated_content), array('title' => $page_name));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $menu_result[0]->post_title)); //Delete all occurrences of the word from existing list of errors
	} elseif ($page_type == 'Smart Slider Title') {
		//****SLIDER CAPTIONS****
		$slider_table = $wpdb->prefix . 'wp_nextend_smartslider_slides';
		$menu_result = $wpdb->get_results('SELECT title FROM ' . $slider_table . ' WHERE title="' . $page_name . '"');
		$updated_content = str_replace($old_word, $new_word, $menu_result[0]->title);

		$wpdb->update($slider_table, array('title' => $updated_content), array('title' => $page_name));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $menu_result[0]->post_title)); //Delete all occurrences of the word from existing list of errors
	} elseif ($page_type == 'Media Alternate Text') {
		//****SLIDER CAPTIONS****
		$menu_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');
		$caption = get_post_meta($menu_result[0]->ID, '_wp_attachment_image_alt', true);
		$updated_content = str_replace($old_word, $new_word, $caption);

		update_post_meta($menu_result[0]->ID, '_wp_attachment_image_alt', $updated_content);
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $menu_result[0]->post_title)); //Delete all occurrences of the word from existing list of errors
	} elseif ($page_type == 'Media Caption') {
		//****MEDIA CAPTIONS****
		$page_result = $wpdb->get_results('SELECT post_excerpt, post_title FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');

		$updated_content = str_replace($old_word, $new_word, $page_result[0]->post_excerpt);

		$old_name = $page_result[0]->post_title;
		$wpdb->update($table_name, array('post_excerpt' => $updated_content), array('ID' => $page_name));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $old_name)); //Delete all occurrences of the word from existing list of errors for this page
	} elseif ($page_type == 'Post Tag' || $page_type == 'Post Category') {
		//****POST TAGS AND CATEGORIES****
		$tag_result = $wpdb->get_results('SELECT name FROM ' . $terms_table . ' WHERE name LIKE "%' . $old_word . '%"');
		$title_result = $wpdb->get_results('SELECT post_title FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');

		$updated_content = str_replace($old_word, $new_word, $tag_result[0]->name);

		$old_name = $title_result[0]->post_title;
		$wpdb->update($terms_table, array('name' => $updated_content), array('name' => $tag_result[0]->name));
		$wpdb->delete($words_table, array('word' => $old_word)); //Delete all occurrences of the word from existing list of errors
	} elseif ($page_type == 'Yoast SEO Description') {
		//****YOAST SEO DESCRIPTION****
		$page_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_yoast_wpseo_metadesc"');

		$updated_content = str_replace($old_word, $new_word, $desc_result[0]->meta_value);

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_yoast_wpseo_metadesc'));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $old_name)); //Delete all occurrences of the old word.
	} elseif ($page_type == 'All in One SEO Description') {
		//****ALL IN ONE SEO DESCRIPTION****
		$page_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_aioseop_description"');

		$updated_content = str_replace($old_word, $new_word, $desc_result[0]->meta_value);

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_aioseop_description'));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $old_name)); //Delete all occurrences of the old word.
	} elseif ($page_type == 'Ultimate SEO Description') {
		//****ULTIMATE SEO DESCRIPTION****
		$page_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_su_description"');

		$updated_content = str_replace($old_word, $new_word, $desc_result[0]->meta_value);

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_su_description'));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $old_name)); //Delete all occurrences of the old word.
	} elseif ($page_type == 'Yoast SEO Title') {
		//****YOAST SEO TITLE
		$page_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_yoast_wpseo_title"');

		$updated_content = str_replace($old_word, $new_word, $desc_result[0]->meta_value);

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_yoast_wpseo_title'));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $old_name)); //Delete all occurrences of the old word.
	} elseif ($page_type == 'All in One SEO Title') {
		$page_result = $wpdb->get_results('SELECT ID FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_aioseop_title"');

		$updated_content = str_replace($old_word, $new_word, $desc_result[0]->meta_value);

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_aioseop_title'));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $old_name)); //Delete all occurrences of the old word.
	} elseif ($page_type == 'Ultimate SEO Title') {
		$page_result = $wpdb->get_results('SELECT ID FROM ' . $table_name . ' WHERE ID="' . $page_name . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_su_title"');

		$updated_content = str_replace($old_word, $new_word, $desc_result[0]->meta_value);

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_su_title'));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $old_name)); //Delete all occurrences of the old word.
	} elseif ($page_type == 'Post Slug' || $page_type == 'Page Slug') {
		//****PAGE AND POST SLUGS****
		$page_result = $wpdb->get_results('SELECT post_name FROM ' . $table_name . ' WHERE post_title="' . $page_name . '"');

		$updated_content = str_replace($old_word, $new_word, $page_result[0]->post_name);
		$wpdb->update($table_name, array('post_name' => $updated_content), array('post_title' => $page_name));
		$wpdb->delete($words_table, array('word' => $old_word, 'page_name' => $page_name)); //Delete all occurrences of the word from existing list of errors
	}

	

	//Log file for pro features to keep track of updates made
	$page_url = get_permalink( $page_name );
	$page_title = get_the_title( $page_name );
	$current_time = date( 'l F d, g:i a' );
	$loc = dirname(__FILE__) . "/spellcheck.debug";
	$debug_file = fopen($loc, 'a');
	$debug_var = fwrite( $debug_file, "Old Word: " . $old_word . " | New Word: " . $new_word . " | Type: " . $page_type . " | Page Name: " . $page_title . " | Page URL: " . $page_url . " | Timestamp: " . $current_time . "\r\n\r\n" );
	fclose($debug_file);
	return "$old_word has been updated";
}

function admin_render() {
	global $wpdb;
	global $ent_included;
	$table_name = $wpdb->prefix . "spellcheck_words";
	$word_count = $wpdb->get_var ( "SELECT COUNT(*) FROM $table_name WHERE ignore_word='false'" );
	$options_table = $wpdb->prefix . "spellcheck_options";
	$message = '';
	$ignore = '';
	$add = '';
	$ignore = $_GET['ignore'];
	$add = $_GET['add'];
	$total_pages = sizeof(get_pages(array('number' => PHP_INT_MAX, 'hierarchical' => 0, 'post_type' => 'page', 'post_status' => array('publish', 'draft'))));
	$total_posts = sizeof(get_posts(array('posts_per_page' => PHP_INT_MAX, 'post_type' => 'post', 'post_status' => array('publish', 'draft'))));
	$total_media = sizeof(get_posts(array('posts_per_page' => PHP_INT_MAX, 'post_type' => 'attachment', 'post_status' => array('publish', 'draft'))));
	if (!$ent_included) {
		if ($total_pages > 500) $total_pages = 500;
		if ($total_posts > 500) $total_posts = 500;
		if ($total_media > 500) $total_posts = 500;
	}
	$estimated_time = intval(($total_pages + $total_posts + $total_media) / 2.4);
	$scan_message = '';
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Pages') {
		$scan_message = 'Scan has been started for page content. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckpages_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckpages');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Posts') {
		$scan_message = 'Scan has been started for post content. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckposts_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckposts');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Menus') {
		$scan_message = 'Scan has been started for menus. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckmenus_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckmenus');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Page Titles') {
		$scan_message = 'Scan has been started for page titles. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckpagetitles_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckpagetitles');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Post Titles') {
		$scan_message = 'Scan has been started for post titles. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckposttitles_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckposttitles');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Tags') {
		$scan_message = 'Scan has been started for tags. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckposttags_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckposttags');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Categories') {
		$scan_message = 'Scan has been started for categories. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckcategories_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckcategories');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'SEO Descriptions') {
		$scan_message = 'Scan has been started for SEO descriptions. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckseodesc_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckseodesc');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'SEO Titles') {
		$scan_message = 'Scan has been started for SEO titles. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckseotitles_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckseotitles');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Page Slugs') {
		$scan_message = 'Scan has been started for page slugs. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckpageslugs_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckpageslugs');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Post Slugs') {
		$scan_message = 'Scan has been started for post slugs. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckpostslugs_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckpostslugs');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Sliders') {
		$scan_message = 'Scan has been started for sliders. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'adminchecksliders_ent');
		} else {
		wp_schedule_single_event(time(), 'adminchecksliders_pro');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Media Files') {
		$scan_message = 'Scan has been started for media files. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckmedia_ent');
		} else {
		wp_schedule_single_event(time(), 'admincheckmedia_pro');
		}
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Entire Site') {
		$scan_message = 'Scan has been started for the entire site. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
		clear_results();
		wp_schedule_single_event(time(), 'adminscansite');
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Clear Results') {
		$message = 'All results have been cleared';
		clear_results();
	}
	if ($_GET['old_word'] != '' && $_GET['new_word'] != '' && $_GET['page_name'] != '' && $_GET['page_type'] != '') 
		$message = update_word_admin($_GET['old_word'], $_GET['new_word'], $_GET['page_name'], $_GET['page_type']);
	if ($ignore != '')
		$message = ignore_word($ignore); //Flag a word to be ignored by the plug-in
	if ($add != '')
		$message = add_to_dictionary($add); //Add a word to the plug-in dictionary
		
	$list_table = new sc_table();
	$list_table->prepare_items();

	$path = plugin_dir_path( __FILE__ ) . '../premium-functions.php';
	global $pro_included;

	//Get the number of words scanned by the last scan
	$pro_words = 0;
	if (!$pro_included && !$ent_included) {
		$pro_word_count = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='pro_word_count';");
		$pro_words = $pro_word_count[0]->option_value;
	}
	$total_word_count = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='total_word_count';");
	$total_words = $total_word_count[0]->option_value;
	if ($total_words > 0) { $literacy_factor = (($total_words - $word_count - $pro_words) / $total_words) * 100;
	} else { $literacy_factor = 100; }
	$literacy_factor = number_format((float)$literacy_factor, 2, '.', '');

	$scanning = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='scan_in_progress';"); 
	if ($scanning[0]->option_value == "true" && $scan_message == '') {
		$scan_message = 'A scan is currently in progress. Estimated time for completion is '.$estimated_time.' Seconds. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results.';
	} elseif ($scan_message == '') {
		$scan_message = "No scan currently running";
	}
	$time_of_scan = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='last_scan_finished';"); 
	if ($time_of_scan[0]->option_value == "0") {
		$time_of_scan = "0 Minutes";
	} else {
		$time_of_scan = $time_of_scan[0]->option_value;
	}

	$post_types = get_post_types();
	$post_type_list = array();
	foreach ($post_types as $type) {
		if ($type != 'revision' && $type != 'page' && $type != 'optionsframework' && $type != 'attachment' && $type != 'leadpages_post' && $type != 'slider')
			array_push($post_type_list, $type);
	}

	$page_count = get_pages(array('number' => PHP_INT_MAX, 'hierarchical' => 0, 'post_type' => 'page', 'post_status' => array('publish', 'draft')));
	$post_count = get_posts(array('posts_per_page' => PHP_INT_MAX, 'post_type' => $post_type_list, 'post_status' => array('publish', 'draft')));
	$media_count = get_posts(array('posts_per_page' => PHP_INT_MAX, 'post_type' => 'attachment'));
	$page_scan = $wpdb->Get_results("SELECT option_value FROM $options_table WHERE option_name='page_count';");
	$post_scan = $wpdb->Get_results("SELECT option_value FROM $options_table WHERE option_name='post_count';");
	$media_scan = $wpdb->Get_results("SELECT option_value FROM $options_table WHERE option_name='media_count';");
	foreach ($post_count as $post_debug) {
		//echo $post_debug->post_type . "<br />";
	}
	?>
		<?php show_feature_window(); ?>
		<?php check_install_notice(); ?>
		<div class="wrap">
			<h2><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/logo.png'; ?>" alt="WP Spell Check" /> <span style="position: relative; top: -15px;">Scan Results</span></h2>
			<?php $bulk_message = $_GET['bulk_message']; ?>
			<form action="<?php echo admin_url('admin.php'); ?>" method='GET'>
				<input type="hidden" name="page" value="wp-spellcheck.php">
				<input type="hidden" name="action" value="check">
				<style> p.submit { display: inline-block; margin-left: 10px; } h3.sc-message { width: 49%; display: inline-block; } .wpsc-mouseover-text-page,.wpsc-mouseover-text-post { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 20px; padding: 3px; border: 1px solid black; border-radius: 10px; opacity: 0; background: white; } </style>
				<?php echo "<h3 class='sc-message'style='color: rgb(0, 150, 255); font-size: 1.4em;'>Website Literacy Factor: " . $literacy_factor . "%"; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>The last scan found {$word_count} errors".$pro_message."</h3>"; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>" . $page_scan[0]->option_value . " pages scanned out of " . sizeof($page_count);
					if ($pro_included && sizeof($page_count) >= 500) { echo "<span class='wpsc-mouseover-button-page' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-page'>Our pro version scans up to 500 pages.<br /><a href='https://www.wpspellcheck.com/purchase-options' target='_blank'>Click here</a> to upgrade to enterprise</span></span>";
					} elseif (!$pro_included && !$ent_included && sizeof($page_count) >= 100) { echo "<span class='wpsc-mouseover-button-page' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-page'>Our free version scans up to 100 pages.<br /><a href='https://www.wpspellcheck.com/purchase-options' target='_blank'>Click here</a> to upgrade to pro</span></span>"; }
					echo "</h3>"; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>" . $post_scan[0]->option_value . " posts scanned out of " . sizeof($post_count);
				if ($pro_included && sizeof($post_count) >= 500) { echo "<span class='wpsc-mouseover-button-post' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-post'>Our pro version scans up to 500 posts.<br /><a href='https://www.wpspellcheck.com/purchase-options' target='_blank'>Click here</a> to upgrade to enterprise</span></span>";
				} elseif (!$pro_included && !$ent_included && sizeof($post_count) >= 100) { echo "<span class='wpsc-mouseover-button-post' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-post'>Our free version scans up to 100 posts.<br /><a href='https://www.wpspellcheck.com/purchase-options' target='_blank'>Click here</a> to upgrade to pro</span></span>"; }
				echo "</h3>"; ?>
				<?php if ($pro_included || $ent_included) { echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>" . $media_scan[0]->option_value . " media files scanned out of " . sizeof($media_count) . "</h3>"; } ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>$total_words words were scanned on your entire website</h3>"; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>The last scan took $time_of_scan</h3>"; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>$scan_message</h3><br />"; ?>
				<?php if (!$pro_included && !$ent_included) echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'>$pro_words errors have been found on other parts of your website. <a href='https://www.wpspellcheck.com/purchase-options' target='_blank'>Click here</a> to update to pro version to fix them.</h3><br />"; ?>
			<?php if($bulk_message != '') echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $bulk_message . "</div>"; ?>
			<?php if($message != '') echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $message . "</div>"; ?>
				<h3 style="display: inline-block;">Scan:</h3>
				<?php submit_button( 'Entire Site' ); submit_button( 'Pages' ); submit_button( 'Posts' ); if ($pro_included || $ent_included) { submit_button( 'Menus'); submit_button( 'Page Titles' ); submit_button( 'Post Titles' ); submit_button( 'Tags' ); submit_button( 'Categories' ); submit_button( 'SEO Descriptions' ); submit_button( 'SEO Titles' ); submit_button( 'Page Slugs' ); submit_button( 'Post Slugs' ); submit_button( 'Sliders' ); submit_button( 'Media Files' ); } submit_button( 'Clear Results' ); ?> <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" style="background-color: red;" value="See Scan Results"></p>
			</form>
			<form id="words-list" method="get" style="width: 75%; float: left;">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $list_table->display() ?>
			</form>
			<div style="float: right; width:23%; margin-left: 2%;">
				<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.0";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
				<a href="https://www.wpspellcheck.com/" target="_blank"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/logo.png'; ?>" alt="WP Spell Check" /></a>
<script type="text/javascript">
//<![CDATA[
if (typeof newsletter_check !== "function") {
window.newsletter_check = function (f) {
    var re = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-]{1,})+\.)+([a-zA-Z0-9]{2,})+$/;
    if (!re.test(f.elements["ne"].value)) {
        alert("The email is not correct");
        return false;
    }
    for (var i=1; i<20; i++) {
    if (f.elements["np" + i] && f.elements["np" + i].value == "") {
        alert("");
        return false;
    }
    }
    if (f.elements["ny"] && !f.elements["ny"].checked) {
        alert("You must accept the privacy statement");
        return false;
    }
    return true;
}
}
//]]>
</script>

<div class="newsletter newsletter-subscription">
<h2>Stay up to date with news and software updates</h2>
<form method="post" action="https://www.wpspellcheck.com/wp-content/plugins/newsletter/do/subscribe.php" onsubmit="return newsletter_check(this)">

<table cellspacing="0" cellpadding="3" border="0">

<!-- email -->
<tr>
	<th>Email</th>
	<td align="left"><input class="newsletter-email" type="email" name="ne" size="30" required></td>
</tr>

<tr>
	<td colspan="2" class="newsletter-td-submit">
		<input class="newsletter-submit" type="submit" value="Sign me up"/>
	</td>
</tr>

</table>
</form>
</div>
				<h2>Follow us on Facebook</h2>
				<div class="fb-like-box" data-href="https://www.facebook.com/pages/WP-Spell-Check/981317565238438" data-colorscheme="light" data-show-faces="false" data-header="true" data-stream="false" data-show-border="true"></div>
				<div class="wpsc-sidebar" style="margin-bottom: 15px;"><h2>Like the Plugin? Leave us a review</h2><center><a class="review-button" href="https://www.facebook.com/pages/WP-Spell-Check/981317565238438" target="_blank">Leave a Quick Review</a></center><small>Reviews help constantly improve the plugin &amp; keep us motivated! <strong>Thank you for your support!</strong></small></div>
				<div class="wpsc-sidebars" style="margin-bottom: 15px;"><h2>Want your entire website scanned?</h2>
					<p><a href="https://www.wpspellcheck.com/purchase-options/" target="_blank">Upgrade to WP Spell Check Pro<br />
					See Benefits and Features here »</a></p>
				</div>
			</div>
		</div>
		<!-- Quick Edit Clone Field -->
		<table style="display: none;">
			<tbody>
				<tr id="wpsc-editor-row" class="wpsc-editor">
					<td colspan="4">
						<div class="wpsc-edit-content">
							<h4>Edit Word</h4>
							<label><span>Word</span><input type="text" name="word_update" style="margin-left: 3em;" value class="wpsc-edit-field"></label>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-button" value="Cancel">
							<input type="button" class="button-primary save alignleft wpsc-edit-update-button" style="margin-left: 3em" value="Update">
							<div style="clear: both;"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<!-- Suggested Spellings Clone Field -->
		<table style="display: none;">
			<tbody>
				<tr id="wpsc-suggestion-row" class="wpsc-editor">
					<td colspan="4">
						<div class="wpsc-suggestion-content">
							<label><span>Suggested Spellings</span>
							<select class="wpsc-suggested-spelling-list">
								<option id="wpsc-suggested-spelling-1" value></option>
								<option id="wpsc-suggested-spelling-2" value></option>
								<option id="wpsc-suggested-spelling-3" value></option>
								<option id="wpsc-suggested-spelling-4" value></option>
							</select>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-suggest-button" value="Cancel">
							<input type="button" class="button-primary save alignleft wpsc-update-suggest-button" style="margin-left: 3em" value="Update">
							<div style="clear: both;"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	<?php 
	}
?>