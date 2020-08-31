<?php
//$conn = mssql_connect("216.10.240.149","maze_un","LionZebra@123$%","ashokrajd_db_tender_test");

$server = "216.10.240.149";
$connectionInfo = array( "Database"=>"ashokrajd_db_tender_test", "UID"=>"maze_un", "PWD"=>"LionZebra@123$%" );
$conn = sqlsrv_connect( $server, $connectionInfo );
print_r($_GET['Login_Name']);

$sql = "SELECT * FROM Tbl_User_Master";
$params = array();
$options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
$stmt = sqlsrv_query( $conn, $sql , $params, $options );

$row_count = sqlsrv_num_rows( $stmt );
   
if ($row_count === false)
   echo "Error in retrieveing row count.";
else
   echo $row_count;
   $result = sqlsrv_query($conn, $sql);
   while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    print_r($row);
}
?>