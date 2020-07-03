<?php

/** Details
 * Lib Name: SimBASE API
 * Description: Lib for sending API messages to SimBASE system
 * Version: 2.1
 * Author: Vyacheslav Odinokov
 * Author contact: kagayakashi.vo@gmail.com | @kagayakashi
 **/

/** HowTo
 * 1. Edit config params;
 * 2. Include library and create new object:

require_once 'path/to/sbapi.php';
$sbapi = new SBAPI;

 * 3. Prepare data for request:

$msg_type = 9000; // API message type
$xml_body = '<body>This is body content</body>'; // API message body content
$api_usr = 'myusername'; // System user name
$api_pwd = 'mypassword'; // System user password

$sbapi->set_data($msg_type, $xml_body, $api_usr, $api_pwd);

 * 4. Send data:
$sbapi->send_r();

 * Username and password can be empty or NULL, but then they will taken from config file
 * Also, you can run all this methods without arguments:

$sbapi->set_data()->send_r();

 * In this case body is empty, message type is 9000 (echo test), username and password
taken from config file;
 * This example is very useful for test connection;
 
 * To get response use this method:
$sbapi->get_response();
 * Response is in simplexml object data type
 **/

class SBAPI {
    protected $cfg;
    protected $msg_type;
    protected $xml_header;
    protected $xml_body;
    protected $response;

    public function __construct(){
        $this->set_config();
    }

/** set_data:
 * Method to save message type, body and auth data into object;
 * By default msg_type = 9000, xml_body = empty body;
 **/
    public function set_data($msg_type = 9000, $xml_body = NULL, $api_usr = NULL, $api_pwd = NULL){
        $this->msg_type = (is_null($msg_type) || $msg_type == '') ? 9000 : $msg_type;
        $this->xml_body = (is_null($xml_body) || $xml_body == '') ? '<body />' : $xml_body;
        $this->set_xml_header($api_usr, $api_pwd);

        return $this;
    }

/** send_r:
 * Method to send selected API message to SimBASE and put saved data into it;
 * After sending it checks errors of Curl and errors from SimBASE API;
 * If Curl error found it will show error by stopping the script;
 **/
    public function send_r(){
        $body_r = $this->xml_header.$this->xml_body.'</sbapi>';
        $header_r = array(
            "Content-type: text/xml",
            "Content-length: ".strlen( $body_r ),
            "Connection: close",
        );

        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $this->cfg['api_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->cfg['curl_to']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body_r);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_r);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->cfg['curl_sslv']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->cfg['curl_sslv']);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        if($errno){die("Curl error! Error code: $errno; See more at https://curl.haxx.se/libcurl/c/libcurl-errors.html");}
        $this->set_response($response);
        curl_close($ch);
    }

/** get_response:
 * Method to get XML response (object type)
 **/
    public function get_response(){
        return $this->response;
    }

/** set_xml_header:
 * Method to form xml header with auth and message type data;
 **/
    protected function set_xml_header($api_usr, $api_pwd){
        $usr = (is_null($api_usr) || $api_usr == '') ? $this->cfg['api_usr'] : $api_usr;
        $pwd = (is_null($api_pwd) || $api_pwd == '') ? $this->cfg['api_pwd'] : $api_pwd;
        $pwd = ($this->cfg['api_hash'] == 'hash') ? sha1($pwd) : $pwd;

        $ignore_id = $this->cfg['api_imid']; 
        $msg_id = ( $ignore_id == 'yes' ) ? 1 : microtime(true)*10000;

        $msg_created = ''.date('Y-m-d').'T'.date('H:m:s').'Z';
        $xml_header  = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml_header .= '<sbapi>';
        $xml_header .= '<header>';
        $xml_header .= '<interface id="'.hexdec($this->cfg['api_iid']).'" version="'.$this->cfg['api_ver'].'" />';
        $xml_header .= '<message ignore_id="'.$ignore_id.'" id="'.$msg_id.'" type="'.$this->msg_type.'" created="'.$msg_created.'" />';
        $xml_header .= '<error id="0" />';
        $xml_header .= '<auth pwd="open">';
        $xml_header .= base64_encode( '<authdata msg_id="'.$msg_id.'" user="'.$usr.'" password="'.$pwd.'" msg_type="'.$this->msg_type.'" user_ip="'.$_SERVER['REMOTE_ADDR'].'" />' );
        $xml_header .= '</auth>';
        $xml_header .= '</header>';
        $this->xml_header = $xml_header;

        return $this;
    }

/** check_error:
 * Method to check error from SimBASE API and display error by stopping the script;
 * Otherwise save response
 **/
    protected function set_response($response){
        $response_xml = simplexml_load_string($response);
        $error = $response_xml->{'header'}->error;
        if( $error['id'] == '0' ){
            $this->response = $response_xml;
            return NULL;
        }
        die('Request to sbapi successful. Error found! Error code: '.$error['id'].'; Error text: '.$error['text']);
    }

/** set_config:
 * Method to save config parameters to object;
 **/
    protected function set_config(){
        require realpath(dirname(__FILE__)).'\config.php';
        $this->cfg = $config;
    }
}