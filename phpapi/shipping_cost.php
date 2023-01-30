<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: access");
    header("Access-Control-Allow-Methods:  POST");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    require 'connection.class.php';
    require 'auth/AuthMiddleware.php';

    $allHeaders = getallheaders();
    $db_connection = new Database();
    $conn = $db_connection->dbConnection();
    $auth = new Auth($conn, $allHeaders);
    echo json_encode($auth->isValid());
    $auth->isValid();
    ['success'=>$isAuthenticated] = $auth->isValid();

    
	$request_method=$_SERVER["REQUEST_METHOD"];
	if (!!$isAuthenticated){
	switch($request_method)
	{
		case 'POST':
			post_order();
			break;
		
		default:
		// Invalid Request Method
		header("HTTP/1.0 405 Method Not Allowed");
		break;
	}
	} else {
		header("HTTP/1.1 401 Unauthorized");
	}
  
    function post_shipping(){
		global $conn;
		$data = json_decode(file_get_contents('php://input'), true);
		$initialAddress=$data["initialAddress"];
		$shippingAddress=$data["shippingAddress"];


        $shippingCost = setShippingCost($initialAddress, $shippingAddress);
        
		$sql="INSERT INTO `shippings` SET initialAddress='".$title."', shippingAddress='".$shippingAddress."', shippingCost='".$shippingCost."'";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		
	    if($stmt->rowCount() > 0)
		{
			$response=array(
			  'status' => 1,
			  'status_message' =>'Shipping Added Successfully.',
              'shippingCost' => $shippingCost
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
   function setShippingCost($shippingAddress, $deliveryAddress) {
        $pricePerKilometere = 30;
        $shippingCoordinates = getAddressCoordinates($shippingAddress);
        $deliveryAddress = getAddressCoordinates($deliveryAddress);
        $distance = distance($shippingCoordinates, $deliveryCoordinates);
        $shippingCost = $distance * $pricePerKilometere;
        $shippingCost = $shippingCost.'$';
        return $shippingCost;
        
    }

   function distance($shippingCoordinates, $deliveryCoordinates) {
        ['lat'=>$lat1, 'long'=>$lon1] = $shippingCoordinates;
        ['lat'=>$lat2, 'long'=>$lon2] = $deliveryCoordinates;
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
          return 0;
        }
        else {
          $theta = $lon1 - $lon2;
          $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
          $dist = acos($dist);
          $dist = rad2deg($dist);
          $miles = $dist * 60 * 1.1515;
          $unit = strtoupper($unit);
      
          if ($unit == "K") {
            return ($miles * 1.609344);
          } else if ($unit == "N") {
            return ($miles * 0.8684);
          } else {
            return $miles;
          }
        }
      }

  function getAddressCoordinates($address){
        $prepAddr = str_replace(' ','+',$address);
        $geocode=file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false');
        $output= json_decode($geocode);
        $latitude = $output->results[0]->geometry->location->lat;
        $longitude = $output->results[0]->geometry->location->lng;
        $coordinates = ['lat' => $latitude, 'lon'=> $longtitude];
        return $coordinates;
    }

?>