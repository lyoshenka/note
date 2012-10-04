<html>
<head>
<title>Demo</title>
</head>
<body>
<h1>PHP Mongo Test</h1>
<?php
  $services = getenv("VCAP_SERVICES");
  echo "<h2>VCAP_SERVICES</h2>";
  echo "<p>" . $services . "</p>";
  $services_json = json_decode($services,true);
  $mongo_config = $services_json["mongodb-1.8"][0]["credentials"];

  $username = $mongo_config["username"];
  $password = $mongo_config["password"];
  $hostname = $mongo_config["hostname"];
  $port = $mongo_config["port"];
  $db = $mongo_config["db"];

  try {
    $connect = "mongodb://${username}:${password}@${hostname}:${port}/${db}";
    $m = new Mongo($connect);
    $db = $m->selectDB($db);
    echo "<h2>Collections</h2><ul>";
    $cursor = $db->listCollections();
    $collection_name = "";
    foreach( $cursor as $doc ) {
      echo "<li>" .  $doc->getName() . "</li>";
      $collection_name = $doc->getName();
    }
    echo "</ul>";

    if ( $collection_name != "" ) {
      $collection = $db->selectCollection($collection_name);
      echo "<h2>Documents in ${collection_name}</h2>";
      $cursor = $collection->find();
      foreach( $cursor as $doc ) {
        echo "<pre>";
        var_dump($doc);
        echo "</pre>";
      }
    }
  } catch ( Exception $e ) {
    echo "Exception: ", $e->getMessage(), "\n";
  }
  
?>
</body>
</html>
