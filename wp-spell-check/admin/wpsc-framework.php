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
	/* WP Spell Check classes */
		
	/* Main WP Spell Check Functions */
	
	// Check a single word for spelling
	function check_word($word) {
		if (strlen($word) <= 2) { return true; }
		if (preg_replace('/[^A-Za-z0-9]/', '', $word) == '') { return true; }
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$language_setting = $wpdb->get_results('SELECT option_value from ' . $table_name . ' WHERE option_name="language_setting";');
		$word_ignore = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="word";');
		$pspell_link = pspell_new($language_setting[0]->option_value);
		
		if (preg_match('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $word)) { return true; }
		if (is_numeric($word)) { return true; }
		if (preg_match("/^[0-9]{3}-[0-9]{4}-[0-9]{4}$/", $word)) { return true; }

		$ignore_word = $wpdb->get_results("SELECT word FROM $words_table WHERE word='$word' AND ignore_word!=0");
		if (sizeof($ignore_word) >= 1) return true;

		foreach($word_ignore as $ignore_check) {
			if ($word == $ignore_check->keyword) {
				return true;
			}
		}
		
		if (pspell_check($pspell_link, $word) && $word !== '' && $word != '&nbsp;' && $word != '&nbsp; ' && $word != ' ') {
			return true;
		} else {
			return false;
		}
	}

	function check_pages($is_running = false) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		set_time_limit(600); // Set PHP timeout limit in case of large website
		$total_words = 0;
		$page_count = 0;
		$page_list = get_pages(array('number' => 100, 'hierarchical' => 0, 'post_type' => 'page', 'post_status' => array('publish', 'draft')));
		if (!$is_running) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); // Flag that a scan is in progress
			$start_time = time();
		}
		$options_list = $wpdb->get_results("SELECT option_value FROM $options_table");

		$ignore_pages = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');

		foreach ($page_list as $page) {
			$ignore_flag = 'false';
			foreach($ignore_pages as $ignore_check) {
				if (strtoupper($page->post_title) == strtoupper($ignore_check->keyword)) {
					$ignore_flag = 'true';
				}
			}
			if ($ignore_flag == 'true') { continue; }
			$page_count++;
			$words_content = $page->post_content;
			$words_content = preg_replace("/(\[.*?\])/s",' ',$words_content);
			$words_content = preg_replace("@<style[^>]*?>.*?</style>@siu",' ',$words_content);
			$words_content = preg_replace("@<script[^>]*?>.*?</script>@siu",' ',$words_content);
			$words_content = preg_replace("/(\<.*?\>)/",' ',$words_content);
			$words_content = html_entity_decode(strip_tags($words_content), ENT_QUOTES, 'utf-8');
			if ($options_list[23]->option_value == 'true') {
				$words_content = preg_replace('/\S+\@\S+\.\S+/', '', $words_content);
			}
			if ($options_list[24]->option_value == 'true') {
				$words_content = preg_replace('/http|https|ftp\S+/', '', $words_content);
				$words_content = preg_replace('/www\.\S+/', '', $words_content);
			}
			$words_content = preg_replace("/[0-9]/", " ", $words_content);
			$words_content = preg_replace("/[^a-zA-z'’`]/", " ", $words_content);
			$words_content = preg_replace('/\s+/', ' ', $words_content);
			$words_content = preg_replace("/\\*/", ' ', $words_content);
			$words_content = str_replace("\r\n", ' ',$words_content);
			$words_content = str_replace("\\r\\n", ' ',$words_content);
			$words_content = str_replace("\xA0", ' ',$words_content);
			$words_content = str_replace("\xC2", '',$words_content);
			$words_content = str_replace("&nbsp;", ' ',$words_content);
			$words_content = str_replace("/",' ',$words_content);
			$words_content = str_replace("-",' ',$words_content);
			$words_content = str_replace("@",' ',$words_content);
			$words_content = str_replace("|",' ',$words_content);
			$words_content = str_replace("&",' ',$words_content);
			$words_content = str_replace("*",' ',$words_content);
			$words_content = str_replace("+",' ',$words_content);
			$words_content = str_replace("#",' ',$words_content);
			$words_content = str_replace("?",' ',$words_content);
			$words_content = str_replace("…",'',$words_content);
			$words_content = str_replace(";",' ',$words_content);
			$words_content = str_replace("’s",'',$words_content);
			$words_content = str_replace("'s",'',$words_content);
			$words_content = str_replace("’","'",$words_content);
			$words_content = str_replace("`","'",$words_content);
			$words_content = str_replace("s'",'s',$words_content);
			$words_content = str_replace(".",' ',$words_content);
			$words = explode(" ", $words_content);

			foreach($words as $word) {
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				if (!check_word($word) && !is_null($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$dict_check = $wpdb->get_results("SELECT word FROM " . $dict_table . " WHERE word='".$dict_word."';");
					$caps_check = $wpdb->get_results("SELECT option_name, option_value FROM " . $options_table . " WHERE option_name='ignore_caps';");

					if (sizeof($dict_check) < 1) {
						//Check if word already exists in the database for that page
						if ((strtoupper($word) != $word || $caps_check[0]->option_value == 'false') && $word != '' && !is_numeric($word)) {
							$word = addslashes($word);
							$wpdb->insert($table_name, array('word' => $word, 'page_name' => $page->post_title, 'page_type' => 'Page Content'));
						}
					}
				}
			}	
		}
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
		$wpdb->update($options_table, array('option_value' => $page_count), array('option_name' => 'page_count'));
		if (!$is_running) {
			$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'scan_in_progress')); // Flag that a scan is in progress
			$end_time = time();
			$total_time = time_elapsed($end_time - $start_time);
			$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_finished')); // Update the total time of the scan
		}
	}
	add_action ('admincheckpages', 'check_pages');

	function check_posts($is_running = false) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		set_time_limit(600); // Set PHP timeout limit in case of large website
		$total_words = 0;
		$post_count = 0;
		if (!$is_running) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); // Flag that a scan is in progress
			$start_time = time();
		}

		$ignore_posts = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');
		$options_list = $wpdb->get_results("SELECT option_value FROM $options_table");

		$post_types = get_post_types();
		$post_type_list = array();
		foreach ($post_types as $type) {
			if ($type != 'revision' && $type != 'page')
				array_push($post_type_list, $type);
		}

		$posts_list = get_posts(array('posts_per_page' => 100, 'post_type' => $post_type_list, 'post_status' => array('publish', 'draft')));

		foreach ($posts_list as $post) {
			$ignore_flag = 'false';
			foreach($ignore_posts as $ignore_check) {
				if (strtoupper($post->post_title) == strtoupper($ignore_check->keyword)) {
					$ignore_flag = 'true';
				}
			}
			if ($ignore_flag == 'true') { continue; }
			$post_count++;
			$words_list = $post->post_content;
			$words_list = preg_replace("/(\[.*?\])/s",'',$words_list);
			//$words_list = preg_replace("(\<.*?\>)",'',$words_list);
			$words_list = preg_replace("/<style>\s\S*?<\/style>/",'',$words_list);
			$words_list = html_entity_decode(strip_tags($words_list), ENT_QUOTES, 'utf-8');
			if ($options_list[23]->option_value == 'true') {
				$words_list = preg_replace('/\S+\@\S+\.\S+/', '', $words_list);
			}
			if ($options_list[24]->option_value == 'true') {
				$words_list = preg_replace('/http|https|ftp\S+/', '', $words_list);
				$words_list = preg_replace('/www\.\S+/', '', $words_list);
			}
			//$words_list = htmlspecialchars_decode($words_list);
			$words_list = preg_replace("/[0-9]/", "", $words_list);
			$words_list = preg_replace("/[^a-zA-z'’`]/", " ", $words_list);
			$words_list = preg_replace('/\s+/', ' ', $words_list);
			$words_list = str_replace("\xA0", ' ',$words_list);
			$words_list = str_replace("\xC2", '',$words_list);
			$words_list = str_replace("&nbsp;", ' ',$words_list);
			$words_list = str_replace('/',' ',$words_list);
			$words_list = str_replace("-",' ',$words_list);
			$words_list = str_replace("|",' ',$words_list);
			$words_list = str_replace("@",' ',$words_list);
			$words_list = str_replace("&",' ',$words_list);
			$words_list = str_replace("#",' ',$words_list);
			$words_list = str_replace("+",' ',$words_list);
			$words_list = str_replace("*",'',$words_list);
			$words_list = str_replace("?",' ',$words_list);
			$words_list = str_replace("…",' ',$words_list);
			$words_list = str_replace(";",' ',$words_list);
			$words_list = str_replace("’","'",$words_list);
			$words_list = str_replace("`","'",$words_list);
			$words_list = str_replace("'s",'',$words_list);
			$words_list = str_replace("’s",'',$words_list);
			$words_list = str_replace("s'",'s',$words_list);
			$words_list = str_replace(".",' ',$words_list);
			$words = explode(' ', $words_list);
		
			foreach($words as $word) {
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				if (!check_word($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$dict_check = $wpdb->get_results("SELECT word FROM " . $dict_table . " WHERE word='".$dict_word."';");
					$caps_check = $wpdb->get_results("SELECT option_name, option_value FROM " . $options_table . " WHERE option_name='ignore_caps';");

					if (sizeof($dict_check) < 1) {
						//Check if word already exists in the database for that page
						if ((strtoupper($word) != $word || $caps_check[0]->option_value == 'false') && $word != '') {
							$word = addslashes($word);
							$wpdb->insert($table_name, array('word' => addslashes($word), 'page_name' => $post->post_title, 'page_type' => 'Post Content'));
						}
					}
				}	
			}
		}
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
		$wpdb->update($options_table, array('option_value' => $post_count), array('option_name' => 'post_count'));
		if (!$is_running) {
			$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'scan_in_progress')); // Flag that a scan is in progress
			$end_time = time();
			$total_time = time_elapsed($end_time - $start_time);
			$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_finished')); // Update the total time of the scan
		}
	}
	add_action ('admincheckposts', 'check_posts');

	function clear_results() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'pro_word_count')); // Clear out the pro errors count
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'total_word_count')); // Clear out the total word count
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'page_count')); // Clear out the page count
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'post_count')); // Clear out the post count

		$wpdb->delete($table_name, array('ignore_word' => false));
	}
	
		//Main scanning function for the entire website
	function scan_site() {
		global $wpdb;
		global $pro_included;
		global $ent_included;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		clear_results();
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'pro_word_count')); // Clear out the pro errors count
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'total_word_count')); // Clear out the total word count
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); // Flag that a scan is in progress
		$start_time = time(); // Get the timestamp for start of the scan

		$settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table); //4 = Pages, 5 = Posts, 6 = Theme, 7 = Menus
		if ($ent_included) {
		if ($settings[4]->option_value == 'true')
			check_pages_ent(true);
		if ($settings[5]->option_value =='true')
			check_posts_ent(true);
		if ($settings[7]->option_value =='true')
			check_menus_ent(true);
		if ($settings[12]->option_value =='true')
			check_page_title_ent(true);
		if ($settings[13]->option_value =='true')
			check_post_title_ent(true);
		if ($settings[14]->option_value =='true')
			check_post_tags_ent(true);
		if ($settings[15]->option_value =='true')
			check_post_categories_ent(true);
		if ($settings[16]->option_value =='true')
			check_yoast_ent(true);
		if ($settings[17]->option_value =='true')
			check_seo_titles_ent(true);
		if ($settings[18]->option_value =='true')
			check_page_slugs_ent(true);
		if ($settings[19]->option_value =='true')
			check_post_slugs_ent(true);
		} else {
		if ($settings[4]->option_value == 'true')
			check_pages(true);
		if ($settings[5]->option_value =='true')
			check_posts(true);
		if ($pro_included) {
		if ($settings[7]->option_value =='true')
			check_menus(true);
		if ($settings[12]->option_value =='true')
			check_page_title(true);
		if ($settings[13]->option_value =='true')
			check_post_title(true);
		if ($settings[14]->option_value =='true')
			check_post_tags(true);
		if ($settings[15]->option_value =='true')
			check_post_categories(true);
		if ($settings[16]->option_value =='true')
			check_yoast(true);
		if ($settings[17]->option_value =='true')
			check_seo_titles(true);
		if ($settings[18]->option_value =='true')
			check_page_slugs(true);
		if ($settings[19]->option_value =='true')
			check_post_slugs(true);
		} else {
			check_menus_free();
			check_page_title_free();
			check_post_title_free();
			check_post_tags_free();
			check_post_categories_free();
			check_yoast_free();
			check_seo_titles_free();
			check_page_slugs_free();
			check_post_slugs_free();
		}
		}
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'scan_in_progress')); // Flag that a scan has finished
		$end_time = time();
		$total_time = time_elapsed($end_time - $start_time);
		$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_finished')); // Update the total time of the scan
	}

	function scan_site_event() {
		global $wpdb;
		global $pro_included;
		global $ent_included;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		clear_results();
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'pro_word_count')); // Clear out the pro errors count
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'total_word_count')); // Clear out the total word count
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); // Flag that a scan is in progress
		$start_time = time(); // Get the timestamp for start of the scan

		$settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table); //4 = Pages, 5 = Posts, 6 = Theme, 7 = Menus
		
		if ($ent_included) {
		if ($settings[4]->option_value == 'true')
			check_pages_ent(true);
		if ($settings[5]->option_value =='true')
			check_posts_ent(true);
		if ($settings[7]->option_value =='true')
			check_menus_ent(true);
		if ($settings[12]->option_value =='true')
			check_page_title_ent(true);
		if ($settings[13]->option_value =='true')
			check_post_title_ent(true);
		if ($settings[14]->option_value =='true')
			check_post_tags_ent(true);
		if ($settings[15]->option_value =='true')
			check_post_categories_ent(true);
		if ($settings[16]->option_value =='true')
			check_yoast_ent(true);
		if ($settings[17]->option_value =='true')
			check_seo_titles_ent(true);
		if ($settings[18]->option_value =='true')
			check_page_slugs_ent(true);
		if ($settings[19]->option_value =='true')
			check_post_slugs_ent(true);
		} else {
		if ($settings[4]->option_value == 'true')
			check_pages(true);
		if ($settings[5]->option_value =='true')
			check_posts(true);
		if ($pro_included) {
		if ($settings[7]->option_value =='true')
			check_menus(true);
		if ($settings[12]->option_value =='true')
			check_page_title(true);
		if ($settings[13]->option_value =='true')
			check_post_title(true);
		if ($settings[14]->option_value =='true')
			check_post_tags(true);
		if ($settings[15]->option_value =='true')
			check_post_categories(true);
		if ($settings[16]->option_value =='true')
			check_yoast(true);
		if ($settings[17]->option_value =='true')
			check_seo_titles(true);
		if ($settings[18]->option_value =='true')
			check_page_slugs(true);
		if ($settings[19]->option_value =='true')
			check_post_slugs(true);
		} else {
			check_menus_free();
			check_page_title_free();
			check_post_title_free();
			check_post_tags_free();
			check_post_categories_free();
			check_yoast_free();
			check_seo_titles_free();
			check_page_slugs_free();
			check_post_slugs_free();
		}
		}
		if ($settings[0]->option_value == 'true')
			email_admin();
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'scan_in_progress')); // Flag that a scan is in progress
		$end_time = time();
		$total_time = time_elapsed($end_time - $start_time);
		$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_finished')); // Update the total time of the scan
	}
	add_action ('adminscansite', 'scan_site_event');

	function time_elapsed($secs){
	    $bit = array(
	        ' year'        => $secs / 31556926 % 12,
	        ' week'        => $secs / 604800 % 52,
	        ' day'        => $secs / 86400 % 7,
	        ' hour'        => $secs / 3600 % 24,
	        ' minute'    => $secs / 60 % 60,
	        ' second'    => $secs % 60
	        );
        
	    foreach($bit as $k => $v){
	        if($v > 1)$ret[] = $v . $k . 's';
	        if($v == 1)$ret[] = $v . $k;
	        }
	    array_splice($ret, count($ret)-1, 0, ' ');
	    $ret[] = '';
    
	    return join(' ', $ret);
	    }

	function send_test_email() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		set_time_limit(600); // Set PHP timeout limit in case of large website

		$settings = $wpdb->get_results('SELECT option_value FROM ' . $table_name . ' WHERE option_name="email_address";');
		$words_list = $wpdb->get_results('SELECT word FROM ' . $words_table . ' WHERE ignore_word is false');
		
		$output = 'This is a test email sent from WP Spell Check on ' . get_option( 'blogname' );
		$headers  = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
		$headers .= "From: " . get_option( 'admin_email' );

		$to_emails = explode(',', $settings[0]->option_value);
		$valid_email = false;
		foreach($to_emails as $email_test) {
			if (!filter_var($email_test, FILTER_VALIDATE_EMAIL) === false) {
				$valid_email = true;
			}
		}
		if (!$valid_email) {
			return 'Please enter a valid email address';
		}
		array_walk($to_emails, 'trim_value');

		if (wp_mail($to_emails, 'Test Email from WP Spell Check', $output, $headers)) {
			return "A test email has been sent";
		} else {
			return "An error has occurring in sending the test email";
		}
	}

	function email_admin() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		set_time_limit(600); // Set PHP timeout limit in case of large website

		$settings = $wpdb->get_results('SELECT option_value FROM ' . $table_name . ' WHERE option_name="email_address";');
		$words_list = $wpdb->get_results('SELECT word FROM ' . $words_table . ' WHERE ignore_word is false');
		
		$output = 'Dear Admin, <br /><br />We have finished the scan of your website and detected ' . sizeof($words_list) . ' spelling errors. To view them you can log into your website administrator panel';
		$headers  = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
		$headers .= "From: " . get_option( 'admin_email' );

		$to_emails = explode(',', $settings[0]->option_value);
		array_walk($to_emails, 'trim_value');

		wp_mail($to_emails, 'Misspelled Words on ' . get_option( 'blogname' ), $output, $headers);
	}

	//Set up the request a feature window
	function show_feature_window() {
		echo "<div class='request-feature-container'>";
		echo "<div class='request-feature-popup' style='display: none;'>";
		echo "<a href='' class='close-popup'>X</a>";
		echo "<img src='" . plugin_dir_url( __FILE__ ) . "images/logo.png' alt='WP Spell Check' /><br />";
		echo "<h3>We love hearing from you</h3>";
		echo "<p>Please leave your idea/feature request to make the WP Spell Check plugin better</p>";
		echo "<a href='https://www.wpspellcheck.com/feature-request' target='_blank'><button>Send Feature Request</button></a>";
		echo "<p>Please note: Support requests will not be handled through this form</p>";
		echo "</div>";
		echo "<div class='request-feature'><a href='' class='request-feature-link'>Submit a Feature Request</a></div>";
		echo"</div>";
	}
	
	function check_menus_free() {
		global $wpdb;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'posts';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$word_count = 0;
		$total_words = 0;
		set_time_limit(600); // Set PHP timeout limit in case of large website

		$wpdb->delete($words_table, array('page_type' => 'Menu Item')); //Clean out menu entries before rechecking it all

		$menus = $wpdb->get_results('SELECT post_title FROM ' . $table_name . ' WHERE post_type ="nav_menu_item";');
		
		foreach($menus as $menu) {
			$word_list = html_entity_decode(strip_tags($menu->post_title), ENT_QUOTES, 'utf-8');
			$word_list = preg_replace("/[0-9]/", " ", $word_list);
			$word_list = preg_replace('/\s+/', ' ', $word_list);
			$word_list = str_replace("\xA0", ' ',$word_list);
			$word_list = str_replace("\xC2", '',$word_list);
			$word_list = str_replace("&nbsp;", ' ',$word_list);
			$word_list = str_replace("/",' ',$word_list);
			$word_list = str_replace("-",' ',$word_list);
			$word_list = str_replace("@",' ',$word_list);
			$word_list = str_replace("&",' ',$word_list);
			$word_list = str_replace("*",' ',$word_list);
			$word_list = str_replace("+",' ',$word_list);
			$word_list = str_replace("#",' ',$word_list);
			$word_list = str_replace("?",' ',$word_list);
			$word_list = str_replace("…",'',$word_list);
			$word_list = str_replace(";",' ',$word_list);
			$word_list = str_replace("'s",'',$word_list);
			$word_list = str_replace("’","'",$word_list);
			$word_list = str_replace("`","'",$word_list);
			$word_list = str_replace("s'",'s',$word_list);
			$word_list = str_replace(".",' ',$word_list);
			$words = explode(' ', $word_list);
			foreach($words as $word) {
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				if (!check_word($word)) {
					$word_check = $wpdb->get_results("SELECT word FROM " . $words_table . " WHERE word='".$word."' AND page_name='Menu: " . $menu_items->title . "'");
					$dict_word = str_replace("'", "\'", $word);
					$dict_check = $wpdb->get_results("SELECT word FROM " . $dict_table . " WHERE word='".$dict_word."';");
					$caps_check = $wpdb->get_results("SELECT option_name, option_value FROM " . $options_table . " WHERE option_name='ignore_caps';");

					if (sizeof($word_check) < 1 && sizeof($dict_check) < 1) {
						$word_count++;
					}
				}	
			}
		}	
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';");
		$word_count = $word_count + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $word_count), array('option_name' => 'pro_word_count'));
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
	}
	add_action('admincheckmenus', 'check_menus');

	function check_page_title_free() {
		global $wpdb;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$word_count = 0;
		$total_words = 0;
		set_time_limit(600); // Set PHP timeout limit in case of large website
		$page_ids = get_all_page_ids();

		$wpdb->delete($table_name, array('page_type' => 'Page Title')); //Clean out entries before rechecking it all

		for ($x=0; $x<sizeof($page_ids); $x++) {
			$words = array();
			$page = get_post( $page_ids[$x] );
			$word_list = html_entity_decode(strip_tags($page->post_title), ENT_QUOTES, 'utf-8');
			$word_list = preg_replace("/[0-9]/", " ", $word_list);
			$word_list = preg_replace('/\s+/', ' ', $word_list);
			$word_list = str_replace("\xA0", ' ',$word_list);
			$word_list = str_replace("\xC2", '',$word_list);
			$word_list = str_replace("&nbsp;", ' ',$word_list);
			$word_list = str_replace("/",' ',$word_list);
			$word_list = str_replace("-",' ',$word_list);
			$word_list = str_replace("@",' ',$word_list);
			$word_list = str_replace("&",' ',$word_list);
			$word_list = str_replace("*",' ',$word_list);
			$word_list = str_replace("+",' ',$word_list);
			$word_list = str_replace("#",' ',$word_list);
			$word_list = str_replace("?",' ',$word_list);
			$word_list = str_replace("…",'',$word_list);
			$word_list = str_replace(";",' ',$word_list);
			$word_list = str_replace("'s",'',$word_list);
			$word_list = str_replace("’","'",$word_list);
			$word_list = str_replace("`","'",$word_list);
			$word_list = str_replace("s'",'s',$word_list);
			$word_list = str_replace(".",' ',$word_list);
			$words = explode(' ', $word_list);

			foreach($words as $word) {
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				if (!check_word($word)) {
					$word_check = $wpdb->get_results("SELECT word FROM " . $table_name . " WHERE word='".$word."' AND page_name='".$page->post_title."';");
					$dict_word = str_replace("'", "\'", $word);
					$dict_check = $wpdb->get_results("SELECT word FROM " . $dict_table . " WHERE word='".$dict_word."';");
					$caps_check = $wpdb->get_results("SELECT option_name, option_value FROM " . $options_table . " WHERE option_name='ignore_caps';");

					if (sizeof($word_check) < 1 && sizeof($dict_check) < 1) {
						$word_count++;
					}
				}
			}	
		}
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';");
		$word_count = $word_count + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $word_count), array('option_name' => 'pro_word_count'));
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
	}
	add_action('admincheckpagetitles', 'check_page_title');

	function check_post_title_free() {
		global $wpdb;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$word_count = 0;
		$total_words = 0;
		set_time_limit(600); // Set PHP timeout limit in case of large website

		$wpdb->delete($table_name, array('page_type' => 'Post Title')); //Clean out entries before rechecking it all

		$posts_list = get_posts(array('posts_per_page' => 20000, 'post_status' => array('publish', 'draft')));

		foreach ($posts_list as $post) {
			$word_list = html_entity_decode(strip_tags($post->post_title), ENT_QUOTES, 'utf-8');
			$word_list = preg_replace("/[0-9]/", " ", $word_list);
			$word_list = preg_replace('/\s+/', ' ', $word_list);
			$word_list = str_replace("\xA0", ' ',$word_list);
			$word_list = str_replace("\xC2", '',$word_list);
			$word_list = str_replace("&nbsp;", ' ',$word_list);
			$word_list = str_replace("/",' ',$word_list);
			$word_list = str_replace("-",' ',$word_list);
			$word_list = str_replace("@",' ',$word_list);
			$word_list = str_replace("&",' ',$word_list);
			$word_list = str_replace("*",' ',$word_list);
			$word_list = str_replace("+",' ',$word_list);
			$word_list = str_replace("#",' ',$word_list);
			$word_list = str_replace("?",' ',$word_list);
			$word_list = str_replace("…",'',$word_list);
			$word_list = str_replace(";",' ',$word_list);
			$word_list = str_replace("'s",'',$word_list);
			$word_list = str_replace("’","'",$word_list);
			$word_list = str_replace("`","'",$word_list);
			$word_list = str_replace("s'",'s',$word_list);
			$word_list = str_replace(".",' ',$word_list);
			$words = explode(' ', $word_list);
		
			foreach($words as $word) {
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				if (!check_word($word)) {
					$word_check = $wpdb->get_results("SELECT word FROM " . $table_name . " WHERE word='".$word."' AND page_name='".$post->post_title."';");
					$dict_word = str_replace("'", "\'", $word);
					$dict_check = $wpdb->get_results("SELECT word FROM " . $dict_table . " WHERE word='".$dict_word."';");
					$caps_check = $wpdb->get_results("SELECT option_name, option_value FROM " . $options_table . " WHERE option_name='ignore_caps';");

					if (sizeof($word_check) < 1 && sizeof($dict_check) < 1) {
						$word_count++;
					}
				}	
			}
		}
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';");
		$word_count = $word_count + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $word_count), array('option_name' => 'pro_word_count'));
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
	}
	add_action('admincheckposttitles', 'check_post_title');

