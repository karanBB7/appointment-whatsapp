<?php 

function appointments($phone, $message, $headers) {
    $url = "https://whatsappapi-79t7.onrender.com/send-text-message";
    $data = array(
        "messaging_product" => "whatsapp",
        "to" => $phone,
        "text" => array(
            "body" => $message
        )
    );
    return sendWhatsAppMessage($url, $data, $headers);
}

?>