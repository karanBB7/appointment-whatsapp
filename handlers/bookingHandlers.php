<?php 

function handleClinicList($conn, $messageId, $name, $phone, $url, $headers) {
    $getclinic = book($name, $phone);
    clincList($phone, $getclinic, $url, $headers);
    $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'datelist' WHERE id = $messageId";
    mysqli_query($conn, $updateQuery);
}

function handleDateList($conn, $messageId, $name, $phone, $type, $url, $headers) {
    $_SESSION['clinicid'] = $type;
    $getdate = getday($name, $phone, $type);
    sendDate($phone, $getdate, $url, $headers);
    $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'name' WHERE id = $messageId";
    mysqli_query($conn, $updateQuery);
}

function handleNameInput($conn, $messageId, $phone, $type, $headers) {
    $message = "Please enter your name";
    name($phone, $message, $headers);
    $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'slotslist' WHERE id = $messageId";
    mysqli_query($conn, $updateQuery);
    $_SESSION['dateid'] = $type;
}

function handleSlotsList($conn, $messageId, $name, $phone, $url, $headers, $content) {
    $_SESSION['content'] = $content; 
    $clinicid = $_SESSION['clinicid'] ?? 'undefined';
    $dateid = $_SESSION['dateid'] ?? 'undefined';
    $slots = getslots($name, $phone, $clinicid, $dateid);
    sendslots($phone, $slots, $url, $headers);
    $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'booking' WHERE id = $messageId";
    mysqli_query($conn, $updateQuery);
}

function handleBooking($conn, $messageId, $name, $phone, $description, $type, $url, $headers) {
    $patientname = $_SESSION['content'] ?? '';
    $clinicid = $_SESSION['clinicid'] ?? 'undefined';
    $dateid = $_SESSION['dateid'] ?? 'undefined';
    $slotname = str_replace('_slot', '', $description);
    $slottime = $type;
    $res = dobooking($name, $phone, $clinicid, $dateid, $slotname, $slottime, $patientname);
    
    $response = json_decode($res, true);
    
    $spaces = str_repeat(" ", 10);
    $message =  "\n" . $spaces . "*" . $response['message'] . "*" . "\n";
    $message .= "\n";
    $message .= "Name: " . $patientname . "\n";
    $message .= "Slot: " . $slotname . "\n";
    $message .= "Time: " . $slottime . "\n";

    confirmation($phone, $message, $headers);
    $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'done' WHERE id = $messageId";
    mysqli_query($conn, $updateQuery);
}
?>