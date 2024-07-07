<?php



declare(ticks=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once("handlers/bookingHandlers.php");
require_once("handlers/rescheduleHandlers.php");
require_once("handlers/cancelHandlers.php");
require_once("handlers/viewHandlers.php");
require_once("middleware/viewMidware.php");


function logError($message) {
    error_log($message, 3, '/var/www/html/appointment/log/appointment_daemon.err.log');
}

$url = 'https://whatsappapi-79t7.onrender.com/interact-messages';
$headers = array(
    'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJPd25lck5hbWUiOiJCaXp0ZWNobm9zeXMtbWlkd2FyZSIsInBob25lTnVtYmVySWQiOiIyNDg4OTg2NDQ5NzI0MDQiLCJ3aGF0c2FwcE1ldGFUb2tlbiI6IkVBQXhWMWc0dDI0UUJPd2ZBOGw1Q3d6Tm1qNUlvaHlWUkdaQWNKemRpTW9xb3hMWDZ1a3h3cVEzSDlGZVRHZUVuVmxaQkRhMXc0dUYxUzczUUk0OVkwTEpPQ1hJU0tTd2dBZkJnZ1N6dzNyUWlWSmtLRWt0Q0lMaTlqdzNRbUhXMmxnWFpBaXlwdXdaQ3FhSmRRaXBsb0M1SEtyYUx0ODZiSnVtSEt3RUFXNGthMGRaQlRPNWl4dWV1R1Ztb0daQ2JLbkZBUEEwVzkwWkNVR2dSZ29oIiwiaWF0IjoxNzA5MjAwMTEwfQ.ZMy9wpBxphJbpEOYI3bBchlywwKCIN23GJiYrDlvXyc',
    'Content-Type: application/json',
);

function checkUsernumber($phone, $url, $headers){
    handleCheckNumber($phone, $url, $headers);
}
checkUsernumber("919964642973", $url, $headers);




$conn = mysqli_connect("localhost", "root", "", "appointment");
$query = "SELECT id, messages, fromNumber, buttonText,title, description, status, listid 
FROM received_whatsapp_messagebot 
WHERE status IN (0, 2, 3) AND processing = 0";
$result = mysqli_query($conn, $query);

if (!$result) {
throw new Exception('Error in SQL query: ' . mysqli_error($conn));
}


$messagesToProcess = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messagesToProcess[] = $row;
}

foreach ($messagesToProcess as $row) {
    processMessage($conn, $row, $url, $headers);
}

function processMessage($conn, $row, $url, $headers) {
    $messageId = $row['id'];
    $phone = $row['fromNumber'];
    $content = $row['messages'];
    $type = $row['listid'];
    $status = $row['status'];
    $description = $row['description'];
    $title = $row['title'];

    mysqli_begin_transaction($conn);

    try {
        $lockQuery = "UPDATE received_whatsapp_messagebot SET processing = 1 WHERE id = ? AND processing = 0";
        $lockStmt = $conn->prepare($lockQuery);
        $lockStmt->bind_param("i", $messageId);
        $lockStmt->execute();
        
        if ($lockStmt->affected_rows === 0) {
            throw new Exception("Message already being processed");
        }

        $session = getOrCreateSession($conn, $phone);
        $currentStep = $session ? $session['current_step'] : 'no record';
        $sessionData = $session ? json_decode($session['data'], true) : [];

        echo "Initial state - Phone: $phone, CurrentStep: $currentStep, Content: $content\n";

        if (!empty($content) && ($currentStep === 'no record' || $currentStep === 'complete')) {
            $responseData = handleCheckNumber($phone, $url, $headers);
            
            if (!is_array($responseData)) {
                throw new Exception("Invalid response from handleCheckNumber");
            }

            if (isset($responseData['success']) && $responseData['success'] === "true") {
                $name = $responseData['Username'];
                $sessionData['name'] = $name;
                $currentStep = 'initial';
                
                updateSession($conn, $phone, $currentStep, $sessionData);
                echo "Updated session to 'initial' - Phone: $phone, Name: $name\n";
            } else {
                throw new Exception("Unable to find user information");
            }
        } elseif (empty($content) && in_array($type, ['1', '2', '3', '4'])) {
            if ($session) {
                $sessionData['storedType'] = $type;
                $currentStep = 'StartProcess';
                updateSession($conn, $phone, $currentStep, $sessionData);
                echo "Updated session to 'StartProcess' - Phone: $phone, StoredType: $type\n";
            } else {
                throw new Exception("No session found for phone: $phone");
            }
        }

        echo "Handling section - Phone: $phone, CurrentStep: $currentStep, Type: $type\n";

        $newStep = handleSection($conn, $currentStep, $messageId, $sessionData, $phone, $status, $type, $description, $title, $url, $headers, $content);

        if ($newStep !== $currentStep) {
            updateSession($conn, $phone, $newStep, $sessionData);
            echo "Updated session to new step - Phone: $phone, NewStep: $newStep\n";
        }

        $session = getOrCreateSession($conn, $phone);
        $updatedStep = $session['current_step'];
        echo "Updated step - Phone: $phone, UpdatedStep: $updatedStep, Expected new step: $newStep\n";

        if ($newStep !== $updatedStep) {
            throw new Exception("Step mismatch: Expected $newStep, but got $updatedStep");
        }

        $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, processing = 0 WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $messageId);
        $stmt->execute();

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "Exception: " . $e->getMessage() . "\n";
    }
}





