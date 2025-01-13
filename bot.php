<?php

define('API_KEY', 'TOKENINGIZNI_BU_YERGA_YOZING');

// Telegram API bilan muloqot funksiyasi
function callTelegramApi(string $method, array $data = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.telegram.org/bot' . API_KEY . '/' . $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Test uchun o'chirib qo'yilgan
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true); // Javobni faqat ichki ishlash uchun qaytaradi
}

// Foydalanuvchiga Invoice yuborish funksiyasi
function sendInvoice($chat_id)
{
    $invoiceData = [
        'chat_id' => $chat_id,
        'title' => 'Premium A\'zolik',
        'description' => 'Botimizning barcha funksiyalaridan foydalanish uchun premium a\'zolikni sotib oling.',
        'payload' => 'invoice-payload-12345',
        //'provider_token' => 'YOUR_PROVIDER_TOKEN', // Telegram stars bilan to'lov qilish uchun kerak emas bu
        'currency' => 'XTR',
        'prices' => [
            ['label' => 'Premium A\'zolik', 'amount' => 1000] // Narx (1,000 stars)
        ],
        'start_parameter' => 'start-premium'
    ];

    callTelegramApi('sendInvoice', $invoiceData); // Javobni ekranga chiqarmaydi
}

// To'lov muvaffaqiyatli amalga oshirilganligini tekshirish funksiyasi
function processSuccessfulPayment($paymentData)
{
    $chat_id = $paymentData['chat']['id'];
    $user_id = $paymentData['from']['id'];
    $currency = $paymentData['successful_payment']['currency'];
    $total_amount = $paymentData['successful_payment']['total_amount'] / 100; // Minor unitsdan asosiy formatga
    $invoice_payload = $paymentData['successful_payment']['invoice_payload'];
    $telegram_payment_id = $paymentData['successful_payment']['telegram_payment_charge_id'];
    $provider_payment_id = $paymentData['successful_payment']['provider_payment_charge_id'];

    // Foydalanuvchiga to'lov haqida to'liq ma'lumot yuborish
    callTelegramApi('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "To'lov amalga oshirildi!\n"
            . "Foydalanuvchi ID: $user_id\n"
            . "Miqdor: $total_amount $currency\n"
            . "Invoice: $invoice_payload\n"
            . "Telegram Payment ID: $telegram_payment_id\n"
            . "Provider Payment ID: $provider_payment_id"
    ]);
}

// Kiruvchi yangilanishlarni qayta ishlash
$update = json_decode(file_get_contents('php://input'), true);

if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];

    // Foydalanuvchiga Invoice yuborish uchun buyruq
    if ($message['text'] === '/buy') {
        sendInvoice($chat_id);
    }
}

if (isset($update['pre_checkout_query'])) {
    $pre_checkout_query_id = $update['pre_checkout_query']['id'];

    // To'lovga ruxsat berish
    callTelegramApi('answerPreCheckoutQuery', [
        'pre_checkout_query_id' => $pre_checkout_query_id,
        'ok' => true
    ]);
}

if (isset($update['message']['successful_payment'])) {
    $paymentData = $update['message']['successful_payment'];

    // Muvaffaqiyatli to'lovni qayta ishlash
    processSuccessfulPayment($paymentData);
}
?>