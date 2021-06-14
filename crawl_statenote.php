<?php
include('./simple_html_dom.php');
$servername = 'localhost';
$database = 'dmv_test0';
$username = 'root';
$password = '';
// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

// check connection
if (!$conn)
    die('“Connection failed: ” ' . mysqli_connect_error());
$string = file_get_contents("data-car.json");
$fakedatastate = json_decode($string, true);



function push_state_notes_to_database($conn)
{
    $html = file_get_html('https://en.wikipedia.org/wiki/Driver%27s_licenses_in_the_United_States');
    foreach ($html->find('table[class=wikitable sortable]') as $tableData) {
        foreach ($tableData->find('tbody') as $tbody) {
            foreach ($tableData->find('tr') as $tr) {
                $HardshipLicense = $tr->find('td', 1);
                if ($HardshipLicense = 'No ') {
                    $HardshipLicense = preg_replace('/ /', '', $HardshipLicense);
                }
                if (strlen($HardshipLicense) > 3) {
                    $HardshipLicense = 'Yes';
                }
                $MiniumAgeLearnPermit = $tr->find('td', 2);
                $MiniumAgeRestrictedLicense = $tr->find('td', 3);
                $MiniumAgeFull = $tr->find('td', 4);
                $LicenseValidityForFull = $tr->find('td', 5);
                $Notes = $tr->find('td', 6);
                $Notes = preg_replace('/\'/', `'\'`, $Notes);
                $sql = "INSERT INTO `StateNote` ( StateID,HardshipLicense,MiniumAgeLearnPermit,MiniumAgeRestrictedLicense,MiniumAgeFull,LicenseValidityForFull,Notes)
                VALUES (1,'$HardshipLicense','$MiniumAgeLearnPermit','$MiniumAgeRestrictedLicense','$MiniumAgeFull','$LicenseValidityForFull','$Notes');";
                
                echo '<pre>';
                echo $sql;
                echo '<br/>';
                echo '<br/>';
                echo '<br/>';
                echo '</pre>';
            }
        }
    }
}
push_state_notes_to_database($conn);