function deleteOldData($conn, $phone) {
    $query = "DELETE FROM received_whatsapp_messagebot WHERE fromNumber = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $phone);
    $stmt->execute();

    $query = "DELETE FROM user_sessions WHERE phone = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
}





function moveToUserHistory($conn, $phone) {
    $query = "SELECT * FROM user_sessions WHERE phone = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $session = $result->fetch_assoc();

    if ($session) {
        $decodedData = json_decode($session['data'], true);
        $cleanData = json_encode($decodedData, JSON_UNESCAPED_SLASHES);

        $historyObject = [
            'data' => $cleanData,
            'last_updated' => $session['last_updated']
        ];
        $historyJson = json_encode($historyObject, JSON_UNESCAPED_SLASHES);
        $query = "INSERT INTO user_history (phone, history, createdAt) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $phone, $historyJson);
        $stmt->execute();
    }
}

function getOrCreateSession($conn, $phone) {
    $query = "SELECT * FROM user_sessions WHERE phone = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    } else {
        return $result->fetch_assoc();
    }
}

function updateSession($conn, $phone, $currentStep, $data) {
    $query = "INSERT INTO user_sessions (phone, current_step, data, last_updated) 
              VALUES (?, ?, ?, NOW()) 
              ON DUPLICATE KEY UPDATE 
              current_step = VALUES(current_step), 
              data = VALUES(data), 
              last_updated = NOW()";
    $stmt = $conn->prepare($query);
    $jsonData = json_encode($data);
    $stmt->bind_param("sss", $phone, $currentStep, $jsonData);
    $stmt->execute();
}

function handleInitialResponse($conn, $messageId, $name, $phone, $url, $headers) {
    $response = checkUser($name, $phone);
    $responseData = json_decode($response, true);
    if (!empty($responseData['booking_data'])) {
        $response = listMesasage($name, $phone);
        Listappointment($phone, $response, $url, $headers);
    } else {
        bookAppointmentList($phone,$url,$headers);
    }    
}

