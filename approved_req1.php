<?php
include("config.php");
$response = array();

$sql = "
select Reqid as 'Ref No',Convert(varchar(11),ReqDate,105) as 'Request Date',ProjectNum as 'Project No',
sum(AmountForSch) as  'Amount Requested',RequestStatus as 'Request Status' 

from tbl_BudgetRequst where RequestStatus=1 or RequestStatus=2

group by Reqid,ReqDate,ProjectNum,RequestStatus  order by Reqid desc
";
$result = sqlsrv_query($conn,$sql);
while($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC))
{
 //   print_r($row);
  //  echo"<br/>";
  $response[]=array(  "Ref No"                   => $row['Ref No'],
                    "Request Date"            =>	$row['Request Date'],
                    "Project No"          =>  $row['Project No'],
                    "Amount Requested"       =>  $row['Amount Requested'],
					"Request Status"		=> $row['Request Status']	
     );

}
echo json_encode($response);



?>