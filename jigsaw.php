<?php

/*
	Plugin Name: Jigsaw
	Description: Simple ways to make admin customizations for WordPress
	Author: Jared Novack + Upstatement
	Version: 0.8.0
	Author URI: http://jigsaw.upstatement.com/
	*/


class Jigsaw {

	public static function add_js( $file ) {
		self::add_admin_js_or_css( $file, 'wp_enqueue_script' );
	}

	public static function add_css( $file ) {
		self::add_admin_js_or_css( $file, 'wp_enqueue_style' );
	}

	static function add_admin_js_or_css( $file, $function = 'wp_enqueue_style' ) {
		if ( !is_admin() ) {
			return;
		}
		if ( !file_exists( ABSPATH.$file ) ) {
			$file = trailingslashit( get_template_directory_uri() ).$file;
		}
		add_action( 'admin_enqueue_scripts', function() use ( $file, $function ) {
				$function( sanitize_title( $file ), $file );
			} );
	}

	public static function show_notice( $text, $class = 'updated' ) {
		if ( $class == 'yellow' ) {
			$class = 'updated';
		}
		if ( $class == 'red' ) {
			$class = 'error';
		}
		add_action( 'admin_notices', function() use ( $text, $class ) {
				echo '<div class="'.$class.'"><p>'.$text.'</p></div>';
			}, 1 );
	}

	public static function add_toolbar_group( $label, $items ) {
		add_action( 'admin_bar_menu', function( $wp_admin_bar ) use ( $label ) {
				$args = array(
					'id'    => sanitize_title( $label ),
					'title' => $label
				);
				$wp_admin_bar->add_node( $args );
			}, 9999 );
		foreach ( $items as $item ) {
			if ( is_array( $item ) && count( $item == 2 ) ) {
				$array_item = $item;
				$item = new stdClass();
				$item->label = $array_item[0];
				$item->action = $array_item[1];
			}
			self::add_toolbar_item( $item->label, $item->action, sanitize_title( $label ) );
		}
	}

	public static function add_toolbar_item( $label, $url_or_callback, $parent = false ) {
		self::add_admin_bar_item( $label, $url_or_callback, $parent );
	}

	public static function add_admin_bar_item( $label, $url_or_callback, $parent = false ) {
		$href = $url_or_callback;
		$slug = sanitize_title( $label );
		if ( !is_string( $href ) ) {
			//its a callback
			$href = '?jigsaw-function='.$slug;
			if ( !isset( $GLOBALS['jigsaw_functions'] ) ) {
				$GLOBALS['jigsaw_functions'] = array();
			}
			$GLOBALS['jigsaw_functions'][$slug] = $url_or_callback;
		} else {
			//string so you should prob just do yo thang;
		}
		add_action( 'admin_bar_menu', function( $wp_admin_bar ) use ( $label, $slug, $href, $parent ) {
				$args = array( 'id' => $slug,
					'title' => __( $label ),
					'href' => $href
				);
				if ( $parent ) {
					$args['parent'] = $parent;
				}
				$wp_admin_bar->add_menu( $args );
			}, 9999 );
		add_action( 'init', function() use ( $slug ) {
				if ( isset( $_GET['jigsaw-function'] ) ) {
					$func_name = $_GET['jigsaw-function'];
					if ( $func_name != $slug ) {
						//only run actual function if that get is set.
						return;
					}
					$jigsaw_functions = $GLOBALS['jigsaw_functions'];
					if ( isset( $jigsaw_functions[$func_name] ) ) {
						$callback = $jigsaw_functions[$func_name];
						if ( $callback ) {
							$callback();
						}
					}
				}
			} );
	}

	public static function remove_column( $post_types, $columns ) {
		if ( !is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}
		if ( !is_array( $columns ) ) {
			$columns = array( $columns );
		}
		foreach ( $post_types as $post_type ) {
			add_action( 'manage_edit-'.$post_type.'_columns', function( $column_headers ) use ( $columns ) {
					foreach ( $columns as $column ) {
						unset( $column_headers[$column] );
						unset( $column_headers[strtolower( $column )] );
					}
					return $column_headers;
				} );
		}
	}

