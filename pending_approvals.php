<?php 

include("config.php");
$sql = "SELECT ReqId,Convert(varchar(11),ReqDate,105) as RequestDate,ProjectNum,SUM(AmountForSch) as ReqAmount FROM tbl_BudgetRequst WHERE RequestStatus=0 GROUP BY ReqId,ReqDate,ProjectNum
order by ReqId desc";
    $result = sqlsrv_query($conn, $sql);
    while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC))
    {
      // echo $row['Reqid'];
         //print_r($row);
       $response[]=array("Ref No"           =>	$row['ReqId'],
                        "Request Date"      =>  $row['RequestDate'],
                        "Project No"        =>  $row['ProjectNum'],
                        "Amount Requested"  =>  $row['ReqAmount']
                           );



        }
        echo json_encode($response);

?>