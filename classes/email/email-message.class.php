<?php
/**
 * A simple non-validating representation of an email message for data transfer
 */
class EmailMessage
{
    private $_to;
    private $_from_address;
    private $_from_name;
    private $_cc;
    private $_subject;
    private $_body;

    public function SetTo($to) {
        $this->_to = (string)$to;
    }

    public function GetTo() {
        return $this->_to;
    }
    
    public function SetFromAddress($from_address) {
        $this->_from_address = (string)$from_address;
    }

    public function GetFromAddress() {
        return $this->_from_address;
    }

    public function SetFromName($from_name) {
        $this->_from_name = (string)$from_name;
    }

    public function GetFromName() {
        return $this->_from_name;
    }

    public function SetCC($cc) {
        $this->_cc = (string)$cc;
    }

    public function GetCC() {
        return $this->_cc;
    }
    
    public function SetSubject($subject) {
        $this->_subject = (string)$subject;
    }

    public function GetSubject() {
        return $this->_subject;
    }

    public function SetBody($body) {
        $this->_body = (string)$body;
    }

    public function GetBody() {
        return $this->_body;
    }
}
?>