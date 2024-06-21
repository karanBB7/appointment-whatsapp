<?php

session_start();

require_once("./booking.php");
require_once("./middleware.php");

$url = 'https://whatsappapi-79t7.onrender.com/interact-messages';
$headers = array(
    'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJPd25lck5hbWUiOiJCaXp0ZWNobm9zeXMtbWlkd2FyZSIsInBob25lTnVtYmVySWQiOiIyNDg4OTg2NDQ5NzI0MDQiLCJ3aGF0c2FwcE1ldGFUb2tlbiI6IkVBQXhWMWc0dDI0UUJPd2ZBOGw1Q3d6Tm1qNUlvaHlWUkdaQWNKemRpTW9xb3hMWDZ1a3h3cVEzSDlGZVRHZUVuVmxaQkRhMXc0dUYxUzczUUk0OVkwTEpPQ1hJU0tTd2dBZkJnZ1N6dzNyUWlWSmtLRWt0Q0lMaTlqdzNRbUhXMmxnWFpBaXlwdXdaQ3FhSmRRaXBsb0M1SEtyYUx0ODZiSnVtSEt3RUFXNGthMGRaQlRPNWl4dWV1R1Ztb0daQ2JLbkZBUEEwVzkwWkNVR2dSZ29oIiwiaWF0IjoxNzA5MjAwMTEwfQ.ZMy9wpBxphJbpEOYI3bBchlywwKCIN23GJiYrDlvXyc',
    'Content-Type: application/json',
);



$conn = mysqli_connect("localhost", "root", "", "appointment");

$query = "SELECT id, messages, fromNumber, buttonText, description, status, listid FROM received_whatsapp_messagebot WHERE status = 0 OR status = 2";
$result = mysqli_query($conn, $query);

if (!$result) {
    die('Error in SQL query: ' . mysqli_error($conn));
}

$messagesToProcess = [];
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['status'] == 0 | $row['status'] == "2") {
        $messagesToProcess[] = $row;
    }
}

foreach ($messagesToProcess as $row) {
    $messageId = $row['id'];
    $content = $row['messages'];
    $phone = $row['fromNumber'];
    $description = $row['description'];
    $status = $row['status'];
    $type = $row['listid'];


    $name = $_SESSION['name'] ?? '';
    $_SESSION['phone'] = $phone;

    if (strpos($content, "Hello!") !== false) {
        $name = trim(substr($content, strpos($content, "Hello!") + strlen("Hello!")));
        $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 2, sectionname = 'cliniclist' WHERE id = $messageId";
        mysqli_query($conn, $updateQuery);
        $_SESSION['name'] = $name;
        $_SESSION['clinic_status'] = "0";
    }

    $initial = "SELECT status,messages FROM received_whatsapp_messagebot WHERE id = $messageId";
    $result = mysqli_query($conn, $initial);
    $doctorname = mysqli_fetch_assoc($result);
    $doctorstatus = $doctorname['status'];

       
        if ($content !== null && $status ==="2") {
            $response = listMesasage($name, $phone);
            Listappointment($phone, $response, $url, $headers);
            $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'cliniclist' WHERE id = $messageId";
            mysqli_query($conn, $updateQuery);
        } else {
            $prevMessageId = $messageId - 1;
            $query2 = "SELECT sectionname FROM received_whatsapp_messagebot WHERE id = $prevMessageId";
            $result2 = mysqli_query($conn, $query2);
            $prevRow = mysqli_fetch_assoc($result2);
            $prevSectionname = $prevRow['sectionname'];    

            if ($prevSectionname === "cliniclist" & $type === "1") {
                $getclinic = book($name, $phone);
                clincList($phone, $getclinic, $url, $headers);
                $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'datelist' WHERE id = $messageId";
                mysqli_query($conn, $updateQuery);
            } 
    
            elseif ($prevSectionname === "datelist" && $status == "0") {
                $_SESSION['clinicid'] = $type; 
                $getdate = getday($name, $phone, $type);
                sendDate($phone, $getdate, $url, $headers);
                $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'name' WHERE id = $messageId";
                mysqli_query($conn, $updateQuery);
            }
    
            elseif ($prevSectionname === "name" && $status == "0") {
                $message = "Please enter your name";
                name($phone, $message, $headers);
                $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'slotslist' WHERE id = $messageId";
                mysqli_query($conn, $updateQuery);
                $_SESSION['dateid'] = $type; 
            }
            
    
             elseif($prevSectionname === "slotslist" && $status == "0") {
                $_SESSION['content'] = $content;
                $clinicid = $_SESSION['clinicid'] ?? 'undefined';
                $dateid = $_SESSION['dateid'] ?? 'undefined';
                $slots = getslots($name, $phone,$clinicid,$dateid);
                sendslots($phone,$slots, $url, $headers);
                $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'booking' WHERE id = $messageId";
                mysqli_query($conn, $updateQuery);
            }
        

            elseif ($prevSectionname === "booking"  && $status == "0") {
                $patientname = $_SESSION['content'];
                $clinicid = $_SESSION['clinicid'] ?? 'undefined';
                $dateid = $_SESSION['dateid'] ?? 'undefined';
                $description;
                $slotname = str_replace('_slot', '', $description);
                $slottime = $type;
                $res = dobooking($name,$phone,$clinicid,$dateid,$slotname,$slottime,$patientname);
               
                $response = json_decode($res, true);
                
                $spaces = str_repeat(" ", 10);
                $message =  "\n" . $spaces . "*" . $response['message'] . "*" . "\n";
                $message .= "\n";
                $message .= "Name: " . $patientname . "\n";
                $message .= "Slot: " . $slotname . "\n";
                $message .= "Time: " . $slottime . "\n";

                confirmation($phone, $message, $headers);
                $updateQuery = "UPDATE received_whatsapp_messagebot SET status = 1, sectionname = 'done' WHERE id = $messageId";
                mysqli_query($conn, $updateQuery);
            }

        }
    
    
}

mysqli_close($conn);

?>
