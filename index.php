<?php
require_once('Slim/Slim.php');
require_once('Lite/Lite.php');

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->get('/api/products', 
	  function () {
	    echo json_encode(get_products());
	  }
);

$app->get('/api/products/:pids', 
	  function ($pids) {
	    echo json_encode(get_products($pids));
	  }
);

$app->post('/api/sync',
	   function () {
	     echo json_encode(sync_products());
	   }
);

$app->post('/api/sync/vendor/:vid',
	   function ($vid) {
	     echo json_encode(sync_products($vid, NULL));
	   }
);

$app->post('/api/sync/channel/:cid',
	   function ($cid) {
	     echo json_encode(sync_products(NULL, $cid));
	   }
);

$app->post('/api/sync/vendor/:vid/channel/:cid',
	   function ($vid, $cid) {
	     echo json_encode(sync_products($vid, $cid));
	   }
);

$app->run();
