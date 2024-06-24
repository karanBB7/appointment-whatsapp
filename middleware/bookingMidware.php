<?php

function Listappointment($phone, $response, $url, $headers) {
    $responseArray = json_decode($response, true);
    if (isset($responseArray['list_message'])) {
        $listMessage = $responseArray['list_message'];
        $rows = array();
        foreach ($listMessage as $id => $title) {
            $rows[] = array(
                'id' => $id,
                'title' => $title,
                'description' => '' 
            );
        }
        $rows[] = array(
            'id' => '5',
            'title' => 'Other Services',
            'description' => ''
        );

        $data = array(
            'to' => $phone,
            'interactive' => array(
                'type' => 'list',
                'header' => array(
                    'type' => 'text',
                    'text' => 'How can I help you?',
                ),
                'body' => array(
                    'text' => 'Please select the respective activity in given',
                ),
                'action' => array(
                    'button' => 'Select Options',
                    'sections' => array(
                        array(
                            'title' => 'Select The following',
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


function bookAppointmentList($phone, $url, $headers) {
    $data = array(
        'to' => $phone,
        'interactive' => array(
            'type' => 'list',
            'header' => array(
                'type' => 'text',
                'text' => 'How can I help you?',
            ),
            'body' => array(
                'text' => 'Please select the respective activity given below:',
            ),
            'action' => array(
                'button' => 'Select Options',
                'sections' => array(
                    array(
                        'title' => 'Select The following',
                        'rows' => array(
                            array(
                                'id' => "1",
                                'title' => "Book Appointment",
                            )
                        ),
                    ),
                ),
            ),
        ),
    );
    return sendWhatsAppMessage($url, $data, $headers);
}


function clincList($phone, $getclinic, $url, $headers) {
    $responseArray = json_decode($getclinic, true);
    if (isset($responseArray['clinic'])) {
        $listMessage = $responseArray['clinic'];
        $rows = array();
        $counter = 1;
        foreach ($listMessage as $id => $description) {
            $rows[] = array(
                'id' => $id,
                'title' => "Clinic " . $counter,
                'description' => $description
            );
            $counter++;
        }

        $data = array(
            'to' => $phone,
            'interactive' => array(
                'type' => 'list',
                'header' => array(
                    'type' => 'text',
                    'text' => 'Which clinic do you want to visit the doctor?',
                ),
                'body' => array(
                    'text' => 'Please select the clinic',
                ),
                'action' => array(
                    'button' => 'Clinic',
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

function sendDate($name, $phone, $getdate, $url, $headers) {
    $responseArray = json_decode($getdate, true);
    if (isset($responseArray['date'])) {
        $listMessage = $responseArray['date'];
        $rows = array();
        foreach ($listMessage as $id => $date) {
            $rows[] = array(
                'id' => $id,
                'title' => $date,
            );
        }

        $data = array(
            'to' => $phone,
            'interactive' => array(
                'type' => 'list',
                'header' => array(
                    'type' => 'text',
                    'text' => 'When would you like to visit? '. $name,
                ),
                'body' => array(
                    'text' => 'Please choose the date',
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

function sendslots($phone, $slots, $url, $headers) {
    $response = json_decode($slots, true);
    $sections = array();
    
    function formatSlotTitle($title) {
        return ucfirst(str_replace('_slot', '', $title));
    }
    
    foreach ($response['slots'] as $slot_title => $times) {
        $rows = array();
        $formatted_title = formatSlotTitle($slot_title);
        
        foreach ($times as $time_id => $time_title) {
            $rows[] = array(
                'id' => $time_id,
                'title' => $time_title,
                'description' => $formatted_title,
            );
        }
        
        $sections[] = array(
            'title' => $formatted_title,
            'rows' => $rows,
        );
    }

    $data = array(
        'to' => $phone,
        'type' => 'interactive',
        'interactive' => array(
            'type' => 'list',
            'header' => array(
                'type' => 'text',
                'text' => 'Please choose time slot convenient to you',
            ),
            'body' => array(
                'text' => 'Please select the respective activity in given',
            ),
            'action' => array(
                'button' => 'Time slots',
                'sections' => $sections,
            ),
        ),
    );

    return sendWhatsAppMessage($url, $data, $headers);
}

function confirmation($phone, $message, $headers) {
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

function name($phone, $message, $headers) {
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

function sendWhatsAppMessage($url, $data, $headers) {
    if (!is_array($headers)) {
        $headers = array($headers);
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    // echo $result;exit;
    if (curl_errno($ch)) {
        error_log('Curl error: ' . curl_error($ch));
    }
    curl_close($ch);
    return $result;
}


?>