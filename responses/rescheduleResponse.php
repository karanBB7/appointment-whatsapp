<?php

function makeApiRequestReschedule($payload) {
    $curl = curl_init();
    
    $defaultOptions = [
        CURLOPT_URL => 'http://13.234.213.35/webhook-appointment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Basic bGlucW1kOlNAaVBrSG1GU2FpOXo='
        ],
    ];

    curl_setopt_array($curl, $defaultOptions);

    try {
        $response = curl_exec($curl);
        if ($response === false) {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }
        return $response;
    } catch (Exception $e) {
        error_log("cURL Error: " . $e->getMessage());
        return false;
    } finally {
        curl_close($curl);
    }
}


function getBookedDates($username, $phonenumber) {
    $payload = [
        "username" => $username,
        "mobilenumber" => $phonenumber,
        "type" => "2"
    ];
    return makeApiRequestReschedule($payload);
}

function getRescheduleDates($username, $phonenumber, $bookingDateID) {
    $payload = [
        "username" => $username,
        "mobilenumber" => $phonenumber,
        "type" => "2",
        "booking_date" => $bookingDateID
    ];
    return makeApiRequestReschedule($payload);
}

function getRescheduleSlots($username, $phonenumber, $bookingDateID, $dateId) {
    $payload = [
        "username" => $username,
        "mobilenumber" => $phonenumber,
        "type" => "2",
        "booking_date" => $bookingDateID,
        "date" => $dateId
    ];
    return makeApiRequestReschedule($payload);
}


function getRescheduleStatus($username, $phonenumber,  $bookingDateID, $dateId, $slot_name, $slot_time) {
    $payload = [
        "username" => $username,
        "mobilenumber" => $phonenumber,
        "type" => "2",
        "booking_date" => $bookingDateID,
        "date" => $dateId,
        "slot_name" => $slot_name,
        "slot_time" => $slot_time
    ];
    return makeApiRequestReschedule($payload);
}



?>