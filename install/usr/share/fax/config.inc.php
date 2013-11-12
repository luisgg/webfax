<?php 
//Settings 
$config['max_allowed_file_size'] = 4000; // size in KB 
$config['allowed_extensions'] = array("jpg", "jpeg", "gif", "bmp", "pdf");
$config['upload_folder'] = '/tmp/uploads/'; //<-- this folder must be writeable by the script
$config['from_email'] = '46021711.fax <admin_moodle@ausiasmarch.net>';//<<--  update this to your email address
$config['to_email'] = 'lgarcia@ausiasmarch.net';//<<--  Generate from $fax variable
$config["host"] = "ssl://smtp.gmail.com";
$config["port"] = "465";
$config["auth"] = true;
$config["username"] = "admin_moodle@ausiasmarch.net";
$config["password"] = "T...s"; 
$config["mail_domain"] = "ausiasmarch.net"; 
?>
