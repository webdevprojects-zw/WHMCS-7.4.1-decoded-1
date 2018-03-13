<?php 
function twitter_getTwitterIntents($username, $version)
{
    require_once(ROOTDIR . "/modules/social/twitter/twitterIntents.php");
    $twitter = new twitterIntents($username, $version);
    $tweets = $twitter->getTweets();
    return $tweets;
}


