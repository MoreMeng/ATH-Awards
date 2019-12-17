<?php
error_reporting( E_ALL ^ E_NOTICE );
mb_internal_encoding( 'UTF-8' );

require realpath( '../dv-config.php' );
require DEV_PATH . '/classes/db.class.v2.php';
require DEV_PATH . '/functions/global.php';

$tokens = [
    // 'HVacSQ1BdCIE3Nc00F3jIJTWQB4naHZU8lkgFfDaT1H' => '🤖ICT-Bots --- Angthong Hospital',
    // 'OiahHNbZ8viWbuQnHd7lUuHkva0GeM7asy2ItoWZLdk' =>     '📅 ICT Meeting --- Angthong Hospital' ,
    // 'q9hrCxVG2TYDaeH6dJSQ3zFbfj5BfCSpq9Cbo4aKZM1' => 'ICT-Bots --- ICT OnLine',
    // 'xLcBVvZEzYZecJtnp7xgsVSBsXmb04AOGIiUjGSuTAp' => 'ICT-Bots --- ICT ADMIN',
    'nw9GZ0FII6vcRuDVmgRxhZtbLff3YHeRfJwkRPcFcEl' => 'เตือนประชุม --- Thanikul Sriuthis'
    // 'MgkeBanuqasT16ROsIB46v4a15QBOKyY6bs4ZnrJUZc' => 'แจ้งประชุมเวชนิทัศน์',
    // 'a9wMrZW4mGyv5lTHktmpKhyw07XwkM2CZ5Sg8hEGnTp' => '📃ICT-Bots --- กลุ่มสาระer😂'
];

// SSL USE

if ( !function_exists( "send_line_curl" ) ) {
    function send_line_curl( $message, $token ) {
        $result_ = "";
        $chOne   = curl_init();
        curl_setopt( $chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify" );
        //POST
        curl_setopt( $chOne, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $chOne, CURLOPT_SSL_VERIFYPEER, 0 );
        //ADD header array
        curl_setopt( $chOne, CURLOPT_POST, 1 );
        curl_setopt( $chOne, CURLOPT_POSTFIELDS, $message );
        curl_setopt( $chOne, CURLOPT_FOLLOWLOCATION, 1 );
        //RETURN
        $headers = ['Content-type: multipart/form-data', 'Authorization: Bearer ' . $token];
        curl_setopt( $chOne, CURLOPT_HTTPHEADER, $headers );
        //Check error
        curl_setopt( $chOne, CURLOPT_RETURNTRANSFER, 1 );
        $result = curl_exec( $chOne );
        //Close connect
        if ( curl_error( $chOne ) ) {
            $error_  = ['status' => "Error", 'massage' => curl_error( $chOne )];
            $result_ = json_decode( $error_, true );
        } else {
            $result_ = json_decode( $result, true );
        }
        // p( $_POST );
        curl_close( $chOne );

        return $result_;
    }
}
// p( $ap_select );

$GETAWARDNAME   = ( isset( $_POST['name'] ) ) ? $_POST['name'] : null;
$GETAWARDNUMBER = ( isset( $_POST['number'] ) ) ? $_POST['number'] : null;

$year      = date( "Y" );
$ap_select = CON::selectArrayDB( [], "SELECT ap_id FROM award_person WHERE ap_award IS NULL AND ap_year = '" . $year . "' ORDER BY RAND()" );

// p( $random_keys );

$random_keys = array_rand( $ap_select, $GETAWARDNUMBER );
$apid        = '';
// echo "SELECT ap_name, ap_award FROM award_person WHERE ap_id IN (" . substr( $apid, 0, -1 ) . ") ";
if ( is_array( $random_keys ) ) {
    foreach ( $random_keys as $row ) {
        $temp = implode( "", $ap_select[$row] );
        $apid .= $temp . ",";
    }
} else {
    $apid = $ap_select[$random_keys]['ap_id'] . ",";
}

CON::updateDB( [], " UPDATE award_person SET ap_award = '" . $GETAWARDNAME . "' WHERE ap_id IN (" . substr( $apid, 0, -1 ) . ") " );

CON::updateDB( [], " INSERT INTO award_list (al_name, al_datetime, al_number) VALUES ('" . $GETAWARDNAME . "', NOW(), " . $GETAWARDNUMBER . ") " );

$query = CON::selectArrayDB( [], "SELECT ap_name, ap_award FROM award_person WHERE ap_id IN (" . substr( $apid, 0, -1 ) . ") " );

// p( $query );
foreach ( $query as $row ) {
    $json_data[] = [
        'winner' => $row['ap_name'],
        'award'  => $row['ap_award']
    ];
    $message .= "\n" . '⊦ ' . $row['ap_name'];
}

echo json_encode( $json_data );

try {

    $post = [
        'message' => "\n" . '🎉' . " ผู้ได้รับรางวัล" . $GETAWARDNAME . $message
    ];
    // p($post);

    foreach ( $tokens as $value => $name ) {
        // p(send_line_curl($post, $value)); //debug
        send_line_curl( $post, $value );
    }

    $response = '<div class="alert alert-success">ส่งข้อความเรียบร้อยแล้ว</div>';

} catch ( Exception $e ) {
    $response = "<div>Error: กรุณากรอกข้อมูลให้ครบถ้วน</div>";

}