function handleSection($conn, $currentStep, $messageId, $sessionData, $phone, $status, $type, $description, $title, $url, $headers, $content) {
    $name = $sessionData['name'] ?? '';
    $storedType = $sessionData['storedType'] ?? null;
    $clinicid = $sessionData['clinicid'] ?? null;
    $clinicname = $sessionData['clinicname'] ?? null;
    $dateid = $sessionData['dateid'] ?? null;
    $dateName = $sessionData['dateName'] ?? null;
    $slotName = $sessionData['slotName'] ?? null;
    $slotTime = $sessionData['slotTime'] ?? null;
    $patientname = $sessionData['patientname'] ?? '';
    
    $nextStep = $currentStep; 

    if ($storedType === "1") {
        switch ($currentStep) {
            case "StartProcess":
                handleDateList($conn, $messageId, $name, $phone, $type, $url, $headers);
                $nextStep = "cliniclist";
                break;

            case "cliniclist":
                $dateid = $type;
                $sessionData['dateid'] = $dateid;
                $dateName = $title;
                $sessionData['dateName'] = $dateName;
                handleClinicList($conn, $messageId, $name, $phone,$dateid , $url, $headers);
                $nextStep = "timeSlots";
                break;


            case "timeSlots":
                $clinicid = $type;
                $sessionData['clinicid'] = $clinicid;
                $clinicname = $description;
                $sessionData['clinicname'] = $clinicname;
                handleTimeSlot($phone, $url, $headers);
                $nextStep = "slotslist";
                break;

            
            case "slotslist":
                $slotName = $title;
                $sessionData['slotName'] = $slotName;
                $nextStep = handleSlotsList($conn, $messageId, $name, $phone, $dateid, $clinicid, $slotName, $dateName, $url, $headers);
                break;

            case "name":
                $slotTime = $type;
                $sessionData['slotTime'] = $slotTime;
                if (empty($content)) {
                    handleNamePrompt($conn, $messageId, $phone, $headers);
                    $nextStep = "booking"; 
                } else {
                    $sessionData['name'] = $name;
                    handleNameInput($conn, $messageId, $phone, $content, $headers);
                    $nextStep = "booking";
                }
                break;

            case "booking":
                $patientname = $content;
                $sessionData['patientname'] = $patientname;
                handleBooking($conn, $messageId, $name, $phone, $url, $headers, $sessionData);
                $nextStep = "complete";
                break;
        }

    } else if ($storedType === "2") {
        $bookingDateID = $sessionData['bookingDateID'] ?? null;
        $rescheduleDate = $sessionData['rescheduleDate'] ?? null;
        $slotName = $sessionData['slotName'] ?? null;
        switch ($currentStep) {
            case "StartProcess":
                $nextStep = handleGetBookedDate($conn, $messageId, $name, $phone, $url, $headers);
                break;
            case "bookedDates":
                $sessionData['bookingDateID'] =  $type;
                handleGetDayReschedule($conn, $messageId, $name, $phone, $type, $url, $headers);
                $nextStep = "timeSlotsReschedule";
                break;

            case "timeSlotsReschedule":
                $sessionData['rescheduleDate'] =  $type;
                handleTimeSlotReschedule($phone, $url, $headers);
                $nextStep = "dateReschedule";
                break;


            case "dateReschedule":
                $slotName = $title;
                $sessionData['slotName'] = $slotName;
                $nextStep = handleRescheduleSlots($conn, $messageId, $name, $phone, $bookingDateID, $rescheduleDate, $slotName, $url, $headers);
                break;

            case "slotReschedule":
                $slotTime = $type;
                handleReschedule($conn, $messageId, $name, $phone, $bookingDateID, $rescheduleDate, $slotName, $slotTime, $url, $headers);
                $nextStep = "complete";
                break;
        }

        
    } else if ($storedType === "3") {
        switch ($currentStep) {
            case "StartProcess":
                $nextStep = handleGetDatesToDrop($conn, $messageId, $name, $phone, $url, $headers);
                break;
            case "SelectDatesToCancel":
                handleGetDropStatus($conn, $messageId, $name, $phone, $type, $url, $headers);
                $nextStep = "complete";
                break;
            }
    } else if ($storedType === "4") {
        switch ($currentStep) {
            case "StartProcess":
                handleFetchAppointments($conn, $messageId, $name, $phone, $url, $headers);
                $nextStep = "complete";
                break;
        }
    }
    
    updateSession($conn, $phone, $nextStep, $sessionData);
    return $nextStep; 
}


// pcntl_signal(SIGTERM, "signalHandler");
// pcntl_signal(SIGINT, "signalHandler");

function signalHandler($signo) {
    global $running;
    logError("Received signal $signo");
    $running = false;
}

?>