<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
$success = 0;
$total = count($_FILES['upload']['name']);
$rawType=$_POST['sel1'];
$rawComment=$_POST['comment'];
$conn = sqlConnect();
$keylog="";
$sql = "select max(OBJECTID)+1 as m from dbo.table";
$stmt = sqlsrv_query($conn, $sql);
while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC) ) {
    // echo "<br>"."ADDRESS ".$row[0]." COUNT ".$row[1];

    $keylog= $row[0];

}
sqlsrv_free_stmt( $stmt);

if(isset($_POST['checkbox'])) {

    $i = 0;
    $check = false;
    foreach ($_FILES['upload']['tmp_name'] as $tmpFilePath) {

        if ($tmpFilePath != "") {
            //Setup our new file path
            $keylog++;
            $info = new SplFileInfo($_FILES['upload']['name'][$i]);
            $ext = strtolower($info->getExtension());

            $new = $keylog;
            $renameFile[$i] = $rawType . '_' . $new . '_TEST.' . $ext;
            $newFilePath = "dirrectory patch here" . $renameFile[$i];

            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                $i++;
            }
        }
    }
    echo "UPLOAD,$i";
}else {
    $i = 0;
    $check = false;
    foreach ($_FILES['upload']['tmp_name'] as $tmpFilePath) {

        if ($tmpFilePath != "") {
            //Setup our new file path
            $keylog++;
            $info = new SplFileInfo($_FILES['upload']['name'][$i]);
            $ext = strtolower( $info->getExtension());

            $new = $keylog;
            $renameFile[$i] = $rawType . '_' . $new . '_TEST.' . $ext;
            $newFilePath = "directoerypathhere" . $renameFile[$i];

            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                $check = false;
                if($ext == "jpg"||$ext == "jpeg"){
                    $exif = exif_read_data($newFilePath);
                    $lon1[$i] = getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
                    $lat1[$i] = getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
                    if(isset($exif["GPSImgDirection"])){
                        $direction[$i] = $exif["GPSImgDirection"];
                    }else{
                        $direction[$i] = "NaN";
                    }

                    $dateTime[$i] = $exif["DateTime"];
                    $make[$i] = $exif["Make"];
                    $model[$i] = $exif["Model"];
                    $ext = strtolower($ext);
                    if($direction==''){

                        $direction[$i] = "NaN";

                    }
                    if(empty($lon1[$i])){
                        $lon1[$i]  = "NaN";
                        $renameFile[$i] =$_FILES['upload']['name'][$i];
                        $dateTime[$i] = "NaN";
                        $make[$i] = "NaN";
                        $model[$i] = "NaN";
                        $check = true;
                    }
                    if(empty($lat1[$i])){
                        $lat1[$i] = "NaN";
                        $renameFile[$i] =$_FILES['upload']['name'][$i];
                        $dateTime[$i] = "NaN";
                        $make[$i] = "NaN";
                        $model[$i] = "NaN";
                        $check = true;
                    }
                    echo "$renameFile[$i],$lat1[$i],$lon1[$i],$direction[$i],$dateTime[$i],$make[$i], $model[$i],";

                    if($check == false) {
                        $sql = "INSERT INTO dbo.exif_images (FILENAME, LAT,LON,GPS_DIRECTION,DATE_TAKEN,MAKE,MODEL,COMMENTS,TYPE) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $params = array($renameFile[$i], $lat1[$i], $lon1[$i], $direction[$i], $dateTime[$i], $make[$i], $model[$i], $rawComment, $rawType);
                        $stmt = sqlsrv_query($conn, $sql, $params);
                        if ($stmt === false) {
                            die(print_r(sqlsrv_errors(), true));
                        }
                    }

                    $i++;

                }else{
                    $name = $_FILES['upload']['name'][$i];
                    echo "NaN,$ext,$name,NaN,NaN,NaN,NaN,";

                    unlink($newFilePath);


                    $i++;


                }

            }
        }
    }

   $csv = CreateCsv($lat1,$lon1,$renameFile,$rawType,$dateTime,$model,$make,$direction,$rawComment);
    require './PHPMailer-master/PHPMailerAutoload.php';

    $mail = new PHPMailer;

//$mail->SMTPDebug = 3;                               // Enable verbose debug output

    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'fake host ';  // Specify main and backup SMTP servers
// Enable TLS encryption, `ssl` also accepted
    $mail->Port = 25;                                    // TCP port to connect to
    $mail->setFrom('GIS@fake.email', 'JPG READER UPLOAD');
    if($rawType=="LABELING" ||$rawType=="GIS ISSUE"){

        $mail->addAddress('kyle.schultz@email.fake','GISLandMark Data');


    }else{

        $mail->addAddress('kyle.schultz@email.fake',"Kyle");

    }
//$mail->addBCC('bcc@example.com');

    $mail->addAttachment($csv);         // Add attachments

    $mail->isHTML(true);                                  // Set email format to HTML

    $mail->Subject = 'IMAGES UPLOADED';
    $mail->Body    = 'IMAGES HAVE BEEN POSTED FROM JPG READER';


    if(!$mail->send()) {
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    } else {
        //echo 'Message has been sent';
    }



}


function CreateCsv($lat,$lon,$filename,$rawType,$datetaken,$model,$make,$direction,$rawComment)

{

    $countMain = count($lat);
    for ($i = 0; $i < $countMain; $i++) {

        if($i ==0){
            $record[$i] = "LATTITUDE,LONGITUDE,FILE,TYPE,DATE,MODEL,MAKE,GPS IMG DIRECTION,COMMENTS";
        }else {
            if ($lat[$i] == 'NaN' || $lon[$i] == 'NaN' || $filename[$i] == 'NaN' || $datetaken[$i] == 'NaN' || $model[$i] == 'NaN' || $make[$i] == 'NaN') {

            } else {
                $record[$i] = $lat[$i] . ',' . $lon[$i] . ',' . "http://maps.bgohio.org/imageupload/GPS_Storage/".$filename[$i] . ',' . $rawType . ',' . $datetaken[$i] . ',' . $model[$i] . ',' . $make[$i] . ',' . $direction[$i] . ',' . $rawComment;
            }
        }
    }


    $filenameR = $rawType.date('Y-m-d_H_i_s')."_TEST.csv";

    $fp = fopen('\\\\10.32.51.200\\imagestore\\image_upload\\CSV\\'.$filenameR, 'w');

    foreach ($record as $line)
    {
        fputcsv($fp,explode(',',$line));
    }

    fclose($fp);
    return '\\\\10.32.51.200\\imagestore\\image_upload\\CSV\\'.$filenameR;
}



function sqlConnect(){
    $serverName = "SQLSERVER";
    $connectionInfo = array( "CREDENTIALS");
    $conn = sqlsrv_connect( $serverName, $connectionInfo);
    if( $conn ) {

        return $conn;
    }else{

    }
}








function getGps($exifCoord, $hemi) {

    $degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);

}

function gps2Num($coordPart) {

    $parts = explode('/', $coordPart);

    if (count($parts) <= 0)
        return 0;

    if (count($parts) == 1)
        return $parts[0];

    return floatval($parts[0]) / floatval($parts[1]);
}

