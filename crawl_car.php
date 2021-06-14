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

$string = file_get_contents("data-car.json");
$fakedatastate = json_decode($string, true);


foreach ($fakedatastate as $key => $state) {
    $Name = $state['State'];
    $StateCode = $state['Code'];
    $sql = "INSERT INTO StateInformation ( StateCode,StateName)
                VALUES ('$StateCode','$Name')";
    if ($conn->query($sql)) {
        $html = file_get_html('https://dmv-practice-test.com/' . $Name . '/car/practice-test-1');
        foreach ($html->find('#series-content-holder') as $series_content_holder) {
            $numberTest = $series_content_holder->find('mark', 0)->plaintext;
            $NumberQuestion =  $series_content_holder->find('mark', 1)->plaintext;
            $MiniumAnswerCorrect =  $series_content_holder->find('mark', 2)->plaintext;
            $numberTest = preg_replace('/[^0-9]/', '', $numberTest);
            $sql = "INSERT INTO ExamFormat ( StateID, NumberQuestion,MiniumAnswerCorrect,Vehicle)
            VALUES ('$conn->insert_id', '$NumberQuestion' , '$MiniumAnswerCorrect','car')";
            if ($conn->query($sql)) {
                pushDataExam($NumberQuestion, $Name, $conn, $StateCode, $numberTest);
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
};

function pushDataExam($NumberQuestion, $Name, $conn, $StateCode, $numberTest)
{
    $StateID_from_db = "SELECT  `StateID` from StateInformation where StateCode=$StateCode";
    $result = $conn->query($StateID_from_db);
    if ($result) {
        $row = $result->fetch_assoc();
        $StateID = $row['StateID'];
    } else {
        $StateID =  $conn->insert_id;
    }
    for ($numberExam = 1; $numberExam <= $numberTest; $numberExam++) {
        $exam = 'DMV Permit  Test #' . $numberExam;
        $sql = "INSERT INTO Exam ( FormatID, Exam_Name)
        VALUES ('$StateID', '$exam')";

        if ($conn->query($sql)) {
            $last_id = $conn->insert_id;
            pushDataQuestion($Name, $NumberQuestion, $numberExam, $conn, $last_id);
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}


function pushDataQuestion($Name, $NumberQuestion, $numberExam, $conn, $last_id)
{

    $x = 1;
    $y = 1;
    while ($y < $NumberQuestion) {
        $url = 'https://dmv-practice-test.com/' . $Name . '/car/practice-test-' . $numberExam . '/' . $x;
        $html = file_get_html($url);
        $all_question_row = $html->find('.question-row');
        $y += count($all_question_row);
        foreach ($all_question_row as $question_row) {
            $question = $question_row->find('.question')[0];
            $question_text = $question->find('h3')[0]->text();
            $data_value = $question_row->getAttribute('data-value');
            $data_id = $question_row->getAttribute('data-id');
            $Explain = $question_row->find('summary')[0]->text();
            $all_answer = $question_row->find('.radio-btn-holder');
            $answer_text = ['', '', '', ''];
            $answer_value = ['', '', '', ''];
            $i = 0;
            foreach ($all_answer as $answer) {
                $answer_text[$i] = $answer->find('label')[0]->text();
                $answer_value[$i++] = $answer->find('input')[0]->value;
            }
            foreach ($question_row->find('.answer-image-holder') as $element) {
                $image = $element->childNodes(0);

                if (isset($image->src)) {
                    $imageSrc = $image->src;
                    $imageLocalPath = preg_replace('/https:\/\/dmv-practice-test.com\//', '', $imageSrc);
                    // echo $imageLocalPath;
                    // echo '<br/>';
                    if (!file_exists($imageLocalPath)) {
                        copy($imageSrc, $imageLocalPath);
                    }
                    $QuestionImageName = $imageLocalPath;
                } else {
                    $QuestionImageName = '';
                }
                // echo $QuestionImageName;
            }
            $question_from_db = "SELECT  `QuestionID` from Question where QuestionID=$data_id";
            $result = $conn->query($question_from_db);
            $question_id = $result->fetch_assoc();
            $question_id = $question_id['QuestionID'];
            if ($result) {
                $row = mysqli_num_rows($result);
                if (!$row) {
                    $question_text = preg_replace('/\'/', `'\'`, $question_text);
                    $answer_text[0] = preg_replace('/\'/', `'\'`, $answer_text[0]);
                    $answer_text[1] = preg_replace('/\'/', `'\'`, $answer_text[1]);
                    $answer_text[2] = preg_replace('/\'/', `'\'`, $answer_text[2]);
                    $answer_text[3] = preg_replace('/\'/', `'\'`, $answer_text[3]);
                    $Explain = preg_replace('/\'/', `'\'`, $Explain);

                    $sql1 = "INSERT INTO Question ( `GroupID`, `QuestionText`,`QuestionImageName`,
                Answer1,Answer2,Answer3,Answer4,AnswerCorrect,`Explain`,Difficult,QuestionID)
                VALUES (1,'$question_text','$QuestionImageName','$answer_text[0]','$answer_text[1]','$answer_text[2]','$answer_text[3]','$data_value','$Explain',1,'$data_id')";
                    if ($conn->query($sql1)) {
                        $question_id = $data_id;
                    } else {
                        echo "Error: " . $sql1 . "<br>" . $conn->error;
                    }
                }
            }
            $sql2 = "INSERT INTO ExamQuestion ( `ExamID`, `QuestionID`)
            VALUES ('$last_id','$question_id')";
            if ($conn->query($sql2)) {
                // echo 'done';
            } else {
                echo "Error: " . $sql2 . "<br>" . $conn->error;
                break;
            }
        }
        $x++;
    }
}
