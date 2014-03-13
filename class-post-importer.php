<?php

/**
 * WordPress Post Importer
 *
 * Class to import posts with images and meta data
 *
 * @class       Post_Importer
 * @version     1.0.0
 * @package     class-post-importer
 * @category    Class
 * @author      David Featherston
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Post_Importer' ) ) {

    class Post_Importer {

        /* Variables */
        private $errors;
        private $wpdb;

        /* Construct */
        function __construct() {

            // Create error object
            $this->errors = new WP_Error();
            // Get global wpbd
            global $wpdb;
            $this->wpdb = $wpdb;

        }
        
        /**
         * Post Defaults
         *
         * Setup the post default arguments
         *
         * @access private
         * @return array
         */
        private function post_defaults() {

            return [
                'ID'                => '',
                'post_author'       => '',
                'post_title'        => '',
                'post_name'         => '',
                'post_content'      => '',
                'post_excerpt'      => '',
                'post_status'       => 'publish',
                'post_type'         => 'post',
                'post_parent'       => 0,
                'comment_status'    => 'closed',
                'ping_status'       => 'closed',
                'menu_order'        => 0,
                'page_template'     => 0,
                'tags_input'        => [],
                'post_category'     => [],
                'tax_input'         => [],
                'meta'              => [],
                'images'            => []
            ];
                
        }
        
        /**
         * Add Posts
         *
         * Add posts array to WordPress
         *
         * @access public
         * @param  array   $posts
         * @param  boolean $update
         * @return array   $post_ids
         */
        public function add_posts( $posts, $update = true ) {

            $post_ids = [];
            // Loop through and add each post
            foreach( $posts as $post ) {
                $post_ids[] = self::add_post( $post, $update );
            }
            // Return post ids array
            return $post_ids;

        }      
          
        /**
         * Add Post
         *
         * Add post to WordPress
         *
         * @access public
         * @param  array   $post
         * @param  boolean $update
         * @return int     $post_id
         */
        public function add_post( $post, $update = true ) {
        
            // Setup default options array
            $post = self::parse_args_r( $post, self::post_defaults() );
        
            // Set and remove tax input
            $tax_input = $post['tax_input'];
            unset( $post['tax_input'] );
            
            // Set and remove categories
            $categories = $post['post_category'];
            unset( $post['post_category'] );
            
            // Set and remove meta data
            $meta = $post['meta'];
            unset( $post['meta'] );
            
            // Set and remove images
            $images = $post['images'];
            unset( $post['images'] );
            
            // Get the category ids
            if ( ! empty( $categories ) ) {
                $categories = self::get_categories( $categories );
                $post['post_category'] = $categories;
            }      
                      
            // Format the tax input
            if ( ! empty( $tax_input ) ) {
                $tax_input = self::format_tax_input( $tax_input );
                $post['tax_input'] = $tax_input;
            }

            // Check if image exists
            $existing_post = get_page_by_title( $post['post_title'], 'OBJECT', $post['post_type'] );
            
            // Return existing post id, update or insert post
            if ( ! empty( $existing_post ) && $update === false ) {
                return $existing_post->ID;
            } elseif ( ! empty( $existing_post ) ) {
                $post['ID'] = $existing_post->ID;
                $post_id = wp_update_post( $post );
            } else {
                $post_id = wp_insert_post( $post );
            }

            // Add product meta data
            if ( ! empty( $meta ) )
                self::add_meta( $post_id, $meta );

            // Add product images
            if ( ! empty( $images ) )
                self::add_images( $post_id, $images );
            
            // Return post id
            return $post_id;

        }
        
         /**
         * Add Meta
         *
         * Updates the post meta data
         *
         * @access public
         * @param  int   $post_id
         * @param  array $meta
         */
        public function add_meta( $post_id, $meta = [] ) {

            // Loop through and update meta values
            foreach ( $meta as $key => $value ) {
                if ( ! empty( $value ) ) {
                    $meta_id = update_post_meta( $post_id, $key, $value );
                    // Check if meta data was added successfully
                    if ( $meta_id === false )
                        $this->errors->add( 'Post Meta', 'Error adding meta data for post: ' . $post_id );
                }
            }

        }
        
        /**
         * Add Images
         *
         * Uploads and adds images to a post
         *
         * @access public
         * @param  int    $post_id
         * @param  array  $images
         */
        public function add_images( $post_id, $images = [] ) {

            // Convert to array if string
            if ( ! is_array( $images ) )
                $images = explode( ',', $images );

            // Set array values just in case
            $images = array_values( $images );

            $image_ids = [];
            // Loop through and add ech image
            foreach( $images as $image ) {
                // Upload image and create array of ids
                $image_ids[] = self::add_image( $post_id, $image );
            }

            // Set featured image & product image gallery
            if ( ! empty( $image_ids ) ) {
                // Set featured image
                $thumbnail_id = set_post_thumbnail( $post_id, $image_ids[0] );
                // Check if featured image was added successfully
                if ( $thumbnail_id === false )
                    $this->errors->add( 'Product Image', 'Error setting featured image for post: ' . $post_id );
            }

        }

        /**
         * Add Image
         *
         * Uploads and adds an image
         *
         * @access public
         * @param  int    $post_id
         * @param  string $image
         * @return int    $image_id
         */
        public function add_image( $post_id, $image ) {

            // Check if image exists
            $existing_image = get_page_by_title( pathinfo( $image )['filename'], 'OBJECT', 'attachment' );

            // Return existing image id
            if ( ! empty( $existing_image ) )
                return $existing_image->ID;

            // Download and get file info
            $tmp = download_url( $image );

            // Format file array
            $file_array = [
                'name' => pathinfo( $image )['basename'],
                'tmp_name' => $tmp
            ];

            // If error storing temporarily, unlink
            if ( is_wp_error( $tmp ) ) {
                @unlink( $file_array['tmp_name'] );
                $file_array['tmp_name'] = '';
            }

            // Upload image
            $image_id = media_handle_sideload( $file_array, $post_id );

            // If error storing permanently, unlink
            if ( is_wp_error( $image_id ) ) {
                @unlink( $file_array['tmp_name'] ) ;
            }

            // Return image id or false
            if ( ! is_wp_error( $image_id ) ) {
                return $image_id;
            } else {
                $this->errors->add( 'Post Image', 'Error uploading image to post: ' . $post_id );
            }

        }
        
        /**
         * Get Categories
         *
         * Converts category names to ids and add new categories
         *
         * @access private
         * @param  array $categories
         * @return array $cat_ids
         */
        private function get_categories( $categories ) {

            // Convert to array if string
            if ( ! is_array( $categories ) )
                $categories = explode( ',', $categories );
            
            $cat_ids = [];

            // Loop through tax inputs
            foreach( $categories as $cat ) {
                // Get cat id
                $cat_id = get_cat_ID( $cat );
                // Check attribute taxonomy exist
                if ( empty( $cat_id ) ) {
                    $cat_id = wp_insert_category( [ 'cat_name' => $cat  ] );
                }
                // Add cat id to array
                $cat_ids[] = $cat_id;
            }
            // Return cat ids array
            return $cat_ids;

        }   
             
        /**
         * Format Tax Input
         *
         * Converts term names to ids and add new terms
         *
         * @access private
         * @param  array $tax_input
         * @return array $tax_formatted
         */
        private function format_tax_input( $tax_input ) {

            $tax_formatted = [];

            // Loop through tax inputs
            foreach( $tax_input as $tax => $terms ) {
                $tax_ids = [];
                // Convert to array if string
                if ( ! is_array( $terms ) )
                    $terms = explode( ',', $terms );

                // Loop through terms
                foreach( $terms as $term ) {
                    // Get term id
                    $term_id = term_exists( $term, $tax );
                    // Check attribute taxonomy exist
                    if ( empty( $term_id ) ) {
                        $term_id = wp_insert_term( $term, $tax );
                    }
                    $tax_ids[] = (int) $term_id['term_id'];
                }
                // Add tax to array
                $tax_formatted[$tax] = $tax_ids;
            }

            // Create completed array
            $tax_formatted[] = [
                $tax => (int) $term_id['term_id']
            ];

            // Return tax input array
            return $tax_formatted;

        }
        
        /**
         * Parse Args Recursive
         *
         * Parses the various post arrays
         *
         * @access private
         * @param  array $array
         * @param  array $defaults
         * @return array $r
         */
        private function parse_args_r( &$a, $b ) {
            $a = (array) $a;
            $b = (array) $b;
            $r = $b;

            foreach ( $a as $k => &$v ) {
                if ( is_array( $v ) && isset( $r[ $k ] ) ) {
                    $r[ $k ] = self::parse_args_r( $v, $r[ $k ] );
                } else {
                    $r[ $k ] = $v;
                }
            }

            return $r;
        }
        

    }
}
