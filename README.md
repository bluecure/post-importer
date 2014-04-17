#Post Importer

A work in progress Post Importer (not properly tested).

####Sample Usage for default post type:

```
$post_importer = new Post_Importer();

$posts = [
    [
        'post_title'    => 'Sample post',
        'post_content'  => 'This is sample posts content',
        'tags_input'    => ['New', 'Clean'],
        'post_category' => ['News', 'Blog'],
        'meta'          => [
            'sample_meta_one' => 'Sample meta 1 value',
            'sample_meta_two' => 'Sample meta 2 value'
        ],
        'images'        => [
            'http://myimage.com/image-001.jpg',
            'http://myimage.com/image-002.jpg'
        ]
    ]
];

$post_id = $post_importer->add_posts( $posts );

```
####Sample Usage for custom post type:

```
$post_importer = new Post_Importer();

$posts = [
    [
        'post_title'   => 'Sample post',
        'post_content' => 'This is sample posts content',
        'post_type'    => 'book',
        'meta'         => [
            'sample_meta_one' => 'Sample meta 1 value',
            'sample_meta_two' => 'Sample meta 2 value'
        ],
        'images'       => [
            'http://myimage.com/image-001.jpg',
            'http://myimage.com/image-002.jpg'
        ],
        'tax_input'    => [
            'genre' => [ 'Rock', 'Classic' ]
        ]
    ]
];

$post_id = $post_importer->add_posts( $posts );
```