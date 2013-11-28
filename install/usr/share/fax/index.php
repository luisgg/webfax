<?php
  session_start();
  if (isset($_REQUEST['userid']) && isset($_REQUEST['password'])) { //el usuario ha intentado entrar con login y password
	// variables de autenticacion y LDAP
        $ldap['user']              = $_REQUEST['userid'];
        $ldap['pass']              = $_REQUEST['password'];
        //$ldap['host']              = '10.10.0.250'; // nombre del host o servidor
        $ldap['host']              = '172.16.0.85'; // nombre del host o servidor
        $ldap['port']              = 389; // puerto del LDAP en el servidor
        $ldap['dn']                = 'uid='.$ldap['user'].',ou=usuarios,dc=centro,dc=com'; // modificar respecto a los valores del LDAP
        $ldap['base']              = 'ou=grupos,dc=centro,dc=com';
        $ldap['filter']            = "(cn=PROFESORES)";
        $ldap['searchdn']          = 'uid=joindomain,ou=usuarios,dc=centro,dc=com';
        $ldap['searchpass']          = 'joindomain';
        // conexion a ldap
        $ldap['conn'] = ldap_connect( $ldap['host'], $ldap['port'] );
        ldap_set_option($ldap['conn'], LDAP_OPT_PROTOCOL_VERSION, 3);
        $is_valid_user = false;
	// match de usuario y password
        $ldap['bind'] = ldap_bind( $ldap['conn'], $ldap['dn'], $ldap['pass'] );
        if ($ldap['bind']){
                if ( ldap_bind( $ldap['conn'], $ldap['searchdn'], $ldap['searchpass']) ) {
                        // pertenencia a grupo
                        if ($result = ldap_search($ldap['conn'], $ldap['base'], $ldap['filter'], array("memberUid"))) {
                                $entries = ldap_get_entries($ldap['conn'], $result);
                                for ($i=0; $i<$entries[0]["memberuid"]['count'];$i++) {
                                        $uid = $entries[0]["memberuid"][$i];
                                        if ( $uid == $ldap['user'] ) {
                                                $is_valid_user = true;
                                                break ;
                                        }
                                }
                        }
                }
                ldap_unbind($ldap['conn']);
        }
	if ( $is_valid_user ) {
		$_SESSION['valid_user']=$_REQUEST['userid'];
	}
  }
?>
<?php

// Pear library includes
// You should have the pear lib installed
include_once('Mail.php');
include_once('Mail/mime.php');

//Settings 
include_once('config.inc.php');

$errors ='';

