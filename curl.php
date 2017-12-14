<?php
/**
 * Created by PhpStorm.
 * User: altansukh.a
 * Date: 12/12/2017
 * Time: 7:02 PM
 */
if ($_POST['request'] == 'otp') {
    $data = array("phone" => $_POST['phone'], "amount" => $_POST['src_amount'], "content" => $_POST['content']);
    $url = 'http://ewa.api/api/eMerchantBill';
} elseif ($_POST['request'] == 'confirm') {
    $data = array("id" => $_POST['id'], "code" => $_POST['code']);
    $url = 'http://ewa.api/api/billPay';
}


$data_string = json_encode($data);

$headers[] = 'ewa-session: f0859105768a633f8f792fdb6f09c57a';
$headers[] = 'Accept: application/json';
$headers[] = 'Content-Type: application/json';

$cURL = curl_init();
curl_setopt($cURL, CURLOPT_URL, $url);
curl_setopt($cURL, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($cURL, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($cURL, CURLOPT_HTTPHEADER, $headers);
$result = json_decode(trim(curl_exec($cURL)));

print_r(trim(curl_exec($cURL)));

?>