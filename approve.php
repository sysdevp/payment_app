<?php

include("config.php");
$response = array();
$message = array();
$projNo="";
$schNo=array();
$reqAmt=array();

if(isset($_GET['id']) && isset($_GET['Remarks']))
{

echo "<br>";
echo $_GET['id'];
echo "<br>";
echo $_GET['Remarks'];
echo "<br>";


    $explode = explode(",",$_GET['id']);

            for ($x = 0; $x < count($explode); $x++)
             {
                $id = $explode[$x];
                $sql = "SELECT id FROM tbl_BudgetRequst WHERE id='".$id."'";
                $params = array();
                $options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );

	//$stmt = mysqli_query( $conn, $sql);
                $stmt = sqlsrv_query( $conn, $sql , $params, $options );

	//$row_count = mysqli_num_rows($stmt);
               $row_count = sqlsrv_num_rows($stmt);

                        if($row_count > 0)
                        {
                           //$sql_update = "UPDATE tbl_BudgetRequst set RequestStatus=1 WHERE id='".$id."'";
	
 	            $sql_fetch="select ProjectNum, SchNo, Req_Amount from tbl_BudgetRequst where id='".$id."'";

		//$fetch_result = mysqli_query($conn, $sql_fetch);
	            $fetch_result = sqlsrv_query($conn, $sql_fetch);

	              while($row = sqlsrv_fetch_array($fetch_result, SQLSRV_FETCH_ASSOC))
		//while($row = mysqli_fetch_array($fetch_result, MYSQLI_ASSOC))
		{
		$projNo=$row['ProjectNum'];
		$schNo[]=$row['SchNo'];
		$reqAmt[]=$row['Req_Amount'];
		// $schNo[]=array($row['SchNo']);
		// $reqAmt[]=array($row['Req_Amount']);
		}

	echo "Project Num: ".$projNo."<br>";

	echo json_encode($schNo)."<br>";
	
	echo json_encode($reqAmt)."<br>";

                           $sql_update = "UPDATE tbl_BudgetRequst set RequestStatus=1, Remarks='".$_GET['Remarks']."' WHERE id='".$id."'";
                           //  print_r($sql_update);
                            

		 //$update = mysqli_query($conn, $sql_update);
		$update = sqlsrv_query($conn, $sql_update);
                        
                            	$response[] = array("Status"=>"1", "Notification Title"  => "Budget Approved","Notification Content" => "'".$row_count."'row_count.schedules approved for Project No:'".$projNo."'");


                        }
                        else
                        {
                        $response[] =   array("Status" =>"0", "Message"  => "Budget Approved Failed");
                        }

           }  

sendMail();

}

function sendMail()
{

global $projNo;
global $schNo;
global $reqAmt;
global $row_count;



	 $to = "koushik@mazenetsolution.com";
        	 $subject = "Payment Request Approved by Mr. Mani";
         
         	 $message = "<br><br><b>".$row_count." (no. of schedules approved) schedules payment approved for project No.:".$projNo."</b><br>";
        	 $message .= "<br>"."Schedule and Amount  Details:"."<br>";
                 $message .= "<br><table><tr><th>"."Schedule No."."</th><th>"."Amount"."</th></tr>";
	
	foreach($schNo as $value) 
	{
    	$message.= "<tr><td>".$value."</td>";
	}

	foreach($reqAmt as $value) 
	{
		//$message.= "<td>".$reqAmt[$ctr]."</td></tr>";
		$message.= "<td>".$value."</td></tr>";
	}
	
	$message.="</table>";

	echo "<br>".$message."<br>";

	 $header = "From: mukherjeekoushik@gmail.com \r\n";
        	
        	 $header .= "MIME-Version: 1.0\r\n";
        	 $header .= "Content-type: text/html\r\n";
         
        	 $result = mail ($to, $subject, $message, $header);
         
	echo "<br>".$result."<br>";

        	 if($result == true ) 
	 {
            		echo "Email sent successfully";
        	 }	
	else 
	 {
           		 echo "Email could not be sent.....";
        	 }
}

//echo "<br>".json_encode($response);
echo json_encode($response);
//echo json_encode($message);

?>