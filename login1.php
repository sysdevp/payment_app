<?php 

include("config.php");

$response =array();
//if($_GET['Login_Name']=="mani@mazenetsolution.com" || $_GET['Login_Name']=="akilsoundariya@mazenetsolution.com")

if(isset($_GET['Login_Name'])!="" && isset($_GET['Password'])!="" && isset($_GET['device_id'])!="")
{
    $login_name  = $_GET['Login_Name'];
    $password    = $_GET['Password']; 
 
    // $user_role   = $_GET['UserRole'];
   $device_id=$_GET['device_id'];
   $status=0;
   $message="";
   $userRole="";

    $sql = "{CALL spSel_Email_Login_Authenticate_M(?, ?, ?, ? OUTPUT, ? OUTPUT, ? OUTPUT)}";
   
    $params = array( 
                 array($login_name, SQLSRV_PARAM_IN),
                 array($password, SQLSRV_PARAM_IN),
	 array($device_id, SQLSRV_PARAM_IN),
	 array( $status, SQLSRV_PARAM_OUT),
	 array($message, SQLSRV_PARAM_OUT),
	 array($userRole, SQLSRV_PARAM_OUT),
               );

    $options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
    
    $stmt = sqlsrv_query( $conn, $sql , $params, $options);
    
       //$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
   
//print_r($row);

    if ($stmt==true)
    {
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
	{
    	$response['status'] = $row[0]; 
	$response['details'] = $row[1]; 
	$response['user_role'] = $row[2];

   	}
    }
    else
     {
        $response['status'] = $row[0];
        $response['details'] = $row[1];
        $response['user_role'] = $row[2];

     }
}

else
{
      // echo $row_count;

    $response['status'] = '0';
    $response['details'] = 'Login Failed Username or Password is incorrect'; 

}


echo "<br><br>".json_encode($response);
//}
?>
