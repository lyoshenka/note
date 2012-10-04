<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Response;

function base64_url_encode($input) {
 return strtr(base64_encode($input), '+/=', '-_,');
}

function base64_url_decode($input) {
 return base64_decode(strtr($input, '-_,', '+/='));
}

$app = new Silex\Application();

$app['debug'] = true;
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/views',
));

$app['mongo'] = function() {
    $services_json = json_decode(getenv("VCAP_SERVICES"),true);
    $mongo_config = $services_json["mongodb-1.8"][0]["credentials"];
    $username = $mongo_config["username"];
    $password = $mongo_config["password"];
    $hostname = $mongo_config["hostname"];
    $port = $mongo_config["port"];
    $db = $mongo_config["db"];
    $name = $mongo_config["name"];
    $connect = "mongodb://${username}:${password}@${hostname}:${port}/${db}";
    $m = new Mongo($connect);
    return $m->selectCollection($db,'users');
};

$app->get('/new', function() use($app) {
  return $app['twig']->render('new.twig.html');
});
$app->post('/new', function() use($app) {
  $email = $app['request']->get('email');
  $users = $app['mongo'];

  $user = $users->findOne(array('email' => $email));
  if (!$user)
  {
    $user = array(
      'email' => $email
    );
    $users->insert($user);
  }

  return var_export($user, true);
});

$app->get('/bookmarklet/{key}', function($key) {
  return '<html><body>Drag this to your bookmark bar: <a href="javascript:(function(){s=document.createElement(\'script\');s.src=\'//note.grin.io/note.js?k='.$key.'&r=\'+Math.random();document.body.appendChild(s);})();">Note</a></body></html>';
});

$app->get('/note.js', function() use ($app) {
  return file_get_contents('base_64_url.min.js') . str_replace('KEY', $app['request']->get('k'), file_get_contents('note.js.incomplete'));
});

$app->get('/note/{key}/{url}', function ($key, $url) use ($app) {

  include 'config.php';

  $url = base64_url_decode($url);

  $response = new Response('', 200, array(
    'Content-Type' => 'application/json',
    'Access-Control-Allow-Origin' => '*'
  ));

  if ($key !== $secret) {
    return $response->setContent(json_encode(array('ok' => 0, 'error' => 'Invalid key.')));
  }

  $fields = array(
    'from' => $from, // set in config.php
    'to' => $to, // set in config.php
    'subject' => '{note} ' . $url,
    'text' => 'source: $url' . "\n" . date('F j, Y, g:i a'),
    'html' => '<html><body>source: <a href="$url">' . $url . '</a><br/>' . date('F j, Y, g:i a') . '</body></html>',
  );

  $ch = curl_init();
  curl_setopt_array($ch,array(
    CURLOPT_URL            => 'https://api.mailgun.net/v2/' . $mailgunDomain . '/messages',
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($fields),
    CURLOPT_USERPWD        => 'api:' . $mailgunKey,
    CURLOPT_RETURNTRANSFER => true
  ));
  $result = curl_exec($ch);
  curl_close($ch);

  return $response->setContent(json_encode(array('ok' => 1)));
})->assert('url', '.+');

$app->run();
