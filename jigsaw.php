<?php

	/*
	Plugin Name: Jigsaw
	Description: Simple ways to make admin customizations for WordPress
	Author: Jared Novack + Upstatement
	Version: 0.4
	Author URI: http://jigsaw.upstatement.com/
	*/

	class JigsawPermalinks {

		public static function set_author_base($base){
			global $wp_rewrite;
			$wp_rewrite->author_base = $base;
		}

		public static function remove_permalink_slug($cpt_slugs){
			if (is_string($cpt_slugs)){
				$cpt_slugs = array($cpt_slugs);
			}
			$removed_permalink_slugs = $GLOBALS['removed_permalink_slugs'];
			if (is_array($removed_permalink_slugs)){
				$removed_permalink_slugs = array_merge($removed_permalink_slugs, $cpt_slugs);
			} else {
				$removed_permalink_slugs = $cpt_slugs;
			}
			$GLOBALS['removed_permalink_slugs'] = $removed_permalink_slugs;
			if (!has_filter('post_type_link', array('Jigsaw', 'remove_permalink_slug_post_type_link'))){
    			add_filter('post_type_link', array('Jigsaw', 'remove_permalink_slug_post_type_link'), 10, 3);
    		}
    		if (!has_action('pre_get_posts', array('Jigsaw', 'remove_permalink_slug_pre_get_posts'))){
    			add_action('pre_get_posts', array('Jigsaw', 'remove_permalink_slug_pre_get_posts'));
    		}
		}

		function remove_permalink_slug_post_type_link($post_link, $post, $leavename){
			$post_types = $GLOBALS['removed_permalink_slugs'];
			if ( ! in_array( $post->post_type, $post_types ) || 'publish' != $post->post_status ){
    			return $post_link;
    		}
    		$post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link );
    		return $post_link;
		}

		function remove_permalink_slug_pre_get_posts($query){
			$post_types = $GLOBALS['removed_permalink_slugs'];
			$post_types[] = 'page';
			$post_types[] = 'post';
			if ( ! $query->is_main_query() )
					return;

			// Only noop our very specific rewrite rule match
			if ( 2 != count( $query->query )
			|| ! isset( $query->query['page'] ) )
				return;

			// 'name' will be set if post permalinks are just post_name, otherwise the page rule will match
			if ( ! empty( $query->query['name'] ) || !empty( $query->query['pagename'] ) )
				$query->set( 'post_type', $post_types );

		}
	}

	class Jigsaw {

		public static function add_css($file){
			if (!is_admin()){
				return;
			}
			if (!file_exists(ABSPATH.$file)){
				$file = trailingslashit(get_template_directory_uri()).$file;
			}
			wp_enqueue_style(sanitize_title($file), $file);
		}

		public static function show_notice($text, $class = 'updated'){
			if ($class == 'yellow'){
				$class = 'updated';
			}
			if ($class == 'red'){
				$class = 'error';
			}
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
				if (is_array($item) && count($item == 2)){
					$array_item = $item;
					$item = new stdClass();
					$item->label = $array_item[0];
					$item->action = $array_item[1];
				}
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
				//string so you should prob just do yo thang;
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
