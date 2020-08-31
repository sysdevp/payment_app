<?php 

include("config.php");
$response =array();
//if($_GET['Login_Name']=="mani@mazenetsolution.com" || $_GET['Login_Name']=="akilsoundariya@mazenetsolution.com")
if($_GET['Login_Name']!="" && $_GET['Password']!=""  )
{
    $login_name  = $_GET['Login_Name'];
    $password    = $_GET['Password']; 
   // $device_id   = $_GET['device_id']; 
    // $user_role   = $_GET['UserRole'];
    $sql = "SELECT * FROM tbl_PaymentReqMaster WHERE Emailid='$login_name' AND UserPassword='$password'" ;
    $params = array();
    $options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
    $stmt = sqlsrv_query( $conn, $sql , $params, $options );
    
    $row_count = sqlsrv_num_rows( $stmt );

    $result = sqlsrv_query($conn, $sql);
    $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

//print_r($row);
    if ($row_count > 0)
    {
    $login_id_update = "UPDATE tbl_PaymentReqMaster set device_id='".$_GET['device_id']."' WHERE Emailid='$login_name' AND UserPassword='$password'";    
    $update = sqlsrv_query($conn,$login_id_update);
    $response['status'] = '1'; 
    $response['details'] = 'Login successfull'; 
    $response['user_role'] = $row['UserRole'];

    }
    else{
        $response['status'] = '0';
        $response['details'] = 'Login Failed Username or Password is incorrect'; 
        $response['user_role'] = "";

    }
}

else
{
      // echo $row_count;

    $response['status'] = '0';
    $response['details'] = 'Login Failed Username or Password is incorrect'; 

}


echo json_encode($response);
//}
?>