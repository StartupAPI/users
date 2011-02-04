<?php
/**
 * Store tamper-proof strings in an HTTP cookie
 *
 * Source: http://code.google.com/p/mrclay/source/browse/trunk/php/MrClay/CookieStorage.php
 *
 * <code>
 * $storage = new MrClay_CookieStorage(array(
 *     'secret' => '67676kmcuiekihbfyhbtfitfytrdo=op-p-=[hH8'
 * ));
 * if ($storage->store('user', 'id:62572,email:bob@yahoo.com,name:Bob')) {
 *    // cookie OK length and no complaints from setcookie()
 * } else {
 *    // check $storage->errors
 * }
 * 
 * // later request
 * $user = $storage->fetch('user');
 * if (is_string($user)) {
 *    // valid cookie
 *    $age = time() - $storage->getTimestamp('user');
 * } else {
 *     if (false === $user) {
 *         // data was altered!
 *     } else {
 *         // cookie not present
 *     }
 * }
 * 
 * // encrypt cookie contents
 * $storage = new MrClay_CookieStorage(array(
 *     'secret' => '67676kmcuiekihbfyhbtfitfytrdo=op-p-=[hH8'
 *     ,'mode' => MrClay_CookieStorage::MODE_ENCRYPT
 * ));
 * </code>
 */
class MrClay_CookieStorage {

    // conservative storage limit considering variable-length Set-Cookie header
    const LENGTH_LIMIT = 3896;
    const MODE_VISIBLE = 0;
    const MODE_ENCRYPT = 1;
    
    /**
     * @var array errors that occured
     */
    public $errors = array();


    public function __construct($options = array())
    {
        $this->_o = array_merge(self::getDefaults(), $options);
    }
    
    public static function hash($input)
    {
        return str_replace('=', '', base64_encode(hash('ripemd160', $input, true)));
    }
    
