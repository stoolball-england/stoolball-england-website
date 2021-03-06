<?php
function warning_handler($errno, $errstr) { 
    // ignore the warning
}
    
/**
 * Protect email addresses by obfuscating or encrypting them (depending on whether the current user is signed in)
 */
class EmailAddressProtector {
        
    /**
     * @var SiteSettings
     */
    private $settings;
    
    /**
     * Creates a new EmailAddressProtector
     * @var SiteSettings $settings
     */
    public function __construct(SiteSettings $settings) {
        $this->settings = $settings;
    }
    
    /**
     * Hide email addresses from spammers as far as possible
     *
     * @param string $text
     * @param bool $is_signed_in
     * @return string
     */
    public function ApplyEmailProtection($text, $is_signed_in)
    {
        # From http://regexlib.com/REDetails.aspx?regexp_id=328
        $email_pattern = "((\"[^\"\f\n\r\t\v\b]+\")|([\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+(\.[\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+)*))@((\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9\-])+\.)+[A-Za-z\-]+))";

        # Remove link tags - we'll generate these
        $text = preg_replace('/\[url=mailto:(' . $email_pattern . ')]' . $email_pattern . '\[\/url]/', '$1', $text);
        $text = preg_replace('/<a href="mailto:(' . $email_pattern . ')">' . $email_pattern . '<\/a>/', '$1', $text);

        if ($is_signed_in)
        {            
            $text = preg_replace_callback('/(' . $email_pattern . ')/', "self::ObfuscateProtectedEmail", $text);
        }
        else
        {
            $text = preg_replace_callback('/(' . $email_pattern . ')/', "self::EncryptProtectedEmail", $text);
        }

        return $text;
    }

    private function ObfuscateProtectedEmail($matches) {
        return '<a href="' . $this->Obfuscate($matches[0], true) . '">' . $this->Obfuscate($matches[0], false) . '</a>';
    }
    
    private function EncryptProtectedEmail($matches) {
        

        $cipher = "aes-128-gcm";
        $tag = null;
        if (in_array($cipher, openssl_get_cipher_methods()))
        {
            $encryption_key = $this->settings->GetEmailAddressEncryptionKey();
            $iv = $this->settings->GetEmailAddressEncryptionIv();
            $encrypted = openssl_encrypt($matches[0], $cipher, $encryption_key, $options=0, $iv, $tag);
        }

        return '<a href="/contact/email?to=' . urlencode(base64_encode($encrypted)) . '&tag=' . urlencode(base64_encode($tag)) . '">' . htmlentities(substr($matches[0], 0, strpos($matches[0], "@")), ENT_QUOTES, "UTF-8", false) . '@&#8230;</a>';
    }
    
    /**
     * Decrypt an e-mail address encrypted by the ApplyEmailProtection() method
     * @param $address string
     * @param $tag string
     */
    public function DecryptProtectedEmail($address, $tag) 
    {
        $cipher = "aes-128-gcm";
        if (in_array($cipher, openssl_get_cipher_methods()))
        {
            $address = base64_decode($address);
            $encryption_key = $this->settings->GetEmailAddressEncryptionKey();
            $iv = $this->settings->GetEmailAddressEncryptionIv();
            $tag = base64_decode($tag);

            // When bots hit this sometimes it throws an E_WARNING "Setting tag for AEAD cipher decryption failed", 
            // yet it seems to work when tested so catch and ignore the warning
            set_error_handler("warning_handler", E_WARNING);
            $address = rtrim(openssl_decrypt($address, $cipher, $encryption_key, $options=0, $iv, $tag), "\0");
            restore_error_handler();
        }
        return $address;
    }
    
    /**
     * Obfuscate an email address as XHTML entities to hide it from at least the dumber spam-bots
     *
     * @param string $address
     * @param bool $add_mailto
     * @return string
     */
    private function Obfuscate($address, $add_mailto)
    {
        $address = strtolower($address);
        
        if (strpos($address, 'mailto:') === 0) $address = substr($address, 7);
        
        $address = str_replace(".", "&#0046;", $address);
        $address = str_replace(":", "&#0058;", $address);
        $address = str_replace("@", "&#0064;", $address);
        $address = str_replace("a", "&#0097;", $address);
        $address = str_replace("b", "&#0098;", $address);
        $address = str_replace("c", "&#0099;", $address);
        $address = str_replace("d", "&#0100;", $address);
        $address = str_replace("e", "&#0101;", $address);
        $address = str_replace("f", "&#0102;", $address);
        $address = str_replace("g", "&#0103;", $address);
        $address = str_replace("h", "&#0104;", $address);
        $address = str_replace("i", "&#0105;", $address);
        $address = str_replace("j", "&#0106;", $address);
        $address = str_replace("k", "&#0107;", $address);
        $address = str_replace("l", "&#0108;", $address);
        $address = str_replace("m", "&#0109;", $address);
        $address = str_replace("n", "&#0110;", $address);
        $address = str_replace("o", "&#0111;", $address);
        $address = str_replace("p", "&#0112;", $address);
        $address = str_replace("q", "&#0113;", $address);
        $address = str_replace("r", "&#0114;", $address);
        $address = str_replace("s", "&#0115;", $address);
        $address = str_replace("t", "&#0116;", $address);
        $address = str_replace("u", "&#0117;", $address);
        $address = str_replace("v", "&#0118;", $address);
        $address = str_replace("w", "&#0119;", $address);
        $address = str_replace("x", "&#0120;", $address);
        $address = str_replace("y", "&#0121;", $address);
        $address = str_replace("z", "&#0122;", $address);

        if ($add_mailto)
        {
            $s_mailto = "&#0109;&#0097;&#0105;&#0108;&#0116;&#0111;&#0058;";
            $address = $s_mailto . $address;
        }

        return $address;
    }
}
?>