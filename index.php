<?php
require_once('Slim/Slim.php');
require_once('Lite/Lite.php');

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->get('/get_products', 
	  function () {
	    echo json_encode(get_products());
	  }
);

$app->post('/sync_products',
	   function () {
	     echo json_encode(sync_products());
	   }
);

$app->run();
