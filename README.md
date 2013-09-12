# Jigsaw
Simple ways to make admin customizations for WordPress. For example, you can...

### Add a column to an admin page!

###### `Jigsaw::add_column($post_type, $key, $column_label, $callback_function);`

```php
Jigsaw::add_column('slides', 'preview_image', 'Preview', function($pid){
  	$data = array();
	$data['post'] = new TimberPost($pid);
	Timber::render('admin/slide-table-preview.twig', $data);
});
```

### Add something to the admin bar

###### `Jigsaw::add_admin_bar_item($label, $url_or_callback_function);`
```
Jigsaw::add_admin_bar_item('Clear Cache', function(){
	$total_cache->flush_call();
});
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

