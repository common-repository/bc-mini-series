<?php

namespace BinaryCarpenter\BC_MNS;

include_once 'load.php';


class A {

    public static function searchPostByTitle() {

        
        $keyword = sanitize_text_field($_POST['keyword']);

        error_log('search keywords is ' . $keyword);

        $postQuery = new \WP_Query( array( 
            'posts_per_page' => 5, 
            's' => esc_attr(  $keyword ), 
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key'     => C::SERIES_OF_POST_META,
                    'compare' => 'NOT EXISTS',
                    'value' => ''
                ),
            ),
            'post_type' => ['post', C::POST_TYPE] ) );



        wp_send_json_success($postQuery->posts);
        die();
    }


    public static function addPostToSeries() {
        $mini = new Mini();

        $postId = intval($_POST['post_id']);
        $seriesId = intval($_POST['series_id']);

        $mini->addPostToSeries($postId, $seriesId);

        wp_send_json_success([
            'message' => 'post added to series',
            'post_id' => $postId,
            'series_id' => $seriesId,
            'post_link' => get_post_permalink($postId),
            'post_edit_link' => get_edit_post_link($postId),
            'post' => get_post($postId)
        ]);
        die();
    }

    public static function removePostFromSeries() {
        $mini = new Mini();
        $postId = intval($_POST['post_id']);
        $seriesId = intval($_POST['series_id']);
        $mini->removePostFromSeries($postId, $seriesId);

        wp_send_json_success([
            'message' => 'post removed from series',
            'post_id' => $postId,
            'series_id' => $seriesId
        ]);
        die();
    }


    public static function updatePostsOfSeries() {
        $mini = new Mini();
        $seriesId = intval($_POST['series_id']);
        $postsArray = self::sanitizePostArray($_POST['post_ids']);
        $mini->updateListOfPostsInSeries($seriesId, $postsArray);

        wp_send_json_success([
            'message' => 'Series updated',
            'posts_id' => $postsArray,
            'series_id' => $seriesId
        ]);
        die();
    }


    public static function addNewPostToSeries() {
        $seriesId = intval($_POST['series_id']);
        $postTitle = esc_html($_POST['post_title']);


        $postId = wp_insert_post([
            'post_title' => $postTitle
        ]);

        if (!is_int($postId) || $postId == 0) {
            wp_send_json_error([
                'message' => 'Failed to create post'
            ]);
        }


        $mini = new Mini();

        

        $mini->addPostToSeries($postId, $seriesId);

        error_log("Create new post with title " . $postTitle . " to series " . $seriesId);

        wp_send_json_success([
            'message' => 'New post created and posted to series',
            'post_id' => $postId,
            'series_id' => $seriesId,
            'post_link' => get_post_permalink($postId),
            'post_edit_link' => get_edit_post_link($postId),
            'post' => get_post($postId)
        ]);
        die();


    }

    private static function sanitizePostArray($postData) {

        if (!is_array($postData)) {
            return [];
        }

        $postDataFiltered = array_map(function($item) {
            return intval($item);
        }, $postData);

        return $postDataFiltered;
    }
}