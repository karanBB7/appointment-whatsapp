<?php 
require_once("responses/rescheduleResponse.php");
require_once("middleware/rescheduleMidware.php");


function handleGetBookedDate($conn, $messageId, $name, $phone, $url, $headers) {
    $bookedDates = getBookedDates($name, $phone);
    $bookedDatesArray = json_decode($bookedDates, true);
    if (isset($bookedDatesArray['status']) && $bookedDatesArray['status'] == "error") {
        $message = $bookedDatesArray['message'];
        confirmation($phone, $message, $headers);
        return "complete";
    } else {
        sendBookedData($phone, $url, $headers, $bookedDates);
        return "bookedDates";
    }
}
function handleGetDayReschedule($conn, $messageId, $name, $phone, $type, $url, $headers) {
    $rescheduleDays = getRescheduleDates($name, $phone, $type);
    sendRescheduleDates($phone, $rescheduleDays, $url, $headers);
}


function handleTimeSlotReschedule($phone, $url, $headers){
    sendRescheduleTimeSlotName($phone, $url, $headers);
}


function handleRescheduleSlots($conn, $messageId, $name, $phone, $bookingDateID, $rescheduleDate, $slotName, $rescheduleDateName, $url, $headers) {
    $rescheduleSlots = getRescheduleSlots($name, $phone, $bookingDateID, $rescheduleDate);
    $slotsData = json_decode($rescheduleSlots, true);
    
    $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $appointmentDate = DateTime::createFromFormat('Y-m-d', $rescheduleDate, new DateTimeZone('Asia/Kolkata'));

    if (!$appointmentDate) {
        $appointmentDate = $currentDateTime;
    }

    $isToday = $slotName === 'Today';
    $slotKey = strtolower($rescheduleDateName) . '_slot';
    
    $filteredSlots = [];
    
    if ($isToday) {
        if (isset($slotsData['slots'][$slotKey])) {
            foreach ($slotsData['slots'][$slotKey] as $time => $value) {
                $slotDateTime = DateTime::createFromFormat('Y-m-d g:i A', $appointmentDate->format('Y-m-d') . ' ' . $time, new DateTimeZone('Asia/Kolkata'));
                if ($slotDateTime > $currentDateTime) {
                    $filteredSlots[$time] = $value;
                }
            }
        }
    } else {
        // For tomorrow and day after, keep all slots for the specified time period
        $filteredSlots = $slotsData['slots'][$slotKey] ?? [];
    }

    if (empty($filteredSlots)) {
        sendErrorMessage($phone, "No " . $rescheduleDateName . " slots available for " . $slotName, $headers);
        sendRescheduleTimeSlotName($phone, $url, $headers);
        return "dateReschedule";
    } else {
        $slots = json_encode([$slotKey => $filteredSlots]);
        sendslotsReschedule($phone, $slots, $rescheduleDateName, $url, $headers);
        return "slotReschedule";
    }
}


function handleReschedule($conn, $messageId, $name, $phone, $bookingDateID, $rescheduleDate, $slotName, $slotTime, $url, $headers){
    $slotname = strtolower($slotName);
    $rescheduleStatus = getRescheduleStatus($name, $phone, $bookingDateID, $rescheduleDate, $slotname, $slotTime);
    $response = json_decode($rescheduleStatus, true);
    if($response['status'] == "success"){
        $spaces = str_repeat(" ", 10);
        $message =  "\n" . $spaces . "*" . $response['message'] . "*" . "\n";
        $message .= "Slot: " . $slotname . "\n";
        $message .= "Time: " . $slotTime . "\n";
        confirmation($phone, $message, $headers);
    }

}



?>