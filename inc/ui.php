<?php

namespace BinaryCarpenter\BC_MNS;

include 'load.php';


class UI {
    public static function seriesManager($series)  {
        $seriesId = $series->ID;
        $mini = new Mini();

        $postsIdsOfThisSeries = $mini->getAllPostsOfASeries($seriesId);

        error_log("about to query all posts from list: " . json_encode($postsIdsOfThisSeries));

        $postsOfThisSeries = [];
        if (is_array($postsIdsOfThisSeries) && count($postsIdsOfThisSeries) > 0) {
            $postsOfThisSeries = get_posts([
                'include' => $postsIdsOfThisSeries,
                'orderby'   => 'post__in',
                'nopaging' => true,
                'post_status' => 'any',
                'post_type' => [C::POST_TYPE, 'post'],
                'posts_per_page' => -1
            ]);
        }
        self::displayLoadingSpinner();
        self::displaySeriesData($seriesId);
        self::displayPostSearchForm();
        self::displayPostsInSeries($postsOfThisSeries);
        self::displayAddNewPostForm();
        echo '<hr>';
        self::displayUpdateSeriesButton();
        
    }

    public static function postInSeriesManager($post) {
        
        $mini = new Mini();
        $seriesId = $mini->getSeriesIdOfAPost($post->ID);
        
        self::displayLoadingSpinner();

        if ($seriesId) {
            $series = get_post($seriesId);

            self::displaySeriesOfPost($series, $post->ID);

            return;
            
        }


        $allSeries = new \WP_Query( array( 
            'posts_per_page' => -1, 
            'post_status' => 'publish',
            'post_type' => C::POST_TYPE ) );

        self::displayAddPostToSeriesForm($allSeries->posts, $post->ID);


    }

    private static function displaySeriesOfPost($series, $postId) {

        ?>
        <div id="in-post-in-series">
            <p>This post is a part of this series: <a target="_blank" href="<?php echo esc_html( $series->guid); ?>"><?php echo esc_html( $series->post_title); ?></a></p>
            <button data-post-id="<?php echo esc_html( $postId); ?>" data-series-id="<?php echo esc_html( $series->ID); ?>" class="button-secondary" id="in-post-remove-post-from-series">Remove</button>
        </div>
        <?php
    }

    private static function displayAddPostToSeriesForm($allSeries, $postId) {
        if (!is_array($allSeries) || count($allSeries) == 0) {
            ?>
                <p>There is no series. Create one first</p>

            <?php
            return;
        }
        
        ?>
            <div id="available-series">
            <h3>Available series</h3>
            <ul>
        <?php
        foreach ($allSeries as $series) {
            ?>
                <li class="single-post-in-series ">
                    <a href="<?php echo esc_html( $series->guid); ?>"><span class="single-post-title"><?php echo esc_html( $series->post_title ); ?></span></a> 

                    <button data-post-id="<?php echo esc_html( $postId ); ?>" data-series-id="<?php echo esc_html( $series->ID ); ?>"  class="button-secondary in-post-add-post-to-series">Add to this series</button>
                </li>
            <?php
        }

        ?>
            </ul>

            </div>
        <?php
    }

    private static function displayLoadingSpinner() {
        ?>
            <div class="bc-mns-loading">
                <div></div>
                <div></div>
            </div>
        <?php
    }

    private static function displaySeriesData($seriesId) {
        ?>
            <script>window.bc_mns_series_id=<?php echo esc_html( $seriesId ); ?></script>

        <?php
    }


