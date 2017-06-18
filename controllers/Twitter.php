<?php

/**
* Controller for all Twitter related functions.
*/

namespace Controllers;
class Twitter extends BaseController
{
    /**
     * Requests our TwitterAPI model
     * to fetch the specified tweets
     * using the Twitter REST API.
     * @param  string   $max_id     the max ID of the tweets
     *                              to be fetched
     * @param  int      $count      the number of tweets
     *                              to be fetched
     * @return array    array of tweets along with
     *                  some search metadata
     */
    function getTweets($max_id, $count) {

        // Asking our model to fetch the tweets.
        // Hardcoding the required hashtag (custserv)
        // and the min. number of retweets, but they
        // can just as easily be requested from the front end
        $api = new \Models\TwitterAPI();
        return $api->getFeaturedTweets('custserv', 1, $max_id, $count);
    }
}

?>
