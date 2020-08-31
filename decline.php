<?php
include("config.php");
include("PHPMailer/class.phpmailer.php");
$projNo="";
$response = array();
if($_GET['RefNo'] && $_GET['Remarks'])
{
    $ref_no = $_GET['RefNo'];
    $sql = "SELECT id FROM tbl_BudgetRequst WHERE ReqId='".$ref_no."'";
    $params = array();
    $options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
    $stmt = sqlsrv_query( $conn, $sql , $params, $options );

    $row_count = sqlsrv_num_rows($stmt);

            if($row_count > 0)
            {
            //  $sql_update = "UPDATE tbl_BudgetRequst set RequestStatus=2 WHERE ReqId='".$ref_no."'";
              $sql_update = "UPDATE tbl_BudgetRequst set RequestStatus=2 ,Remarks='".$_GET['Remarks']."' WHERE ReqId='".$ref_no."'";
              //  print_r($sql_update);
                $update = sqlsrv_query($conn,$sql_update);
            
                $response[] = array( "Status"  =>	"1",
                                    "Message"  => "Budget Declined"  
                                 );

//get project no
$get_projno_sql = "SELECT * FROM tbl_BudgetRequst WHERE ReqId='".$ref_no."'";
                        
$projno_result = sqlsrv_query($conn, $get_projno_sql);

while($projno_row = sqlsrv_fetch_array($projno_result, SQLSRV_FETCH_ASSOC)) 
{
   $projNo = $projno_row['ProjectNum'];
   //print_r($device_id);
   $notification_content = "Payment Declined for  Project No:'".$projNo."'";
}


                                 //FIREBASE PUSH NOTIFICATION FOR USERS



                        
                        $get_user_sql = "SELECT device_id,Emailid FROM tbl_PaymentReqMaster WHERE UserRole='User'";
                        
                        $user_result = sqlsrv_query($conn, $get_user_sql);
                        
                        while($user_row = sqlsrv_fetch_array($user_result, SQLSRV_FETCH_ASSOC)) 
                        {
                           $device_id = $user_row['device_id'];
                           $email_id[]  = ($user_row['Emailid']!="")?$user_row['Emailid']:"";
                           //print_r($device_id);
                           push_notification_android($device_id,$notification_content);

                        }


            }
            else
            {
            $response[] =   array(  "Status"  =>	"0",
                                    "Message"  => "Budget Declined Failed"  
                                 );
            }


            $name="manoj_p@mazenetsolution.com";
            $pass="kuttymanoj";
        
            $to="chandrika@mazenetsolution.com";
    //        $cc="mukherjeekoushik@gmail.com";
     //       $cc="karthik@mazenetsolution.com";
     //       $bcc="akilsoundariya@mazenetsolution.com"; 
     $cc_recipients  = array("chandrika@mazenetsolution.com","karthik@mazenetsolution.com","akilsoundariya@mazenetsolution.com");
  
     $subject="Budget Declined by Mr. Mani";
            
            $message = "<br><br><b>Budget Declined for project No.'".$projNo."':</b><br>";

    
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
    
    
          if($mail->Send())
          {
    
             
    
          }
     
        }


            echo json_encode($response);

}
function push_notification_android($device_id,$notification_content){

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
              "message" => $notification_content
      ),
      'notification' => array(
          'title' => 'Budget Declined',
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
?>