<?php
session_start();

require_once("handlers/bookingHandlers.php");
require_once("handlers/rescheduleHandlers.php");
require_once("handlers/cancelHandlers.php");
require_once("handlers/viewHandlers.php");
require_once("middleware/viewMidware.php");

$url = 'https://whatsappapi-79t7.onrender.com/interact-messages';
$headers = array(
    'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJPd25lck5hbWUiOiJCaXp0ZWNobm9zeXMtbWlkd2FyZSIsInBob25lTnVtYmVySWQiOiIyNDg4OTg2NDQ5NzI0MDQiLCJ3aGF0c2FwcE1ldGFUb2tlbiI6IkVBQXhWMWc0dDI0UUJPd2ZBOGw1Q3d6Tm1qNUlvaHlWUkdaQWNKemRpTW9xb3hMWDZ1a3h3cVEzSDlGZVRHZUVuVmxaQkRhMXc0dUYxUzczUUk0OVkwTEpPQ1hJU0tTd2dBZkJnZ1N6dzNyUWlWSmtLRWt0Q0lMaTlqdzNRbUhXMmxnWFpBaXlwdXdaQ3FhSmRRaXBsb0M1SEtyYUx0ODZiSnVtSEt3RUFXNGthMGRaQlRPNWl4dWV1R1Ztb0daQ2JLbkZBUEEwVzkwWkNVR2dSZ29oIiwiaWF0IjoxNzA5MjAwMTEwfQ.ZMy9wpBxphJbpEOYI3bBchlywwKCIN23GJiYrDlvXyc',
    'Content-Type: application/json',
);


try {
    $conn = mysqli_connect("localhost", "root", "", "appointment");

    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    $query = "SELECT id, messages, fromNumber, buttonText, description, status, listid 
              FROM received_whatsapp_messagebot 
              WHERE status IN (0, 2)";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        throw new Exception('Error in SQL query: ' . mysqli_error($conn));
    }

    $messagesToProcess = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['status'] == 0 || $row['status'] == "2") {
            $messagesToProcess[] = $row;
        }
    }

    foreach ($messagesToProcess as $row) {
        processMessage($conn, $row, $url, $headers);
    }

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}



