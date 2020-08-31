<?php

include("config.php");
include("class.phpmailer.php");

$response = array();

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
		$schNo[]=array($row['SchNo']);
		$reqAmt[]=array($row['Req_Amount']);
		}

	echo "Project Num: ".$projNo."<br>";

	echo json_encode($schNo)."<br>";
	
	echo json_encode($reqAmt)."<br>";

                           $sql_update = "UPDATE tbl_BudgetRequst set RequestStatus=1,Remarks='".$_GET['Remarks']."' WHERE id='".$id."'";
                           //  print_r($sql_update);
                            

		  //$update = mysqli_query($conn, $sql_update);
		$update = sqlsrv_query($conn, $sql_update);
                        
                            	$response[] = array("Status"=>"1", "Message"  => "Budget Approved");


                        }
                        else
                        {
                        $response[] =   array("Status" =>"0", "Message"  => "Budget Approved Failed");
                        }

           }  

sendMail1();

}


function sendMail1()
{
global $schNo, $reqAmt, $projNo, $row_count;
$name="manoj_p@mazenetsolution.com";
        $pass="kuttymanoj";
    
        $to="mohammedsuhail@mazenetsolution.com";
        $cc="mukherjeekoushik@gmail.com";
        $subject="Payment Request Approved by Mr. Mani";
        
        $message = "<br><br><b>".$row_count." (no. of schedules approved) schedules payment approved for project No.:".$projNo."</b><br>";
        $message.= "<br>"."Schedule and Amount  Details:"."<br>";
        $message.= "<br><table><tr><th>"."Schedule No."."</th>	    	   <th>"."Amount"."</th></tr>";
	
	  foreach($schNo as $value) 
	  {
    	  $message.= "<tr><td>".$value."</td>";
	  }

	  foreach($reqAmt as $value) 
	  {
	  $message.= "<td>".$value."</td></tr>";
	  }
	
	  $message.="</table>";
	
	  $message.="</table>";

	  $fromdept="";



	
        $mail=new PHPMailer();



        $mail->CharSet="utf-8";
        $mail->IsSMTP();
        $mail->SMTPAuth=true;
        $mail->Username=$name;
        $mail->Password=$pass;
        $mail->SMTPSecure="ssl"; // SSL FROM DATABASE
        $mailer->Host = 'smtp.gmail.com';	// Host FROM DATABASE
        $mail->Port="465";	// Port FROM DATABASE
        $mail->SMTPOptions = array(

'ssl' => array(
'verify_peer' => false,
'verify_peer_name' => false,
'allow_self_signed' => true
)
);

        $mail->setFrom($name, $fromdept);
        $mail->AddAddress($to);
        $mail->addCC($cc);
        //$mail->addCC($bcc);
        $mail->Subject=$subject;
        $mail->IsHTML(true);
        $mail->Body=$message;


      if($mail->Send())
      {
         echo "MAIL SENT.";
         

      }
    

}

echo "<br>".json_encode($response);
?>
