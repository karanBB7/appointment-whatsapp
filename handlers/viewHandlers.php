<?php 
require_once("responses/viewResponse.php");
require_once("middleware/viewMidware.php");


function handleFetchAppointments($conn, $messageId, $name, $phone, $url, $headers) {
    $viewAppointments = getAppointments($name, $phone);
    // echo $viewAppointments;exit;
    $response = json_decode($viewAppointments, true);

    if (isset($response['booking_data']) && is_array($response['booking_data']) && count($response['booking_data']) > 0) {
        $patientName = ucfirst($response['booking_data'][0]['patient_name'] ?? $name);
        $message = "*Dear $patientName*, Your appointment details:\n\n";

        if (count($response['booking_data']) > 1) {
            foreach ($response['booking_data'] as $index => $appointment) {
                $clinicName = $appointment['clinic_name'] ?? 'N/A';
                $time = $appointment['Time'] ?? 'N/A';
                $bookingDate = isset($appointment['booking_date']) ? date('l, F j, Y', strtotime($appointment['booking_date'])) : 'N/A';
                $doctorName = $appointment['fullname'] ?? 'your doctor'; 
                $appointmentNumber = $index + 1;
                $message .= "*Appointment $appointmentNumber:*\n";
                $message .= "Your appointment with *$doctorName* at *$clinicName* on *$bookingDate* at *$time* is accepted. ";
                $message .= "\n\n";
            }
        } else {
            $appointment = $response['booking_data'][0];
            $clinicName = $appointment['clinic_name'] ?? 'N/A';
            $time = $appointment['Time'] ?? 'N/A';
            $bookingDate = isset($appointment['booking_date']) ? date('l, F j, Y', strtotime($appointment['booking_date'])) : 'N/A';
            $doctorName = $appointment['fullname'] ?? 'your doctor'; 
            $message .= "Your appointment with *$doctorName* at *$clinicName* on *$bookingDate* at *$time* is accepted. ";
            $message .= "\n\n";
        }
        $message .= "Thank you for choosing our services. We look forward to seeing you.";

    } else {
        $message = "No appointments found.";
    }
    
    appointments($phone, $message, $headers);
}


?>