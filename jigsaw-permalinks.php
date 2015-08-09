<?php

class JigsawPermalinks {

	public static function set_author_base( $base, $with_front = true ) {
		global $wp_rewrite;
		if ( $wp_rewrite ) {
			$wp_rewrite->author_base = $base;
		}
		if ( !$with_front && $wp_rewrite ) {
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
