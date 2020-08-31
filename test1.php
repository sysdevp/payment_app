<?php
//$conn = mssql_connect("216.10.240.149","maze_un","LionZebra@123$%","ashokrajd_db_tender_test");

$server = "216.10.240.149";
$connectionInfo = array( "Database"=>"ashokrajd_db_tender_test", "UID"=>"maze_un", "PWD"=>"LionZebra@123$%" );
$conn = sqlsrv_connect( $server, $connectionInfo );

$sql = "SELECT * FROM tbl_BudgetRequst";
   $result = sqlsrv_query($conn, $sql);
   while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    print_r($row);
}
?>