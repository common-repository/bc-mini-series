<?php

namespace BinaryCarpenter\BC_MNS;


class MiniSeriesSideBarWidget extends \WP_Widget {

 
    public function __construct() {
        parent::__construct(
            'bc_mns_post_widget', // Base ID
            'BC Mini Series ', // Name
            array( 'description' => __( 'Mini series posts', 'bc_mini_series' ), ) // Args
        );
    }
 
    public function widget( $args, $instance ) {
        $post = get_queried_object();


        if ( !$post || !isset($post->post_type) || $post->post_type != 'post') {
            error_log("BC_MNS: post type is not post or post not exists");
            return;
        }

        $mini = new Mini();

        
        $rootSeriesId = $mini->getRootSeriesIdOfAPost($post->ID);
        
        if (!$rootSeriesId) {
            error_log("BC_MNS: post's series not found");
            return;
        }

        $allPostsInSeries = $mini->getFullTreeOfASeriesByRootSeriesId($rootSeriesId, ['publish']);
        
        if (!$allPostsInSeries || count($allPostsInSeries) == 0) {
            return;
        }

        UI::printListOfPosts($allPostsInSeries, $post->ID, get_post($rootSeriesId));

    }
 
    public function form( $instance ) {
        ?>
<h3>Series display options</h3>

<?php
    }
 
    public function update( $new_instance, $old_instance ) {
        // processes widget options to be saved
    }
}