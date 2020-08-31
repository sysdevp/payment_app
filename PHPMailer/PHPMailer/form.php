<?php  
include("PHPMailer/class.phpmailer.php");
if(isset($_REQUEST['submit1']))
{
     
  if(isset($_REQUEST['g-recaptcha-response'])){
   
     $captcha=$_REQUEST['g-recaptcha-response'];
  }
  
  $secretKey = "6Lf67dAUAAAAAL0bD6zXiixHouYLbI5wJB8Z19mh";
  $ip = $_SERVER['REMOTE_ADDR'];
  // post request to server
  $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  '&response=' . urlencode($captcha);
  $response = file_get_contents($url);
  $responseKeys = json_decode($response,true);
  // should return JSON with success as true
  if($responseKeys["success"]) {

         $cust_name = $_REQUEST['name'];
        $contact_number = $_REQUEST['phone'];
        $mail_id = $_REQUEST['email'];
        $Organization = $_REQUEST['subject'];
        $member = $_REQUEST['member'];
        $location = $_REQUEST['location'];
        $course = $_REQUEST['course'];
        $Comment = $_REQUEST['message'];
        $createdon  = date("d-m-Y H:i:s A");
        $name 	= "info@skillsharesolution.com";
        $pass		= "infoskillshare";
    
        $to			=	"mari21.er21@gmail.com";
        $cc			=	"mari21.er21@gmail.com";
        $subject	=	"Skillshare Landing Enquiry";
        $message 	= "Customer Name : ".$cust_name."<br>"." Mobile No : ".$contact_number."<br>"." Mail Id : ".$mail_id."<br>"." Company Name : ".$Organization."<br>"." No.of Member : ".$member."<br>"." Course : ".$course."<br>"."location : ".$location."<br>"." Comment : ".$Comment;
        $fromdept	=	"";
      
        $mail = new PHPMailer();
        $mail->CharSet =  "utf-8";
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->Username = $name;
        $mail->Password = $pass;
        $mail->SMTPSecure = "ssl"; // SSL FROM DATABASE
        $mail->Host = 	    "smtp.gmail.com";		// Host FROM DATABASE
        $mail->Port = 		"465";		// Port FROM DATABASE 465 existing
        $mail->setFrom($name, $fromdept);
        $mail->AddAddress($to);
        $mail->addCC($cc);
        // $mail->addCC($bcc);
        $mail->Subject  = $subject;
        $mail->IsHTML(true);
        $mail->Body    = $message;
      if($mail->Send())
      {
          $_SESSION['user']=$name;
          $_SESSION['Level']=1;
          $_SESSION['id']=$name;
        //   echo "MAIL SENT.";
          header("Location: tq.php");
      }
      else
      {
          "Mail Error - >".$mail->ErrorInfo;
      }
    }
    }
?> 




                         <form name="contactform" id="contactform" method="post" action="" class="sticky-top common-frm">
                            <fieldset>
                            <div class="row">
                                <div class="col-md-12">
                                    
                                    <div class="form-group">
                                        <label class="sr-only" for="name">Name<br></label>
                                        <div class="inner-addon left-addon">
                                            
                                            <input type="text" class="form-control" placeholder="Name" name="name" id="name" required data-name="Name">
                                        </div>
                                    </div>
                                    
                                        <div class="col-lg-12 px-0">
                                            <div class="form-group">
                                                <label class="sr-only" for="email">Email<br></label>
                                                <div class="inner-addon left-addon">
                                                    
                                                    <input type="email" class="form-control" placeholder="Email" name="email" id="email" required data-name="Email">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 px-0">
                                            <div class="form-group">
                                                <label class="sr-only" for="name">Phone<br></label>
                                                <div class="inner-addon left-addon">
                                                    
                                                    <input type="text" class="form-control" placeholder="Phone" name="phone" id="phone" required data-name="Phone">
                                                </div>
                                            </div>
                                        </div>
                                    
                                    
                                        <div class="col-lg-12 px-0">
                                            <div class="form-group">
                                                <label class="sr-only" for="email">Course Interst<br></label>
                                                <div class="inner-addon left-addon">
                                                   
                                                    <select class="form-control" name="course">
                                                        <option value="">Select Course</option>
                                                        <option value="ccna">CCNA</option>
                                                        <option value="ccna security">CCNA SECURITY</option>
                                                        <option value="ccnp">CCNP</option>
                                                        <option value="a+training">A+ Training</option>
                                                        <option value="n+training">N+ Training</option>
                                                        <option value="networksecurity">NETWORK SECURITY</option>
                                                        <option value=".Net">. Net</option>
                                                        <option value="Java">Java</option>
                                                        <option value="AWS">AWS</option>
                                                        <option value="Python">Python</option>
                                                        <option value="PHP">PHP</option>
                                                        <option value="Digital Marketing">Digital Marketing</option>
                                                        <option value="Industrial visit">Industrial visit</option>
                                                        <option value="Free College workshops">Free College workshops</option>
                                                        <option value="Free Seminar">Free Seminar</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 px-0">
                                            <div class="form-group">
                                                <label class="sr-only" for="name">No oF Member<br></label>
                                                <div class="inner-addon left-addon">
                                                    
                                                    <input type="text" class="form-control" placeholder="No of member" name="member" id="phone" required data-name="Phone">
                                                </div>
                                            </div>
                                        </div>
                                    
                                    <div class="form-group">
                                        <label class="sr-only" for="subject">Location<br></label>
                                        <div class="inner-addon left-addon">
                                            
                                            <input type="text" class="form-control" placeholder="Location" name="location" required id="subject">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="sr-only" for="message">Messages<br></label>
                                        <div class="inner-addon left-addon">
                                            
                                            <textarea rows="3" name="message" id="message" class="form-control" placeholder="Messagess" required data-name="Message"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row actions">
                           <div class="g-recaptcha" data-sitekey="6Lf67dAUAAAAALYxSXbdxgkFwAcE346lJnxS--I5"></div>
                                <div class="col-sm-12 col-md-6">
                                    <input type="submit" value="Send Now" name="submit1" id="submitButton" class="btn btn-default btn-primary-corp-big" title="Click here to submit your message!">
                                </div>

                            </div>
                            </fieldset>
                        </form>