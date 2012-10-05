<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Response;

function base64_url_encode($input) {
 return strtr(base64_encode($input), '+/=', '-_,');
}

function base64_url_decode($input) {
 return base64_decode(strtr($input, '-_,', '+/='));
}

function idToHash($id) {
  return gmp_strval(gmp_init($id, 16), 62);
}

function hashToId($hash) {
  return gmp_strval(gmp_init($hash, 62), 16);
}

$app = new Silex\Application();

$app['debug'] = true;
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/views',
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

include 'config.php';
include 'mailer.php';
$app['mailer'] = new Mailer($mailgunKey, $mailgunDomain);

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

$app->get('/reset', function() use($app) {
  $app['mongo']->drop();
  return 'OK';
});

$app->get('/new', function() use($app) {
  return $app['twig']->render('new.twig.html');
});
$app->post('/new', function() use($app) {
  $email = $app['request']->get('email');

  $user = $app['mongo']->findOne(array('email' => $email));
  if (!$user)
  {
    $user = array(
      'email' => $email,
      'confirmed' => false,
    );
    $app['mongo']->insert($user);
    $user['hash'] = idToHash($user['_id']->{'$id'});
    $app['mongo']->update(array('_id' => new MongoId($user['_id']->{'$id'})), $user);
  }

  if (!$user['confirmed'])
  {
    $confirmUrl = 'http://note.grin.io' . $app['url_generator']->generate('confirm', array('hash' => $user['hash'])); // fake absolute url so we don't get a port number in there
    $app['mailer']->send(
      $email,
      'Confirm Your Email Address',
      'Confirm your email address by clicking here: ' . $confirmUrl,
      '<html><body>Confirm your email address by clicking here: <a href="' . $confirmUrl . '">' . $confirmUrl . '</a></body></html>'
    );
 
    return 'We sent you an email to confirm your email address. Click the link in that email to continue.';
  }
  else
  {
    return $app->redirect($app['url_generator']->generate('bookmarklet', array('hash' => $user['hash'])));
  }
});


$app->get('/confirm/{hash}', function($hash) use ($app) {
  $user = $app['mongo']->findOne(array('hash' => $hash));
  if (!$user)
  {
    return 'User not found.';
  }
  $user['confirmed'] = true;
  $app['mongo']->update(array('_id' => new MongoId($user['_id']->{'$id'})), $user);
  return $app->redirect($app['url_generator']->generate('bookmarklet', array('hash' => $user['hash'])));
})->bind('confirm');


$app->get('/bookmarklet/{hash}', function($hash) {
  return '<html><body>Drag this to your bookmark bar: <a href="javascript:(function(){s=document.createElement(\'script\');s.src=\'//note.grin.io/note.js?k=' . $hash . 
         '&r=\'+Math.random();document.body.appendChild(s);})();">Note</a></body></html>';
})->bind('bookmarklet');


$app->get('/note.js', function() use ($app) {
  return file_get_contents('base_64_url.min.js') . str_replace('KEY', $app['request']->get('k'), file_get_contents('note.js.incomplete'));
});


$app->get('/note/{hash}/{url}', function ($hash, $url) use ($app) {

  $url = base64_url_decode($url);

  $response = new Response('', 200, array(
    'Content-Type' => 'application/json',
    'Access-Control-Allow-Origin' => '*'
  ));

  $user = $app['mongo']->findOne(array('hash' => $hash));
  if (!$user)
  {
    return $response->setContent(json_encode(array('ok' => 0, 'error' => 'User not found.')));
  }
  if (!$user['confirmed'])
  {
    return $response->setContent(json_encode(array('ok' => 0, 'error' => 'Email address not confirmed.')));
  }

  $app['mailer']->send(
    $user['email'],
    '{note} ' . $url,
    'source: $url' . "\n" . date('F j, Y, g:i a'),
    '<html><body>source: <a href="' . $url . '">' . $url . '</a><br/>' . date('F j, Y, g:i a') . '</body></html>'
  );

  return $response->setContent(json_encode(array('ok' => 1)));
})->assert('url', '.+');


$app->run();
