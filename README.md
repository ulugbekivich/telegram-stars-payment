# telegram-stars-payment

Ushbu repository Telegram botining soddalashtirilgan implementatsiyasini o'z ichiga oladi. Bot foydalanuvchilarga Telegram orqali to'lovlarni amalga oshirish imkoniyatini beradi.

## Xususiyatlar

- **Invoice yuborish**: Foydalanuvchi Telegram chatida invoice (to'lov summasi) oladi.
- **To'lovga ruxsat berish**: Bot foydalanuvchidan to'lovni tasdiqlash uchun so'rovni qayta ishlaydi.
- **To'lovni tasdiqlash**: To'lov muvaffaqiyatli amalga oshirilganda, bot tafsilotlarni foydalanuvchiga yuboradi.

## Ishlash Printsipi

1. **Invoice yuborish**: Foydalanuvchi `/buy` komandasi orqali invoice oladi. Invoice-da xizmat yoki premium a'zo bo'lish uchun narx va ma'lumotlar ko'rsatiladi.
2. **To'lovga ruxsat berish**: Foydalanuvchi to'lovni tasdiqlaganida, bot `pre_checkout_query`ni qayta ishlaydi va to'lovga ruxsat beradi.
3. **To'lovni tasdiqlash**: To'lov muvaffaqiyatli amalga oshgandan so'ng, bot foydalanuvchiga tasdiqlash xabarini yuboradi va tafsilotlarni saqlaydi.

## Fayl Tuzilishi

- `bot.php`: Botning asosiy fayli. Barcha funksiyalar va yangilanishlarni qayta ishlashni o'z ichiga oladi.

## Asosiy Funksiyalar

### 1. Telegram API bilan muloqot qilish

```php
function callTelegramApi(string $method, array $data = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.telegram.org/bot' . API_KEY . '/' . $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
```

Ushbu funksiya Telegram API bilan ishlashni soddalashtiradi. U API'ga so'rov yuboradi va javobni qaytaradi.

### 2. Invoice yuborish funksiyasi

```php
function sendInvoice($chat_id) {
    $invoiceData = [
        'chat_id' => $chat_id,
        'title' => 'Premium A\'zolik',
        'description' => 'Botimizning barcha funksiyalaridan foydalanish uchun premium a\'zolikni sotib oling.',
        'payload' => 'invoice-payload-12345',
        'currency' => 'XTR',
        'prices' => [
            ['label' => 'Premium A\'zolik', 'amount' => 1000]
        ],
        'start_parameter' => 'start-premium'
    ];

    callTelegramApi('sendInvoice', $invoiceData);
}
```

Foydalanuvchiga premium a'zolik uchun invoice yuboradi.

### 3. To'lovga ruxsat berish funksiyasi

```php
if (isset($update['pre_checkout_query'])) {
    $pre_checkout_query_id = $update['pre_checkout_query']['id'];

    callTelegramApi('answerPreCheckoutQuery', [
        'pre_checkout_query_id' => $pre_checkout_query_id,
        'ok' => true
    ]);
}
```

Ushbu kod `pre_checkout_query` so'rovini qayta ishlaydi va to'lovni davom ettirishga ruxsat beradi.

### 4. Muvaffaqiyatli to'lovni qayta ishlash funksiyasi

```php
function processSuccessfulPayment($paymentData) {
    $chat_id = $paymentData['chat']['id'];
    $user_id = $paymentData['from']['id'];
    $currency = $paymentData['successful_payment']['currency'];
    $total_amount = $paymentData['successful_payment']['total_amount'] / 100;
    $invoice_payload = $paymentData['successful_payment']['invoice_payload'];
    $telegram_payment_id = $paymentData['successful_payment']['telegram_payment_charge_id'];
    $provider_payment_id = $paymentData['successful_payment']['provider_payment_charge_id'];

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
```

To'lov muvaffaqiyatli amalga oshganda, ushbu funksiya foydalanuvchiga to'lov haqida ma'lumot yuboradi.

## Jarayonni Qisqacha Tavsifi

1. **Foydalanuvchi invoice oladi**: `/buy` komandasi orqali.
2. **To'lovga ruxsat beriladi**: `pre_checkout_query` so'rovi qayta ishlanadi.
3. **To'lov muvaffaqiyatli amalga oshiriladi**: Bot foydalanuvchiga tasdiqlash xabarini yuboradi.

## Qanday Ishlatiladi

1. `API_KEY` ni `bot.php` fayliga qo'shing.
2. Serveringizga ushbu faylni joylashtiring.
3. Webhook sozlamalarini Telegram API orqali sozlang.
4. `/buy` komandasi orqali invoice jarayonini sinab ko'ring.

## Eslatma

- Ushbu kod faqat o'quv maqsadida yozilgan. Real loyihalarda xavfsizlik choralari ko'rilishi tavsiya etiladi.
- `provider_token` kerak bo'lmagan holatlar uchun o'chirilgan.
