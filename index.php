<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Response;

function base64_url_decode($input) {
 return base64_decode(strtr($input, '-_,', '+/='));
}

function idToHash($id) {
  return gmp_strval(gmp_init($id, 16), 62);
}

function myHTMLEntities($str) {
  return htmlentities($str, ENT_QUOTES|ENT_IGNORE, 'UTF-8');
}

$app = new Silex\Application();

$app['debug'] = true;
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/views',
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

include 'mailer.php';
$app['mailer'] = new Mailer(getenv('MAILGUN_API_KEY'), getenv('MAILGUN_DOMAIN'));

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

//$app->get('/reset', function() use($app) {
//  $app['mongo']->drop();
//  return 'OK';
//});

$app->get('/', function() use($app) {
  return $app['twig']->render('new.twig.html');
});
$app->post('/', function() use($app) {
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

  $confirmUrl = 'http://note.grin.io' . $app['url_generator']->generate('confirm', array('hash' => $user['hash'])); // fake absolute url so we don't get a port number in there
  $app['mailer']->send(
    $email,
    'Confirm Your Email Address',
    'Confirm your email address by clicking here: ' . $confirmUrl,
    '<html><body>Confirm your email address by clicking here: <a href="' . $confirmUrl . '">' . $confirmUrl . '</a></body></html>'
  );

  return 'We sent you an email to confirm your email address. Click the link in that email to continue.';
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


$app->get('/bookmarklet/{hash}', function($hash) use($app) {
  return $app['twig']->render('bookmarklet.twig.html', array('hash' => $hash));
})->bind('bookmarklet');


$app->get('/note/{hash}', function ($hash) use ($app) {

  $response = new Response('', 200, array(
    'Content-Type' => 'application/json',
    'Access-Control-Allow-Origin' => '*'
  ));

  if ($hash === '0')
  {
    return $response->setContent(json_encode(array('ok' => 0, 'error' => 'User id not set in Javascript')));
  }

  $user = $app['mongo']->findOne(array('hash' => $hash));
  if (!$user)
  {
    return $response->setContent(json_encode(array('ok' => 0, 'error' => 'User not found.')));
  }
  if (!$user['confirmed'])
  {
    return $response->setContent(json_encode(array('ok' => 0, 'error' => 'Email address not confirmed.')));
  }

  $url = base64_url_decode($app['request']->get('u'));
  $title = base64_url_decode($app['request']->get('t'));
  $selection = base64_url_decode($app['request']->get('s'));

  $app['mailer']->send(
    $user['email'],
    '{note} ' . $url,
    "source: $url\ntitle: $title\n" . date('F j, Y, g:i a') . "\n\n$selection",
    '<html><body>source: <a href="' . $url . '">' . $url . '</a><br/>title: <b>' . myHTMLEntities($title) . '</b><br/>' . date('F j, Y, g:i a') . '<br/><br/>' . myHTMLEntities($selection) . '</body></html>'
  );

  return $response->setContent(json_encode(array('ok' => 1)));
});


$app->run();