	public static function add_taxonomy_column( $tax_types, $label, $callback, $priority = 10 ) {
		if ( !is_array( $tax_types ) ) {
			$tax_types = array( $tax_types );
		}
		foreach ( $tax_types as $tax ) {
			$filter_name = 'manage_edit-'.$tax.'_columns';
			add_filter( $filter_name, function( $columns ) use ( $label, $priority ) {
					$key = sanitize_title( $label );
					$columns[$key] = __( $label );
					return $columns;
				} );

			add_action( 'manage_'.$tax.'_custom_column', function( $val, $col, $tid ) use ( $label, $callback ) {
					$key = sanitize_title( $label );
					if ( $col == $key ) {
						$callback( $tid );
					}
				}, 5, 3 );
		}
	}

	public static function add_user_column( $label, $callback, $priority = 10 ) {
		$title_filter_name = 'manage_users_columns';
		$value_filter_name = 'manage_users_custom_column';

		add_filter( $title_filter_name, function($columns) use ($label, $priority){
			return Jigsaw::column_title_filter( $columns, $label, $priority );	
		}, $priority);

		add_action( $value_filter_name, function($val, $col, $uid ) use ( $label, $callback ) {
			$key = sanitize_title( $label );
			if ( $col === $key ) {
				ob_start();
				$callback( $uid );
				return ob_get_clean();
			} else {
				return $val;
			}
		}, 10, 3);
	}

	static function column_title_filter( $columns, $label, $priority ) {
		$key = sanitize_title( $label );
		$col = array( $key => $label );
		if ( $priority < 0 ) {
			return array_merge( $col, $columns );
		} else if ( $priority > count( $columns ) ) {
			return array_merge( $columns, $col );
		} else {
			$offset = $priority;
			$sorted = array_slice( $columns, 0, $offset, true ) + $col + array_slice( $columns, $offset, NULL, true );
			return $sorted;
		}
	}

	public static function add_column( $post_types, $label, $callback, $priority = 10 ) {
		if ( !is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}
		$key = sanitize_title( $label );
		foreach ( $post_types as $post_type ) {
			$filter_name = 'manage_'.$post_type.'_posts_columns';
			$action_name = 'manage_'.$post_type.'_posts_custom_column';

			add_filter( $filter_name , function($columns) use ( $label, $priority ) {
				return Jigsaw::column_title_filter( $columns, $label, $priority );		
			}, $priority );

			add_action( $action_name, function( $col, $pid ) use ( $key, $callback ) {
					if ( $col == $key ) {
						$callback( $pid );
					}
				}, $priority, 2 );
		}

	}

	public static function sort_column( $post_types, $label, $meta_key = null, $numeric = false ) {
		if ( !is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}
		$key = sanitize_title( $label );
		if ( is_null($meta_key) ) {
			$meta_key = $key;
		}
		foreach ( $post_types as $post_type ) {	
			add_filter( 'manage_edit-'.$post_type.'_sortable_columns', function($cols) use ( $key ) {
					$cols[$key] = $key;
					return $cols;
			} );

			add_action( 'pre_get_posts', function ( $query ) use ( $key, $meta_key, $numeric ) {
			    if( ! is_admin() )
			        return;
			 
			    $orderby = $query->get( 'orderby' );
			 
			    if( $key == $orderby ) {
			        
			        $query->set('meta_key', $meta_key );

			        if ( $numeric ) {
			        	$query->set('orderby','meta_value_num');
			    	}
			    }
			});
		}
	}
	
