<?php 

require_once(__DIR__ . "/../responses/cancelResponse.php");
require_once(__DIR__ . "/../middleware/cancelMidware.php");

function handleGetDatesToDrop($conn, $messageId, $name, $phone, $url, $headers){
    $getDatesToDrop = getDatesToDrop($name, $phone);
    $datesArray = json_decode($getDatesToDrop, true);
    if (isset($datesArray['status']) && $datesArray['status'] == "error") {
        $message = $datesArray['message'];
        confirmation($phone, $message, $headers);
        return "complete";
    } else {
        sendDatesToCancel($phone, $getDatesToDrop, $url, $headers);
        return "SelectDatesToCancel";
    }
}

function handleGetDropStatus($conn, $messageId, $name, $phone, $type, $url, $headers) {
    $dropStatus = dropDates($name, $phone, $type);
    $response = json_decode($dropStatus, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($response['status']) && isset($response['message'])) {
        if ($response['status'] === "success" || $response['status'] === "sucess") {
            $spaces = str_repeat(" ", 10);
            $message = "\n" . $spaces . "*" . $response['message'] . "*" . "\n";
            cancelAppointment($phone, $message, $headers);
        } else {
            $spaces = str_repeat(" ", 10);
            $message = "\n" . $spaces . "*Error: " . $response['message'] . "*\n";
            cancelAppointment($phone, $message, $headers);
        }
    } else {
        $spaces = str_repeat(" ", 10);
        $message = "\n" . $spaces . "*Unexpected response format or JSON error.*\n";
        cancelAppointment($phone, $message, $headers);
    }
}



?>