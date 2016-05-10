<?php

require_once 'libs/facebook-php/facebook-php-sdk-v4/src/Facebook/autoload.php';

session_start();

# login-callback.php
$fb = new Facebook\Facebook([
	'app_id' => '10154159683889496',
	'app_secret' => '3cc1bc327802d9c8a4b1b8ae076dd14e',
	'default_graph_version' => 'v2.6',
]);

$helper = $fb->getRedirectLoginHelper();
try {
	$accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
	// When Graph returns an error
	echo 'Graph returned an error: ' . $e->getMessage();
	exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
	// When validation fails or other local issues
	echo 'Facebook SDK returned an error: ' . $e->getMessage();
	exit;
}

if (isset($accessToken)) {
	// Logged in!
	$_SESSION['facebook_access_token'] = (string) $accessToken;

	// Now you can redirect to another page and use the
	// access token from $_SESSION['facebook_access_token']
}



# login.php

$helper = $fb->getRedirectLoginHelper();
$permissions = ['user_friends', 'rsvp_event', 'pages_show_list']; // optional
$loginUrl = $helper->getLoginUrl('http://sexy.drexelforchrist.org:8000/TenthStats/import_facebookTCN.php', $permissions);

echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';


if($accessToken != null) {

	$fb->setDefaultAccessToken($accessToken);

//	$fb->get();


	$response = $fb->get(
		'/tenthcitynetwork/events?fields=id%2Cname%2Cstart_time%2Cattending.limit(500)%7Bid%2Cfirst_name%2Clast_name%2Cmiddle_name%7D%2Cmaybe.limit(500)%7Bid%2Cfirst_name%2Clast_name%2Cmiddle_name%7D%2Cnoreply.limit(500)%7Bid%2Cfirst_name%2Clast_name%2Cmiddle_name%2Crsvp_status%7D&limit=20'
	);

	require_once '_facebookBody.php';

}