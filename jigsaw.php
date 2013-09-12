<?php

	/*
	Plugin Name: Jigsaw
	Description: Simple ways to make admin customizations for WordPress
	Author: Jared Novack + Upstatement
	Version: 0.1
	Author URI: http://jigsaw.upstatement.com/
	*/

	class Jigsaw {

		public static function show_notice($text, $class = 'updated'){
			add_action( 'admin_notices', function() use ($text, $class){
				echo '<div class="'.$class.'"><p>'.$text.'</p></div>';
			});
		}

		public static function add_admin_bar_item($label, $url_or_callback){
			$href = $url_or_callback;
			$slug = sanitize_title($label);
			if (!is_string($href)){
				//its a callback
				$href = '?jigsaw-function='.$slug;
				if (!isset($GLOBALS['jigsaw_functions'])){
					$GLOBALS['jigsaw_functions'] = array();
				}
				$GLOBALS['jigsaw_functions'][$slug] = $url_or_callback;
			} else {
				echo 'iamasring';
			}
			add_action('admin_bar_menu', function($wp_admin_bar) use ($label, $slug, $href){
				$wp_admin_bar->add_menu(
			    	array(	'id' => $slug,
			    			'title' => __($label),
			    			'href' => $href
			    		)
			    );
			}, 9999);
			add_action('init', function(){
				if (isset($_GET['jigsaw-function'])){
					$func_name = $_GET['jigsaw-function'];
					$jigsaw_functions = $GLOBALS['jigsaw_functions'];
					if (isset($jigsaw_functions[$func_name])){
					$callback = $jigsaw_functions[$func_name];
						if ($callback){
							$callback();
						}
					}
				}
			});
		}

		public static function add_column($post_type, $label, $callback, $priority = 10){
			$filter_name = 'manage_'.$post_type.'_posts_columns';
			add_filter($filter_name , function ($columns) use ($label, $priority){
				$key = sanitize_title($label);
				$col = array($key => $label);
				if ($priority < 0){
					return array_merge($col, $columns);
				}
				return array_merge($columns, $col);
			}, $priority);

			add_action('manage_'.$post_type.'_posts_custom_column', function($col, $pid) use ($label, $callback){
				$key = sanitize_title($label);
				if ($col == $key){
					$callback($pid);
				}
			}, $priority, 2);
		}

	}