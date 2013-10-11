# Jigsaw
Simple ways to make admin customizations for WordPress. For example, you can...

### Add a column to an admin page!

###### `Jigsaw::add_column($post_type, $column_label, $callback_function);`

```php
Jigsaw::add_column('slides', 'Preview', function($pid){
  	$data = array();
	$data['post'] = new TimberPost($pid);
	Timber::render('admin/slide-table-preview.twig', $data);
});
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
Jigsaw::add_toolbar_group('Clear Cache', array($optionOne, $optionTwo));
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

#JigsawPermalinks

### Remove a custom post type permalink

###### 'JigsawPermalinks::remove_permalink_slug($custom_post_type);

```php
JigsawPermalinks::remove_permalink_slug('event');
```

or 

```php
JigsawPermalinks::remove_permalink_slug(array('event', 'book', 'my_other_cpt'));
```
