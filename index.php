<?php

//SBAPI
require_once 'lib/sbapi.php';

$sbapi = new SBAPI;

$msg_type = 9000;
$xml_body = '<body>This is body content</body>';
$api_usr = 'myusername';
$api_pwd = 'mypassword';

//$sbapi->set_data($msg_type,$xml_body,$api_usr,$api_pwd)->send_r();
$sbapi->set_data()->send_r();

$respone = $sbapi->get_response();