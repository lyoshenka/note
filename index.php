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
