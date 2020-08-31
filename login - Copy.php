<?php 

include("config.php");
$response =array();
if($_GET['Login_Name']=="mani@mazenetsolution.com" || $_GET['Login_Name']=="akilsoundariya@mazenetsolution.com")
{
    $login_name  = $_GET['Login_Name'];
    $sql = "SELECT * FROM Tbl_User_Master WHERE Login_Name='$login_name'";
    $params = array();
    $options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
    $stmt = sqlsrv_query( $conn, $sql , $params, $options );
    
    $row_count = sqlsrv_num_rows( $stmt );
    //echo $row_count;  

    if ($row_count >= 0)
    {
    $response['status'] = '1'; 
    $response['details'] = 'Login successfull'; 

    }
}

else
{
       echo $row_count;

    $response['status'] = '0';
    $response['details'] = 'User Not Exist'; 

}


echo json_encode($response);
//}
?>