<?php

function connect_to_db() {
  global $conn;

  $db_host = '10.208.17.75';
  $db_user = 'devadmin';
  $db_pass = 'devadm!n123';
  $db_database = 'labs';
  if ($conn = mysqli_connect($db_host, $db_user, $db_pass, $db_database)) {
    return true;
  }
  return false;
}

function get_products($pids = NULL) {
  global $conn;

  connect_to_db();
  $sql = "
SELECT p.product_id, p.title, v.title AS variant_title, v.sku, v.price, v.qty, s.store_name, c.channel, ven.vendor 
  FROM product AS p INNER JOIN variant AS v ON p.product_id=v.product_id
  JOIN store AS s ON v.store_id = s.store_id
  JOIN channel AS c ON c.channel_id=s.channel_id
  JOIN vendor AS ven ON ven.vendor_id=s.vendor_id
  WHERE 1
";

  if ($pids && preg_match('/^(\d|,)+$/', $pids)) {
    $sql .= ' AND p.product_id IN ('.$pids.')';
  }
  $query = mysqli_query($conn, $sql);
  $products = [];
  while ($product = mysqli_fetch_object($query)) {
    if (empty($products[$product->product_id])) {
      $p = new stdClass();
      $p->title = $product->title;
      $products[$product->product_id] = $p;
    }
    $v = new stdClass();
    $v->channel = $product->channel;
    $v->vendor = $product->vendor;
    $v->store_name = $product->store_name;
    $v->title = $product->variant_title;
    $v->sku = $product->sku;
    $v->price = $product->price;
    $v->qty = $product->qty;
    $products[$product->product_id]->variants[] = $v;
  }

  return $products;
}

function sync_products($vid=NULL, $cid=NULL) {
  global $conn;
  
  connect_to_db();
  $sql = "
SELECT c.channel_id, c.channel, c.products_api, s.store_id, s.store_name, s.api_key, s.api_password, s.access_token, v.vendor
  FROM store AS s
  JOIN vendor AS v ON s.vendor_id = v.vendor_id
  JOIN channel AS c ON s.channel_id = c.channel_id
  WHERE 1
";

  if ($vid && preg_match('/^\d+$/', $vid)) {
    $sql .= ' AND s.vendor_id='.$vid;
  }
  if ($cid && preg_match('/^\d+$/', $cid)) {
    $sql .= ' AND s.channel_id='.$cid;
  }

  // actually, Vend's API can be accessed using a private access-token without expiration!
  $query = mysqli_query($conn, $sql);
  while ($row = mysqli_fetch_assoc($query)) {
    extract($row);
    $api_url = str_replace(['{STORE_NAME}', '{API_KEY}', '{API_PASSWORD}', '{ACCESS_TOKEN}'],
			   [$store_name, $api_key, $api_password, $access_token],
			   $products_api);
    $store = new stdClass();
    $store->store_name = $store_name;
    $store->vendor = $vendor;
    $store->channel = $channel;
    $store->api_url = $api_url;
    $stores[$store_id] = $store;
  }
  
  $stores_apis = array_combine(array_keys($stores),
			       array_map(function($x) {return $x->api_url;}, $stores));
  $api_results = fetch_products($stores_apis);

  $results = [];
  foreach ($stores as $store_id => $store) {
    $result = new stdClass();
    $result->store_name = $store->store_name;
    $result->vendor = $store->vendor;
    $result->channel = $store->channel;

    if (!empty($api_results[$store_id])) {
      $channel_products = json_decode($api_results[$store_id]);
      $products = translate_channel_products($store->channel, $channel_products);
      save_products($store_id, $products);
      $result->status = 'succeeded';
    }
    else {
      $result->status = 'failed';
    }
    $results[$store_id] = $result;
  }

  return $results;
}


