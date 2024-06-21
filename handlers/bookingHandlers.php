<?php 
require_once("./responses/bookingResponse.php");
require_once("./middleware/bookingMidware.php");

function handleClinicList($conn, $messageId, $name, $phone, $url, $headers) {
    $getclinic = book($name, $phone);
    clincList($phone, $getclinic, $url, $headers);
}

function handleDateList($conn, $messageId, $name, $phone, $type, $url, $headers) {
    $getdate = getday($name, $phone, $type);
    sendDate($phone, $getdate, $url, $headers);
}

function handleNamePrompt($conn, $messageId, $phone, $headers) {
    $message = "Please enter your name";
    name($phone, $message, $headers);
}

function handleNameInput($conn, $messageId, $phone, $content, $headers) {
    echo $content;exit;
    name($phone, $message, $headers);
    return $content; 
}

function handleSlotsList($conn, $messageId, $name, $phone, $url, $headers, $dateid, $clinicid) {
    $slots = getslots($name, $phone, $clinicid, $dateid);
    sendslots($phone, $slots, $url, $headers);
}

function handleBooking($conn, $messageId, $name, $phone, $description, $type, $url, $headers, $sessionData,$patientname) {

    $clinicid = $sessionData['clinicid'] ?? '';
    $dateid = $sessionData['dateid'] ?? '';
    $slotname = str_replace('_slot', '', $description);
    $slottime = $type;
    $patientname = $sessionData['patientname'] ?? '';

    $res = dobooking($name, $phone, $clinicid, $dateid, $slotname, $slottime, $patientname);
    $response = json_decode($res, true);
    $spaces = str_repeat(" ", 10);
    $message =  "\n" . $spaces . "*" . $response['message'] . "*" . "\n";
    $message .= "\n";
    $message .= "Name: " . $patientname . "\n";
    $message .= "Slot: " . $slotname . "\n";
    $message .= "Time: " . $slottime . "\n";

    confirmation($phone, $message, $headers);
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