function check_post_tags_free() {
		global $wpdb;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$word_count = 0;
		$total_words = 0;
		set_time_limit(600); // Set PHP timeout limit in case of large website

		$wpdb->delete($table_name, array('page_type' => 'Post Tag')); //Clean out entries before rechecking it all
		$posts_list = get_posts(array('posts_per_page' => 20000, 'post_status' => array('publish', 'draft')));

		foreach ($posts_list as $post) {

			$tags = get_the_tags($post->ID);
			//print_r($tags);
			foreach ($tags as $tag) {
			$words = array();
			$words = explode(' ', strip_tags(html_entity_decode($tag->name)));
		
			foreach($words as $word) {
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				if (!check_word($word)) {
					$word_check = $wpdb->get_results("SELECT word FROM " . $table_name . " WHERE word='".$word."' AND page_name='".$post->post_title."';");
					$dict_word = str_replace("'", "\'", $word);
					$dict_check = $wpdb->get_results("SELECT word FROM " . $dict_table . " WHERE word='".$dict_word."';");
					$caps_check = $wpdb->get_results("SELECT option_name, option_value FROM " . $options_table . " WHERE option_name='ignore_caps';");

					if (sizeof($word_check) < 1 && sizeof($dict_check) < 1) {
						$word_count++;
					}
				}	
			}
			}
		}
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';");
		$word_count = $word_count + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $word_count), array('option_name' => 'pro_word_count'));
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
	}
	add_action('admincheckposttags', 'check_post_tags');

	function check_post_categories_free() {
		global $wpdb;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$word_count = 0;
		$total_words = 0;
		set_time_limit(600); // Set PHP timeout limit in case of large website

		$wpdb->delete($table_name, array('page_type' => 'Post Category')); //Clean out entries before rechecking it all
		$posts_list = get_posts(array('posts_per_page' => 20000, 'post_status' => array('publish', 'draft')));

		foreach ($posts_list as $post) {
			$cats = get_the_category($post->ID);
			foreach ($cats as $cat) {
			$words = array();
			$words = explode(' ', strip_tags(html_entity_decode($cat->name)));
		
			foreach($words as $word) {
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				if (!check_word($word)) {
					$word_check = $wpdb->get_results("SELECT word FROM " . $table_name . " WHERE word='".$word."' AND page_name='".$post->post_title."';");
					$dict_word = str_replace("'", "\'", $word);
					$dict_check = $wpdb->get_results("SELECT word FROM " . $dict_table . " WHERE word='".$dict_word."';");
					$caps_check = $wpdb->get_results("SELECT option_name, option_value FROM " . $options_table . " WHERE option_name='ignore_caps';");

					if (sizeof($word_check) < 1 && sizeof($dict_check) < 1) {
						$word_count++;
					}
				}	
			}
			}
		}
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';");
		$word_count = $word_count + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $word_count), array('option_name' => 'pro_word_count'));
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
	}
	add_action('admincheckcategories', 'check_post_categories');

	function check_yoast_free() {
		global $wpdb;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$posts_table = $wpdb->prefix . 'posts';
		$word_count = 0;
		$total_words = 0;
		set_time_limit(600); // Set PHP timeout limit in case of large website

		$wpdb->delete($words_table, array('page_type' => 'Yoast SEO Description')); //Clean out entries before rechecking it all
		$wpdb->delete($words_table, array('page_type' => 'All in One SEO Description'));
		$wpdb->delete($words_table, array('page_type' => 'Ultimate SEO Description'));
		$wpdb->delete($words_table, array('page_type' => 'SEO Description'));

		$results = $wpdb->get_results('SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE meta_key="_yoast_wpseo_metadesc" OR meta_key="_aioseop_description" OR meta_key="_su_description"');

		foreach($results as $desc) {
			$page_results = $wpdb->get_results('SELECT post_title FROM ' . $posts_table . ' WHERE ID=' . $desc->post_id);
			$desc_type = $desc->meta_key;
			$desc = html_entity_decode(strip_tags($desc->meta_value), ENT_QUOTES, 'utf-8');
			$desc = preg_replace("/[0-9]/", " ", $desc);
			$desc = preg_replace('/\s+/', ' ', $desc);
			$desc = str_replace("\xA0", ' ',$desc);
			$desc = str_replace("\xC2", '',$desc);
			$desc = str_replace("&nbsp;", ' ',$desc);
			$desc = str_replace("/",' ',$desc);
			$desc = str_replace("-",' ',$desc);
			$desc = str_replace("@",' ',$desc);
			$desc = str_replace("&",' ',$desc);
			$desc = str_replace("*",' ',$desc);
			$desc = str_replace("+",' ',$desc);
			$desc = str_replace("#",' ',$desc);
			$desc = str_replace("?",' ',$desc);
			$desc = str_replace("…",'',$desc);
			$desc = str_replace(";",' ',$desc);
			$desc = str_replace("'s",'',$desc);
			$desc = str_replace("’","'",$desc);
			$desc = str_replace("`","'",$desc);
			$desc = str_replace("s'",'s',$desc);
			$desc = str_replace(".",' ',$desc);
			$words = explode(' ', $desc);
			$words = explode(' ', $desc);

			foreach($words as $word) {
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				if (!check_word($word)) {
					$word_check = $wpdb->get_results("SELECT word FROM " . $words_table . " WHERE word='".$word."' AND page_name='".$page_results[0]->post_title."';");
					$dict_word = str_replace("'", "\'", $word);
					$dict_check = $wpdb->get_results("SELECT word FROM " . $dict_table . " WHERE word='".$dict_word."';");
					$caps_check = $wpdb->get_results("SELECT option_name, option_value FROM " . $options_table . " WHERE option_name='ignore_caps';");

					if (sizeof($word_check) < 1 && sizeof($dict_check) < 1) {
						//Check if word already exists in the database for that page
						if ((strtoupper($word) != $word || $caps_check[0]->option_value == 'false') && $word != '') {
							$word_count++;
						}
					}
				}
			}
		}
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';");
		$word_count = $word_count + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $word_count), array('option_name' => 'pro_word_count'));
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
	}
	add_action('admincheckseodesc', 'check_yoast');

	function check_seo_titles_free() {
		global $wpdb;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$posts_table = $wpdb->prefix . 'posts';
		$word_count = 0;
		$total_words = 0;
		set_time_limit(600); // Set PHP timeout limit in case of large website

		$wpdb->delete($words_table, array('page_type' => 'Yoast SEO Title')); //Clean out entries before rechecking it all
		$wpdb->delete($words_table, array('page_type' => 'All in One SEO Title'));
		$wpdb->delete($words_table, array('page_type' => 'Ultimate SEO Title'));
		$wpdb->delete($words_table, array('page_type' => 'SEO Title'));

		$results = $wpdb->get_results('SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE meta_key="_yoast_wpseo_title" OR meta_key="_aioseop_title" OR meta_key="_su_title"');

		foreach($results as $desc) {
			$page_results = $wpdb->get_results('SELECT post_title FROM ' . $posts_table . ' WHERE ID=' . $desc->post_id);
			$desc_type = $desc->meta_key;
			$desc = html_entity_decode(strip_tags($desc->meta_value), ENT_QUOTES, 'utf-8');
			$desc = preg_replace("/[0-9]/", " ", $desc);
			$desc = preg_replace('/\s+/', ' ', $desc);
			$desc = str_replace("\xA0", ' ',$desc);
			$desc = str_replace("\xC2", '',$desc);
			$desc = str_replace("&nbsp;", ' ',$desc);
			$desc = str_replace("/",' ',$desc);
			$desc = str_replace("-",' ',$desc);
			$desc = str_replace("@",' ',$desc);
			$desc = str_replace("&",' ',$desc);
			$desc = str_replace("*",' ',$desc);
			$desc = str_replace("+",' ',$desc);
			$desc = str_replace("#",' ',$desc);
			$desc = str_replace("?",' ',$desc);
			$desc = str_replace("…",'',$desc);
			$desc = str_replace(";",' ',$desc);
			$desc = str_replace("'s",'',$desc);
			$desc = str_replace("’","'",$desc);
			$desc = str_replace("`","'",$desc);
			$desc = str_replace("s'",'s',$desc);
			$desc = str_replace(".",' ',$desc);
			$words = explode(' ', $desc);

			foreach($words as $word) {
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				if (!check_word($word)) {
					$word_check = $wpdb->get_results("SELECT word FROM " . $words_table . " WHERE word='".$word."' AND page_name='".$page_results[0]->post_title."';");
					$dict_word = str_replace("'", "\'", $word);
					$dict_check = $wpdb->get_results("SELECT word FROM " . $dict_table . " WHERE word='".$dict_word."';");
					$caps_check = $wpdb->get_results("SELECT option_name, option_value FROM " . $options_table . " WHERE option_name='ignore_caps';");

					if (sizeof($word_check) < 1 && sizeof($dict_check) < 1) {
						$word_count++;
					}
				}
			}
		}
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';");
		$word_count = $word_count + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $word_count), array('option_name' => 'pro_word_count'));
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
	}
	add_action('admincheckseotitles', 'check_seo_titles');

	function check_page_slugs_free() {
		global $wpdb;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$posts_table = $wpdb->prefix . 'posts';
		$word_count = 0;
		$total_words = 0;
		set_time_limit(600); // Set PHP timeout limit in case of large website

		$wpdb->delete($words_table, array('page_type' => 'Page Slug')); //Clean out entries before rechecking it all
		$results = $wpdb->get_results('SELECT post_name, post_title FROM ' . $posts_table . ' WHERE post_type="page"');

		foreach($results as $desc) {
			$desc_title = $desc->post_title;
			$desc = html_entity_decode(strip_tags($desc->post_name), ENT_QUOTES, 'utf-8');
			$words = explode('-', $desc);

			foreach($words as $word) {
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				if (!check_word($word) && !check_word(ucfirst($word))) {
					$word_check = $wpdb->get_results("SELECT word FROM " . $words_table . " WHERE word='".$word."' AND page_name='".$page_results[0]->post_title."';");
					$dict_word = str_replace("'", "\'", $word);
					$dict_check = $wpdb->get_results("SELECT word FROM " . $dict_table . " WHERE word='".$dict_word."';");
					$caps_check = $wpdb->get_results("SELECT option_name, option_value FROM " . $options_table . " WHERE option_name='ignore_caps';");

					if (sizeof($word_check) < 1 && sizeof($dict_check) < 1) {
						$word_count++;
					}
				}
			}
		}
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';");
		$word_count = $word_count + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $word_count), array('option_name' => 'pro_word_count'));
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
	}
	add_action('admincheckpageslugs', 'check_page_slugs');

	function check_post_slugs_free() {
		global $wpdb;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$posts_table = $wpdb->prefix . 'posts';
		$word_count = 0;
		set_time_limit(600); // Set PHP timeout limit in case of large website

		$wpdb->delete($words_table, array('page_type' => 'Post Slug')); //Clean out entries before rechecking it all
		$results = $wpdb->get_results('SELECT post_name, post_title FROM ' . $posts_table . ' WHERE post_type="post"');

		foreach($results as $desc) {
			$desc_title = $desc->post_title;
			$desc = html_entity_decode(strip_tags($desc->post_name), ENT_QUOTES, 'utf-8');
			$words = explode('-', $desc);

			foreach($words as $word) {
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				if (!check_word($word) && !check_word(ucfirst($word))) {
					$word_check = $wpdb->get_results("SELECT word FROM " . $words_table . " WHERE word='".$word."' AND page_name='".$page_results[0]->post_title."';");
					$dict_word = str_replace("'", "\'", $word);
					$dict_check = $wpdb->get_results("SELECT word FROM " . $dict_table . " WHERE word='".$dict_word."';");
					$caps_check = $wpdb->get_results("SELECT option_name, option_value FROM " . $options_table . " WHERE option_name='ignore_caps';");

					if (sizeof($word_check) < 1 && sizeof($dict_check) < 1) {
						$word_count++;
					}
				}
			}
		}
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';");
		$word_count = $word_count + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $word_count), array('option_name' => 'pro_word_count'));
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
	}
	add_action('admincheckpostslugs', 'check_post_slugs');
?>