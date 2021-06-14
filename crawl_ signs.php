<?php
include('./simple_html_dom.php');
$servername = 'localhost';
$database = 'dmv_test1';
$username = 'root';
$password = '';
// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

// check connection
if (!$conn)
    die('“Connection failed: ” ' . mysqli_connect_error());

function get_data_TrafficSignGroup_from_HTML($conn)
{
    $url = 'http://www.trafficsign.us/';
    $html = file_get_html($url);
    $motsbody = $html->find('div[class=motsbody]', 0);
    $table = $motsbody->find('table', 0);
    $tbody = $table->find('tbody', 0);
    $tr = $tbody->find('tr', 1);
    foreach ($tr->find('td') as $key => $td) {
        $urlHTML = $td->find('a', 0)->getAttribute('href');
        $GroupName = $td->text();
        $urlHTML = 'http://www.trafficsign.us/' . $urlHTML;
        $sql = "INSERT INTO `TrafficSignGroup` (GroupName)
            VALUES ('$GroupName');";

        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            push_data_TrafficSignGroup_to_db($sql, $conn, $urlHTML, $key, $GroupName,$last_id);
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

    }
}
function push_data_TrafficSignGroup_to_db($sql, $conn, $urlHTML, $key, $CategoryName, $last_id)
{
    $html = file_get_html($urlHTML);
    $signtable = $html->find('div[class=signtable]', 0);
    foreach ($signtable->find('table') as $table) {
        foreach ($table->find('tbody') as $tbody) {
            foreach ($tbody->find('tr') as $key => $td) {
                if ($td->childNodes(3) == null) {
                    continue;
                } else {
                    $urlImage = 'http://www.trafficsign.us/' . $td->find('a', 0)->href;
                    $TrafficName = $td->childNodes(1)->text();
                    $TrafficDesc = $td->childNodes(2)->text();
                }
                get_data_TrafficSign_from_HTML($td, $conn, $CategoryName, $urlImage, $TrafficName, $last_id,$TrafficDesc);
            }
        }
    }
}
function  get_data_TrafficSign_from_HTML($td, $conn, $CategoryName, $urlImage, $TrafficName, $last_id,$TrafficDesc)
{
    if (isset($urlImage)) {
        $nameImage  = preg_replace('/\ /', '-', $TrafficName);
        file_put_contents('Signboard_photo_source/' . $nameImage . '.png', file_get_contents($urlImage));
        $ImageName = $nameImage . '.png';
    } else {
        $ImageName = '';
    }
    $sql = "INSERT INTO `TrafficSign` (GroupID,CategoryName,ImageName,Information,TrafficDesc)
    VALUES ('$last_id','$CategoryName','$ImageName','$TrafficName','$TrafficDesc');";
    echo $sql;
    echo '<br>';
    // if ($key == 1) {

    // } else {
    // }
}
// get_data_TrafficSignGroup_from_HTML($conn);
