<?php


function sendBookedData($phone, $url, $headers, $bookedDates) {
    $responseArray = json_decode($bookedDates, true);

    if (isset($responseArray['booking_date']) && is_array($responseArray['booking_date'])) {
        $listMessage = $responseArray['booking_date'];
        $rows = array();

        foreach ($listMessage as $id => $item) {
            $parts = explode(' ', $item);
            $date = $parts[0];
            $time = $parts[1] . ' ' . $parts[2];
            
            $rows[] = array(
                'id' => $id,
                'title' => $date, 
                'description' => $time 
            );
        }

        $data = array(
            'to' => $phone,
            'interactive' => array(
                'type' => 'list',
                'header' => array(
                    'type' => 'text',
                    'text' => 'Reschedule Appointment',
                ),
                'body' => array(
                    'text' => 'Please select the slot you want to Reschedule',
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

function sendRescheduleDates($phone, $rescheduleDays, $url, $headers) {
    $responseArray = json_decode($rescheduleDays, true);
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
                    'text' => 'When would you like to visit?',
                ),
                'body' => array(
                    'text' => 'Please select the respective activity given:',
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


function sendRescheduleSlots($phone, $rescheduleSlots, $url, $headers){
    $response = json_decode($rescheduleSlots, true);
    $sections = array();
    foreach ($response['slots'] as $slot_title => $times) {
        $rows = array();
        foreach ($times as $time_id => $time_title) {
            $rows[] = array(
                'id' => $time_id,
                'title' => $time_title,
                'description' => $slot_title, 
            );
        }
        $sections[] = array(
            'title' => $slot_title,
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
                'text' => 'Choose your preferred Time Slots',
            ),
            'body' => array(
                'text' => 'Please select the respective activity in given',
            ),
            'action' => array(
                'button' => 'Select Options',
                'sections' => $sections,
            ),
        ),
    );

    return sendWhatsAppMessage($url, $data, $headers);
}



?>
