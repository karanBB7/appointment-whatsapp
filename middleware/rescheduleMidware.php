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
        $today = new DateTime();
        $tomorrow = new DateTime('tomorrow');
        $dayAfterTomorrow = new DateTime('tomorrow +1 day');
        $todayFormatted = $today->format('d/m/Y l');
        $tomorrowFormatted = $tomorrow->format('d/m/Y l');
        $dayAfterTomorrowFormatted = $dayAfterTomorrow->format('d/m/Y l');
        
        foreach ($listMessage as $id => $day) {
            if ($day == "Today") {
                $description = $todayFormatted;
            } elseif ($day == "Tomorrow") {
                $description = $tomorrowFormatted;
            } elseif ($day == "day after") {
                $description = $dayAfterTomorrowFormatted;
            } else {
                $description = '';
            }
            $rows[] = array(
                'id' => $id,
                'title' => $day,
                'description' => $description
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
