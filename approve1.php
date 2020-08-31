<?php

include("config.php");
include("PHPMailer/class.phpmailer.php");

$response = array();

$projNo="";
//$req_amount="";
$refNo="";
$schNo=array();
$reqAmt=array();
 
if(isset($_GET['id']) && isset($_GET['Remarks']) && isset($_GET['PaidAmt']) && isset($_GET['PaidQty']) )
{

 
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

                        // if($row_count > 0)
                        // {
                           //$sql_update = "UPDATE tbl_BudgetRequst set RequestStatus=1 WHERE id='".$id."'";
	
 	           // $sql_fetch="select ProjectNum, SchNo, Req_Amount from tbl_BudgetRequst where id='".$id."'";         
                  $sql_fetch="select ProjectNum, SchNo, AmountForSch as Req_Amount from tbl_BudgetRequst  where id='".$id."'";

		//$fetch_result = mysqli_query($conn, $sql_fetch);
	               $fetch_result = sqlsrv_query($conn, $sql_fetch);

	              while($row = sqlsrv_fetch_array($fetch_result, SQLSRV_FETCH_ASSOC))
	            //while($row = mysqli_fetch_array($fetch_result, MYSQLI_ASSOC))
		{
		$projNo=$row['ProjectNum'];
		$schNo[]=$row['SchNo'];
                $reqAmt[]=$row['Req_Amount'];
  //              $req_amount = $row['Req_Amount'];
//                $refNo = $row['ReqId'];
		}

	 "Project Num: ".$projNo;

	 json_encode($schNo);
	
	 json_encode($reqAmt);

                       // $sql_update = "UPDATE tbl_BudgetRequst set RequestStatus=1,Remarks='".$_GET['Remarks']."',PaidAmt='".$_GET['PaidAmt']."',PaidQty='".$_GET['PaidQty']."' WHERE id='".$id."'";
                              $sql_update = "UPDATE tbl_BudgetRequst set RequestStatus=1,Remarks='".$_GET['Remarks']."' WHERE id='".$id."'";
                        $update = sqlsrv_query($conn, $sql_update);
                        
                        $response[] = array("Status"=>"1", "Message"  => "Budget Approved");
                        
                       
                        //FIREBASE PUSH NOTIFICATION FOR USERS
                        $push_message = array("Notification Title:" => "Budget Approved","Notification Content " =>"'".$row_count."'schedules approved for Project No:'".$projNo."'");
                        $notification_content = "'".$row_count."'Schedules Approved for  Project No:'".$projNo."'";
                        $get_user_sql = "SELECT device_id,Emailid FROM tbl_PaymentReqMaster WHERE UserRole='User'";
                        
                        $user_result = sqlsrv_query($conn, $get_user_sql);
                        
                        while($user_row = sqlsrv_fetch_array($user_result, SQLSRV_FETCH_ASSOC)) 
                        {
                           $device_id = $user_row['device_id'];

                           $email_id[]  = ($user_row['Emailid']!="")?$user_row['Emailid']:"";
                           //print_r($device_id);
                           push_notification_android($device_id,$push_message,$notification_content);

                        }


                        



                        // }
                        // else
                        // {
                        // $response[] =   array("Status" =>"0", "Message"  => "Budget Approved Failed");
                        // }

           }  
          // print_r($email_id);
          //$implode = implode(",",$email_id);
        //  print_r($implode);
		 

        $name="manoj_p@mazenetsolution.com";
        $pass="kuttymanoj";
    
       $cc_recipients  = array("chandrika@mazenetsolution.com","karthik@mazenetsolution.com","akilsoundariya@mazenetsolution.com");
		//$cc_recipients  = array("l.chandrika8@gmail.com");
      //  print_r($cc_recipients);
        //$to= $cc_recipients;
       // $cc= $cc_recipients;
       // $bcc="akilsoundariya@mazenetsolution.com";   
        $subject="Payment Request Approved by Mr. Mani";
        
        $message = "<br><br><b>".$row_count." (no. of schedules approved) schedules payment approved for project No.:".$projNo."</b><br>";
        $message.= "<br>"."Schedule and Amount  Details:"."<br>";
        $message.= "<br><table><tr><th>"."Schedule No."."</th>	    	   <th>"."Amount"."</th></tr><br><br>";
		
		
		
	
	  foreach($schNo as $value) 
	  {
    	  $message.= "<tr><td style='border: 1px solid #ccc;'>".$value."</td>";
	  }

	  foreach($reqAmt as $value) 
	  {
	  $message.= "<td style='border: 1px solid #ccc;'>".$value."</td></tr>";
	  }
	  
	  
	  
	  // $message.= "<br><br><br>
		// <tr>
		// <td style='border: 1px solid #ccc;'>Payment Req Ref No</td><td style='border: 1px solid #ccc;'>$payment_no</td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Date</td><td style='border: 1px solid #ccc;'>$date</td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Project</td><td style='border: 1px solid #ccc;'> $project</td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Stage</td><td style='border: 1px solid #ccc;'>$stage</td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Payable To</td><td style='border: 1px solid #ccc;'>$payable_to</td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Description of Payment</td><td style='border: 1px solid #ccc;'>$dis_amount</td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Sch No</td><td style='border: 1px solid #ccc;'>$sch_no</td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Loa Qty</td><td style='border: 1px solid #ccc;'>$loa_qty</td ></tr><tr>
		// <td style='border: 1px solid #ccc;'>Claimable Qty & Amt</td><td style='border: 1px solid #ccc;'>$claim_amt</td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Budgeted Amount</td><td style='border: 1px solid #ccc;'>$budget_amt</td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Completed Qty</td><td style='border: 1px solid #ccc;'> $completed_qty</td ></tr><tr>
		// <td style='border: 1px solid #ccc;'>Billed Qty as on Date</td><td style='border: 1px solid #ccc;'>$billed_qty_as_on_date</td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Up to Paid</td><td style='border: 1px solid #ccc;'></td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Today Payable</td><td style='border: 1px solid #ccc;'></td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Balance After Payable</td><td style='border: 1px solid #ccc;'></td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Mode of Payment</td><td style='border: 1px solid #ccc;'></td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Remarks</td><td style='border: 1px solid #ccc;'></td></tr><tr>
		// <td style='border: 1px solid #ccc;'>Budget Remarks</td><td style='border: 1px solid #ccc;'></td>
		
		// </tr>";
	
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
        $mail->Host="smtp.gmail.com";	// Host FROM DATABASE
        $mail->Port="465";	// Port FROM DATABASE
        $mail->SMTPOptions = array(

'ssl' => array(
'verify_peer' => false,
'verify_peer_name' => false,
'allow_self_signed' => true
)
);
foreach($email_id as $value)
{
   // print_r($value);
        $mail->setFrom($name, $fromdept);
        $mail->AddAddress($value);
        foreach($cc_recipients as $values)
        {
           // print_r(values);

            $mail->addCC($values);
        }
      //  $mail->addBCC($bcc);
        $mail->Subject=$subject;
        $mail->IsHTML(true);
        $mail->Body=$message;


      
 
    }
