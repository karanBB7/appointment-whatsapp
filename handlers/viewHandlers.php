<?php 
require_once("responses/viewResponse.php");
require_once("middleware/viewMidware.php");


function handleFetchAppointments($conn, $messageId, $name, $phone, $url, $headers) {
    $viewAppointments = getAppointments($name, $phone);
    $response = json_decode($viewAppointments, true);

    if (isset($response['booking_data']) && is_array($response['booking_data']) && count($response['booking_data']) > 0) {
        $spaces = str_repeat(" ", 10);
        $message = "\n" . $spaces . "*Your Appointments*\n";

        foreach ($response['booking_data'] as $appointment) {
            $clinicName = $appointment['clinic_name'] ?? 'N/A';
            $time = $appointment['Time'] ?? 'N/A';
            $bookingDate = $appointment['booking_date'] ?? 'N/A';

            $message .= "\nClinic Name: " . $clinicName;
            $message .= "\nTime: " . $time;
            $message .= "\nBooking Date: " . $bookingDate . "\n";
        }
    } else {
        $message = "No appointments found.";
    }
    
    appointments($phone, $message, $headers);
}


?>