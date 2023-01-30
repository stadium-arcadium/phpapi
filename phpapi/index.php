<?php
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Headers: access");
	header("Access-Control-Allow-Methods: GET, POST, PUT");
	header("Content-Type: application/json; charset=UTF-8");
	header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
	
	require 'connection.class.php';
	require 'auth/AuthMiddleware.php';
	
	$redis = new Redis();
	$redis->connect('127.0.0.1', 6379);
	$redis->auth('REDIS_PASSWORD');

	$max_calls_limit = 10;
	$time_period = 3600;
	$total_user_calls = 0;
	
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$user_ip_address = $_SERVER['HTTP_CLIENT_IP'];
	}elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$user_ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$user_ip_address = $_SERVER['REMOTE_ADDR'];
	}
	if (!$redis->exists($user_ip_address)) {
		$redis->set($user_ip_address, 1);
		$redis->expire($user_ip_address, $time_period);
		$total_user_calls = 1;
	} else {
		$redis->INCR($user_ip_address);
		$total_user_calls = $redis->get($user_ip_address);
	if ($total_user_calls > $max_calls_limit) {
		echo "User " . $user_ip_address . " limit exceeded.";
		exit();
	}
	}
	$allHeaders = getallheaders();
	$db_connection = new Database();
	$conn = $db_connection->dbConnection();
	$auth = new Auth($conn, $allHeaders);
	echo json_encode($auth->isValid());
	$auth->isValid();

	['success'=>$isAuthenticated] = $auth->isValid();
	
	$route 	= $_SERVER['REQUEST_URI'];
	$method = $_SERVER['REQUEST_METHOD'];

	$request_method=$_SERVER["REQUEST_METHOD"];
	if (!!$isAuthenticated){
	switch($request_method)
	{
		case 'GET':
		if(!empty($_GET["id"]))
		{
			$id=intval($_GET["id"]);
			get_order($id);
		}
		else
		{
			get_orders();
		}
		break;
		case 'POST':
			post_order();
			break;
		case 'PUT':
			update_order();
			break;
		default:
		// Invalid Request Method
		header("HTTP/1.0 405 Method Not Allowed");
		break;
	}
	} else {
		header("HTTP/1.1 401 Unauthorized");
	}

	function get_orders()
	{
		global $conn;
		$sql="SELECT * FROM `orders`";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$response = array();
		if($stmt->rowCount() > 0)
	    {
			
	    	while($row = $stmt->fetch(PDO::FETCH_ASSOC))
			{
				$response[] = $row;
			}
			$arr_json = array('status' => 200, 'orders' => $response);
			echo json_encode($arr_json);
	    }else{
			$arr_json = array('status' => 404);
			echo json_encode($arr_json);
	    }
	}
	
	function get_order($id){
		global $conn;
		$sql="SELECT * FROM `orders` where id=".$id." limit 1";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$response = array();
		if($stmt->rowCount() > 0)
	    {
	    	while($row = $stmt->fetch(PDO::FETCH_ASSOC))
			{
				$response[] = $row;
			}
			$arr_json = array('status' => 200, 'orders' => $response);
			echo json_encode($arr_json);
	    }else{
			$arr_json = array('status' => 404);
			echo json_encode($arr_json);
	    }
	}
	
	function post_order(){
		global $conn;
		$data = json_decode(file_get_contents('php://input'), true);
		$title=$data["title"];
		$initialAddress=$data["initialAddress"];
		$shippingAddress=$data["shippingAddress"];
		$sql="INSERT INTO `orders` SET title='".$title."', initialAddress='".$initialAddress."', shippingAddress='".$shippingAddress."'";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		
	    if($stmt->rowCount() > 0)
		{
			$response=array(
			  'status' => 1,
			  'status_message' =>'Order Added Successfully.'
			);
		  }
		  else
		  {
			$response=array(
			  'status' => 0,
			  'status_message' =>'Order Addition Failed.'
			);
		  }
		  header('Content-Type: application/json');
		  echo json_encode($response);
		
	 	
	}
	
	function update_order($id)
	{
		global $conn;
		$data = json_decode(file_get_contents('php://input'), true);
		$title=$data["title"];
		$initialAddress=$data["initialAddress"];
		$shippingAddress=$data["shippingAddress"];
		$sql="UPDATE `orders` SET title='".$title."', initialAddress='".$initialAddress."', shippingAddress='".$shippingAddress."' WHERE id=".$id;
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		if($stmt->rowCount() > 0)
		{
		  $response=array(
			'status' => 1,
			'status_message' =>'Employee Updated Successfully.'
		  );
		}
		else
		{
		  $response=array(
			'status' => 0,
			'status_message' =>'Employee Updation Failed.'
		  );
		}
		header('Content-Type: application/json');
		echo json_encode($response);
	}

	
	
?>