if(isset($_POST['submit']))
{
	//Get the uploaded file information
	$name_of_uploaded_file =  basename($_FILES['uploaded_file']['name']);
	
	//get the file extension of the file
	$type_of_uploaded_file = substr($name_of_uploaded_file, 
							strrpos($name_of_uploaded_file, '.') + 1);
	
	$size_of_uploaded_file = $_FILES["uploaded_file"]["size"]/1024;
	
	///------------Do Validations-------------
	if(empty($_POST['name'])||empty($_POST['fax']))
	{
		$errors .= "\n Name and Email are required fields. ";	
	}
	$user_email=$_SESSION['valid_user'].'@'.$config["mail_domain"];
	if(IsInjected($user_email))
	{
		$errors .= "\n Bad email value!";
	}
	
	if($size_of_uploaded_file > $config['max_allowed_file_size'] ) 
	{
		$errors .= "\n Size of file should be less than ". $config['max_allowed_file_size'];
	}
	
	//------ Validate the file extension -----
	$allowed_ext = false;
	for($i=0; $i<sizeof($config['allowed_extensions']); $i++) 
	{ 
		if(strcasecmp($config['allowed_extensions'][$i],$type_of_uploaded_file) == 0)
		{
			$allowed_ext = true;		
		}
	}
	
	if(!$allowed_ext)
	{
		$errors .= "\n The uploaded file is not supported file type. ".
		" Only the following file types are supported: ".implode(',',$config['allowed_extensions']);
	}
	
	//send the email 
	if(empty($errors))
	{
		//copy the temp. uploaded file to uploads folder
		$path_of_uploaded_file = $config['upload_folder'] . $name_of_uploaded_file;
		$tmp_path = $_FILES["uploaded_file"]["tmp_name"];
		
		if(is_uploaded_file($tmp_path))
		{
		    if(!copy($tmp_path,$path_of_uploaded_file))
		    {
		    	$errors .= '\n error while copying the uploaded file';
		    }
		}
		
		//send the email
		$name = $_POST['name'];
//		$visitor_email = $_POST['email'];
		$fax = $_POST['fax'];
		$user_message = $_POST['message'];
//		$to_email = 'lgarcia@ausiasmarch.net';//<<--  Generate from $fax variable
		$to = $config['to_email'];
		$subject="Enviar fax al numero " . $fax . " Remitido por: " . $user_email;
		$from = $config['from_email'];
		$text = "FAX a la Atención de " . $name . "\n Enviado por <" . $user_email . "> desde el CIPFP Ausiàs March\n\n\n ". $user_message ."-". $path_of_uploaded_file;
		$message = new Mail_mime(); 
		$message->setTXTBody($text); 
		$message->addAttachment($path_of_uploaded_file);
		$body = $message->get();
		$sinfaxnumber = $fax;
		$sinfaxusermail = $user_email;
		$extraheaders = array("From"=>$from, "Subject"=>$subject,"Reply-To"=>$user_email, "To"=>$to, "X-SinFax-Number"=>$sinfaxnumber, "X-SinFax-User-Mail"=>$sinfaxusermail);
		$headers = $message->headers($extraheaders);

//		$params["host"] = "mail.edu.gva.es";
//		$params["port"] = "25";
//		$params["auth"] = false;

		$params["host"] = $config['host'];
		$params["port"] = $config['port'];
		$params["auth"] = true;
		$params["username"] = $config['username'];
		$params["password"] = $config['password']; 

		$mail = Mail::factory("smtp", $params);
		$mail->send($to, $headers, $body);
		//redirect to 'thank-you page
		header('Location: thank-you.html');
	}
}
///////////////////////////Functions/////////////////
// Function to validate against any email injection attempts
function IsInjected($str)
{
  $injections = array('(\n+)',
              '(\r+)',
              '(\t+)',
              '(%0A+)',
              '(%0D+)',
              '(%08+)',
              '(%09+)'
              );
  $inject = join('|', $injections);
  $inject = "/$inject/i";
  if(preg_match($inject,$str))
    {
    return true;
  }
  else
    {
    return false;
  }
}
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="./css/default.css">
        <title>Envio de FAX</title>
	<!-- a helper script for vaidating the form-->
	<script language="JavaScript" src="./scripts/gen_validatorv31.js" type="text/javascript"></script>       
</head>
<body><h1>Envio de FAX</h1>

<?php
  if (isset($_SESSION['valid_user'])) {
    echo "Bienvenido, " . $_SESSION['valid_user'] . "<br>";
	if(!empty($errors))
	{
	echo nl2br($errors);
	}
    echo "<form method=\"POST\" name=\"email_form_with_php\"" ;
    echo "action=\"". htmlentities($_SERVER['PHP_SELF']) ."\" enctype=\"multipart/form-data\">" ;
$html = <<< EOH
<p>
<label for='fax'>Numero de FAX: </label><br>
<input type="text" name="fax" >
</p>
<p>
<label for='name'>A la Atencion de: </label><br>
<input type="text" name="name" >
</p>
<p>
<label for='message'>Message:</label> <br>
<textarea name="message"></textarea>
</p>
<p>
<label for='uploaded_file'>Select A File To Upload:</label> <br>
<input type="file" name="uploaded_file">
</p>
<input type="submit" value="Submit" name='submit'>
</form>
<a href="logout.php">Salir del sistema</a><br>
<script language="JavaScript">
// Code for validating the form
// Visit http://www.javascript-coder.com/html-form/javascript-form-validation.phtml
// for details
var frmvalidator  = new Validator("email_form_with_php");
frmvalidator.addValidation("name","req","Please provide name"); 
frmvalidator.addValidation("fax","req","Please provide the destination fax number"); 
// frmvalidator.addValidation("email","email","Please enter a valid email address"); 
</script>
<noscript>
<small><a href='http://www.html-form-guide.com/email-form/php-email-form-attachment.html'
>How to attach file to email in PHP</a> article page.</small>
</noscript>
EOH;
  echo($html);
}
  else
  //no esta registrado
    if (isset($_REQUEST['userid'])) { //Ha intentado entrar pero no ha podido;
      echo "ERROR: nombre de usuario o/y contraseña incorrecta/os<br>";
      echo "<a href=\"index.php\">Volver a la pagina principal</a><br>";
    }
    else { //No ha intentado entrar o acaba de salir
      echo "<h2>Acceso al sistema</h2>";
      echo "<form method=post action=\"index.php\">";
      echo "<p><label for='userid'>Usuario: </label><br>";
      echo "<input type=text name=userid></p>";
      echo "<p><label for='passsword'>Password: </label><br>";
      echo "<input type=password name=password></p>";
      echo "<input type=submit value=\"Entrar\">";
      echo "</form>";
    }
?>
</body>
</html>
