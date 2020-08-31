<?php
//$conn = mssql_connect("216.10.240.149","maze_un","LionZebra@123$%","ashokrajd_db_tender_test");
include("config.php");
if($_GET['Reqid'])
{
//echo "jo";
$sql = "SELECT ProjectNum,sum(AmountForSch) as Description FROM tbl_BudgetRequst WHERE Reqid='".$_GET['Reqid']."' GROUP BY ProjectNum";
$result = sqlsrv_query($conn, $sql);
// print_r($result);
$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
//print_r($row['Description']);
$notification_content = "Project No:'".$row['ProjectNum']."' Description:'".$row['Description']."'";
   // while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
   //    //$device_id = $row['device_id'];
   //    print_r($row);
   // }

//print_r($notification_content);

                        //FIREBASE PUSH NOTIFICATION FOR USERS
                        $get_user_sql = "SELECT * FROM tbl_PaymentReqMaster WHERE UserRole='Admin'";
                        
                        $user_result = sqlsrv_query($conn, $get_user_sql);
                        
                        while($user_row = sqlsrv_fetch_array($user_result, SQLSRV_FETCH_ASSOC)) 
                        {
                           $device_id = $user_row['device_id'];
                           //print_r($device_id);
                           push_notification_android($device_id,$notification_content);

                        }
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
           'title' => 'Payment Requested',
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
//print_r(push_notification_android($device_id,$notification_content));
?>