<?php
/**
* Plugin Name: BC Mini Series
* Plugin URI:  https://www.binarycarpenter.com
* Description: Create mini series for WordPress
* Version: 1.5.5
* Author: BinaryCarpenter.com
* Author URI: https://www.binarycarpenter.com/
* License: GPL2
* Text Domain: bc_mini_series
*/
/*
BC Mini Series is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
BC Mini Series is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with BC Mini Series.
*/
namespace BinaryCarpenter\BC_MNS;

include 'inc/load.php';

class Initiator {
    private static $instance;

    public function __construct() {
        add_action('init', [$this, 'createCustomPostType']);
        add_action( 'add_meta_boxes', [$this, 'addCustomBoxInSeriesPostType'] );
        add_action( 'add_meta_boxes', [$this, 'addCustomBoxInNormalPostType'] );
        add_action(C::HOOK_AJAX_FETCH_POSTS , 'BinaryCarpenter\BC_MNS\A::searchPostByTitle');
        add_action(C::HOOK_AJAX_ADD_POST_TO_SERIES , 'BinaryCarpenter\BC_MNS\A::addPostToSeries');
        add_action(C::HOOK_AJAX_REMOVE_POST_FROM_SERIES , 'BinaryCarpenter\BC_MNS\A::removePostFromSeries');
        add_action(C::HOOK_AJAX_UPDATE_POSTS_OF_SERIES , 'BinaryCarpenter\BC_MNS\A::updatePostsOfSeries');
        add_action(C::HOOK_AJAX_ADD_NEW_POST_TO_SERIES , 'BinaryCarpenter\BC_MNS\A::addNewPostToSeries');
        add_action( 'widgets_init', function() {
            register_widget( 'BinaryCarpenter\BC_MNS\MiniSeriesSideBarWidget' );
        } );

        add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

        add_filter('the_content', [$this, 'addContentForSeries']);


        // /HOOK_AJAX_REMOVE_POST_FROM_SERIES

        add_action( 'admin_enqueue_scripts', [$this, 'enqueueScriptAdmin'] );
        add_action( 'wp_enqueue_scripts', [$this, 'enqueueScriptPublic'] );


        add_filter( 'manage_'.C::POST_TYPE.'_posts_columns', [$this, 'add_series_status_column_in_series_list'] );
        
        add_action( 'manage_'.C::POST_TYPE.'_posts_custom_column' , [$this, 'series_list_status_column_display'], 10, 2 );
    }


// Add the custom columns to the book post type:

    public function add_series_status_column_in_series_list($columns) {
        $columns['series_status'] = __( 'Series status', C::TEXT_DOMAIN );

        return $columns;
    }

    // Add the data to the custom columns for the book post type:
    public function series_list_status_column_display( $column, $post_id ) {
        switch ( $column ) {
            case 'series_status' :
                $mini = new Mini();
                $posts = $mini->getAllPostsObjectOfSeries($post_id, ['any']);
                
                if (count($posts) == 0) {
                    echo ('0 '.__('published', C::TEXT_DOMAIN).' / 0 <br>'.
                    '<progress id="file" value="0" max="'.count($posts).'"> 0% </progress>');
                    break;
                }
                $published = 0;
                foreach($posts as $post) {
                    if ($post->post_status == 'publish') {
                        $published++;
                    }
                }
                $percentage = number_format(($published * 100.0)/count($posts), 2);

                echo ($published . ' ' . __('published', C::TEXT_DOMAIN).' / '.count($posts) . ' ('. $percentage  .'%) <br>'.
                '<progress id="file" value="'.$published.'" max="'.count($posts).'"> '.$percentage.'% </progress>');

                


                break;

        }
    }

    public function post_updated_messages($messages) {
        global $post;
        $messages[C::POST_TYPE] = [
            0 => '',
            1  => sprintf( __( 'Series updated. <a href="%s">View series</a>', 'bc-mns' ), esc_url( get_permalink( $post->ID ) ) ),
        ];
        return $messages;
    }

    public static function get_instance() {
        if (self::$instance == null)
            self::$instance = new Initiator();

        return self::$instance;
    }

