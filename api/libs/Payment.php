<?php

class Payment
{

    private $_requestUrl = 'https://sandbox.zarinpal.com/pg/rest/WebGate/PaymentRequest.json';
    private $_verifyUrl = 'https://sandbox.zarinpal.com/pg/rest/WebGate/PaymentVerification.json';

    function __construct()
    {
    }

    function send($amount, $redirect, $phone, $email, $description)
    {
        $data = array(
            'MerchantID' => MERCHANT_ID,
            'Amount' => $amount,
            'CallbackURL' => $redirect,
            'Phone' => $phone,
            'Email' => $email,
            'Description' => $description);
        $jsonData = json_encode($data);
        $ch = curl_init($this->_requestUrl);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($jsonData)));
        $result = curl_exec($ch);
        $err = curl_error($ch);
        $result = json_decode($result, true);
        curl_close($ch);
        if ($err):
            return (array(0 => false, 'text' => 'cURL Error #:' . $err));
        else:
            if ($result["Status"] == 100)
                return (array(0 => true, 'text' => 'https://sandbox.zarinpal.com/pg/StartPay/' . $result["Authority"]));
            else
                return (array(0 => false, 'text' => 'ERR: ' . $result["Status"]));
        endif;
    }


    function verify($Authority, $price)
    {
        $data = array('MerchantID' => MERCHANT_ID, 'Authority' => $Authority, 'Amount' => $price);
        $jsonData = json_encode($data);
        $ch = curl_init($this->_verifyUrl);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($jsonData)));
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        if ($err) {
            return (array(0 => false, 'text' => 'cURL Error #:' . $result["Status"]));
        } else {
            if ($result['Status'] == 100) {
                return (array(0 => true, 'text' => $result['RefID']));
            } else {
                return (array(0 => false, 'text' => 'ERR: ' . $result["Status"]));
            }
        }
    }
}