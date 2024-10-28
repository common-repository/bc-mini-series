<?php


namespace BinaryCarpenter\BC_MNS;

use Error;
use Exception;
use JsonException;

include 'load.php';

class Mini {

    public function getAllMiniSeries() {
        return get_posts([
            'post_type' => C::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => -1
          ]);
    }



    public function getSeriesIdOfAPost(int $postId) {
        return get_post_meta($postId, C::SERIES_OF_POST_META, true);
    }


    public function getRootSeriesIdOfAPost(int $postId) {
        $tempRootId = $postId;
        while (get_post_meta($tempRootId, C::SERIES_OF_POST_META, true)) {
            $tempRootId = get_post_meta($tempRootId, C::SERIES_OF_POST_META, true);
        }
        
        return $tempRootId;

    }

    public function getFullTreeOfASeriesByRootSeriesId(int $rootSeriesID, array $postStatus = ['any']) {
        

        $allPostsInSeries = $this->getAllPostsObjectOfSeries($rootSeriesID, $postStatus);

        if (!is_array($allPostsInSeries) || count($allPostsInSeries) == 0) {
            error_log("BC_MNS: list of posts in this series " . $rootSeriesID . " is empty");
            return;
        }

        foreach($allPostsInSeries as $singlePost) {
            if ($singlePost->post_type == C::POST_TYPE) {
                $singlePost->child_posts = $this->getAllPostsObjectOfSeries($singlePost->ID, $postStatus);
            }
        }

        return $allPostsInSeries;



    }

    public function getFullTreeOfASeriesByPostId(int $postId, array $postStatus = ['any']) {
        

        $rootSeries = $this->getRootSeriesIdOfAPost($postId);

        $allPostsInSeries = $this->getAllPostsObjectOfSeries($rootSeries, $postStatus);

        if (!is_array($allPostsInSeries) || count($allPostsInSeries) == 0) {
            error_log("BC_MNS: list of posts in this series " . $rootSeries . " is empty");
            return;
        }

        foreach($allPostsInSeries as $singlePost) {
            if ($singlePost->post_type == C::POST_TYPE) {
                $singlePost->child_posts = $this->getAllPostsObjectOfSeries($singlePost->ID, $postStatus);
            }
        }

        return $allPostsInSeries;



    }



    //get all posts of the current series, not its parent
    public function getAllPostsOfASeries(int $seriesId) {
        $posts = get_post_meta($seriesId, C::LIST_POSTS_META, true);

        error_log('getting posts of a series ' . json_encode(unserialize($posts)));
        
        return is_array(unserialize($posts)) ? unserialize($posts) : [];
    }


    public function getAllPostsObjectOfSeries(int $seriesId, array $postStatus = ['any']) {
        $postIds = $this->getAllPostsOfASeries($seriesId);

        error_log("about to query all posts from list: " . json_encode($postIds));

        if (count($postIds) == 0) {
            return [];
        }

        return  get_posts([
            'include' => $postIds,
            'post_type' => [C::POST_TYPE, 'post'],
            'orderby'   => 'post__in',
            'nopaging' => true,
            'post_status' => $postStatus
        ]);
    }


    //Get all mini series, regardless of statuses (even draft...)
    private function getAllMiniSeriesAllStatus() {
        return get_posts([
            'post_type' => C::POST_TYPE,
            'numberposts' => -1
          ]);
    }


    private function validatePostAndSeries(int $postId, int $seriesId) {
        $post = get_post($postId);
        $series = get_post($seriesId);


        if (!$post || ! $series) {
            throw new Exception("Post or series does not exist");
        }

        //allow nested series
        if ($post->post_type != 'post' && $post->post_type != C::POST_TYPE) {
            throw new Exception("Post's type must be 'post' or type '" . C::POST_TYPE . "' !");
        }

        if ($series->post_type != C::POST_TYPE) {
            throw new Exception("Series is not a valid series type!");
        }

        //to avoid circular link, if the post is a series and currently has seriesId as its post, reject this
        if ($post->post_type == C::POST_TYPE) {
            $postsUnderThisSeries = $this->getAllPostsOfASeries($postId);
            if (in_array($seriesId, $postsUnderThisSeries)) {
                throw new Exception("Prevent chicken-egg relationship " . $postId . " is already a series containing " . $seriesId . " in its post list");
            }

        }
    }

    public function addPostToSeries(int $postId, int $seriesId) {

        $this->validatePostAndSeries($postId, $seriesId);

        error_log('validate post and series ok');


        //add post meta to post
        update_post_meta($postId, C::SERIES_OF_POST_META, $seriesId);

        //update series 
        $postsInSeries = $this->getAllPostsOfASeries($seriesId) ;

        if (!is_array($postsInSeries)) {
            $postsInSeries = [];
        }

        if (!in_array($postId, $postsInSeries)) {
            $postsInSeries[] = $postId;
            $this->updateListOfPostsInSeries($seriesId, $postsInSeries);
        }
        
    }



    public function updateListOfPostsInSeries(int $seriesId, array $postIdsArray) {
        error_log('update list of posts '. json_encode($postIdsArray) . ' to ' . $seriesId);
        update_post_meta($seriesId, C::LIST_POSTS_META, serialize($postIdsArray));
    }

    public function removePostFromSeries($postId, $seriesId) {
        $this->validatePostAndSeries($postId, $seriesId);


        delete_post_meta($postId, C::SERIES_OF_POST_META);

        //update series 
        $postsInSeries = $this->getAllPostsOfASeries($seriesId) ;

        if (!is_array($postsInSeries) || count($postsInSeries) == 0) {
            return;
        }

        $newPostsArray = [];

        for ($i = 0; $i < count($postsInSeries); $i++) {
            if (!($postsInSeries[$i] == $postId)) {
                $newPostsArray[] = $postsInSeries[$i];
            }
        }


        $this->updateListOfPostsInSeries($seriesId, $newPostsArray);

    }

}