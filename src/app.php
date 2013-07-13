<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$app['cfg'] = $app->share(function () {
    return \Symfony\Component\Yaml\Yaml::parse(__DIR__ . '/../config/config.yml');
});

$app['api'] = function ($app) {
    $api = new \ServerGrove\APIClient('https://control.servergrove.com');
    $api->setApiKey($app['cfg']['sg_api']['key']);
    $api->setApiSecret($app['cfg']['sg_api']['secret']);

    return $api;
};

$app->post('/server/{number}/{serverId}', function ($number, $serverId, Request $request) use ($app) {

    $api = $app['api'];

    $digits = $request->request->get('Digits');

    $response = new Services_Twilio_Twiml();

    switch ($digits) {
        case 1:
            $api->call('server/stop', array('debug' => 1, 'serverId' => $serverId));
            $response->say("Your server is being stopped.");
            break;
        case 2:
            $api->call('server/start', array('debug' => 1, 'serverId' => $serverId));
            $response->say("Your server is being started.");
            break;
        case 3:
            $api->call('server/restart', array('debug' => 1, 'serverId' => $serverId));
            $response->say("Your server is being restarted.");
            break;
        case 4:
            $result = $api->call('domain/list', array('serverId' => $serverId));
            $apirsp = $api->getResponse('array');

            $message = 'Server has the following domains: ';
            foreach ($apirsp['rsp'] as $dom) {
                $message .= ', ' . $dom['name'];
            }

            $response->say($message);
            break;
        default:
            $response->say("Your selection is invalid.");
            break;
    }

    $response->redirect('http://' . $request->getHttpHost() . '/server/' . $number . '/' . $serverId,
        array('method' => 'GET')
    );

    $rsp = new Response($response, 200, array(
        'content-type' => 'application/xml'
    ));
    return $rsp;
});


$app->get('/server/{number}/{serverId}', function ($number, $serverId) use ($app) {

    $api = $app['api'];

    $result = $api->call('server/get', array('serverId' => $serverId));
    $server = $api->getResponse('array');
    $s = $server['rsp'];
    $response = new Services_Twilio_Twiml();

    $gather = $response->gather(array(
        'numDigits' => 1,
        'timeout' => 30,
    ));

    $gather->say('You have selected server ' . $s['hostname'] . ' and the status is running. Select 1 to stop, 2 to start, 3 to restart, 4 to list the domains in the server');
    $rsp = new Response($response, 200, array(
        'content-type' => 'application/xml'
    ));
    return $rsp;
});

$app->post('/servers/{number}', function ($number, Request $request) use ($app) {
    $host = $request->getHttpHost();

    $api = $app['api'];

    $result = $api->call('server/list');
    $apirsp = $api->getResponse('array');

    $digits = $request->request->get('Digits');
    $response = new Services_Twilio_Twiml();


    if (!empty($digits)) {
        $server = null;
        $i = 1;
        foreach ($apirsp['rsp'] as $s) {
            if ($i == $digits) {
                $server = $s;
                break;
            }
            $i++;
        }
        if (!$server) {
            $response->say("Your selection is invalid.");
            $response->redirect('http://' . $host . '/servers/' . $number,
                array('method' => 'GET')
            );
        } else {
            $response->redirect('http://' . $host . '/server/' . $number . '/' . $server['id'],
                array('method' => 'GET')
            );
        }
    } else {
        $response->redirect('http://' . $host . '/servers/' . $number,
            array('method' => 'GET')
        );
    }

    $rsp = new Response($response, 200, array(
        'content-type' => 'application/xml'
    ));
    return $rsp;

});

$app->get('/servers/{number}', function ($number, Request $request) use ($app) {

    $host = $request->getHttpHost();

    $api = $app['api'];

    $result = $api->call('server/list');
    $servers = $api->getResponse('array');

    $response = new Services_Twilio_Twiml();

    if (empty($number)) {
        $number = $request->query->get('From');
    }

    $gather = $response->gather(array(
        'numDigits' => 1,
        'timeout' => 30,
        'action' => 'http://' . $host . '/servers/' . $number,
    ));
    $greeting = 'Howdy! Select a server from the following list:';

    $i = 1;
    foreach ($servers['rsp'] as $s) {
        $greeting .= ", Press $i for " . $s['hostname'];
        $i++;
    }

    $gather->say($greeting);
    $rsp = new Response($response, 200, array(
        'content-type' => 'application/xml'
    ));
    return $rsp;
})->value('number', null);


$app->get('/call/{number}', function ($number, Request $request) use ($app) {
    $client = new Services_Twilio($app['cfg']['twilio']['sid'], $app['cfg']['twilio']['token']);

    $call = $client->account->calls->create(
        $app['cfg']['twilio']['number'], // From a valid Twilio number
        $number, // Call this number
            'http://' . $request->getHttpHost() . '/servers/' . $number
    );

    return 'Initiating call to ' . $number;
});

