# Kayako Twitter challenge

This is a PHP based app which fetches tweets from the Twitter API using *application only authentication*.  
Tweets which contain a particular hashtag are fetched and then filtered based on the number of retweets.  
A live version can be viewed here: [tweets.praagya.com](http://tweets.praagya.com).

## App Structure

The app is split into various folders with self explanatory names, and no framework has been used to keep the footprint as small as possible.  
The architecture of the app consists of `models` and `controllers` which handle all required functionalities.  
There are also no dedicated views right now - the views are rendered in `JavaScript` using `ES6 Template Literals`.  


The API routing is done through a simple `.htaccess` file in `/api/v1/` folder. This file routes all valid API calls to `index.php` in the same folder. Because of the folder structure, versioning is maintained as the complete path of the API becomes `<domain>/api/v1/tweets`.

## Dependencies

Dependencies have been kept to a minimum, especially for the backend. No SDK/API client has been used.

*Backend dependencies:*
- 64bit PHP - required for processing the max IDs for Twitter load more call (https://dev.twitter.com/rest/public/timelines)
- `/config/application.php` has not been checked into version control as it contains the Twitter application secret - a sample can be seen here: https://gist.github.com/praagyajoshi/71dc825b12dfa91e8847e46263c610e6

*Frontend dependencies:*
- jQuery - makes life a little bit easier
- [Moment.js](https://momentjs.com/) - makes dealing with dates and times much easier
- [Bulma](http://bulma.io/) - a small CSS framework
- [FontAwesome](http://fontawesome.io/) - for that icon goodness

## Improvements

- **Bearer Token storage**  
As it currently stands, the bearer token is obtained before every request. Ideally, it should be stored (only on the server end as it is sensitive information) for as long as it is valid.
- **Tweet formatting**  
Links in the fetched tweets are parsed, while hashtags and user mentions are not. They should also link to relevant pages on Twitter.
- **Tweet media**  
If any media (photo/video) is included in the tweet, it is not displayed on the front end.
- **Front end framework**  
A templating engine such as [Underscore.js](http://underscorejs.org/) can be used to improve code reusability. Alternatively, a web framework such as `React` would go a long way, especially if this app is extended.

