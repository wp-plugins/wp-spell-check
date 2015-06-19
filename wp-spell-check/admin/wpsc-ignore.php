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

class sc_ignore_table extends WP_List_Table {

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
			'Unignore'      			=> sprintf('<a href="?page=wp-spellcheck-ignore.php&delete=' . $item['id'] . '">Unignore</a>'),
		);
		
		//return the word contents
		return sprintf('%1$s <span style="color:silver"></span>%3$s',
            $item['word'],
            $item['id'],
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
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$results = $wpdb->get_results('SELECT id, word FROM ' . $table_name . ' WHERE ignore_word=true;', OBJECT); // Query that grabs data from database
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

function unignore_word($id) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'spellcheck_words';
	
	$wpdb->update($table_name, array('ignore_word' =>false), array('id' => $id));
	return "Word has been removed from the ignore list";
}

/*function update_word($old_word, $new_word) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'spellcheck_words';

	$wpdb->update($table_name, array('word' => $new_word), array('word' => $old_word));
	return "Word has been updated";
}*/

function ignore_render() {
	global $wpdb;
	$table_name = $wpdb->prefix . "spellcheck_words";
	$message = '';
	$delete = $_GET['delete'];
	if ($delete != '') {
		$message = unignore_word($delete); // Adds flag to ignore word when selected
	}
	if ($_POST['submit'] == "Add to Ignore List") {
		$words = explode(PHP_EOL, $_POST['words-ignore']);
		$message = '';
		foreach ($words as $word) {
			$wpdb->insert($table_name, array('word' => $word, 'page_name' => 'WPSC_Ignore', 'ignore_word' => true, 'page_type' => 'wpsc_ignore'));
			$message .= $word . ', ';
		}
		$message .= 'have been added to the ignore list';
	}
		
	$list_table = new sc_ignore_table();
	$list_table->prepare_items();
	

	?>
		<div class="wrap">
			<h2><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" alt="WP Spell Check" /> <span style="position: relative; top: -15px;">Ignore List</span></h2>
			<?php if($message != '') echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold; float: left; width: 100%;'>" . $message . "</span>"; ?>
			<form action="admin.php?page=wp-spellcheck-ignore.php" name="add-to-ignore" id="add-to-ignore" method="POST">
				<label>Words to ignore(Place one on each line)</label><br /><textarea name="words-ignore" rows="4" cols="50"><?php echo $word_list; ?></textarea><br />
				<input type="submit" name="submit" value="Add to Ignore List" />
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
				<a href="https://www.wpspellcheck.com/"><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" alt="WP Spell Check" /></a>
				<h2>Follow us on Facebook</h2>
				<div class="fb-like-box" data-href="https://www.facebook.com/pages/WP-Spell-Check/981317565238438" data-colorscheme="light" data-show-faces="false" data-header="true" data-stream="false" data-show-border="true"></div>
				<div class="wpsc-sidebar" style="margin-bottom: 15px;"><h2>Like the Plugin? Leave us a review</h2><center><a class="review-button" href="" target="_blank">Leave a Quick Review</a></center><small>Reviews help constantly improve the plugin &amp; keep us motivated! <strong>Thank you for your support!</strong></small></div>
				<div class="wpsc-sidebars" style="margin-bottom: 15px;"><h2>Help keep the plugin up to date, awesome &amp; free!</h2><form action="" method="post" target="_top">
		<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
		<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>
		<small>Spare some change? Buy us a coffee/beer.<strong> We appreciate your continued support.</strong></small></div>
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