    function addContentForSeries($content) {
        $post = get_queried_object();
        
        if ($post && $post->post_type == C::POST_TYPE) {
            $mini = new Mini();

            //if the series is the root series, display the full tree. Otherwise, just display this tree
            $rootSeriesId = $mini->getRootSeriesIdOfAPost($post->ID);
            error_log("getting root series: ". $rootSeriesId. " with current series id: " . $post->ID);

            if ($rootSeriesId != $post->ID) {
                $allPostsInSeries = $mini->getAllPostsObjectOfSeries($post->ID, ['publish']);
            } else {
                $allPostsInSeries = $mini->getFullTreeOfASeriesByPostId($post->ID, ['publish']);
            }

        

            if (!$allPostsInSeries || !is_array($allPostsInSeries)) {
                return $content;
            }
            
            return $content . UI::getListOfPostsInSeriesHTML($allPostsInSeries);
        }

        return $content;
    }

    function enqueueScriptAdmin() {
        wp_enqueue_script( 'bc_mns_admin_script', plugin_dir_url( __FILE__ ) . 'static/admin.js', array('jquery', 'jquery-ui-core','jquery-ui-sortable'), '1.0' );
        wp_enqueue_style( 'bc_mns_admin_style', plugin_dir_url( __FILE__ ) . 'static/admin.css', array(), '1.0' );
    }

    function enqueueScriptPublic() {
        wp_enqueue_script( 'bc_mns_public_script', plugin_dir_url( __FILE__ ) . 'static/public.js', array('jquery', 'jquery-ui-core','jquery-ui-sortable'), '1.0' );
        wp_enqueue_style( 'bc_mns_public_style', plugin_dir_url( __FILE__ ) . 'static/public.css', array(), '1.0' );
    }


    //Register custom post type
    function createCustomPostType() {
        register_post_type(C::POST_TYPE,
            array(
                'labels'      => array(
                    'name'          => __('BC Series', 'bc-mns'),
                    'singular_name' => __('BC Series', 'bc-mns'),
                    'add_new' => __('Add new series', 'bc-mns'),
                    'add_new_item' =>  __('Add new BC series', 'bc-mns'),
                    'new_item' =>  __('New BC series', 'bc-mns'),
                    'edit_item' =>  __('Edit BC series', 'bc-mns'),
                    'view_item' =>  __('View BC series', 'bc-mns'),
                    'item_updated' =>  __('BC series updated', 'bc-mns'),
                    'all_items' =>  __('All BC series', 'bc-mns'),
                    'search_item' =>  __('Search BC series', 'bc-mns'),
                    'not_found' =>  __('No BC series found', 'bc-mns'),
                    'items_list_navigation' =>  __('BC series list navigation', 'bc-mns'),
                    'items_list' =>  __('BC series list', 'bc-mns'),


                    ),
                    'rewrite' => [
                        'slug' => 'series',
                        'with_front' => true
                    ],
                    'public'      => true,
                    'has_archive' => true,
                    'supports' => array( 'title', 'thumbnail', 'excerpt', 'page-attributes', 'editor', 'post-format', 'author', 'revisions' ),
                    'menu_position'=>5,
                    'menu_icon' => 'dashicons-palmtree',
            )
        );
    }
    


    function addCustomBoxInSeriesPostType() {
        $screens = [ C::POST_TYPE ];
        foreach ( $screens as $screen ) {
            add_meta_box(
                'bc_mini_series',                 // Unique ID
                'Manage posts in series',      // Box title
                'BinaryCarpenter\BC_MNS\UI::seriesManager',  // Content callback, must be of type callable
                $screen                            // Post type
            );
        }
    }


    function addCustomBoxInNormalPostType() {
        $screens = [ 'post' ];
        foreach ( $screens as $screen ) {
            add_meta_box(
                'bc_mini_series',                 // Unique ID
                'Add to series',      // Box title
                'BinaryCarpenter\BC_MNS\UI::postInSeriesManager',  // Content callback, must be of type callable
                $screen                            // Post type
            );
        }
    }

    function add_post_to_series_html($arg1) {
        var_dump($arg1);
        ?>
        <h1>haha</h1>
        <?php
    }


    
}


add_action('plugin_loaded', function(){
    Initiator::get_instance();
});
