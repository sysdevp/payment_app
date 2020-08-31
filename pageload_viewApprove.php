<?php 
include("config.php");
$response = array();
if($_GET['Reqid']!="")
{


//SQL query in respect to MSSQL Server database:

$sql = "select distinct tbl_BudgetRequst.id, tbl_BudgetRequst.ProjectNum, tbl_BudgetRequst.Reqid as 'Ref No', tbl_BudgetRequst.SchNo as 'Schedule No', tbl_BudgetEstimationUpload.LOA_Qty as 'Estimated Qty', tbl_BudgetEstimationUpload.Amount as 'Estimated Budget', tbl_BudgetEstimationUpload.UOM as 'UOM', isnull(tbl_BudgetRequst.Requsted_Qty, 0) as 'Requested Qty', (isnull(tbl_BudgetRequst.Req_Amount, 0)) as 'Requested Budget', sum(isnull(tbl_BudgetRequst_CompeletedQty.Completed_Qty, 0)) as 'Exceuted Qty', sum(isnull(tbl_BudgetRequst_CompeletedQty.Completed_Amount, 0)) as 'Exceuted Budget', isnull(sum(isnull(tbl_BudgetEstimationUpload.Amount, 0))-(sum(isnull(tbl_BudgetRequst_CompeletedQty.Completed_Amount, 0))), 0) as 'Pending_Amount', tbl_BudgetRequst.Request_Remarks as 'Requested Remarks', tbl_BudgetRequst.AmountForSch as 'Amount For Sch', tbl_BudgetRequst.AmountTakesnFromSchNo as 'Amount Taken From SchNo' from tbl_BudgetEstimationUpload inner join tbl_BudgetRequst on tbl_BudgetRequst.ProjectNum=tbl_BudgetEstimationUpload.ProjectNum and cast(tbl_BudgetRequst.SchNo as nvarchar(50))=cast(tbl_BudgetEstimationUpload.SchNo as nvarchar(50)) left join tbl_BudgetRequst_CompeletedQty on tbl_BudgetEstimationUpload.ProjectNum=tbl_BudgetRequst_CompeletedQty.ProjectNum and cast(tbl_BudgetRequst_CompeletedQty.SchNo as nvarchar(50))=cast (tbl_BudgetEstimationUpload.SchNo as nvarchar(50)) where tbl_BudgetRequst.Reqid= '".$_GET['Reqid']."' and tbl_BudgetRequst.RequestStatus=1 group by  tbl_BudgetRequst.id, tbl_BudgetRequst.ProjectNum,  tbl_BudgetRequst.Reqid , tbl_BudgetRequst.SchNo , tbl_BudgetEstimationUpload.LOA_Qty, tbl_BudgetEstimationUpload.Amount, tbl_BudgetRequst.Requsted_Qty, tbl_BudgetRequst.Req_Amount,tbl_BudgetEstimationUpload.UOM, tbl_BudgetRequst.Request_Remarks, tbl_BudgetRequst.AmountForSch, tbl_BudgetRequst.AmountTakesnFromSchNo";



//SQL query in respect to MySql database:

$sql ="select distinct tbl_BudgetRequst.id,tbl_BudgetEstimationUpload.SchNo 'Schedule No',
(tbl_BudgetEstimationUpload.LOA_Qty) as  'Estimated Qty',
(tbl_BudgetEstimationUpload.Unit_Price) as Unit_Price,
(tbl_BudgetEstimationUpload.Amount) as 'Estimated Budget',
tbl_BudgetEstimationUpload.UOM ,
--isnull(sum(tbl_PaidAmounts.paidAmount),0) as 'PaidAmt',
--(isnull(sum(tbl_PaidAmounts.paidAmount),0)/tbl_BudgetEstimationUpload.unit_price) as PaidQty, 
tbl_BudgetRequst.Request_Remarks,
tbl_BudgetRequst.AmountForSch  ,tbl_BudgetRequst.Requsted_Qty as 'Requested Qty',tbl_BudgetRequst.Req_Amount as 'Requested Budget',
--isnull((isnull((tbl_BudgetEstimationUpload.LOA_Qty),0))-(isnull((tbl_PaidAmounts.paidAmount),0)/tbl_BudgetEstimationUpload.unit_price),0) as 'Pending_Qty',
--isnull((isnull((tbl_BudgetEstimationUpload.Amount),0))-(isnull((tbl_PaidAmounts.paidAmount),0)),0) as 'Pending_Amount',
0 as Pending_Amount,
(isnull(CompeletedQty.Completed_Amount,0) )as 'Exceuted Budget',
(isnull(CompeletedQty.Completed_Qty,0)) as 'Exceuted Qty',
tbl_BudgetRequst.AmountForSch 'AmountForSch',tbl_PaidAmounts.SchedulesUsed 'AmountTakesnFromSchNo'
from tbl_BudgetEstimationUpload 
left join tbl_BudgetRequst on tbl_BudgetEstimationUpload.schno=tbl_BudgetRequst.schno
and tbl_BudgetEstimationUpload.Projectnum=tbl_BudgetRequst.Projectnum
left join tbl_PaidAmounts
on tbl_BudgetRequst.schno=tbl_PaidAmounts.schno
and tbl_BudgetRequst.Projectnum=tbl_PaidAmounts.Projectnum and tbl_BudgetRequst.Reqid=tbl_PaidAmounts.Reqid
left join 
(select ProjectNum,SchNo,sum(isnull(tbl_BudgetRequst_CompeletedQty.Completed_Amount,0) )as 'Completed_Amount',sum(isnull(tbl_BudgetRequst_CompeletedQty.Completed_Qty,0) ) as 'Completed_Qty' from tbl_BudgetRequst_CompeletedQty group by tbl_BudgetRequst_CompeletedQty.ProjectNum,tbl_BudgetRequst_CompeletedQty.SchNo) CompeletedQty
on tbl_BudgetEstimationUpload.ProjectNum=CompeletedQty.ProjectNum and tbl_BudgetEstimationUpload.SchNo=CompeletedQty.SchNo
where tbl_BudgetRequst.Reqid='".$_GET['Reqid']."'  and tbl_BudgetRequst.RequestStatus=1
group by  tbl_BudgetEstimationUpload.SchNo,tbl_BudgetEstimationUpload.unit_price,tbl_BudgetEstimationUpload.LOA_Qty,tbl_BudgetEstimationUpload.Amount,
CompeletedQty.Completed_Amount,CompeletedQty.Completed_Qty,tbl_BudgetRequst.id,tbl_BudgetRequst.AmountForSch,tbl_BudgetRequst.Requsted_Qty,tbl_BudgetRequst.Req_Amount,
tbl_BudgetEstimationUpload.UOM,tbl_BudgetRequst.Request_Remarks,tbl_PaidAmounts.AmountsFromSchedule,tbl_PaidAmounts.SchedulesUsed,tbl_PaidAmounts.PaidAmount,tbl_PaidAmounts.AmountRequested

";



//$result = mysqli_query($conn, $sql);
$result = sqlsrv_query($conn, $sql);
//while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC))
{
   //print_r($row);
  //  echo"<br/>";
 if($row['AmountTakesnFromSchno']=='' || $row['AmountTakesnFromSchno']=='null')
 {
	 $row['AmountTakesnFromSchno'] = '';
 } else {
	 $row['AmountTakesnFromSchno'] = $row['AmountTakesnFromSchno'];
 }
 $response[]=array( "id"                   => $row['id'],
                    "Schedule No"            =>	$row['Schedule No'],
                    "Estimated Qty"          =>  $row['Estimated Qty'],
                    "Estimated Budget"       =>  $row['Estimated Budget'],
                    "Requested Qty"          =>  $row['Requested Qty'],
                    "Requested Budget"       =>  $row['Requested Budget'],
                    "Executed Qty"           =>  $row['Exceuted Qty'],
                    "Executed Budget"        =>  $row['Exceuted Budget'],
                    "Uom"                      =>  $row['UOM'],
                    "Pending Amount"        =>  $row['Pending_Amount'],
	    "Requested Remarks" => $row['Request_Remarks'],
	     "Amount For Sch" => $row['AmountForSch'],
	     "Amount Taken From SchNo" =>$row['AmountTakesnFromSchNo']
                       );
}

}
echo json_encode($response);

?>
