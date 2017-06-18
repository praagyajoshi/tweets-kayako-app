<?php

/**
* Responsible for communicating with the Twitter REST API
* including all the required authentication
*/

namespace Models;
class TwitterAPI extends BaseClass
{

    // Some private properties
    private $bearer_token;
    private $consumer_key;
    private $consumer_secret;
    private $base_url;

    /**
     * Just your average constructor which
     * sets up the base URL of the API, the
     * consumer key and secret of the Twitter
     * app which was created at https://apps.twitter.com/
     */
    function __construct() {
        global $ENV;
        $this->base_url = 'https://api.twitter.com/';
        $this->consumer_key = $ENV['TWITTER_CONSUMER_KEY'];
        $this->consumer_secret = $ENV['TWITTER_CONSUMER_SECRET'];
    }

    /**
     * Makes a Search API call looking for tweets
     * with the mentioned hashtag, max ID (if mentioned)
     * and load more count.
     * Post the API call, it filters the tweets to select
     * only those with the minimun retweet count (if > 0)
     * @param  string  $hashtag           - the hashtag to search for
     * @param  integer $min_retweet_count - min. retweets required
     * @param  string  $max_id            - max ID of the tweets to be
     *                                      fetched - used for load more
     * @param  integer $load_more_count   - the number of tweets to be
     *                                      fetched at one time
     * @return array   - array of the tweets along with
     *                   some search metadata
     */
    function getFeaturedTweets(
        $hashtag = 'custserv',
        $min_retweet_count = 1,
        $max_id = '',
        $load_more_count = 20
    ) {

        // Building the parameters
        // of the search API call
        $parameters = [];
        $parameters['include_entities'] = 0;
        $parameters['count'] = $load_more_count;

        // Filtering out retweets and only looking for
        // original tweets with the specified hashtag
        $parameters['q'] = '#' . $hashtag . ' -filter:retweets';

        // If a max_id was provided for load more,
        // including that as well
        if (strlen($max_id)) {
            $parameters['max_id'] = $max_id;
        }

        // Constructing the end point of
        // the API request
        $endpoint = '1.1/search/tweets.json?' . http_build_query($parameters);

        // Fetching the results
        $result = $this->makeGetRequest(
            $endpoint
        );

        // We will explicitly find out the
        // max ID which will be required for
        // the next load more call
        // (reference: https://dev.twitter.com/rest/public/timelines)
        $lowest_id = 0;

        // Looping on the 'statuses' array by reference
        // as we need to modify the array
        foreach ($result['statuses'] as $key => &$value) {

            // Formatting the date as unix timestamp
            // so that the front end can easily convert
            // it into any format
            $value['timestamp'] = strtotime($value['created_at']);

            // Putting anchor tags around links in
            // the tweet
            $url = '@(http)?(s)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
            $tweet_text = preg_replace(
                '`([^"=\'>])((http|https|ftp)://[^\s<]+[^\s<\.)])`i',
                '$1<a href="$2" target="_blank">$2</a>',
                $value['text']
            );
            $value['text'] = $tweet_text;

            // Setting the lowest ID as max ID
            // for the next load more call
            if (!$lowest_id || $value['id'] < $lowest_id) {
                $lowest_id = $value['id'];
            }
        }

        // PHP tends to retain the loop value reference
        // which can cause a lot of hurt if the variable
        // '$value' is used again - unsetting it
        unset($value);

        // Subtracting 1 from max ID to not
        // repeat the last tweet in the next load more call.
        // Need a 64 bit environment to handle the Twitter IDs
        // (reference: https://dev.twitter.com/rest/public/timelines)
        if ($lowest_id) {
            $lowest_id = $lowest_id - 1;

            // Sending as string to make sure
            // the ID is handled without any
            // precision issue
            $result['search_metadata']['load_more_max_id'] = (string)$lowest_id;
        }

        // Filtering the tweets bassed on the
        // minimun retweet count post the API call
        if ($min_retweet_count > 0) {
            $result['statuses'] = array_filter(
                $result['statuses'],
                function ($val) use($min_retweet_count) {
                    return ($val['retweet_count'] >= $min_retweet_count);
                }
            );

            // Resetting the keys of the
            // tweets array
            $result['statuses'] = array_values($result['statuses']);
        }

        return $result;
    }

    /**
     * Requests the bearer token from Twitter
     * using the 'Application only Authentication'
     * method and sets it as a private property
     * (reference: https://dev.twitter.com/oauth/application-only)
     */
    private function getBearerToken() {

        // Encoding the consumer key and secret
        $bearer_auth = $this->encodeBearerAuth();

        // The POST request has to have
        // a specific grant_type in the body
        $request_body = ['grant_type' => 'client_credentials'];
        $request_body = http_build_query($request_body);

        // Setting the required headers
        $headers = [
            'Authorization: Basic ' . $bearer_auth,
            'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            'Content-Length: ' . strlen($request_body)
        ];

        // Requesting the bearer token
        $result = $this->makePostRequest(
            'oauth2/token',
            $headers,
            $request_body,
            true
        );

        if (isset($result['token_type']) &&
            strlen($result['token_type']) &&
            $result['token_type'] === 'bearer' &&
            isset($result['access_token']) &&
            strlen($result['access_token'])) {

            $this->bearer_token = $result['access_token'];
        }
    }

    /**
     * Encodes the consumer key and secret
     * into a set of credentials required by Twitter
     * (reference: https://dev.twitter.com/oauth/application-only)
     * @return string - base64 encoded credentials
     */
    private function encodeBearerAuth() {
        $encoded_key = rawurlencode($this->consumer_key);
        $encoded_secret = rawurlencode($this->consumer_secret);
        return base64_encode($encoded_key . ':' . $encoded_secret);
    }

    /**
     * Makes a GET request to the Twitter REST API
     * @param  string $path the endpoint to hit
     *                      any parameters are already included
     *                      (without preceding backslash)
     * @return array  result fetched from the API
     */
    private function makeGetRequest($path) {

        // Check if the bearer token exists
        // (required for Twitter API calls)
        // If it doesn't, fetch that first
        if (!strlen($this->bearer_token)) {
            $this->getBearerToken();
        }

        // Authenticating the request with
        // the bearer token
        $headers = [
            'Authorization: Bearer ' . $this->bearer_token
        ];

        // Making a cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $r = curl_exec($ch);
        curl_close($ch);

        // Decoding the received JSON
        // as an array and returning it
        return json_decode($r, true);
    }

    /**
     * Makes a POST request to the Twitter REST API
     * @param  string   $path             the endpoint to hit
     *                                    (without preceding backslash)
     * @param  array    $headers          the headers to be sent
     * @param  string   $body             the parameters to be sent
     * @param  boolean  $for_bearer_token is this call for requesting
     *                                    the bearer token?
     * @return array    result fetched from the API
     */
    private function makePostRequest($path,
        $headers = [],
        $body = '',
        $for_bearer_token = false
    ) {

        // If we are not making this call to
        // fetch the bearer token, and if the
        // bearer token does not exist
        // (required for Twitter API calls)
        // then fetch that first.
        if (!$for_bearer_token && !strlen($this->bearer_token)) {
            $this->getBearerToken();
        }

        // Making a cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $r = curl_exec($ch);
        curl_close($ch);

        // Decoding the received JSON
        // as an array and returning it
        return json_decode($r, true);
    }
}

?>
