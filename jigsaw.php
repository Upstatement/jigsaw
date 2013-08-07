<?php

	/*
	Plugin Name: Jigsaw
	Description: Simple ways to make admin customizations for WordPress
	Author: Jared Novack + Upstatement
	Version: 0.1
	Author URI: http://jigsaw.upstatement.com/
	*/
	
	class Jigsaw {

		function add_column($post_type, $key, $label, $callback){
			$filter_name = 'manage_'.$post_type.'_posts_columns';

			add_filter($filter_name , function ($columns) use ($key, $label){
				$col = array($key => $label);
				return array_merge($columns, $col);
			});

			add_action('manage_posts_custom_column', function($col, $pid) use ($key, $callback){
				if ($col == $key){
					$callback($pid);
				}
			}, 10, 2);
		}
		
	}