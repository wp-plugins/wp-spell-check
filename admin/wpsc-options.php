<?php
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
	function render_options() {
	global $wpdb;
	global $key_valid;
	global $pro_included;
	global $ent_included;
	$table_name = $wpdb->prefix . 'spellcheck_options';
	$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
	$message = '';
	if ($_POST['submit'] == 'Update' || $_POST['submit'] == 'Send Test Email') {
		//Check to see if update button was clicked and update all options
		$message = "<h3 style='color: rgb(0, 115, 0);'>Options Updated</h3>";
		if ($_POST['email'] == 'email') {
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'email'));
			$wpdb->update($table_name, array('option_value' => $_POST['email_address']), array('option_name' => 'email_address'));
		} else { 
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'email'));
		}
		if ($_POST['ignore-caps'] == 'ignore-caps')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'ignore_caps'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'ignore_caps'));
		if ($_POST['check-pages'] == 'check-pages')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_pages'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_pages'));
		if ($_POST['check-posts'] == 'check-posts')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_posts'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_posts'));
		if ($_POST['check-sliders'] == 'check-sliders')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_sliders'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_sliders'));
		if ($_POST['check-media'] == 'check-media')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_media'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_media'));;
		if ($_POST['check-menu'] == 'check-menu')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_menus'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_menus'));
		if ($_POST['page-titles'] == 'page-titles')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'page_titles'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'page_titles'));
		if ($_POST['post-titles'] == 'post-titles')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'post_titles'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'post_titles'));
		if ($_POST['tags'] == 'tags')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'tags'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'tags'));
		if ($_POST['categories'] == 'categories')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'categories'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'categories'));
		if ($_POST['seo-titles'] == 'seo-titles')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'seo_titles'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'seo_titles'));
		if ($_POST['seo-desc'] == 'seo-desc')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'seo_desc'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'seo_desc'));
		if ($_POST['page-slugs'] == 'page-slugs')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'page_slugs'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'page_slugs'));
		if ($_POST['post-slugs'] == 'post-slugs')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'post_slugs'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'post_slugs'));
		if ($_POST['ignore-emails'] == 'ignore-emails')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'ignore_emails'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'ignore_emails'));
		if ($_POST['ignore-websites'] == 'ignore-websites')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'ignore_websites'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'ignore_websites'));

		if (is_numeric($_POST['scan_frequency'])) {
			$wpdb->update($table_name, array('option_value' => $_POST['scan_frequency']), array('option_name' => 'scan_frequency'));

			//Update schedules
			$next_scan = wp_next_scheduled('adminscansite');
			wp_unschedule_event($next_scan, 'adminscansite');

			switch($_POST['scan_frequency_interval']) {
				case 'hourly':
					$scan_timer = intval($_POST['scan_frequency']) * 3600;
					break;
				case 'daily':
					$scan_timer = intval($_POST['scan_frequency']) * 86400;
					break;
				case 'weekly':
					$scan_timer = intval($_POST['scan_frequency']) * 604800;
					break;
				case 'monthly':
					$scan_timer = intval($_POST['scan_frequency']) * 2592000;
					break;
				default:
					$scan_timer = 604800;
			}

			wp_schedule_event(time() + $scan_timer, 'wpsc', 'adminscansite');
		} else {
			$message = "Please enter a valid number for scan frequency";
		}
		$wpdb->update($table_name, array('option_value' => $_POST['scan_frequency_interval']), array('option_name' => 'scan_frequency_interval'));
		$wpdb->update($table_name, array('option_value' => $_POST['language_setting']), array('option_name' => 'language_setting'));
		$wpdb->update($table_name, array('option_value' => $_POST['api_key']), array('option_name' => 'api_key'));

		//Updates the ignore list for pages and words
		$words = explode(PHP_EOL, $_POST['words-ignore']);
		$pages = explode(PHP_EOL, $_POST['pages-ignore']);

		$wpdb->query('TRUNCATE TABLE ' . $ignore_table); //Delete all existing data from the ignore table and reset the counter

		foreach($words as $word) {
			if ($word != '')
				$wpdb->insert($ignore_table, array('keyword' => $word, 'type' => 'word')); // Insert all words into the table
		}
		foreach($pages as $page) {
			if ($page != '')
				$wpdb->insert($ignore_table, array('keyword' => $page, 'type' => 'page')); // Insert all pages into the table
		}
	}
	
	//Grab existing options data to fill the page with
	$settings = $wpdb->get_results('SELECT option_name, option_value FROM ' . $table_name);
	$email = $settings[0]->option_value;
	$email_address = $settings[1]->option_value;
	$ignore_caps = $settings[3]->option_value;
	$check_pages = $settings[4]->option_value;
	$check_posts = $settings[5]->option_value;
	$check_menus = $settings[7]->option_value;
	$scan_frequency = $settings[8]->option_value;
	$scan_frequency_interval = $settings[9]->option_value;
	$email_frequency_interval = $settings[10]->option_value;
	$language_setting = $settings[11]->option_value;
	$page_titles = $settings[12]->option_value;
	$post_titles = $settings[13]->option_value;
	$tags = $settings[14]->option_value;
	$categories = $settings[15]->option_value;
	$seo_desc = $settings[16]->option_value;
	$seo_titles = $settings[17]->option_value;
	$page_slugs = $settings[18]->option_value;
	$post_slugs = $settings[19]->option_value;
	$api_key = $settings[20]->option_value;
	$ignore_emails = $settings[23]->option_value;
	$ignore_websites = $settings[24]->option_value;
	$check_sliders = $settings[30]->option_value;
	$check_media = $settings[31]->option_value;
	//Grab the ignore words data
	$word_data = $wpdb->get_results("SELECT keyword FROM " . $ignore_table . " WHERE type='word';");
	$word_list = '';
	foreach ($word_data as $word) {
		$word_list .= $word->keyword . PHP_EOL;
	}
	//Grab the ignore pages data
	$page_data = $wpdb->get_results("SELECT keyword FROM " . $ignore_table . " WHERE type='page';");
	$page_list = '';
	foreach ($page_data as $page) {
		$page_list .= $page->keyword . PHP_EOL;
	}

	if ($_POST['action'] == 'check' && $_POST['submit'] == 'Send Test Email')
		$message = send_test_email();
	?>
		<style> p.submit { display: inline-block; margin-left: 10px; } </style>
		<?php check_install_notice(); ?>
		<div class="wrap">
			<h2><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" alt="WP Spell Check" /> <span style="position: relative; top: -15px;">Options</span></h2>
			<?php if(!$key_valid && $api_key != '') echo "<div class='error' style='color: red; font-weight: bold; font-size: 14px'>API Key not valid</div>"; ?>
			<?php if ($key_valid) echo "<div class='updated' style='color: rgb(0, 115, 0); font-weight: bold; font-size: 14px'>API Key is valid</div>"; ?>
			<?php if($message != '') echo "<span class='wpsc-message'>" . $message . "</span>"; ?>
			<form action="admin.php?page=wp-spellcheck-options.php" method="post" name="options">
			<table class="form-table" style="width: 75%; float: left;" cellpadding="10"><tbody>
				<tr><td scope="row" align="left"><label>API Key</label></td><td><input type="text" name="api_key" value="<?php echo $api_key; ?>"></td></tr>
				<tr><td colspan="2" scope="row" align="left"><input type="checkbox" name="email" value="email" <?php if ($email == 'true') echo 'checked'; ?>>Send Email Reports</td></tr>
				<tr><td scope="row" align="left"><label>Email Address</label></td><td colspan="2"><input type="text" name="email_address" value="<?php echo $email_address; ?>"><input type="hidden" name="page" value="wp-spellcheck-options.php">
				<input type="hidden" name="action" value="check">
				<?php submit_button( 'Send Test Email' ); ?></td></tr>
				<tr><td scope="row" align="left"><label>Scan Frequency</label></td><td colspan="2"><input name="scan_frequency" value="<?php echo $scan_frequency; ?>"><select name="scan_frequency_interval">