function fetch_products($stores=NULL) {
  if (empty($stores)) return NULL;

  $curl_arr = [];
  $master = curl_multi_init();
  foreach ($stores as $store_id => $api_url) {
    $curl_arr[$store_id] = curl_init($api_url);
    curl_setopt($curl_arr[$store_id], CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_arr[$store_id], CURLOPT_TIMEOUT, 1000);
    curl_multi_add_handle($master, $curl_arr[$store_id]);
  }
  
  do {
    curl_multi_exec($master, $running);
  } while($running > 0);
  
  $results = [];
  foreach ($curl_arr as $store_id => $curl_action) {
    $result = curl_multi_getcontent($curl_action);
    $results[$store_id] = $result;
  }
  return $results;
}

function translate_channel_products($channel, $channel_products) {
  $translator = 'translate_'.$channel.'_products';
  $products = call_user_func($translator, $channel_products);
  return $products;
}

function translate_shopify_products($channel_products) {
  $products = [];
  foreach ((array)$channel_products->products as $channel_product) {
    $products[] = translate_shopify_product($channel_product);
  }
  return $products;
}

function translate_shopify_product($channel_product) {
  $product = new stdClass();
  $product->title = $channel_product->title;
  
  foreach ((array)$channel_product->variants as $ch_variant) {
    $variant = new stdClass();
    $variant->title = $product->title; // Shopify: does NOT separate title for each variant
    $variant->sku = $ch_variant->sku;
    $variant->price = $ch_variant->price;
    $variant->qty = $ch_variant->inventory_quantity;
    $product->variants[] = $variant;
  }
  return $product;
}

function translate_vend_products($channel_products) {
  $products = [];
  foreach ((array)$channel_products->products as $channel_product) {
    if ($channel_product->handle != 'vend-discount') {
      $product = translate_vend_product($channel_product);

      // check whether product ID is already there
      if (empty($products[$product->handle])) {
	$p = new stdClass();
	$p->title = $product->title;
	$products[$product->handle] = $p;
      }
      $products[$product->handle]->variants[] = $product->variant;    
    }
  }

  return $products;
}

function translate_vend_product($channel_product) {
  $product = new stdClass();
  $product->title = $channel_product->base_name;
  $product->handle = $channel_product->handle;
  
  $variant = new stdClass();
  $variant->title = $channel_product->name; // Vend: product's title specific to a variant
  $variant->sku = $channel_product->sku;
  $variant->price = $channel_product->price;
  $variant->qty = $channel_product->inventory[0]->count; // inventory qty: may be float number
  $product->variant = $variant;

  return $product;
}

function save_products($store_id, $products) {
  foreach ((array)$products as $product) {
    save_product($store_id, $product);
  }
}

function save_product($store_id, $product) {
  global $conn;

  // if SKU is already there, then update this product in DB
  $skus = array_map(function($x) {return $x->sku;}, $product->variants);
  $sql = "
SELECT product_id 
  FROM variant AS v
  JOIN store AS s ON v.vendor_id=s.vendor_id
  WHERE store_id=$store_id AND sku IN ('". implode("','", $skus) ."') 
  ORDER BY updated DESC
  LIMIT 1
";
 
  $query = mysqli_query($conn, $sql);
  if ($obj = mysqli_fetch_object($query)) {
    $product_id = $obj->product_id;
  }
  else {
    // otherwise, save this product as a new product into DB
    // first, insert the product into DB and get its product ID
    $sql = "INSERT INTO product (title) VALUE ('{$product->title}')";
    $query = mysqli_query($conn, $sql);
    $product_id = mysqli_insert_id($conn);
  }

  // then, synch (replace/insert) the product's variants into DB
  $v = [];
  foreach ((array)$product->variants as $variant) {
    $v[] = "($product_id, $store_id, '" . implode("', '", json_decode(json_encode($variant), true)) . "')";
  }
  $variants = implode(',', $v);
  $sql = "REPLACE INTO variant (product_id, store_id, title, sku, price, qty) VALUE $variants";
  $query = mysqli_query($conn, $sql);
}
