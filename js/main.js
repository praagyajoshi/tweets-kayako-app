var max_id = '';
var tweet_ids = [];
var load_more_count = 30;

$(document).ready(function() {

    // Start fetching tweets as soon
    // as the DOM is ready
    getTweets();

    // Handle clicks on the load more button
    $('#load-more-tweets').on('click', getTweets);
});

/**
 * Makes a GET API call to fetch tweets
 */
function getTweets() {

    // Setting the required parameters
    // of the GET call
    var request_data = {count: load_more_count};
    if (max_id.length) {
        request_data.max_id = max_id;
    }

    // Showing a loading state
    // on the load more button
    $('#load-more-tweets').addClass('is-loading');

    // Making the AJAX request
    $.ajax({
        url: '/api/v1/tweets',
        data: request_data,
        error: function() {
            // console.log('Tweets API call failed!');
        },
        dataType: 'json',
        success: function(data) {

            if (data.result &&
                data.result.search_metadata) {

                // Storing max_id which will
                // be used for the load more call
                if (data.result.search_metadata.load_more_max_id &&
                    data.result.search_metadata.load_more_max_id.length) {
                    max_id = data.result.search_metadata.load_more_max_id;
                }

                // Checking if load more is possible
                // or not - if it isn't, disable
                // the load more button
                if (data.result.search_metadata.count < load_more_count) {
                    $('#load-more-tweets').prop('disabled', true);
                }
            }

            // Generating HTML for each tweet
            if (data.result &&
                data.result.statuses &&
                data.result.statuses.length) {

                var tweets = data.result.statuses;
                var container = $('#tweets-container').find('.columns');
                var new_tweet_class = '';

                // Highlighting new tweets, but
                // only on load more
                if (tweet_ids.length > 0) {
                    new_tweet_class = 'new-tweet';
                }

                $.each(tweets, function (index, value) {

                    // Checking if we have already
                    // displayed the tweet or not
                    if ($.inArray(value.id_str, tweet_ids) < 0) {
                        var html = generateTweetHTML(value, new_tweet_class);
                        container.append(html);
                        tweet_ids.push(value.id_str);
                    } else {
                        // console.log('id ' + value.id_str + ' exists!');
                    }
                });
            }

        },
        complete: function (data) {

            // Hiding the loader on the
            // load more button
            $('#load-more-tweets').removeClass('is-loading');

            // Hiding the main loader
            // and displaying the tweets
            // on initial load - as denoted by
            // the absence of hidden class
            if (!$('#initial-loader').hasClass('hidden')) {
                $('#initial-loader')
                    .addClass('hidden')
                    .one(
                        'webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend',
                        function(e) {
                            $('#tweets-container').removeClass('hidden');
                        }
                    );
            }
        },
        type: 'GET'
    });
}

/**
 * Generates HTML markup for one
 * particular tweet
 * @param  object   tweet_data      details of the Tweet
 * @param  string   new_tweet_class any extra CSS class to
 *                                  be applied
 * @return string   HTML markup
 */
function generateTweetHTML(tweet_data, tweet_class) {

    // Converting the unix timestamp
    // into a nice human readable format
    var time_string = moment.unix(tweet_data.timestamp);
    time_string = time_string.format("hh:mm a - DD/MMM/YYYY");

    // Generating the HTML markup
    // using ES6 template literals
    // (reference: https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Template_literals)
    var html =
        `<div class="column is-one-third">
            <div class="card tweet-card ${tweet_class}">
                <div class="card-content">
                    <div class="media user-details">
                        <div class="media-left">
                            <figure class="image">
                                <img src="${tweet_data.user.profile_image_url}"
                                alt="${tweet_data.user.name}">
                            </figure>
                        </div>
                        <div class="media-content">
                            <p class="title">
                                ${tweet_data.user.name}
                            </p>
                            <p class="subtitle">
                                <a href="https://www.twitter.com/${tweet_data.user.screen_name}""
                                    target="_blank">
                                    @${tweet_data.user.screen_name}
                                </a>
                            </p>
                            </a>
                        </div>
                    </div>

                    <div class="content">
                        ${tweet_data.text}
                        <br>
                        <small>
                            ${time_string}
                        </small>
                    </div>
                </div>
                <footer class="card-footer">
                    <div class="card-footer-item">
                        <span class="icon is-small">
                            <i class="fa fa-retweet"></i>
                        </span>
                        <span class="card-footer-item-text">
                            ${tweet_data.retweet_count}
                        </span>
                    </div>
                    <div class="card-footer-item">
                        <span class="icon is-small">
                            <i class="fa fa-heart"></i>
                        </span>
                        <span class="card-footer-item-text">
                            ${tweet_data.favorite_count}
                        </span>
                    </div>
                </footer>
            </div>
        </div>`;
    return html;
}
