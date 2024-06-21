<?php 
require_once("responses/rescheduleResponse.php");
require_once("middleware/rescheduleMidware.php");


function handleGetBookedDate($conn, $messageId, $name, $phone, $url, $headers){
    $bookedDates = getBookedDates($name, $phone);
    sendBookedData($phone, $url, $headers, $bookedDates);
}

function handleGetDayReschedule($conn, $messageId, $name, $phone, $type, $url, $headers) {
    $rescheduleDays = getRescheduleDates($name, $phone, $type);
    sendRescheduleDates($phone, $rescheduleDays, $url, $headers);
}


function handleRescheduleSlots($conn, $messageId, $name, $phone, $bookingDateID, $type, $url, $headers){
    $rescheduleSlots = getRescheduleSlots($name, $phone, $bookingDateID, $type);
    sendRescheduleSlots($phone, $rescheduleSlots, $url, $headers);
}

function handleReschedule($conn, $messageId, $name, $phone, $bookingDateID, $description, $rescheduleDate, $type, $url, $headers){
    $slotname = str_replace('_slot', '', $description);
    $slottime = $type;

    $rescheduleStatus = getRescheduleStatus($name, $phone, $bookingDateID, $rescheduleDate, $slotname, $slottime);
    $response = json_decode($rescheduleStatus, true);
    if($response['status'] == "success"){
        $spaces = str_repeat(" ", 10);
        $message =  "\n" . $spaces . "*" . $response['message'] . "*" . "\n";
        $message .= "Slot: " . $slotname . "\n";
        $message .= "Time: " . $slottime . "\n";
        confirmation($phone, $message, $headers);
    }

}


?>