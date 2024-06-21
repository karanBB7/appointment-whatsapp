<?php 

function listMesasage($username, $phonenumber) {
    $curl = curl_init();
    $payload = json_encode(array(
        "username" => $username,
        "mobilenumber" => $phonenumber
    ));

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://13.234.213.35/linqmd/webhook-appointment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic bGlucW1kOlNAaVBrSG1GU2FpOXo='
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}



function book($username, $phonenumber) {
    $curl = curl_init();
    $payload = json_encode(array(
        "username" => $username,
        "mobilenumber" => $phonenumber,
        "type" => "1"
    ));

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://13.234.213.35/linqmd/webhook-appointment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic bGlucW1kOlNAaVBrSG1GU2FpOXo='
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}


function getday($username, $phonenumber,$clinicId) {
    $curl = curl_init();
    $payload = json_encode(array(
        "username" => $username,
        "mobilenumber" => $phonenumber,
        "type" => "1",
        "clinic" => $clinicId
    ));

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://13.234.213.35/linqmd/webhook-appointment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic bGlucW1kOlNAaVBrSG1GU2FpOXo='
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function getslots($username, $phonenumber,$clinicId,$date) {
    $curl = curl_init();
    $payload = json_encode(array(
        "username" => $username,
        "mobilenumber" => $phonenumber,
        "type" => "1",
        "clinic" => $clinicId,
        "date" => $date,
    ));

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://13.234.213.35/linqmd/webhook-appointment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic bGlucW1kOlNAaVBrSG1GU2FpOXo='
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function dobooking($username, $phonenumber,$clinicId,$date,$slotname,$slottime) {
    // echo $username . " " .$phonenumber . "  " . $clinicId . "  " . $date . "  " . $slotname . "  " . $slottime;exit;
    $curl = curl_init();
    $payload = json_encode(array(
        "username" => $username,
        "mobilenumber" => $phonenumber,
        "type" => "1",
        "clinic" => $clinicId,
        "date" => $date,
        "slot_name" => $slotname,
        "slot_time" => $slottime
    ));

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://13.234.213.35/linqmd/webhook-appointment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic bGlucW1kOlNAaVBrSG1GU2FpOXo='
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}



function reschedule() {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => '13.234.213.35/linqmd/webhook-appointment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "username":"Test",
            "mobilenumber":"9876543210",
            "type":"2"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic bGlucW1kOlNAaVBrSG1GU2FpOXo='
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;
}

function cancel() {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => '13.234.213.35/linqmd/webhook-appointment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "username":"Test",
            "mobilenumber":"9876543210",
            "type":"3"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic bGlucW1kOlNAaVBrSG1GU2FpOXo='
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;
}
function view() {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => '13.234.213.35/linqmd/webhook-appointment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "username":"Test",
            "mobilenumber":"9876543210",
            "type":"4"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic bGlucW1kOlNAaVBrSG1GU2FpOXo='
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;
}


?>