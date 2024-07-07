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



function sendslotsReschedule($phone, $slots, $slotName, $url, $headers) {
    $response = json_decode($slots, true);
    $slotKey = strtolower($slotName) . '_slot';
    $rows = array();
    foreach ($response[$slotKey] as $time_id => $time_title) {
        $rows[] = array(
            'id' => $time_id,
            'title' => $time_title,
            'description' => $slotName . ' slot',
        );
        if (count($rows) >= 10) break; 
    }
    
    $sections = array(
        array(
            'title' => $slotName,
            'rows' => $rows,
        )
    );

    $data = array(
        'to' => $phone,
        'type' => 'interactive',
        'interactive' => array(
            'type' => 'list',
            'header' => array(
                'type' => 'text',
                'text' => 'Choose a Time Slot',
            ),
            'body' => array(
                'text' => "Select from available " . strtolower($slotName) . " time slots." . 
                          (count($rows) == 10 ? "\nShowing first 10 slots." : ""),
            ),
            'action' => array(
                'button' => 'View Slots',
                'sections' => $sections,
            ),
        ),
    );

    return sendWhatsAppMessage($url, $data, $headers);
}


function sendRescheduleTimeSlotName($phone, $url, $headers) {
    $data = array(
        'to' => $phone,
        'interactive' => array(
            'type' => 'list',
            'header' => array(
                'type' => 'text',
                'text' => 'Choose your convenient time slot',
            ),
            'body' => array(
                'text' => 'Please select the respective activity given below:',
            ),
            'action' => array(
                'button' => 'Slots',
                'sections' => array(
                    array(
                        'title' => 'Select The following',
                        'rows' => array(
                            array(
                                'id' => "1",
                                'title' => "Morning",
                            ),
                            array(
                                'id' => "2",
                                'title' => "Afternoon",
                            ),
                            array(
                                'id' => "3",
                                'title' => "Evening",
                            ),
                        ),
                    ),
                ),
            ),
        ),
    );
    return sendWhatsAppMessage($url, $data, $headers);
}



?>
