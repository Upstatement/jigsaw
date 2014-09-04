<?php

/*
	Plugin Name: Jigsaw
	Description: Simple ways to make admin customizations for WordPress
	Author: Jared Novack + Upstatement
	Version: 0.4.11
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

	public static function add_column( $post_types, $label, $callback, $priority = 10 ) {
		if ( !is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}
		foreach ( $post_types as $post_type ) {
			$filter_name = 'manage_'.$post_type.'_posts_columns';
			add_filter( $filter_name , function ( $columns ) use ( $label, $priority ) {
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
				}, $priority );

			add_action( 'manage_'.$post_type.'_posts_custom_column', function( $col, $pid ) use ( $label, $callback ) {
					$key = sanitize_title( $label );
					if ( $col == $key ) {
						$callback( $pid );
					}
				}, $priority, 2 );
		}
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

class JigsawPermalinks {

	public static function set_author_base( $base, $with_front = true ) {
		global $wp_rewrite;
		$wp_rewrite->author_base = $base;
		if ( !$with_front ) {
			$wp_rewrite->author_structure = '/' . $wp_rewrite->author_base. '/%author%';
		}
	}

	public static function set_permalink( $post_type, $struc ) {
		global $wp_rewrite;
		$gallery_structure = $struc;
		$wp_rewrite->add_rewrite_tag( "%".$post_type."%", '([^/]+)', $post_type."=" );
		$wp_rewrite->add_permastruct( $post_type, $gallery_structure, false );
		// add_action('init', function() use ($struc){
		//  global $wp_rewrite;
		//  $gallery_structure = $struc;
		//  $wp_rewrite->add_rewrite_tag("%foundation-post%", '([^/]+)', "foundation-post=");
		//  $wp_rewrite->add_permastruct('foundation-post', $gallery_structure, false);
		// });
		add_filter( 'post_type_link', array( 'JigsawPermalinks', '_post_type_permalink' ), 10, 3 );
	}

	static function _post_type_permalink( $permalink, $post_id, $leavename ) {
		$post = get_post( $post_id );
		$rewritecode = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			$leavename? '' : '%postname%',
			'%post_id%',
			'%category%',
			'%author%',
			$leavename? '' : '%pagename%',
		);
		if ( '' != $permalink && !in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
			$unixtime = strtotime( $post->post_date );
			$category = '';
			if ( strpos( $permalink, '%category%' ) !== false ) {
				$cats = get_the_category( $post->ID );
				if ( $cats ) {
					usort( $cats, '_usort_terms_by_ID' ); // order by ID
					$category = $cats[0]->slug;
					if ( $parent = $cats[0]->parent )
						$category = get_category_parents( $parent, false, '/', true ) . $category;
				}
				// show default category in permalinks, without
				// having to assign it explicitly
				if ( empty( $category ) ) {
					$default_category = get_category( get_option( 'default_category' ) );
					$category = is_wp_error( $default_category ) ? '' : $default_category->slug;
				}
			}

			$author = '';
			if ( strpos( $permalink, '%author%' ) !== false ) {
				$authordata = get_userdata( $post->post_author );
				$author = $authordata->user_nicename;
			}

			$date = explode( " ", date( 'Y m d H i s', $unixtime ) );
			$rewritereplace =
				array(
				$date[0],
				$date[1],
				$date[2],
				$date[3],
				$date[4],
				$date[5],
				$post->post_name,
				$post->ID,
				$category,
				$author,
				$post->post_name,
			);
			$permalink = str_replace( $rewritecode, $rewritereplace, $permalink );
		} else { // if they're not using the fancy permalink option
		}
		return $permalink;
	}

	public static function set_search_permalink( $base = 'search' ) {
		add_action( 'template_redirect', function() use ( $base ) {
				if ( is_search() && ! empty( $_GET['s'] ) ) {
					wp_redirect( ( "/".$base."/" ) . urlencode( get_query_var( 's' ) ) );
					exit();
				}
			} );
	}

	public static function add_cpt_to_authors( $cpt_slugs ) {
		add_action( 'pre_get_posts', function( &$query ) use ( $cpt_slugs ) {
				if ( $query->is_author ) {
					$query->set( 'post_type', $cpt_slugs );
				}
			} );
	}

	public static function remove_permalink_slug( $cpt_slugs ) {
		if ( is_string( $cpt_slugs ) ) {
			$cpt_slugs = array( $cpt_slugs );
		}
		$removed_permalink_slugs = array();
		if ( isset( $GLOBALS['removed_permalink_slugs'] ) ) {
			$removed_permalink_slugs = $GLOBALS['removed_permalink_slugs'];
		}
		if ( is_array( $removed_permalink_slugs ) ) {
			$removed_permalink_slugs = array_merge( $removed_permalink_slugs, $cpt_slugs );
		} else {
			$removed_permalink_slugs = $cpt_slugs;
		}
		$GLOBALS['removed_permalink_slugs'] = $removed_permalink_slugs;
		if ( !has_filter( 'post_type_link', array( 'JigsawPermalinks', 'remove_permalink_slug_post_type_link' ) ) ) {
			add_filter( 'post_type_link', array( 'JigsawPermalinks', 'remove_permalink_slug_post_type_link' ), 10, 3 );
		}
		if ( !has_action( 'pre_get_posts', array( 'JigsawPermalinks', 'remove_permalink_slug_pre_get_posts' ) ) ) {
			add_action( 'pre_get_posts', array( 'JigsawPermalinks', 'remove_permalink_slug_pre_get_posts' ) );
		}
	}

	static function remove_permalink_slug_post_type_link( $post_link, $post, $leavename ) {
		$post_types = $GLOBALS['removed_permalink_slugs'];
		if ( ! in_array( $post->post_type, $post_types ) || 'publish' != $post->post_status ) {
			return $post_link;
		}
		$post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link );
		return $post_link;
	}

	static function remove_permalink_slug_pre_get_posts( $query ) {
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
