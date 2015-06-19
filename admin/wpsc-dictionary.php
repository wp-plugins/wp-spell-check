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

	Copyright: © 2015 Persyo Inc
	Contributors: wpspellcheck
	Donate Link: www.wpspellcheck.com
	Tags: spelling, SEO, Spell Check, WordPress spell check, Spell Checker, WordPress spell checker, spelling errors, spelling mistakes, spelling report, fix spelling, WP Spell Check

	Author: Persyo Inc.
	Author URI: https://www.wpspellcheck.com
	Plugin Name: WP Spell Check®

	Privacy URI: https://www.wpspellcheck.com/privacy-policy/
	Pro Add-on / Home Page: https://www.wpspellcheck.com/
	Pro Add-on / Prices: https://www.wpspellcheck.com/purchase-options/

	Requires at least: 4.1.1
	Tested up to: 4.1.1
	Stable tag: 1.0

	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
function display_dictionary_quickedit( $column_name, $post_type ) {
		static $printNonce = TRUE;
		if ( $printNonce ) {
			$printNonce = FALSE;
			wp_nonce_field( plugin_basename( __FILE__ ), 'book_edit_nonce' );
		}

		?>

		<fieldset class="inline-edit-col-right inline-edit-book">
			<div class="inline-edit-col column-<?php echo $column_name; ?>">
				<label class="inline-edit-group">
				<span class="title">Word</span><input name="dictionary_word" />
				</label>
			</div>
		</fieldset>
	<?php
	}
	add_action( 'quick_edit_custom_box', 'display_dictionary_quickedit', 10, 2 );

	function save_dictionary_edit( $word_id, $word ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_dictionary';

		$wpdb->update( $table_name, array ('word' => $word, 'id' => $word_id));
		return 'Word Updated';
	}

class sc_dictionary_table extends WP_List_Table {

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
		//build row actions
		$actions = array (
			'Edit'					=> sprintf('<a href="#" class="wpsc-dictionary-edit-button" id="wpsc-word-' . $item['word'] . '">Edit</a>'),
			'Delete'      			=> sprintf('<a href="?page=wp-spellcheck-dictionary.php&delete=' . $item['id'] . '">Delete</a>'),
		);
		
		//return the word contents
		return sprintf('%1$s <span style="color:silver"></span>%3$s',
            $item['word'],
            $item['ID'],
            $this->row_actions($actions)
        );
	}
	
	//Create checkbox for bulk actions
	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$item['ID']
		);
	}
	
	//Get the titles of each column
	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'word' => 'Word',
		);
		return $columns;
	}
	
	//Set which columns the table can be sorted by
	function get_sortable_columns() {
		$sortable_columns = array(
			'word' => array('word',false),
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
		//$this->process_bulk_actions(); // Causes issues with page display
		
		//Grab and set up data
		$table_name = $wpdb->prefix . 'spellcheck_dictionary';
		$results = $wpdb->get_results('SELECT id, word FROM ' . $table_name, OBJECT); // Query that grabs data from database
		$data = array();
		foreach($results as $word) {
			array_push($data, array('id' => $word->id, 'word' => $word->word, 'page_name' => $word->page_name));
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

function delete_word($id) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'spellcheck_dictionary';
	
	$wpdb->delete($table_name, array('id' => $id));
	return "Word Deleted";
}

function update_word($old_word, $new_word) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'spellcheck_dictionary';

	$wpdb->update($table_name, array('word' => $new_word), array('word' => $old_word));
	return "Word has been updated";
}

function dictionary_render() {
	$message = '';
	$delete = $_GET['delete'];
	$updated_word = $_GET['new_word'];
	$old_word = $_GET['old_word'];
	if ($delete != '')
		$message = delete_word($delete); // Adds flag to ignore word when selected
	if ($updated_word != '' && $old_word != '')
		$message = update_word($old_word, $updated_word);
		
	$list_table = new sc_dictionary_table();
	$list_table->prepare_items();

	?>
		<div class="wrap">
			<h2><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" alt="WP Spell Check" /> <span style="position: relative; top: -15px;">User Dictionary</span></h2>
			<?php if($message != '') echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $message . "</span>"; ?>
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
				<a href="https://www.wpspellcheck.com/" target="_blank"><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" alt="WP Spell Check" /></a>
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
							<input type="button" class="button-primary save alignleft wpsc-update-button" style="margin-left: 3em" value="Update">
							<div style="clear: both;"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	<?php 
	}
?>