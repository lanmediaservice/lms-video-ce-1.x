<?php

class mcrypt
{
	var $alghoritm = MCRYPT_BLOWFISH;
	var $blockmode = MODE_ECB;
    var $key = null;
    var $iv  = "z5c8e7gh";

    function mcrypt($blockmode = MODE_ECB, $alghoritm = MCRYPT_BLOWFISH, $key = null)
    {
        // Initialize Vars
        $this->blockmode = $blockmode;
        $this->alghoritm = $alghoritm;
        $this->key = $key;
    }

    function encrypt($plain)
    {
    	if ($this->blockmode==MODE_ECB) {
	    	$cipher = mcrypt_ecb ($this->alghoritm, $this->key, $plain, MCRYPT_ENCRYPT, $this->iv);
    	}
    	if ($this->blockmode==MODE_CBC) {
	    	$cipher = mcrypt_cbc ($this->alghoritm, $this->key, $plain, MCRYPT_ENCRYPT, $this->iv);
    	}
        return $cipher;
    }

    function decrypt($cipher)
    {
    	if ($this->blockmode==MODE_ECB) {
	    	$plain = mcrypt_ecb ($this->alghoritm, $this->key, $cipher, MCRYPT_DECRYPT, $this->iv);
	    }
    	if ($this->blockmode==MODE_CBC) {
	    	$plain = mcrypt_cbc ($this->alghoritm, $this->key, $cipher, MCRYPT_DECRYPT, $this->iv);
    	}
        return $plain;
    }
}

?>
