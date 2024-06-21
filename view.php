<?php
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