<?php
/**
 * ClicRDV
 * 
 * @copyright 2011
 * @author ClicRDV
 * @version 1.0 30/09/2011
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated 
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and 
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions 
 * of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED 
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL 
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF 
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
 * DEALINGS IN THE SOFTWARE.
 */
class ClicRDVclient
{
    // default CURL options
    protected $_default_opts = array(
      CURLOPT_RETURNTRANSFER => true,  // return result instead of echoing
      
      CURLOPT_SSL_VERIFYHOST => true,
      CURLOPT_SSL_VERIFYPEER => true,
      
      CURLOPT_FOLLOWLOCATION => true,  // follow redirects, Location: headers
      CURLOPT_MAXREDIRS      => 1,     // but dont redirect more than 10 times
      
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/json',
      )
      
    );
    
    // hash to store any CURLOPT_ option values
    protected $_options;
    
    // container for full CURL getinfo hash
    protected $_info=null; 

    // variable to hold the CURL handle
    private $_c=null; 
    
    // the ClicRDV endpoint
    private $_endpoint=null;
    
    // ClicRDV apikey
    private $_apikey=null;
    
    /**
    * Instantiate a ClicRDVclient object.
    * 
    * @param string $apikey
    * @param string $username
    * @param string $password
    * @param string $endpoint
    * @param string $cert_file path to SSL public identity file
    * @param string $key_file path to SSL private key file
    * @param string $key_file passphrase to access $key_file
    * @param string $user_agent client identifier sent to server with HTTP headers
    * @param array $options hash of CURLOPT_ options and values
    */
    public function __construct($apikey, $username=null, $password=null, $endpoint="https://www.clicrdv.com",$cert_file=null,$key_file=null, $passphrase=null, $user_agent="ClicRDV PhpRestClient", $options=null) {
        // make sure we can use curl
        if (!function_exists('curl_init')) {
          throw new Exception('Trying to use CURL, but module not installed.');
        }

        $this->_endpoint = $endpoint;
        $this->_apikey = $apikey;

        // load default options then add the ones passed as argument
        $this->_options = $this->_default_opts;
        if (is_array($options)) {
          foreach ($options as $curlopt => $value) {
            $this->_options[$curlopt] = $value;
          }
        }
        
        // Basic Auth
        if($username) {
          $this->_options[CURLOPT_USERPWD] = $username . ":" . $password;
        }
        
        // Use the mutator methods to take advantage of any processing or error checking
        $this->setCertFile($cert_file);
        $this->setKeyFile($key_file, $passphrase);
        $this->_options[CURLOPT_USERAGENT] = $user_agent;
        
        //  initialize the _info container
        $this->_info = array();
    } 
    
    /**
     * Set a CURL option
     *
     * @param int $curlopt index of option expressed as CURLOPT_ constant
     * @param mixed $value what to set this option to
     */
    public function setOption($curlopt, $value) {
      $this->_options[$curlopt] = $value;
    }
    
    /**
    * Set the local file system location of the SSL public certificate file that 
    * cURL should pass to the server to identify itself.
    *
    * @param string $cert_file path to SSL public identity file
    */
    public function setCertFile($cert_file) {
      if (!is_null($cert_file))
      {
        if (!file_exists($cert_file)) {
          throw new Exception('Cert file: '. $cert_file .' does not exist!');
        }
        if (!is_readable($cert_file)) {
          throw new Exception('Cert file: '. $cert_file .' is not readable!');
        }
        //  Put this in _options hash
        $this->_options[CURLOPT_SSLCERT] = $cert_file;
      }
    }
    
    /**
    * Set the local file system location of the private key file that cURL should
    * use to decrypt responses from the server.
    *
    * @param string $key_file path to SSL private key file
    * @param string $passphrase passphrase to access $key_file
    */
    public function setKeyFile($key_file, $passphrase = null) {
      if (!is_null($key_file))
      {
        if (!file_exists($key_file)) {
          throw new Exception('SSL Key file: '. $key_file .' does not exist!');
        }
        if (!is_readable($key_file)) {
          throw new Exception('SSL Key file: '. $key_file .' is not readable!');
        }
        //  set the private key in _options hash
        $this->_options[CURLOPT_SSLKEY] = $key_file;
        //  optionally store a pass phrase for key
        if (!is_null($passphrase)) {
          $this->_options[CURLOPT_SSLCERTPASSWD] = $passphrase;
        }
      }
    }
    

    /**
     * Make an HTTP GET request to the URL provided.
     *
     * @param string $url absolute URL to make an HTTP request to
     */
    public function get($url, $params=array()) {
      
        $url_data = 'format=json&apikey='.$this->_apikey;
        $need_amp = false;
        foreach ($params as $varname => $val) {
          if ($need_amp) $post_data .= '&';
          $val = urlencode($val);
          $url_data .= "{$varname}={$val}";
          $need_amp = true;
        }
      
        $u = $this->_endpoint.$url.'?'.$url_data;
      
        $_c = curl_init($u);
    
        //  set the options
        foreach ($this->_options as $curlopt => $value) {
          curl_setopt($_c, $curlopt, $value);
        }
        
        $_raw_data = curl_exec($_c);
  
        if (curl_errno($_c) != 0) {
          throw new Exception('Aborting. cURL error: ' . curl_error($_c));
        }
      
        //  Store all cURL metadata about this request
        $this->_info = curl_getinfo($_c);
        curl_close($_c);  
        
        return json_decode($_raw_data);
    }
    
    /**
     * Make an HTTP POST request to the URL provided. Capture the results for future
     * use.
     * @param array $data can be either a string or an array
     */
    public function post($url, $data) {
      
      // Serialize the data into a query string
      $post_data = array( 'apikey' => $this->_apikey );
      foreach($data as $key => $value) {
         $post_data[$key] = $value;
      }
      
      $_c = curl_init($this->_endpoint.$url);
      
      //  set the options
      foreach ($this->_options as $curlopt => $value) {
        curl_setopt($_c, $curlopt, $value);
      }
      curl_setopt($_c,CURLOPT_POST, 1);
      curl_setopt($_c,CURLOPT_POSTFIELDS, json_encode($post_data) );
      
      $_raw_data = curl_exec($_c);
      
      if (curl_errno($_c) != 0) {
        throw new Exception('Aborting. cURL error: ' . curl_error($_c));
      }
      
      //  Store all cURL metadata about this request
      $this->_info = curl_getinfo($_c);
      curl_close($_c);  
      
      return json_decode($_raw_data);
    }
    
    public function put($url, $data) {
      // Serialize the data into a query string
      $post_data = array( 'apikey' => $this->_apikey );
      foreach($data as $key => $value) {
         $post_data[$key] = $value;
      }
      
      $_c = curl_init($this->_endpoint.$url);
      
      //  set the options
      foreach ($this->_options as $curlopt => $value) {
        curl_setopt($_c, $curlopt, $value);
      }
      curl_setopt($_c, CURLOPT_CUSTOMREQUEST, 'PUT');
      curl_setopt($_c,CURLOPT_POSTFIELDS, json_encode($post_data) );
      
      $_raw_data = curl_exec($_c);
      
      if (curl_errno($_c) != 0) {
        throw new Exception('Aborting. cURL error: ' . curl_error($_c));
      }
      
      //  Store all cURL metadata about this request
      $this->_info = curl_getinfo($_c);
      curl_close($_c);  
      
      return json_decode($_raw_data);
    }
    
    
    public function delete($url) {
      $_c = curl_init($this->_endpoint.$url.'?apikey='.$this->_apikey.'&format=json');
      
      //  set the options
      foreach ($this->_options as $curlopt => $value) {
        curl_setopt($_c, $curlopt, $value);
      }
      curl_setopt($_c, CURLOPT_CUSTOMREQUEST, 'DELETE');
      
      $_raw_data = curl_exec($_c);
      
      if (curl_errno($_c) != 0) {
        throw new Exception('Aborting. cURL error: ' . curl_error($_c));
      }
      
      //  Store all cURL metadata about this request
      $this->_info = curl_getinfo($_c);
      curl_close($_c);  
      
      if( $this->getStatusCode() != 200) {
        return json_decode($_raw_data);
      }
    }
    
    
    /**
     * Get stats and info about the last run HTTP request. Data available here
     * is from Curl's curl_getinfo function. Either returns the full assoc
     * array of data or the specified item.
     *
     * @param string $item index to a specific info item
     * @return mixed
     */
    public function getInfo($item = 0) {
      
      if ($item === 0) { return $this->_info; }
      
      if (array_key_exists($item, $this->_info)) {
        return $this->_info[$item];
      } else {
        return null;
      }
    }
    
    /**
     * Get the HTTP status code returned by the last execution of makeWebRequest()
     *
     * @return int
     */
    public function getStatusCode() {
        return $this->getInfo('http_code');
    }
    
}

?>