if($mail->Send())
      {

         

      }
    



}

function push_notification_android($device_id,$push_message,$notification_content){

        //API URL of FCM
        $url = 'https://fcm.googleapis.com/fcm/send';
    
        /*api_key available in:
        Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key*/   
         $api_key = 'AAAA7j9M-AQ:APA91bGDfKNmri8mlhGHztoE-dNrzdRw3_xbbLKSXfGIi6s5ozEVKhzKrPAfsJvjpZgj8LMI5iEI5fdfO-adf0DI4vUHhG3qrO0x6QJP-uBr5PswEWl2evgg_5gaDioWeKVhwT80QWMr';
                    
        $fields = array (
            'registration_ids' => array (
                    $device_id
            ),
            'data' => array (
                    "message" => $push_message
            ),
            'notification' => array(
                'title' => 'Budget Approved',
                'body' => $notification_content
            )
        );
    
        //header includes Content type and api key
        $headers = array(
            'Content-Type:application/json',
            'Authorization:key='.$api_key
        );
                    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $results = curl_exec($ch);
        if ($results === FALSE) {
            die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $results;
       
    }
   // $to ="exx91wdCQCy3HP4qmlOqjY:APA91bHdoYKS4hsHPhIhuveebsVg1ovQ1gelmHz-Un65UNGTQliUrL02bjpBcc03egeFNJKs4RmCCdm_X6xBHW5tFALdDoekzBLGJ0dZWUoBK9He6fbc1phqnqMZyb5g2IWUJi2bz5cD";
    //$data = array("message"=>"Tester");
   // print_r(push_notification_android($device_id,$push_message));

  
echo json_encode($response);


?>