<option value="hourly" <?php if ($scan_frequency_interval == 'hourly') echo "selected='selected'"; ?>>Hours</option>
<option value="daily" <?php if ($scan_frequency_interval == 'daily') echo "selected='selected'"; ?>>Days</option>
<option value="weekly" <?php if ($scan_frequency_interval == 'weekly') echo "selected='selected'"; ?>>Weeks</option>
<option value="monthly" <?php if ($scan_frequency_interval == 'monthly') echo "selected='selected'"; ?>>Months</option>
</select></td></tr>
				<tr><td scope="row" align="left"><label>Language</label></td><td colspan="2"><select name="language_setting">
<option value="en_CA" <?php if ($language_setting == 'en_CA') echo "selected='selected'"; ?>>English(Canada)</option>
<option value="en_US" <?php if ($language_setting == 'en_US') echo "selected='selected'"; ?>>English(US)</option>
<option value="en_UK" <?php if ($language_setting == 'en_UK') echo "selected='selected'"; ?>>English(UK)</option>
</select></td></tr>
				<tr><td scope="row" align="left"><label>Words to ignore (Place one on each line)</label></td><td colspan="2"><textarea name="words-ignore" rows="4" cols="50"><?php echo $word_list; ?></textarea></td></tr>
				<tr><td scope="row" align="left"><label>Pages/Posts to ignore (Please enter Page/Post titles and place one on each line)</label></td><td colspan="2"><textarea name="pages-ignore" rows="4" cols="50"><?php echo $page_list; ?></textarea></td></tr>
				<?php if ($pro_included || $ent_included) { ?>
				<tr><td scope="row" align="left"><input type="checkbox" name="check-pages" value="check-pages" <?php if ($check_pages == 'true') echo 'checked'; ?>>Check Pages</td>
				<td scope="row" align="left"><input type="checkbox" name="check-posts" value="check-posts" <?php if ($check_posts == 'true') echo 'checked'; ?>>Check Posts</td>
				<td scope="row" align="left"><input type="checkbox" name="ignore-caps" value="ignore-caps" <?php if ($ignore_caps == 'true') echo 'checked'; ?>>Ignore fully capitalized words</td></tr><tr>
				<td scope="row" align="left"><input type="checkbox" name="check-menu" value="check-menu" <?php if ($check_menus == 'true') echo 'checked'; ?>>Check Wordpress Menus</td>
				<td scope="row" align="left"><input type="checkbox" name="page-titles" value="page-titles" <?php if ($page_titles == 'true') echo 'checked'; ?>>Check Page Titles</td>
				<td scope="row" align="left"><input type="checkbox" name="ignore-emails" value="ignore-emails" <?php if ($ignore_emails == 'true') echo 'checked'; ?>>Ignore Email Addresses</td></tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="post-titles" value="post-titles" <?php if ($post_titles == 'true') echo 'checked'; ?>>Check Post Titles</td>
				<td scope="row" align="left"><input type="checkbox" name="tags" value="tags" <?php if ($tags == 'true') echo 'checked'; ?>>Check Tags</td>
				<td scope="row" align="left"><input type="checkbox" name="ignore-websites" value="ignore-websites" <?php if ($ignore_websites == 'true') echo 'checked'; ?>>Ignore Website URLs</td></tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="categories" value="categories" <?php if ($categories == 'true') echo 'checked'; ?>>Check Categories</td>
				<td  colspan="2" scope="row" align="left"><input type="checkbox" name="seo-desc" value="seo-desc" <?php if ($seo_desc == 'true') echo 'checked'; ?>>Check SEO Descriptions</td></tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="seo-titles" value="seo-titles" <?php if ($seo_titles == 'true') echo 'checked'; ?>>Check SEO Titles</td>
				<td colspan="2" scope="row" align="left"><input type="checkbox" name="page-slugs" value="page-slugs" <?php if ($page_slugs == 'true') echo 'checked'; ?>>Check Page Slugs</td></tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="post-slugs" value="post-slugs" <?php if ($post_slugs == 'true') echo 'checked'; ?>>Check Post Slugs</td>
				<td scope="row" align="left"><input type="checkbox" name="check-sliders" value="check-sliders" <?php if ($check_sliders == 'true') echo 'checked'; ?>>Check Sliders</td></tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="check-media" value="check-media" <?php if ($check_media == 'true') echo 'checked'; ?>>Check Media Files</td></tr>
				<?php } else { ?>
				<tr><td scope="row" align="left"><input type="checkbox" name="check-pages" value="check-pages" <?php if ($check_pages == 'true') echo 'checked'; ?>>Check Pages</td>
				<td colspan="2" scope="row" align="left"><input type="checkbox" name="check-posts" value="check-posts" <?php if ($check_posts == 'true') echo 'checked'; ?>>Check Posts</td></tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="ignore-caps" value="ignore-caps" <?php if ($ignore_caps == 'true') echo 'checked'; ?>>Ignore fully capitalized words</td>
				<td scope="row" align="left"><input type="checkbox" name="ignore-emails" value="ignore-emails" <?php if ($ignore_emails == 'true') echo 'checked'; ?>>Ignore Email Addresses</td>
				<td scope="row" align="left"><input type="checkbox" name="ignore-websites" value="ignore-websites" <?php if ($ignore_websites == 'true') echo 'checked'; ?>>Ignore Website URLs</td></tr>
				<?php } ?>
				<tr><td colspan="3" scope="row" align="left"><span style="font-size: 14px; font-weight: bold; color: red;">Warning: When updating <span style="color: black; text-decoration: underline;">page/post slugs</span>, some links contained within the theme may not be updated. Consult your webmaster before updating page/post slugs.<br /><a href="https://www.wpspellcheck.com/about/faqs#update-slugs" target="_blank">Click here to learn more</a></span><br /><br /><span style="font-size: 14px; font-weight: bold; color: red;">When updating <span style="color: black; text-decoration: underline;">Media filenames</span> this may cause images to stop working on your website. This does not apply to descriptions, alternate text, or captions.</span></td></tr>
				<tr colspan="2"><td><input type="submit" name="submit" value="Update" /></td></tr>
			</tbody></table>
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
	<?php
	}
?>