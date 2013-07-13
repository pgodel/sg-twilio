<?php

$app['cfg'] = $app->share(function()
{
    return \Symfony\Component\Yaml\Yaml::parse(__DIR__.'/../config/config.yml');
});

$app->get('/call/{number}', function ($number) use ($app) {

    $api = new \ServerGrove\APIClient('https://control.servergrove.com');
    $api->setApiKey($app['cfg']['sg_api']['key']);
    $api->setApiSecret($app['cfg']['sg_api']['secret']);

    $result = $api->call('server/list');
    $servers = $api->getResponse('array');


    $greeting = 'Howdy! Select a server from the following list: ';

    $i = 1;
    $options = array();
    foreach($servers['rsp'] as $s) {
        $greeting .= ", Press $i for ".$s['hostname'];
        $options[$i] = 'http://twimlets.com/message?'.urlencode('Message[0]').'='.urlencode('You have selected server '.$s['hostname']);
        $i++;
    }

    $url = 'http://twimlets.com/menu?Message='.urlencode($greeting);

    foreach($options as $i => $o) {
        $url .= '&'.urlencode('Options['.$i.']').'='.urlencode($o);
    }

    $client = new Services_Twilio($app['cfg']['twilio']['sid'], $app['cfg']['twilio']['token']);
    //return print_r($url, true);
    $call = $client->account->calls->create(
        $app['cfg']['twilio']['number'], // From a valid Twilio number
      $number, // Call this number

      // Read TwiML at this URL when a call connects (hold music)
    //'http://twimlets.com/menu?Message=Hello!%20Press%201%20for%20help%2C%20press%202%20for%20more%20help&Options%5B1%5D=http%3A%2F%2Fservergrove.com%2F1&Options%5B2%5D=http%3A%2F%2Fservergrove.com%2F2&'
    // 'http://twimlets.com/holdmusic?Bucket=com.twilio.music.ambient'
    $url
    );

    return $url;
});

