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


$host = getenv('DB_HOST') ?: "13.232.224.97";
$user = getenv('DB_USER') ?: "drupaladmin";
$pass = getenv('DB_PASS') ?: "Linqmd*123";
$db = getenv('DB_NAME') ?: "appointment";

set_time_limit(0);
ignore_user_abort(true);

$running = true;
$lastDbCheck = 0;
$dbCheckInterval = 5; 

while ($running) {
    $now = time();
    if ($now - $lastDbCheck >= $dbCheckInterval) {
        try {
            $conn = mysqli_connect($host, $user, $pass, $db);

            if (!$conn) {
                throw new Exception("Database connection failed: " . mysqli_connect_error());
            }

            mysqli_autocommit($conn, FALSE);

            $query = "SELECT id, messages, fromNumber, buttonText, description, status, listid 
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

            mysqli_close($conn);
        } catch (Exception $e) {
            logError("Error: " . $e->getMessage());
            if (isset($conn) && mysqli_connect_errno() === 0) {
                mysqli_rollback($conn);
                mysqli_close($conn);
            }
        }
        $lastDbCheck = $now;
    }
    
    usleep(100000); 
    pcntl_signal_dispatch();
}

logError("Script shutting down");

$conn = mysqli_connect("localhost", "root", "", "appointment");
$query = "SELECT id, messages, fromNumber, buttonText, description, status, listid 
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
    
    mysqli_begin_transaction($conn);
    
    $lockQuery = "UPDATE received_whatsapp_messagebot SET processing = 1 WHERE id = ? AND processing = 0";
    $lockStmt = $conn->prepare($lockQuery);
    $lockStmt->bind_param("i", $messageId);
    $lockStmt->execute();
    
    if ($lockStmt->affected_rows === 0) {
        mysqli_rollback($conn);
        return;
    }
    
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

    $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, processing = 0 WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("i", $messageId);
    $stmt->execute();

    $updatedSession = getOrCreateSession($conn, $phone);
    if ($updatedSession !== null && $updatedSession['current_step'] === 'complete') {
        moveToUserHistory($conn, $phone);
        deleteOldData($conn, $phone);
    }

    // Commit the transaction
    mysqli_commit($conn);
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

function handleSection($conn, $currentStep, $messageId, $sessionData, $phone, $status, $type, $description, $url, $headers, $content) {
    $name = $sessionData['name'] ?? '';
    $storedType = $sessionData['storedType'] ?? null;
    $clinicid = $sessionData['clinicid'] ?? null;
    $clinicname = $sessionData['clinicname'] ?? null;
    $dateid = $sessionData['dateid'] ?? null;
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
                handleClinicList($conn, $messageId, $name, $phone,$dateid , $url, $headers);
                $nextStep = "slotslist";
                break;

            case "slotslist":
                $clinicid = $type;
                $sessionData['clinicid'] = $clinicid;
                $clinicname = $description;
                $sessionData['clinicname'] = $clinicname;
                handleSlotsList($conn, $messageId, $name, $phone, $dateid, $clinicid, $url, $headers);
                $nextStep = "name";
                break;

            case "name":
                $slotName = $description;
                $sessionData['slotName'] = $slotName;
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


// pcntl_signal(SIGTERM, "signalHandler");
// pcntl_signal(SIGINT, "signalHandler");

function signalHandler($signo) {
    global $running;
    logError("Received signal $signo");
    $running = false;
}

?>