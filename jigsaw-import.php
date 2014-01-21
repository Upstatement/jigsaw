<?php

/*
Plugin Name: Jigsaw Importer
Plugin URI: http://jigsaw.upstatement.com
Author: Jared Novack
Version: 0.1
*/

	class JigsawPostImporter {

		var $fields = array();
		var $metas = array();
		var $queries = array();
		var $taxes = array();
		var $table;

		function __construct($posts_table){
			$this->table = $posts_table;
			$this->add_imported_col();
		}

		function wipe(){
			global $wpdb;
			$query = "UPDATE $this->table SET imported = NULL";
			$wpdb->query($query);
			$query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'oldid'";
			$pids = $wpdb->get_col($query);
			$pid_string = implode(',', $pids);
			$query = "DELETE FROM $wpdb->posts WHERE ID IN ($pid_string)";
			$wpdb->query($query);
			$query = "DELETE FROM $wpdb->postmeta WHERE post_id IN ($pid_string)";
			$wpdb->query($query);
			//$wpdb->query($query);
		}

		function add_imported_col(){
			global $wpdb;
			$query = "ALTER TABLE $this->table ADD COLUMN imported tinyint(1)";
			//$wpdb->query($query);
		}

		function reset_imports(){
			$query = "UPDATE $this->table SET imported = 0 WHERE imported = 1";
		}

		function set_posts_table($table){
			$this->posts_table = 'articles';
		}

		function set_field($field, $db_col){
			$this->fields[$field] = $db_col;
		}

		function set_meta($field, $db_col){
			$this->metas[$field] = $db_col;
		}

		function set_taxonomy($tax){
			$this->taxes[] = $tax;

		}

		function import($count = 10){
			global $wpdb;
			$query = "SELECT * FROM $this->table WHERE imported IS NULL OR imported = 0 LIMIT $count";
			$results = $wpdb->get_results($query);
			$queries = array();
			foreach($results as $row){
				$post = array('post_status' => 'publish');
				foreach($this->fields as $field => $db_col){
					$clear = preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags(html_entity_decode($row->$db_col)));
					if ($field == 'post_date'){
						$clear = strtotime($clear);
						$clear = date("Y-m-d H:i:s", $clear);
					}
					$post[$field] = $clear;
				}
				$post['post_name'] = sanitize_title($post['post_title']);
				$pid = wp_insert_post($post);
				foreach($this->metas as $meta_field => $meta_db_col){
					update_post_meta($pid, $meta_field, $row->$meta_db_col);
				}
				update_post_meta($pid, 'imported', true);
				foreach($this->taxes as $taxonomy){
					$taxonomy->import($pid, $row);
				}
				if ($pid){
					$queries[] = "UPDATE $this->table SET imported = 1 WHERE id = $row->id LIMIT 1";
				}
			}
			foreach($queries as $query){
				$wpdb->query($query);
			}
		}
	}

	class JigsawTaxonomyImporter {

		var $tax_name;
		var $metas = array();
		var $fields = array();

		var $simple_col;
		var $join_data;
		var $pivot_data;
		var $tax_data;

		function __construct($tax_name){
			$this->tax_name = $tax_name;
			if ($tax_name != 'category' && $tax_name != 'post_tag'){
				add_action('init', function() use ($tax_name){
					$this->register($tax_name);
				});
			}
		}

		function import_from_col($pid, $row){
			$col = $this->simple_col;
			if (!isset($row->$col)){
				return;
			}
			$term = trim($row->$col);
			if (!strlen($term)){
				return;
			}
			$split = ' and ';
			if (strstr($term, ',')){
				$split = ', ';
			}
			$terms = explode($split, $term);
			foreach($terms as $term){
				$term = trim($term);
				if (isset($term) && strlen($term)){
					$this->insert_term($pid, $term);
				}
			}
		}

		function insert_term($pid, $term_name){
			$tid = term_exists($term_name, $this->tax_name);
			if ($tid && isset($tid['term_id'])){
				$tid = $tid['term_id'];
			} else {
				echo 'term='.$term_name;
				$tid = wp_insert_term($term_name, $this->tax_name);
				if (is_array($tid) && isset($tid['term_id'])){
					$tid = $tid['term_id'];
				} else {
					print_r($tid);
				}
			}
			$tid = intval($tid);
			wp_set_object_terms($pid, $tid, $this->tax_name, true);
		}

		function import_from_pivot($pid, $row){
			global $wpdb;
			$pd = $this->pivot_data;
			$td = $this->tax_data;
			$id = $row->id;
			$pivot_query = "SELECT * FROM $pd->pivot_table WHERE $pd->post_col = '$id'";
			$pivots = $wpdb->get_results($pivot_query);
			$pivot_tax_column = $pd->tax_col;
			foreach($pivots as $pivot){
				$term_data_query = "SELECT $td->name_col FROM $td->tax_table WHERE $td->tax_col = '$pivot->$pivot_tax_column' LIMIT 1";
				$name = $wpdb->get_var($name);
				$this->insert_term($pid, $name);
			}
		}

		function import_from_join($pid, $row){
			global $wpdb;
			$id_col = $this->join_data->post_col;
			if (isset($row->$id_col) && strlen($row->$id_col)){
				$id = $row->$id_col;
			}
			$jd = $this->join_data;
			$term_query = "SELECT * FROM $jd->tax_table WHERE $jd->tax_col = '$id' LIMIT 1";
			$term_row = $wpdb->get_row($term_query);
			if (isset($this->fields['name'])){
				$name_col = $this->fields['name'];
			}
			if (isset($term_row->$name_col)){
				$name = $term_row->$name_col;
				if (!strlen($name)){
					//$name = 'Volume '.$term_row->issue_volume.', Issue '.$term_row->issue_number;
				}
				$this->insert_term($pid, $name);
			}
		}

		function import($pid, $row){
			if (isset($this->simple_col)){
				$this->import_from_col($pid, $row);
			}
			if (isset($this->join_data)){
				$this->import_from_join($pid, $row);
			}
			if (isset($this->pivot_data) && isset($this->tax_data)){
				$this->import_from_pivot($pid, $row);
			}
		}

		function set_col($post_col){
			$this->simple_col = $post_col;
		}

		function set_meta($key, $db_col){
			$this->metas[$key] = $db_col;
		}

		function set_field($field, $db_col){
			$this->fields[$field] = $db_col;
		}

		function set_pivot_table($pivot_table, $post_col, $tax_col){
			$this->pivot_data = new stdClass();
			$this->pivot_data->post_col = $post_col;
			$this->pivot_data->tax_col = $tax_col;
			$this->pivot_data->pivot_table = $pivot_table;
		}

		function set_tax_table($tax_table, $tax_col, $name_col){
			$this->tax_data = new stdClass();
			$this->tax_data->tax_col = $tax_col;
			$this->tax_data->tax_table = $tax_table;
			$this->tax_data->name_col = $name_col;
		}

		function set_col_table($post_col, $tax_col, $tax_table){
			$this->join_data = new stdClass();
			$this->join_data->post_col = $post_col;
			$this->join_data->tax_col = $tax_col;
			$this->join_data->tax_table = $tax_table;
		}

		function register($tax_name){
			if ($tax_name == 'post_tag' || $tax_name == 'category'){
				return;
			}
			if (taxonomy_exists($tax_name)){
				return;
			}
			$args = array('label' => ucwords($tax_name) );
			register_taxonomy($tax_name, 'post', $args);
		}
	}

	class JigsawUserTransformer extends JigsawTaxonomyImporter {

		function __construct($tax_name = 'authors'){
			parent::__construct($tax_name);
			global $wpdb;
			$this->fields['name'] = 'display_name';
			$this->set_col_table('post_author', 'ID', $wpdb->users);
		}

		function run_transform($count = 50, $post_type = 'post'){
			global $wpdb;
			$query = "SELECT * FROM nmhvd_posts WHERE post_author != 1 AND post_type = '$post_type' LIMIT $count";
			$rows = $wpdb->get_results($query);
			$pids = array();
			foreach ($rows as $row){
				$this->import_from_join($row->ID, $row);
				$pids[] = $row->ID;
			}
			print_r($pids);
			$pid_string = implode(', ', $pids);
			$finish = "UPDATE nmhvd_posts SET post_author = 1 WHERE ID IN ($pid_string)";
			echo $finish;
			$wpdb->query($finish);

		}

	}
