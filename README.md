#Post Importer

A work in progress Post Importer (not properly tested or refactored).

####Sample Usage for default post type:

```
$post_importer = new Post_Importer();

$post = [
    'post_title'    => 'Sample post',
    'post_content'  => 'This is sample posts content',
    'tags_input'    => ['New', 'Clean'],
    'post_category' => ['News', 'Blog'],
    'meta'          => [
        'sample_meta_one' => 'Sample meta 1 value',
        'sample_meta_two' => 'Sample meta 2 value'
    ],
    'images'        => [
        'http://mimosa.bsohosting.com.au/wp-content/uploads/2013/12/brook-292-05.jpg',
        'http://mimosa.bsohosting.com.au/wp-content/uploads/2013/11/investments.png'
    ]
];

$post_id = $post_importer->add_post( $post );

```
####Sample Usage for custom post type:

```
$post_importer = new Post_Importer();

$post = [
    'post_title'   => 'Sample post',
    'post_content' => 'This is sample posts content',
    'post_type'    => 'book',
    'meta'         => [
        'sample_meta_one' => 'Sample meta 1 value',
        'sample_meta_two' => 'Sample meta 2 value'
    ],
    'images'       => [
        'http://mimosa.bsohosting.com.au/wp-content/uploads/2013/12/brook-292-05.jpg',
        'http://mimosa.bsohosting.com.au/wp-content/uploads/2013/11/investments.png'
    ],
    'tax_input'    => [
        'genre' => [ 'Rock', 'Classic' ]
    ]
];

$post_id = $post_importer->add_post( $post );
```