    private static function displayPostsInSeries(array $posts) {
        ?>
            <h3>Posts in series</h3>
        <?php

        if (!$posts || count($posts) == 0) {
            ?>
                <ul id="series_posts_list"></ul>
            <?php
            return;
        }
        
        ?>
        <hr>
        <ul id="series_posts_list">
        <?php


        for($i = 0; $i < count($posts); $i++) {
            ?>
                <li class="single-post-in-series" data-post-id="<?php echo esc_html( $posts[$i]->ID ); ?>">
                    <span class="single-post-title"><?php echo esc_html( $posts[$i]->post_title ); ?> </span>

                    <div>
                        <span class='bc-mns-post-status <?php echo $posts[$i]->post_status; ?>'><?php echo $posts[$i]->post_status; ?></span>
                        <a target="_blank" class="button-secondary" href="<?php echo esc_html( get_edit_post_link($posts[$i]->ID) ); ?>">Edit</a>
                        <a target="_blank" class="button-secondary" href="<?php echo esc_html( get_post_permalink($posts[$i]->ID) ); ?>">View</a>
                        <button class="button-secondary remove-post-from-series" data-post-id="<?php echo esc_html( $posts[$i]->ID ); ?>">Remove</button>
                    </div>
                    
                </li>

            <?php
        }

        ?>
        
        </ul>

        <?php

    }

    private static function displayAddNewPostForm() {
        ?>
        <input type="text" id="bc_mns_new_post_title" placeholder="new post title" />
        <button class="button-secondary" id="bc_mns_add_new_post_to_series">Add new post to series</button>

<?php
    }

    private static function displayUpdateSeriesButton() {
        ?>
            <button class="button-primary" id="bc_mns_update_series">Update series</button>
        <?php
    }

    private static function displayPostSearchForm() {
        ?>
            <p>Find posts by title to add</p>
            <input type="text" id="bc_mns_keyword" name="keyword" placeholder="enter title" />
            <button class="button-primary" id="search_post_by_title">Search</button>

            <h4>Search result</h4>
            <p><em>Remember, posts are already in a series will not be displayed here</em></p>
            <div id="bc_mns_posts_search_results">


            </div>

        <?php
    }


    public static function printListOfPosts(array $posts, $currentPostId, $series) {
        ?>
            <h3><?php echo esc_html(__('In this series', 'bc_mini_series')); ?> </h3>
            <p><?php echo esc_html(__('This post is a part of the mini series: ', 'bc_mini_series')); ?> <a target="_blank" href="<?php echo esc_html( $series->guid ); ?>"><?php echo esc_html( $series->post_title); ?></a> </p>
            <ul class="bc_mns_fe_posts_list">
            <?php
                foreach($posts as $post) {
                    ?>
                        <li class="bc_mns_fe_single_post <?php echo esc_html( $post->ID == $currentPostId ? 'bc_mns_active_post' : ''); ?>">
                            <a href="<?php echo esc_html( $post->guid); ?>"><?php echo esc_html( $post->post_title); ?></a>

                            <?php if (isset($post->child_posts)): ?>
                                <ul class="bc_mns_fe_child_posts">
                                    <?php foreach($post->child_posts as $cp): ?>
                                        <li class="bc_mns_fe_single_post <?php echo esc_html( $cp->ID == $currentPostId ? 'bc_mns_active_post' : ''); ?>">
                                            <a href="<?php echo esc_html( $cp->guid); ?>"><?php echo esc_html( $cp->post_title); ?></a>

                                        </li>

                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>

                    <?php
                }
            ?>
            </ul>
        <?php
    }


    public static function getListOfPostsInSeriesHTML(array $posts) {
        if (!$posts) {
            return;
        }
        $content =
                '<ul class="bc_mns_fe_in_series_posts_list">';

        foreach ($posts as $post) {
            $content .=  '<li class="bc_mns_fe_single_post '.(isset($post->child_posts) ? 'bc_mns_child_series' : '') .'">'.
                            '<a   href="'. esc_html( $post->guid).'"> '. esc_html( $post->post_title)."</a>";

                            if (isset($post->child_posts)) {
                                $content .= '<ul class="bc_mns_fe_child_posts">';
                                foreach($post->child_posts as $cp) {
                                    $content .= '<li class="bc_mns_fe_single_post">';
                                    $content .= '<a href="'.esc_html( $cp->guid).'">'. esc_html( $cp->post_title).'</a>';
                                    $content .='</li>';
                                }
                                $content .='</ul>';
                            }


                        $content.='</li>';
        }

        $content .= '</ul>';

        return $content;
    }

    
}