    public static function encrypt($key, $str)
    {
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);  
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);  
        $data = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB, $iv);
        return base64_encode($data);
    }
    
    public static function decrypt($key, $data)
    {
        if (false === ($data = base64_decode($data))) {
            return false;
        }
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);  
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);  
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_ECB, $iv);
    }

    public function getDefaults()
    {
        return array(
            'secret' => ''
            ,'domain' => ''
            ,'secure' => false
            ,'path' => '/'
            ,'expire' => '2147368447' // Sun, 17-Jan-2038 19:14:07 GMT (Google)
            ,'hashFunc' => array('MrClay_CookieStorage', 'hash')
            ,'encryptFunc' => array('MrClay_CookieStorage', 'encrypt')
            ,'decryptFunc' => array('MrClay_CookieStorage', 'decrypt')
            ,'mode' => self::MODE_VISIBLE
        );
    }

    public function setOption($name, $value)
    {
        $this->_o[$name] = $value;
    }

    /**
     * @return bool success
     */
    public function store($name, $str)
    {
        if (empty($this->_o['secret'])) {
            $this->errors[] = 'Must first set the option: secret.';
            return false;
        }
        return ($this->_o['mode'] === self::MODE_ENCRYPT)
            ? $this->_storeEncrypted($name, $str)
            : $this->_store($name, $str);
    }
    
    private function _store($name, $str)
    {
        if (! is_callable($this->_o['hashFunc'])) {
            $this->errors[] = 'Hash function not callable';
            return false;
        }
        $time = base_convert($_SERVER['REQUEST_TIME'], 10, 36); // pack time
        // tie sig to this cookie name
        $hashInput = $this->_o['secret'] . $name . $time . $str;
        $sig = call_user_func($this->_o['hashFunc'], $hashInput);
        $raw = $sig . '|' . $time . '|' . $str;
        if (strlen($name . $raw) > self::LENGTH_LIMIT) {
            $this->errors[] = 'Cookie is likely too large to store.';
            return false;
        }
        $res = setcookie($name, $raw, $this->_o['expire'], $this->_o['path'], 
                         $this->_o['domain'], $this->_o['secure'], $this->_o['httponly']);
        if ($res) {
            return true;
        } else {
            $this->errors[] = 'Setcookie() returned false. Headers may have been sent.';
            return false;
        }
    }
    
    private function _storeEncrypted($name, $str)
    {
        if (! is_callable($this->_o['encryptFunc'])) {
            $this->errors[] = 'Encrypt function not callable';
            return false;
        }
        $time = base_convert($_SERVER['REQUEST_TIME'], 10, 36); // pack time
        $key = self::hash($this->_o['secret']);
        $raw = call_user_func($this->_o['encryptFunc'], $key, $key . $time . '|' . $str);
        if (strlen($name . $raw) > self::LENGTH_LIMIT) {
            $this->errors[] = 'Cookie is likely too large to store.';
            return false;
        }
        $res = setcookie($name, $raw, $this->_o['expire'], $this->_o['path'], 
                         $this->_o['domain'], $this->_o['secure'], $this->_o['httponly']);
        if ($res) {
            return true;
        } else {
            $this->errors[] = 'Setcookie() returned false. Headers may have been sent.';
            return false;
        }
    }

    /**
     * @return string null if cookie not set, false if tampering occured
     */
    public function fetch($name)
    {
        if (!isset($_COOKIE[$name])) {
            return null;
        }
        return ($this->_o['mode'] === self::MODE_ENCRYPT)
            ? $this->_fetchEncrypted($name)
            : $this->_fetch($name);
    }
    
    private function _fetch($name)
    {
        if (isset($this->_returns[self::MODE_VISIBLE][$name])) {
            return $this->_returns[self::MODE_VISIBLE][$name][0];
        }
        $cookie = get_magic_quotes_gpc()
            ? stripslashes($_COOKIE[$name])
            : $_COOKIE[$name];
        $parts = explode('|', $cookie, 3);
        if (3 !== count($parts)) {
            $this->errors[] = 'Cookie was tampered with.';
            return false;
        }
        list($sig, $time, $str) = $parts;
        $hashInput = $this->_o['secret'] . $name . $time . $str;
        if ($sig !== call_user_func($this->_o['hashFunc'], $hashInput)) {
            $this->errors[] = 'Cookie was tampered with.';
            return false;
        }
        $time = base_convert($time, 36, 10); // unpack time
        $this->_returns[self::MODE_VISIBLE][$name] = array($str, $time);
        return $str;
    }
    
    private function _fetchEncrypted($name)
    {
        if (isset($this->_returns[self::MODE_ENCRYPT][$name])) {
            return $this->_returns[self::MODE_ENCRYPT][$name][0];
        }
        if (! is_callable($this->_o['decryptFunc'])) {
            $this->errors[] = 'Decrypt function not callable';
            return false;
        }
        $cookie = get_magic_quotes_gpc()
            ? stripslashes($_COOKIE[$name])
            : $_COOKIE[$name];
        $key = self::hash($this->_o['secret']);
        $timeStr = call_user_func($this->_o['decryptFunc'], $key, $cookie);
        if (! $timeStr) {
            $this->errors[] = 'Cookie was tampered with.';
            return false;
        }
        $timeStr = rtrim($timeStr, "\x00");
        // verify decryption
        if (0 !== strpos($timeStr, $key)) {
            $this->errors[] = 'Cookie was tampered with.';
            return false;
        }
        $timeStr = substr($timeStr, strlen($key));
        list($time, $str) = explode('|', $timeStr, 2);
        $time = base_convert($time, 36, 10); // unpack time
        $this->_returns[self::MODE_ENCRYPT][$name] = array($str, $time);
        return $str;
    }

    public function getTimestamp($name)
    {
        if (is_string($this->fetch($name))) {
            return $this->_returns[$this->_o['mode']][$name][1];
        }
        return false;
    }

    public function delete($name)
    {
        setcookie($name, '', time() - 3600, $this->_o['path'], $this->_o['domain'], $this->_o['secure'], $this->_o['httponly']);
    }
    
    /**
     * @var array options
     */
    private $_o;

    private $_returns = array();
}

