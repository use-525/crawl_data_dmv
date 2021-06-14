<?php
include('./simple_html_dom.php');
$servername = 'localhost';
$database = 'dmv_test0';
$username = 'root';
$password = '';
// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);
$html = '';
$listHTML = array(
    'https://driving-tests.org/beginner-drivers/driving-test-tips-students/',
    'https://driving-tests.org/beginner-drivers/overcome-permit-test-anxiety/',
    'https://driving-tests.org/beginner-drivers/how-to-prevent-recover-hydroplaning/',
    'https://driving-tests.org/beginner-drivers/top-5-dmv-test-myths/',
    'https://driving-tests.org/beginner-drivers/how-to-renew-your-driver%e2%80%99s-license/',
    'https://driving-tests.org/beginner-drivers/killer-curves-how-to-stay-safe-while-driving-on-curvy-roads/',
    'https://driving-tests.org/beginner-drivers/drivers-exam-101/'
);
function crawl_data($listHTML, $conn)
{
    foreach ($listHTML as $key => $html) {
        crawl_CateTip($html, $conn);
    }
}
function crawl_CateTip($html, $conn)
{
    $html = file_get_html($html);
    $boxHeader = $html->find('div[class=pre-post-header container text-center]', 0);
    $Cate_tip_title = $boxHeader->find('h1', 0)->text();
    $Cate_tip_title = preg_replace('/\'/', '', $Cate_tip_title);
    $checkData = "SELECT  `Cate_tip_title` from Cate_tip where Cate_tip_title='$Cate_tip_title'";
    $result = $conn->query($checkData);
    $row = $result->fetch_assoc();
    if ($row > 0) {
        echo 'Tip đã tồn tại';
    } else {
        $figure_img = $html->find('figure[id=post-featured-image]', 0);
        $urlImg = $figure_img->find('source', 0)->getAttribute('srcset');
        if (isset($urlImg)) {
            $nameImage  = preg_replace('/\ /', '_', $Cate_tip_title);
            file_put_contents('img_tips/' . $nameImage . '.png', file_get_contents($urlImg));
            $img_tips = 'img_tips/' . $nameImage . '.png';
        } else {
            $img_tips = '';
        }
        $sql = "INSERT INTO `Cate_tip` ( Cate_tip_title,Cate_tip_image)
    VALUES ('$Cate_tip_title','$img_tips');";

        push_data_Cate_tip($conn, $sql, $html);
    }
}
function push_data_Cate_tip($conn, $sql, $html)
{
    if ($conn->query($sql)) {
        $Cate_tip_ID = $conn->insert_id;
        crawl_Tips($html, $conn, $Cate_tip_ID);
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
function crawl_Tips($html, $conn, $Cate_tip_ID)
{

    foreach ($html->find('ol[class="bignum"]') as $bignum) {
        foreach ($bignum->find('li') as $key => $item) {
            $key++;
            if ($item->find('p', 0) != null) {
                $Content_tip = $item->find('p', 0)->text();
            } else {
                $Content_tip = '';
            }
            if ($item->find('h2', 0) != null) {
                $Title_tip = $item->find('h2', 0)->text();
            } else if ($item->find('h3', 0) != null) {
                $Title_tip = $item->find('h3', 0)->text();
            } else {
                $Title_tip = 'Tip#' . $key;
            }
            $Content_tip  = preg_replace('/\'/', '', $Content_tip);
            if ($Content_tip != '') {
                $sql = "INSERT INTO `Tips` ( Cate_tip_ID,Content_tip,Title_tip)
                VALUES ('$Cate_tip_ID','$Content_tip','$Title_tip');";
            }
            echo '<pre>';
            echo $sql;
            echo '</pre>';
        }
    }
}

crawl_data($listHTML, $conn);
