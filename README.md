Jigsaw
======

Simple ways to make admin customizations for WordPress. For example, you can...


### Add a column to an admin page!

```php
Jigsaw::add_column('slides', 'preview_image', 'Preview', function($pid){
  	$data = array();
		$data['post'] = new TimberPost($pid);
		Timber::render('admin/slide-table-preview.twig', $data);
});
```
