<?php 
require_once("./responses/bookingResponse.php");
require_once("./middleware/bookingMidware.php");





function handleDateList($conn, $messageId, $name, $phone, $type, $url, $headers) {
    $getdate = getday($name, $phone, $type);
    sendDate($name, $phone, $getdate, $url, $headers);
}

function handleClinicList($conn, $messageId, $name, $phone, $dateid, $url, $headers) {

    // echo $dateid . ' ' . $phone;exit;

    $getclinic = getClinic($name, $phone, $dateid);
    // echo $getclinic;exit;
    clincList($phone, $getclinic, $url, $headers);
}

function handleTimeSlot($phone, $url, $headers){
    sendTimeSlotName($phone, $url, $headers);
}


function handleSlotsList($conn, $messageId, $name, $phone, $dateid, $clinicid, $slotName, $dateName, $url, $headers) {
    $slots = getslots($name, $phone, $dateid, $clinicid);
    $slotsData = json_decode($slots, true);
    
    $selectedSlots = [];
    
    $slotKey = strtolower($slotName) . '_slot';
    if (isset($slotsData['slots'][$slotKey])) {
        $selectedSlots = $slotsData['slots'][$slotKey];
    } else {
        echo json_encode(["message" => "No slots available for " . $slotName]);
        // exit;
    }
    
    $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    
    if (DateTime::createFromFormat('Y-m-d', $dateid) !== false) {
        $appointmentDate = new DateTime($dateid, new DateTimeZone('Asia/Kolkata'));
    } else {
        $appointmentDate = $currentDateTime;
    }

    $filteredSlots = [];

    if ($dateName == 'Today') {
        foreach ($selectedSlots as $time => $value) {
            $slotDateTime = DateTime::createFromFormat('Y-m-d g:i A', $appointmentDate->format('Y-m-d') . ' ' . $time, new DateTimeZone('Asia/Kolkata'));
            if ($slotDateTime > $currentDateTime) {
                $filteredSlots[$time] = $value;
            }
        }
    } else {
        $filteredSlots = $selectedSlots;
    }
    
    if (empty($filteredSlots)) {
        sendErrorMessage($phone, "No slots available", $headers);
        sendTimeSlotName($phone, $url, $headers);
        return "slotslist";
       
    } else {
        $slots = json_encode([$slotKey => $filteredSlots]);
        sendslots($phone, $slots, $slotName, $url, $headers);
        return "name";
    }
}


function handleNamePrompt($conn, $messageId, $phone, $headers) {
    $message = "Please enter your name";
    name($phone, $message, $headers);
}

function handleNameInput($conn, $messageId, $phone, $content, $headers) {
    name($phone, $message, $headers);
    return $content; 
}

function handleBooking($conn, $messageId, $name, $phone, $url, $headers, $sessionData) {

    $doctorname = $sessionData['name'] ?? '';
    $clinicname = $sessionData['clinicname'] ?? '';
    $dateid = $sessionData['dateid'] ?? '';
    $clinicid = $sessionData['clinicid'] ?? '';
    $slotTime = $sessionData['slotTime'] ?? '';
    $slotName = strtolower($sessionData['slotName'] ?? '');
    $patientname = $sessionData['patientname'] ?? '';
    $appointmentDate = getAppointmentDate($dateid);

    $res = dobooking($name, $phone, $dateid, $clinicid, $slotName, $slotTime, $patientname);

    $response = json_decode($res, true);

    if (strtolower($response['status']) == 'success' || strtolower($response['status']) == 'sucess') {
        if ($response['type'] == 'booking') {
            $message = "*Dear $patientname*, your appointment with *$doctorname* at *$clinicname* on *$appointmentDate* at *$slotTime* is confirmed.";
        } elseif ($response['type'] == 'request') {
            $message = "*Dear $patientname*, your request for appointment with *$doctorname* at *$clinicname* on *$appointmentDate* at *$slotTime* is accepted. Someone from the clinic will call and confirm the appointment shortly.";
        }
    } else {
        $message = "There was an error with your booking.";
    }
    confirmation($phone, $message, $headers);

}


function getAppointmentDate($dateid) {
    $date = new DateTime();
    if ($dateid == 1) {
    } elseif ($dateid == 2) {
        $date->add(new DateInterval('P1D'));
    } elseif ($dateid == 3) {
        $date->add(new DateInterval('P2D'));
    }

    return $date->format('Y-m-d (l)');
}

function getSessionData($conn, $phone, $key) {
    $query = "SELECT data FROM user_sessions WHERE phone = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $data = json_decode($row['data'], true);
    return $data[$key] ?? null;
}

?>