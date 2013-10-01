<?php

	/*
	Plugin Name: Jigsaw
	Description: Simple ways to make admin customizations for WordPress
	Author: Jared Novack + Upstatement
	Version: 0.3
	Author URI: http://jigsaw.upstatement.com/
	*/

	class Jigsaw {

		public static function show_notice($text, $class = 'updated'){
			add_action( 'admin_notices', function() use ($text, $class){
				echo '<div class="'.$class.'"><p>'.$text.'</p></div>';
			});
		}

		public static function add_toolbar_group($label, $items){
			add_action('admin_bar_menu', function($wp_admin_bar) use ($label){
				$args = array(
					'id'    => sanitize_title($label),
					'title' => $label
				);
				$wp_admin_bar->add_node( $args );
			}, 9999);
			foreach($items as $item){
				self::add_toolbar_item($item->label, $item->action, sanitize_title($label));
			}

		}

		public static function add_toolbar_item($label, $url_or_callback, $parent = false){
			self::add_admin_bar_item($label, $url_or_callback, $parent);
		}

		public static function add_admin_bar_item($label, $url_or_callback, $parent = false){
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
			add_action('admin_bar_menu', function($wp_admin_bar) use ($label, $slug, $href, $parent){
				$args = array(	'id' => $slug,
			    				'title' => __($label),
			    				'href' => $href
			    			);
				if ($parent){
					$args['parent'] = $parent;
				}
				$wp_admin_bar->add_menu($args);
			}, 9999);
			add_action('init', function() use ($slug){
				if (isset($_GET['jigsaw-function'])){
					$func_name = $_GET['jigsaw-function'];
					if ($func_name != $slug){
						//only run actual function if that get is set.
						return;
					}
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