![Jigsaw Banner](https://cloud.githubusercontent.com/assets/1298086/5921029/e0c15824-a60e-11e4-9001-62b4e4fee1e6.jpg)

Simple ways to make admin customizations for WordPress. You know all that brain space you saved for [memorizing hooks](http://wptavern.com/learn-three-wordpress-filters-a-day)? Use it for something better. For example, you can...

### Add a column to an admin page!

###### `Jigsaw::add_column($post_type, $column_label, $callback_function, $order = 10);`

```php
Jigsaw::add_column('slides', 'Preview', function($pid){
  	$data = array();
	$data['post'] = new TimberPost($pid);
	Timber::render('admin/slide-table-preview.twig', $data);
}, 5);
```

```php
Jigsaw::add_column(array('slides', 'post'), 'Preview', function($pid){
  	$data = array();
	$data['post'] = new TimberPost($pid);
	Timber::render('admin/slide-table-preview.twig', $data);
});
```

### Add a column to the users table!

###### `Jigsaw::add_user_column($column_label, $callback_function, $order = 10);`

```php
Jigsaw::add_user_column('Favorite Band', function($uid){
  	echo get_user_meta($uid, 'favorite-band', true);
}, 5);
```


### Remove a column from the admin

###### `Jigsaw::remove_column($post_types, $column_slug);`

```php
Jigsaw::remove_column('slides', 'author');
```

```php
Jigsaw::remove_column(array('slides', 'post'), 'author');
```

### Add something to the admin bar

###### `Jigsaw::add_toolbar_item($label, $url_or_callback_function);`
```php
Jigsaw::add_toolbar_item('Clear Cache', function(){
	$total_cache->flush_all();
});
```

### Add a dropdown

###### `Jigsaw::add_toolbar_group($label, $items);`
```php
$optionOne = new stdClass();
$optionOne->label = 'All Caches';
$optionOne->action = function(){
	$total_cache->flush_all();
};
$optionTwo = new stdClass();
$optionTwo->label = 'Page Cache';
$optionTwo->action = function(){
	$total_cache->flush_page_cache();
};
$optionThree = array('Home', 'http://localhost');
Jigsaw::add_toolbar_group('Clear Cache', array($optionOne, $optionTwo, $optionThree));
```

### Show an admin notice

###### `Jigsaw::show_notice($message, $level = 'updated');`

```php
Jigsaw::show_notice('Cache has been flushed', 'updated');
```
...or
```php
Jigsaw::show_notice('Error flushing cache, is the plugin activated?', 'error');
```

### Add a CSS file to the admin

###### `Jigsaw::add_css($css_file);`

```php
Jigsaw::add_css('css/my-admin-style.css');
```

### Add a JS file to the admin

###### `Jigsaw::add_js($css_file);`

```php
Jigsaw::add_js('js/my-admin-script.js');
```

# JigsawPermalinks

### Set the base of the author permalink

###### `JigsawPermalinks::set_author_base($base_string);`

```php
JigsawPermalinks::set_author_base('writers');
```
After this you have to reset permalinks to see it working.

### Remove a custom post type permalink

###### `JigsawPermalinks::remove_permalink_slug($custom_post_type)`;

```php
JigsawPermalinks::remove_permalink_slug('event');
```

or

```php
JigsawPermalinks::remove_permalink_slug(array('event', 'book', 'my_other_cpt'));
```

### Set a custom permalink
###### `JigsawPermalinks::set_permalink($post_type, $structure);`

```php
JigsawPermalinks::set_permalink('gallery', '/galleries/%year%/%gallery%');
```
