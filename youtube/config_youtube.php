<?php
    // OAUTH Configuration
    session_save_path("/tmp");
    $oauthClientID = '893043449708-hdqjcindjs6gabplp90to8i3c1p080et.apps.googleusercontent.com';
    $oauthClientSecret = 'mEGvfBI7CcoAbCIYyVz5L6ti';
    $baseUri = 'https://www.itshot.com/adempierstock/youtube/import-videos-youtube.php';
    $redirectUri = 'https://www.itshot.com/adempierstock/youtube/import-videos-youtube.php';  
    
    $root_path = "/home/cloudpanel/htdocs/www.itshot.com/current/";

    define('OAUTH_CLIENT_ID',$oauthClientID);
    define('OAUTH_CLIENT_SECRET',$oauthClientSecret);
    define('REDIRECT_URI',$redirectUri);
    define('BASE_URI',$baseUri); 
    
    // Include google client libraries
    require_once $root_path.'adempierstock/youtube/src/Google/autoload.php'; 
    require_once $root_path.'adempierstock/youtube/src/Google/Client.php';
    require_once $root_path.'adempierstock/youtube/src/Google/Service/YouTube.php';
    session_start();
    
    $client = new Google_Client();
    $client->setClientId(OAUTH_CLIENT_ID);
    $client->setClientSecret(OAUTH_CLIENT_SECRET);
    $client->setScopes('https://www.googleapis.com/auth/youtube');
    $client->setRedirectUri(REDIRECT_URI);
   //echo "<pre>";print_r($client);die;
    // Define an object that will be used to make all API requests.
    $youtube = new Google_Service_YouTube($client);
    
?>
