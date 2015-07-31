<?php
	/*
	Plugin Name: WP Spell Check
	Description: Checks pages and posts for spelling errors
	Version: 1.4
	Author: Persyo Inc.
	Requires at least: 4.1.1
	Tested up to: 4.2.3
	Stable tag: 1.4
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
	Copyright: © 2015 Persyo Inc
	Contributors: wpspellcheck
	Donate Link: www.wpspellcheck.com
	Tags: spelling, SEO, Spell Check, WordPress spell check, Spell Checker, WordPress spell checker, spelling errors, spelling mistakes, spelling report, fix spelling, WP Spell Check
	
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

	/* Check for pspell and load if needed */
	global $pspell_loaded;
	$pspell_loaded = true;
	if (!extension_loaded('pspell')) {
		$pspell_loaded = false;
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$manual_load = dl('php_pspell.dll');
			$pspell_loaded = $manual_load;
		}
	}

	/* Include the plugin files */
	// WordPress Files
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	//Javascript and CSS Files
	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_style( 'admin-styles', plugin_dir_url( __FILE__ ) . 'css/admin-styles.css' );
	wp_enqueue_script('admin-js', plugin_dir_url( __FILE__ ) . 'js/feature-request.js');
	wp_enqueue_script('feature-request', plugin_dir_url( __FILE__ ) . 'js/admin-js.js');
	//PHP Files
	//Check for Pro module and load if active
	if (is_plugin_active('wp-spell-check-pro/wpspellcheckpro.php')) {
		include dirname(__FILE__) . '-pro/pro-loader.php';
	}
		if (is_plugin_active('wp-spell-check-enterprise/wpspellcheckenterprise.php')) {
		include dirname(__FILE__) . '-enterprise/enterprise-loader.php';
	}
	include 'admin/wpsc-framework.php';
	include 'admin/wpsc-options.php';
	include 'admin/wpsc-dictionary.php';
	include 'admin/wpsc-ignore.php';
	include 'admin/wpsc-results.php';
	global $scdb_version;
	$scdb_version = '1.0';
	
	/* Initialization Code */
	
	function install_spellcheck() {
		global $wpdb;
		global $scdb_version;
		
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$dictionary_table = $wpdb->prefix . 'spellcheck_dictionary';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		
		$charset_collate = '';
		
		if (!empty($wpdb->charset)) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}
		
		if (!empty($wpdb->collate)) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}
		
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			word varchar(100) NOT NULL,
			page_name varchar(100) NOT NULL,
			page_type varchar(100) NOT NULL,
			ignore_word bool DEFAULT false,
			UNIQUE KEY id (id)
		) $charset_collate;"; //Create the base table that stores all of the misspelled words
		
		//Include the update function here when we have updates to roll out
		
		dbDelta($sql);

		$sql = "CREATE TABLE $dictionary_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			word VARCHAR(100) NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;"; //Create the dictionary table

		dbDelta($sql);

		$sql = "CREATE TABLE $options_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			option_name VARCHAR(100) NOT NULL,
			option_value VARCHAR(100) NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;"; //Create the options table

		dbDelta($sql);

		$sql = "CREATE TABLE $ignore_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			keyword VARCHAR(100) NOT NULL,
			type VARCHAR(100) NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;"; //Create the table used to store which pages and words to ignore

		dbDelta($sql);

		$check = $wpdb->get_results ('SELECT * FROM ' . $options_table);

		if (sizeof($check) < 1) {
			$wpdb->insert($options_table, array('option_name' => 'email', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'email_address', 'option_value' => ''));
			$wpdb->insert($options_table, array('option_name' => 'email_frequency', 'option_value' => '1'));
			$wpdb->insert($options_table, array('option_name' => 'ignore_caps', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'check_pages', 'option_value' => 'true'));
			$wpdb->insert($options_table, array('option_name' => 'check_posts', 'option_value' => 'true'));
			$wpdb->insert($options_table, array('option_name' => 'check_theme', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'check_menus', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'scan_frequency', 'option_value' => '1'));
			$wpdb->insert($options_table, array('option_name' => 'scan_frequency_interval', 'option_value' => 'daily'));
			$wpdb->insert($options_table, array('option_name' => 'email_frequency_interval', 'option_value' => 'daily'));
			$wpdb->insert($options_table, array('option_name' => 'language_setting', 'option_value' => 'en_CA'));
			$wpdb->insert($options_table, array('option_name' => 'page_titles', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'post_titles', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'tags', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'categories', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'seo_desc', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'seo_titles', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'page_slugs', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'post_slugs', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'api_key', 'option_value' => ''));
			$wpdb->insert($options_table, array('option_name' => 'pro_word_count', 'option_value' => '0'));
			$wpdb->insert($options_table, array('option_name' => 'total_word_count', 'option_value' => '0'));
			$wpdb->insert($options_table, array('option_name' => 'ignore_emails', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'ignore_websites', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'scan_in_progress', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'last_scan_started', 'option_value' => '0'));
			$wpdb->insert($options_table, array('option_name' => 'last_scan_finished', 'option_value' => '0'));
			$wpdb->insert($options_table, array('option_name' => 'page_count', 'option_value' => '0'));
			$wpdb->insert($options_table, array('option_name' => 'post_count', 'option_value' => '0'));
		}

		$check = $wpdb->get_results ('SELECT * FROM ' . $dictionary_table);

		if (sizeof($check) < 1) {
		//Add some common words to the dictionary
		$wpdb->insert($dictionary_table, array('word' => 'Facebook'));
		$wpdb->insert($dictionary_table, array('word' => 'LinkedIn'));
		$wpdb->insert($dictionary_table, array('word' => 'Twitter'));
		$wpdb->insert($dictionary_table, array('word' => 'Digg'));
		$wpdb->insert($dictionary_table, array('word' => 'http'));
		$wpdb->insert($dictionary_table, array('word' => 'SEO'));
		$wpdb->insert($dictionary_table, array('word' => 'FTP'));
		$wpdb->insert($dictionary_table, array('word' => 'That\'ll'));
		$wpdb->insert($dictionary_table, array('word' => 'That\'d'));
		$wpdb->insert($dictionary_table, array('word' => 'What\'re'));
		$wpdb->insert($dictionary_table, array('word' => 'What\'ll'));
		$wpdb->insert($dictionary_table, array('word' => 'What\'d'));
		$wpdb->insert($dictionary_table, array('word' => 'Where\'ll'));
		$wpdb->insert($dictionary_table, array('word' => 'Where\'d'));
		$wpdb->insert($dictionary_table, array('word' => 'We\'ve'));
		$wpdb->insert($dictionary_table, array('word' => 'Why\'ll'));
		$wpdb->insert($dictionary_table, array('word' => 'How\'ll'));
		$wpdb->insert($dictionary_table, array('word' => 'How\'d'));
		$wpdb->insert($dictionary_table, array('word' => 'Should\'ve'));
		$wpdb->insert($dictionary_table, array('word' => 'Could\'ve'));
		$wpdb->insert($dictionary_table, array('word' => 'Might\'ve'));
		$wpdb->insert($dictionary_table, array('word' => 'Must\'ve'));
		$wpdb->insert($dictionary_table, array('word' => 'she\'d\'ve'));
		$wpdb->insert($dictionary_table, array('word' => 'tis'));
		$wpdb->insert($dictionary_table, array('word' => 'tisn\'t'));
		$wpdb->insert($dictionary_table, array('word' => 'When\'d'));
		$wpdb->insert($dictionary_table, array('word' => 'When\'ll'));
		$wpdb->insert($dictionary_table, array('word' => 'Online'));
		$wpdb->insert($dictionary_table, array('word' => 'Internet'));
		$wpdb->insert($dictionary_table, array('word' => 'Blog'));
		$wpdb->insert($dictionary_table, array('word' => 'Blogging'));
		$wpdb->insert($dictionary_table, array('word' => 'Blogged'));
		$wpdb->insert($dictionary_table, array('word' => 'Google'));
		$wpdb->insert($dictionary_table, array('word' => 'Google+'));
		$wpdb->insert($dictionary_table, array('word' => 'Groupon'));
		$wpdb->insert($dictionary_table, array('word' => 'YouTube'));
		$wpdb->insert($dictionary_table, array('word' => 'Vimeo'));
		$wpdb->insert($dictionary_table, array('word' => 'unparalleled'));
		$wpdb->insert($dictionary_table, array('word' => 'iPhone'));
		$wpdb->insert($dictionary_table, array('word' => 'iPod'));
		$wpdb->insert($dictionary_table, array('word' => 'www'));
		$wpdb->insert($dictionary_table, array('word' => 'StumbleUpon'));
		$wpdb->insert($dictionary_table, array('word' => 'username'));
		$wpdb->insert($dictionary_table, array('word' => 'yellowpage'));
		$wpdb->insert($dictionary_table, array('word' => 'WordPress'));
		$wpdb->insert($dictionary_table, array('word' => 'Permalinks'));
		$wpdb->insert($dictionary_table, array('word' => 'Plugin'));
		$wpdb->insert($dictionary_table, array('word' => 'Firefox'));
		$wpdb->insert($dictionary_table, array('word' => 'Adwords'));
		$wpdb->insert($dictionary_table, array('word' => 'Yoast'));
		$wpdb->insert($dictionary_table, array('word' => 'Blogs'));
		$wpdb->insert($dictionary_table, array('word' => 'PHP'));
		$wpdb->insert($dictionary_table, array('word' => 'JS'));
		}
		
		add_option( 'scdb_version', $scdb_version );
	}
	
	register_activation_hook( __FILE__, 'install_spellcheck' );

	function update_db_check() {
		global $wpdb;
		$options_table = $wpdb->prefix . 'spellcheck_options';

		$check = $wpdb->get_results ('SELECT * FROM ' . $options_table);

		//Check for new option fields and create if needed
		if (sizeof($check) < 32) {
			$wpdb->insert($options_table, array('option_name' => 'check_sliders', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'check_media', 'option_value' => 'false'));
			$wpdb->insert($options_table, array('option_name' => 'media_count', 'option_value' => '0'));
		}
	}
	add_action( 'plugins_loaded', 'update_db_check' );
	

	/* Menu Functions */
	function add_menu() {	
		add_menu_page( 'WP Spell Checker', 'WP Spell Check', 'manage_options', 'wp-spellcheck.php', 'admin_render', plugin_dir_url( __FILE__ ) . 'images/logo-icon-16x16.png');
		add_submenu_page( 'wp-spellcheck.php', 'WP Scanner', 'WP Scanner', 'manage_options', 'wp-spellcheck.php', 'admin_render');
	}
	add_action('admin_menu', 'add_menu');

	function add_settings_menu() {
		add_submenu_page( 'options-general.php', 'WP Spell Check', 'WP Spell Check', 'manage_options', 'wp-spellcheck-options.php', 'render_options');
	}
	add_action ('admin_menu', 'add_settings_menu');

	function add_options_menu() {
		add_submenu_page( 'wp-spellcheck.php', 'Options', 'Options', 'manage_options', 'wp-spellcheck-options.php', 'render_options');
	}
	add_action ('admin_menu', 'add_options_menu');

	function add_dictionary_menu() {	
		add_submenu_page( 'wp-spellcheck.php', 'My Dictionary', 'My Dictionary', 'manage_options', 'wp-spellcheck-dictionary.php', 'dictionary_render');
	}
	add_action('admin_menu', 'add_dictionary_menu');

	function add_ignore_menu() {	
		add_submenu_page( 'wp-spellcheck.php', 'Ignore List', 'Ignore List', 'manage_options', 'wp-spellcheck-ignore.php', 'ignore_render');
	}
	add_action('admin_menu', 'add_ignore_menu');

	function plugin_add_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=wp-spellcheck-options.php">' . __( 'Settings' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}
	$plugin = plugin_basename( __FILE__ );
	add_filter( "plugin_action_links_$plugin", 'plugin_add_settings_link' );

	function plugin_add_premium_link( $links ) {
		$settings_link = '<a href="https://www.wpspellcheck.com/purchase-options">' . __( 'Premium Features' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}
	$plugin = plugin_basename( __FILE__ );
	add_filter( "plugin_action_links_$plugin", 'plugin_add_premium_link' );

	/* Dashboard Widget */
	function spellcheck_add_dashboard_widget() {
		wp_add_dashboard_widget(
			'wp_spellcheck_widget',			// Widget Slug
			'WP Spell Check',			//Widget Title
			'spellcheck_create_dashboard_widget'	//Display function
		);
	}
	add_action( 'wp_dashboard_setup', 'spellcheck_add_dashboard_widget' );

	function spellcheck_create_dashboard_widget() {
		global $wpdb;
		$table_name = $wpdb->prefix . "spellcheck_words";
		$word_count = $wpdb->get_var ( "SELECT COUNT(*) FROM $table_name WHERE ignore_word=false" );
		$options_table = $wpdb->prefix . "spellcheck_options";
		$pro_words = 0;
		if (!$pro_included && !$ent_included) {
			$pro_word_count = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='pro_word_count';");
			$pro_words = $pro_word_count[0]->option_value;		
		}
		$total_word_count = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='total_word_count';");
		$total_words = $total_word_count[0]->option_value;
		$word_count = $word_count + $pro_words;
		if ($total_words != 0) {
			$literacy_factor = (($total_words - $word_count) / $total_words) * 100;
			$literacy_factor = number_format((float)$literacy_factor, 2, '.', '');
		} else {
			$literacy_factor = 0;
		}
		echo "<p><span style='color: rgb(0, 115, 0); font-weight: bold;'>Website Literacy Factor: </span><span style='color: red; font-weight: bold;'>" . $literacy_factor . "%</span><br />";
		echo "The last scan found $word_count errors<br />";
		echo "<a href='/wp-admin/admin.php?page=wp-spellcheck.php'>Click here</a> To view and fix errors</p>";
	}

	/* Cron timer functions */
	function cron_add_custom( $schedules ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_options';
		$scan_frequency = $wpdb->get_results('SELECT option_value FROM ' . $table_name . ' WHERE option_name="scan_frequency";');
		$scan_frequency_interval = $wpdb->get_results('SELECT option_value FROM ' . $table_name . ' WHERE option_name="scan_frequency_interval";');

		switch($scan_frequency_interval[0]->option_value) {
			case "hourly":
				$scan_recurrence = intval($scan_frequency[0]->option_value) * 3600;
				break;
			case "daily":
				$scan_recurrence = intval($scan_frequency[0]->option_value) * 86400;
				break;
			case "weekly":
				$scan_recurrence = intval($scan_frequency[0]->option_value) * 604800;
				break;
			case "monthly":
				$scan_recurrence = intval($scan_frequency[0]->option_value) * 2592000;
				break;
			default:
				$scan_recurrence = 604800;
		}

		$schedules['wpsc'] = array(
			'interval' => $scan_recurrence,
			'display' => __( 'wpsc' )
		);
		return $schedules;
	}
	add_filter( 'cron_schedules', 'cron_add_custom' );

	function show_upgrade_message()
	{ ?>
		<div style='padding: 20px 10px; text-align: center; margin-top: 10px; width: 95%; border: 3px solid #008200;'>
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

<div class="newsletter newsletter-subscription" style="float: right;">
<h2 style="color: #008200;">Stay up to date with news and software updates</h2>
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
		
<p><strong>We notice you have been enjoying <span style='font-weight: bold; font-size: 14px;'>WP Spell Check</span> for some time. It's time to take the next step</strong></p>	
		<p style='margin-top: 10px; text-align: center;'><a href="https://www.wpspellcheck.com/purchase-options/" target="_blank"><button style='padding: 5px 15px; font-weight: bold; margin: 5px 10px; border: 1px solid #008200;'>Upgrade to Pro</button></a><a href="https://wordpress.org/plugins/wp-spell-check/" target="_blank"><button style='padding: 5px 15px; font-weight: bold; margin: 5px 10px; border: 1px solid #008200;'>Leave a Review</button></a><a href="?wpsc_ignore_notice=1"><button style='padding: 5px 15px; font-weight: bold; margin: 5px 10px; border: 1px solid #008200;'>Dismiss this message</button></a>
		<div style='clear: both;'></div></div>
	<?php 
		} 

	function check_upgrade_message() {
		global $current_user;
		global $pspell_loaded;
		$user_id = $current_user->ID;
		$notice_date = get_user_meta($user_id, 'wpsc_notice_date', true);
		$times_dismissed = get_user_meta($user_id, 'wpsc_times_dismissed', true);
		$show_notice = false;

		//Check to see if date is not set and if so, set it
		if ($notice_date == '') {
			$notice_date = date('Y-m-d');
			add_user_meta($user_id, 'wpsc_notice_date', $notice_date, true);
		}

		//Check to see if times dismissed is not set and if so, set it
		if ($times_dismissed == '') {
			add_user_meta($user_id, 'wpsc_times_dismissed', '0', true);
		}

		//Check the date to see if the message should show
		$time = strtotime($notice_date);
		$first_notice = (time()+(60*60*24*14));
		$second_notice = (time()+(60*60*24*30));

		if ($times_dismissed == '0') {
			if ($first_notice < $time) {
				$show_notice = true;
			}
		} elseif ($times_dismissed == '1' || $times_dismissed == '2') {
			if ($second_notice < $time) {
				$show_notice = true;
			}
		}

		//Check to see that the user is an administrator and hasn't already clicked to ignore the notice
		if ((current_user_can('administrator') || current_user_can('author') || current_user_can('editor') || current_user_can('contributor')) && !is_plugin_active('wp-spell-check-pro/wpspellcheckpro.php') && !is_plugin_active('wp-spell-check-enterprise/wpspellcheckenterprise.php') && $times_dismissed != '3' && $show_notice && $pspell_loaded) {
			show_upgrade_message();
		}
	}

	add_action('admin_notices', 'check_upgrade_message');

	function wpsc_ignore_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		if ( isset($_GET['wpsc_ignore_notice']) && $_GET['wpsc_ignore_notice'] == '1') {
			add_user_meta($user_id, 'wpsc_ignore_notice', 'true', true);
			update_user_meta($user_id, 'wpsc_ignore_notice', 'true');

			//Update the date
			$notice_date = date('Y-m-d');
			update_user_meta($user_id, 'wpsc_notice_date', $notice_date);

			//Update the number of times dismissed
			$times_dismissed = get_user_meta($user_id, 'wpsc_times_dismissed', true);
			if ($times_dismissed == '0') $times_dismissed = '1';
			if ($times_dismissed == '1') $times_dismissed = '2';
			if ($times_dismissed == '2') $times_dismissed = '3';
			update_user_meta($user_id, 'wpsc_times_dismissed', $times_dismissed);
		}
	}

	add_action('admin_init', 'wpsc_ignore_notice');

	function show_install_notice() { 
		$page = $_GET['page'];
		?>
		<div class="wpsc-install-notice">
<div class="wpsc-install-dismiss"><a href="?wpsc_ignore_install_notice=1&page=<?php echo $page; ?>"><button style="padding: 1px 5px; font-weight: bold; margin: 5px 0px; border: 1px solid #008200;">X</button></a></div>
			<img src="/wp-content/plugins/wp-spell-check/admin/../images/logo.png" alt="WP Spell Check">
  		<div class="wpsc-install-content">
<!--[CDATA[
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
//]]-->


<div class="newsletter newsletter-subscription">
<h2 style="color: #008200;">Stay up to date with news and software updates</h2>
<form method="post" action="https://www.wpspellcheck.com/wp-content/plugins/newsletter/do/subscribe.php" onsubmit="return newsletter_check(this)">

<table cellspacing="0" cellpadding="3" border="0">

<!-- email -->
<tbody><tr>
	<th>Email</th>
	<td align="left"><input class="newsletter-email" style="width: 85%;" type="email" name="ne" size="30" required=""></td>
</tr>

<tr>
	<td colspan="2" class="newsletter-td-submit">
		<input class="newsletter-submit" type="submit" value="Sign me up">
	</td>
</tr>

</tbody></table>
</form>
</div>
</div>
</div>
	<?php }

	function check_install_notice() {
		global $current_user;
		global $pspell_loaded;
		$user_id = $current_user->ID;
		$dismissed = get_user_meta($user_id, 'wpsc_ignore_install_notice', true);

		//Check to see that the user is an administrator and hasn't already clicked to ignore the notice
		if ((current_user_can('administrator') || current_user_can('author') || current_user_can('editor') || current_user_can('contributor')) && !is_plugin_active('wp-spell-check-pro/wpspellcheckpro.php') && $dismissed != 'true' && $pspell_loaded) {
			show_install_notice();
		}
	}

	function wpsc_ignore_install_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		if ( isset($_GET['wpsc_ignore_install_notice']) && $_GET['wpsc_ignore_install_notice'] == '1') {
			$dismissed = get_user_meta($user_id, 'wpsc_ignore_install_notice', true);
			if ($dismissed == '') {
				add_user_meta($user_id, 'wpsc_ignore_install_notice', 'true', true);
			} else {
				update_user_meta($user_id, 'wpsc_ignore_install_notice', 'true');
			}
		}
	}
	add_action('admin_init', 'wpsc_ignore_install_notice');

	function pspell_loaded_check() {
		global $pspell_loaded;
		global $current_user;
		$page = $_GET['page'];
		$user_id = $current_user->ID;
		$dismissed = get_user_meta( $user_id, 'wpsc_ignore_pspell_notice', true );
		if (!$pspell_loaded && $dismissed != 'true') {
			echo '<div class="error" style="font-weight: bold; font-size: 16px; line-height: 18px;">The pspell extension could not be loaded due to your server configuration. WP Spell Check needs this extension to run a scan. If this problem persists, please contact your hosting company. We apologize for the inconvenience <a href="?wpsc_ignore_pspell_notice=1&page=' . $page . '">Dismiss</a></div>';
		}
	}
	add_action('admin_notices', 'pspell_loaded_check');

	function wpsc_ignore_pspell_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		if ( isset($_GET['wpsc_ignore_pspell_notice']) && $_GET['wpsc_ignore_pspell_notice'] == '1') {
			$dismissed = get_user_meta($user_id, 'wpsc_ignore_pspell_notice', true);
			if ($dismissed == '') {
				add_user_meta($user_id, 'wpsc_ignore_pspell_notice', 'true', true);
			} else {
				update_user_meta($user_id, 'wpsc_ignore_pspell_notice', 'true');
			}
		}
	}
	add_action('admin_init', 'wpsc_ignore_pspell_notice');
?>