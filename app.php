<?php

session_start();

require_once("responses/bookingResponse.php");
require_once("responses/rescheduleResponse.php");
require_once("responses/cancelResponse.php");
require_once("responses/viewResponse.php");

require_once("middleware/bookingMidware.php");
require_once("middleware/rescheduleMidware.php");
require_once("middleware/cancelMidware.php");
require_once("middleware/viewMidware.php");

require_once("handlers/bookingHandlers.php");
require_once("handlers/rescheduleHandlers.php");
require_once("handlers/cancelHandlers.php");
require_once("handlers/viewHandlers.php");

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
    $name = $_SESSION['name'] ?? '';
    $_SESSION['phone'] = $phone;

    if (strpos($content, "Hello!") !== false) {
        handleHelloMessage($conn, $messageId, $content);
    }

    if ($content !== null && $status === "2") {
        handleInitialResponse($conn, $messageId, $name, $phone, $url, $headers);
    } else {
        $prevSectionname = getPreviousSectionName($conn, $messageId);
        handleSection($conn, $prevSectionname, $messageId, $name, $phone, $status, $type, $description, $url, $headers, $content);
    }
}

function handleHelloMessage($conn, $messageId, $content) {
    $name = trim(substr($content, strpos($content, "Hello!") + strlen("Hello!")));
    $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 2, sectionname = 'cliniclist' WHERE id = $messageId";
    mysqli_query($conn, $updateQuery);
    $_SESSION['name'] = $name;
    $_SESSION['clinic_status'] = "0";
}

function getPreviousSectionName($conn, $messageId) {
    $prevMessageId = $messageId - 1;
    $query = "SELECT sectionname FROM received_whatsapp_messagebot WHERE id = $prevMessageId";
    $result = mysqli_query($conn, $query);
    $prevRow = mysqli_fetch_assoc($result);
    return $prevRow['sectionname'] ?? '';
}

function handleInitialResponse($conn, $messageId, $name, $phone, $url, $headers) {
    $response = listMesasage($name, $phone);
    Listappointment($phone, $response, $url, $headers);
    $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'cliniclist' WHERE id = $messageId";
    mysqli_query($conn, $updateQuery);
}

function handleSection($conn, $prevSectionname, $messageId, $name, $phone, $status, $type, $description, $url, $headers, $content) {
    switch ($prevSectionname) {
        case "cliniclist":
            if ($type === "1") {
                handleClinicList($conn, $messageId, $name, $phone, $url, $headers);
            }
            break;
        case "datelist":
            if ($status == "0") {
                handleDateList($conn, $messageId, $name, $phone, $type, $url, $headers);
            }
            break;
        case "name":
            if ($status == "0") {
                handleNameInput($conn, $messageId, $phone, $type, $headers);
            }
            break;
        case "slotslist":
            if ($status == "0") {
                handleSlotsList($conn, $messageId, $name, $phone, $url, $headers, $content);
            }
            break;
        case "booking":
            if ($status == "0") {
                handleBooking($conn, $messageId, $name, $phone, $description, $type, $url, $headers);
            }
            break;

        case "cliniclist":
            if ($type === "3") {
                handlecancelation($conn, $messageId, $name, $phone, $url, $headers);
            }
            break;


    }
}








?>