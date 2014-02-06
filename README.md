# Jigsaw
Simple ways to make admin customizations for WordPress. For example, you can...

### Add a column to an admin page!

###### `Jigsaw::add_column($post_type, $column_label, $callback_function, $priority = 10);`

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

### Remove a column from the admin

```php
Jigsaw::remove_column('slides', 'author');
```

### Add something to the admin bar

###### `Jigsaw::add_toolbar_item($label, $url_or_callback_function);`
```
Jigsaw::add_toolbar_item('Clear Cache', function(){
	$total_cache->flush_all();
});
```

### Add a dropdown

###### `Jigsaw::add_toolbar_group($label, $items);`
```
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

#JigsawPermalinks

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