function processMessage($conn, $row, $url, $headers) {
    $messageId = $row['id'];
    $content = $row['messages'];
    $phone = $row['fromNumber'];
    $description = $row['description'];
    $status = $row['status'];
    $type = $row['listid'];

    $session = getOrCreateSession($conn, $phone);

    if ($session === null) {
        if (strpos($content, "Hello!") !== false) {
            $name = trim(substr($content, strpos($content, "Hello!") + strlen("Hello!")));
            $sessionData = ['name' => $name];
            $currentStep = 'initial';
            updateSession($conn, $phone, $currentStep, $sessionData);
            handleInitialResponse($conn, $messageId, $name, $phone, $url, $headers);
        } else {
            sendErrorMessage($phone, "Please start the flow again by initializing Hello! doctorname", $headers);
            
        }
    } else {
        $currentStep = $session['current_step'];
        $sessionData = json_decode($session['data'], true);

        if ($currentStep === 'complete') {
            if (strpos($content, "Hello!") !== false) {
                deleteOldData($conn, $phone);
                $name = trim(substr($content, strpos($content, "Hello!") + strlen("Hello!")));
                $sessionData = ['name' => $name];
                $currentStep = 'initial';
                updateSession($conn, $phone, $currentStep, $sessionData);
                handleInitialResponse($conn, $messageId, $name, $phone, $url, $headers);
            } else {
                sendErrorMessage($phone, "Please start the flow again by initializing Hello! doctorname", $headers);
                
            }
        } else if (strpos($content, "Hello!") !== false) {
            deleteOldData($conn, $phone);
            $name = trim(substr($content, strpos($content, "Hello!") + strlen("Hello!")));
            $sessionData = ['name' => $name];
            $currentStep = 'initial';
            updateSession($conn, $phone, $currentStep, $sessionData);
            handleInitialResponse($conn, $messageId, $name, $phone, $url, $headers);
        } else if ($currentStep === 'initial') {
            $sessionData['storedType'] = $type;
            $currentStep = 'StartProcess';
            updateSession($conn, $phone, $currentStep, $sessionData);
            handleSection($conn, $currentStep, $messageId, $sessionData, $phone, $status, $type, $description, $url, $headers, $content);
        } else {
            handleSection($conn, $currentStep, $messageId, $sessionData, $phone, $status, $type, $description, $url, $headers, $content);
        }
    }

    $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1 WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("i", $messageId);
    $stmt->execute();

    $updatedSession = getOrCreateSession($conn, $phone);
    if ($updatedSession !== null && $updatedSession['current_step'] === 'complete') {
        moveToUserHistory($conn, $phone);
        deleteOldData($conn, $phone);
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
    $response = listMesasage($name, $phone);
    Listappointment($phone, $response, $url, $headers);
}

function handleSection($conn, $currentStep, $messageId, $sessionData, $phone, $status, $type, $description, $url, $headers, $content) {
    $name = $sessionData['name'] ?? '';
    $storedType = $sessionData['storedType'] ?? null;
    $clinicid = $sessionData['clinicid'] ?? null;
    $clinicname = $sessionData['clinicname'] ?? null;
    $dateid = $sessionData['dateid'] ?? null;
    $slotDetails = $sessionData['slotDetails'] ?? null;
    $patientname = $sessionData['patientname'] ?? '';
    
    $nextStep = $currentStep; 

    if ($storedType === "1") {
        switch ($currentStep) {
            case "StartProcess":
                handleClinicList($conn, $messageId, $name, $phone, $url, $headers);
                $nextStep = "datelist";
                break;
            case "datelist":
                handleDateList($conn, $messageId, $name, $phone, $type, $url, $headers);
                $clinicid = $type;
                $sessionData['clinicid'] = $clinicid;
                $clinicname = $description;
                $sessionData['clinicname'] = $clinicname;
                $nextStep = "name";
                break;
            case "name":
                $dateid = $type;
                $sessionData['dateid'] = $dateid;
                if (empty($content)) {
                    handleNamePrompt($conn, $messageId, $phone, $headers);
                    $nextStep = "slotslist"; 
                } else {
                    $sessionData['name'] = $name;
                    handleNameInput($conn, $messageId, $phone, $content, $headers);
                    $nextStep = "slotslist";
                }
                break;
            case "slotslist":
                $sessionData['patientname'] = $content;
                $clinicid = $sessionData['clinicid'] ?? null;
                $dateid = $sessionData['dateid'] ?? null;
                handleSlotsList($conn, $messageId, $name, $phone, $url, $headers, $dateid, $clinicid);
                $nextStep = "booking";
                break;
            case "booking":
                $slotDetails = " {slotName = " .$description. " }"." | "." {slotTime = ".$type." }";
                $sessionData['slotDetails'] = $slotDetails;
                $patientname = $sessionData['patientname'];
                handleBooking($conn, $messageId, $name, $phone, $description, $type, $url, $headers, $sessionData, $patientname);
                $nextStep = "complete";
                break;
        }
    } else if ($storedType === "2") {
        $bookingDateID = $sessionData['bookingDateID'] ?? null;
        $rescheduleDate = $sessionData['rescheduleDate'] ?? null;
        $slotDetails = $sessionData['slotDetails'] ?? null;
        switch ($currentStep) {
            case "StartProcess":
                $nextStep = handleGetBookedDate($conn, $messageId, $name, $phone, $url, $headers);
                break;
            case "bookedDates":
                $sessionData['bookingDateID'] =  $type;
                handleGetDayReschedule($conn, $messageId, $name, $phone, $type, $url, $headers);
                $nextStep = "dateReschedule";
                break;
            case "dateReschedule":
                $sessionData['rescheduleDate'] =  $type;
                handleRescheduleSlots($conn, $messageId, $name, $phone, $bookingDateID, $type, $url, $headers);
                $nextStep = "slotReschedule";
                break;
            case "slotReschedule":
                $slotDetails = " {slotName = " .$description. " }"." | "." {slotTime = ".$type." }";
                $sessionData['slotDetails'] = $slotDetails;
                handleReschedule($conn, $messageId, $name, $phone, $bookingDateID, $description, $rescheduleDate, $type, $url, $headers);
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

?>
