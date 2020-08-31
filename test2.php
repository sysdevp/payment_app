<?php
include("config.php");
$sql = "select distinct tbl_BudgetRequst.id, tbl_BudgetRequst.ProjectNum,  tbl_BudgetRequst.Reqid as 'Ref No', tbl_BudgetRequst.SchNo as 'Schedule No',

tbl_BudgetEstimationUpload.LOA_Qty as 'Estimated Qty',

tbl_BudgetEstimationUpload.Amount as 'Estimated Budget',isnull(tbl_BudgetRequst.Requsted_Qty,0) as 'Requested Qty',

isnull(tbl_BudgetRequst.Req_Amount,0) as 'Requested Budget',isnull(tbl_BudgetRequst.Completed_Qty,0) as 'Exceuted Qty',

isnull(tbl_BudgetRequst.Completed_Amount,0) as 'Exceuted Budget'

from tbl_BudgetEstimationUpload

inner join tbl_BudgetRequst on 

tbl_BudgetRequst.ProjectNum=tbl_BudgetRequst.ProjectNum

and cast(tbl_BudgetRequst.SchNo as nvarchar(50)) =cast (tbl_BudgetEstimationUpload.SchNo as nvarchar(50)) 

where tbl_BudgetRequst.ProjectNum=1
";
$result = sqlsrv_query($conn,$sql);
while($row = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC))
{
       print_r($row);

}


echo json_encode($response);

?>