	public static function sort_user_column( $is_meta, $label, $meta_key = null, $numeric = false ) {

		$key = sanitize_title( $label );
		if ( is_null($meta_key) ) {
			$meta_key = $key;
		}
		add_filter( 'manage_users_sortable_columns', function($cols) use ( $key ) {
			$cols[$key] = $key;
			return $cols;
		} );

		add_action( 'pre_get_users', function ( $query ) use ( $key, $meta_key, $numeric, $is_meta ) {
			if ( !is_admin() ) {
				return;
			}

		    $orderby = $query->get( 'orderby' );

		    if( $is_meta &&  $key == $orderby ) {

				$query->set('meta_key', $meta_key );

				if ( $numeric ) {
					$query->set('orderby','meta_value_num');
				}
		    } else if ($key == $orderby) {
				$query->set('orderby', $meta_key);
		    }
		});
	}

	public static function add_versioning( $gitPath, $pathFromRoot = '/' ) {
		$db = '';
		if ( is_multisite() ) {
			$db = get_site_option( 'database_version' );
		} else {
			$db = get_option( 'database_version' );
		}
		if ( !strlen( $db ) ) {
			Jigsaw::show_notice( 'Database version not found' );
		}
		add_filter( 'update_footer', function( $default ) use ( $gitPath, $pathFromRoot, $db ) {
				// SQL row with the meta_key of "database_version," located in the "sitemeta" sql table
				// Edit that row every time you export a database for handoff
				$gitPath = trailingslashit( $gitPath ) . 'commit/';
				$gitMeta = '';
				exec( 'cd ' . ABSPATH . $pathFromRoot . '; git rev-parse --verify HEAD 2> /dev/null', $output );
				$hash = substr( $output[0], 0, 6 );
				// $gitMeta = if(isset($meta)) {'by ' . $meta . ', '};

				// exec('cd ' . $_SERVER["DOCUMENT_ROOT"] . '/wp-content/themes; git log -1 --pretty=format:"%an, %ar"', $meta);
				$db = apply_filters( 'jigsaw_versioning_database', $db );
				$commit = ', <strong>Code Commit:</strong> ' . '<a href="' . $gitPath . $output[0] . '">' . $hash . '</a>, ' . $gitMeta;
				$return = '<strong>Database:</strong> ' . $db . $commit . $default;
				return $return;
			}, 11 );
	}

	public static function remove_taxonomy( $tax_slug ) {
		add_action( 'init', function() use ( $tax_slug ) {
				if ( taxonomy_exists( $tax_slug ) ) {
					global $wp_taxonomies;
					unset( $wp_taxonomies[$tax_slug] );
				}
			} );
	}

	public static function rename_taxonomy( $tax_slug, $new_name, $new_name_plural = null ) {
		add_action( 'init', function() use ( $tax_slug, $new_name, $new_name_plural ) {
				global $wp_taxonomies;
				if (isset($wp_taxonomies[$tax_slug])){
					$tax = $wp_taxonomies[$tax_slug];
					if ( $new_name_plural == null ) {
						$new_name_plural = $new_name.'s';
					}
					$tax->label = $new_name_plural;
					$tax->labels->singular_name = $new_name;
					$tax->labels->name = $tax->label;
					$tax->labels->menu_name = $tax->label;
				}
			} );
	}

	public static function add_row_action( $label, $url, $post_type = null ) {
		if ( in_array( $post_type, array( 'any', 'all' ) ) ) {
			$post_type = null;
		}
		if ( is_string( $post_type ) ) {
			$post_type = array( $post_type );
		}
		$row_action_callback = function( $actions, $post ) use ( $label, $post_type, $url ) {
			if ( is_callable( $url ) ) {
				$url = $url( $post );
			}
			if ( $post_type == null || in_array( $post->post_type, $post_type ) ) {
				$actions[sanitize_title( $label )] = '<a href="'.$url.'" title="Edit this item">'.$label.'</a>';
			}

			return $actions;
		};

		add_filter( 'post_row_actions', $row_action_callback, 10, 2 );
		add_filter( 'page_row_actions', $row_action_callback, 10, 2 );
	}
}

require_once('jigsaw-permalinks.php');

