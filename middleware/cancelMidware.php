<?php 

function sendDatesToCancel($phone, $getDatesToDrop, $url, $headers) {
    $responseArray = json_decode($getDatesToDrop, true);

    if (isset($responseArray['booking_date']) && is_array($responseArray['booking_date'])) {
        $listMessage = $responseArray['booking_date'];
        $rows = array();

        foreach ($listMessage as $id => $item) {
            $dateTime = new DateTime($item);
            $formattedDate = $dateTime->format('l jS F');
            $formattedTime = $dateTime->format('g:i A');
            
            $rows[] = array(
                'id' => $id,
                'title' => $formattedDate, 
                'description' => $formattedTime 
            );
        }

        $data = array(
            'to' => $phone,
            'interactive' => array(
                'type' => 'list',
                'header' => array(
                    'type' => 'text',
                    'text' => 'Cancel Appointment',
                ),
                'body' => array(
                    'text' => 'Please select the slot you want to Cancel',
                ),
                'action' => array(
                    'button' => 'Select Options',
                    'sections' => array(
                        array(
                            'title' => 'Select the following:',
                            'rows' => $rows,
                        ),
                    ),
                ),
            ),
        );

        return sendWhatsAppMessage($url, $data, $headers);
    } else {
        return "Invalid response format";
    }
}

function cancelAppointment($phone, $message, $headers) {
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