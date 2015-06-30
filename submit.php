<?php
session_start();
if (empty($_SESSION['loggedin']) or $_SESSION['loggedin'] == "false"){
	header("Location: login.php");
}
?>

<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        require_once 'db.php';
        if (isset($_POST['submit'])) {
            $dateSelected = $_POST['selecteddate'];
            $dateParts = explode('/', $dateSelected);
            $year = $dateParts[2];
            $month = $dateParts[0];
            $day = $dateParts[1];
            $dateFormatted = $year . "-" . $month . "-" . $day;
            //echo 'DATES: ' . $dateFormatted . '<br> Year: ' . $year . '<br>Month: ' . $month . '<br>Day: ' . $day . '<br>';
            #echo 'Sys Date: '.date('Y-m-d');

            $count = count($_POST['project_id']);


            //echo "Num of rows: " . $count . "<br>";
            //echo "Num of activity: " . count($_POST['activity']) . "<br><br>";
            


            for ($i = 0; $i < $count; $i++) {
                
                #$userID = 30;
                session_start();
                $userID = $_SESSION['userid'];
                $hours = $_POST['hours'][$i];
                #$hr_flag = $_POST['hrFlag'][$i];
                if(!empty($_POST['hrFlag'][$i])){
                        $hr_flag = $_POST['hrFlag'][$i];
                    }
                    else {
                        $hr_flag = null;
                    }
                
                if (!empty($hours)) {
                    $projectId = $_POST['project_id'][$i];
                    $issueId = $_POST['issue_id'][$i];
                    $rowID = $_POST['row_id'][$i];
                    $activity = $_POST['activity'][$i];
                    $aRRactivity = explode('-', $activity);
                    $daysAdded = $_POST['daysAdded'][$i];
                    $notes = str_replace("'","''",$_POST['notes'][$i]);
                    $action_type = $_POST['action_type'][$i];
                    
                    if(!empty($_POST['istri_cvid'][$i])){
                        $istri_cvid = $_POST['istri_cvid'][$i];
                    }
                    else {
                        $istri_cvid = null;
                    }
                    if(!empty($_POST['te_id'][$i])){
                        //Echo '*********&&&&**********POST TE ID:'.$_POST['te_id'][$i];
                        $te_id = $_POST['te_id'][$i];
                    }
                    else {
                        //Echo '<br>*************&&&&*************POST TE ID:'.$_POST['te_id'][$i]. "<br>";
                        $te_id = null;
                    }
                    if(!empty($_POST['effort_cvids'][$i])){
                        $effort_cvids = $_POST['effort_cvids'][$i];
                    }
                    else {
                        $effort_cvids = null;
                    }
                    

                    $effortsID = $rowID . 'efforts';
                    $triID = $rowID . 'tri';
                    $addDaysID = $rowID . 'addDays';
                    
                    /*
                    $effortsID = "";
                    $triID = "";
                    $addDaysID = "";

                    
                    //echo 'Row_id: ' . $rowID . "<br>";
                    //echo 'Project_id: ' . $projectId . "<br>";
                    
                    $rowIdCounter = explode('-', $rowID);
                    #echo count($pidCounter);
                    #print_r($pidCounter);
                    if (count($rowIdCounter) > 1) {
                        $effortsID = $rowIdCounter[0] . $rowIdCounter[1] . 'efforts';
                        $triID = $rowIdCounter[0] . $rowIdCounter[1] . 'tri';
                        $addDaysID = $rowIdCounter[0] . $rowIdCounter[1] . 'addDays';
                    } else {
                        $effortsID = $rowIdCounter[0] . 'efforts';
                        $triID = $rowIdCounter[0] . 'tri';
                        $addDaysID = $rowIdCounter[0] . 'addDays';
                    }
        */


                    //echo 'Issue_id: ' . $issueId . "<br>";
                    //echo 'TE_ID: '.$te_id.'<br>';
                    //echo 'Action Type: '.$action_type.'<br>';
                    //echo 'Hours: ' . $hours . "<br>";
                    //echo 'Activity: ' . $activity . "<br>";
                    //echo 'Activity ID: ' . $aRRactivity[0] . '<br>';
                    //echo 'Effort CVID: '.$effort_cvids.'<br>';
                    //echo 'EFFORT ID ' . $effortsID . '<br>';
                    if (isset($_POST[$effortsID])) {
                        $efforts = $_POST[$effortsID];
                        if (!empty($efforts)) {
                            #implode(',', $efforts);
                            #echo implode(',', $efforts);
                            foreach ($efforts as $a) {
                                //echo $a . ', ';
                            }
                            #print_r($efforts);
                            //echo '<br>';
                        }
                    }

                    /*
                    //echo 'TRI_CVID: '.$istri_cvid. '<br>';
                    if (isset($_POST[$triID])) {
                        $tri = $_POST[$triID];
                        if (!empty($tri)) {
                            #echo "TRI: " . $tri[0] . '<br>';
                        }
                        #print_r($tri);
                    }
                    */
                    

                    //echo 'Notes: ' . $notes . "<br>";

                    if ($_POST['issue_id'][$i] == '0') {
                        $issueId = "null";
                    } else {
                        $issueId = $_POST['issue_id'][$i];
                    }

                    //echo 'Updated Issue ID: ' . $issueId . "<br>";
                    //echo '************************************************<br>';


                    $insertTE = "INSERT INTO bitnami_redmine.time_entries (project_id, user_id, issue_id, hours, comments, activity_id, "
                            . "spent_on, tyear, tmonth, tweek, created_on, updated_on) VALUES (" . $projectId . ", ".$userID.", " . $issueId . ", "
                            . $hours . ", '" . $notes . "', " . $aRRactivity[0] . ", '" . $dateFormatted . "', YEAR('" . $dateFormatted . "'), "
                            . "MONTH('" . $dateFormatted . "'), WEEK('" . $dateFormatted . "'), NOW(), NOW());";
                    $updateTE = "UPDATE bitnami_redmine.time_entries "
                                ."SET project_id = ".$projectId .", user_id = ".$userID.", issue_id = ".$issueId.", hours = ".$hours.", comments = '"
                            .$notes."', activity_id = ".$aRRactivity[0].", spent_on = '".$dateFormatted."', tyear = YEAR('".$dateFormatted."'), "
                            ."tmonth = MONTH('".$dateFormatted."'), tweek = WEEK('".$dateFormatted."'), updated_on = NOW() "
                            ."WHERE id = ".$te_id." ;";
                    
                    if($action_type=="INSERT"){
                        //echo "SQL Insert Time Entry: " . $insertTE;
                            RedmineDB::getInstance()->insertSQL($insertTE);
                            $lastTEID = RedmineDB::getInstance()->getLastInsertedID();
                            //echo '<br>Last inserted TEID: ' . $lastTEID;
                            $arryLastTEID[] = $lastTEID;
                            
                        if (isset($_POST[$addDaysID])) {
                            
                            $addDays = $_POST[$addDaysID];
                            if (!empty($addDays)) {
                                $date = $dateFormatted;
                                //echo '<br>Formatted Selected Date: '.$dateFormatted.'<br>';
                                //echo 'addDays:' . $addDays[0] . '<br>';
                                //echo '# of Days added: ' . $daysAdded . '<br>';
                                for($c=1; $c<=$daysAdded ; $c++){
                                    $date = strtotime("+".$c." day", strtotime($dateFormatted));
                                    //echo date("Y-m-d", $date).'<br>';
                                    $insertTE2 = str_replace($dateFormatted, date("Y-m-d", $date), $insertTE);
                                    //echo  "SQL Insert Time Entry: " . $insertTE2;
                                    RedmineDB::getInstance()->insertSQL($insertTE2);
                                    $lastTEID = RedmineDB::getInstance()->getLastInsertedID();
                                    //echo '<br>Last inserted TEID: ' . $lastTEID.'<br>';
                                    $arryLastTEID[] = $lastTEID;
                                }
                            }
                        }

                    }
                    else if($action_type=="UPDATE"){
                        $lastTEID = $te_id;
                        //echo '<br>TEID to be updated: ' . $lastTEID;
                        $arryLastTEID[] = $te_id;
                        //echo "<br>SQL Update Time Entry: " . $updateTE;
                        RedmineDB::getInstance()->insertSQL($updateTE);                        
                    }
                    
                    if($action_type=="UPDATE"){
                        $deleteEff = "DELETE FROM bitnami_redmine.custom_values WHERE id IN (".$effort_cvids.");";
                        //echo '<br> DELETE Exisitng Efforts: '.$deleteEff;
                        RedmineDB::getInstance()->insertSQL($deleteEff);
                    }

                    if (isset($_POST[$effortsID])) {
                        $efforts = $_POST[$effortsID];
                        if (!empty($efforts)) {
                            #implode(',', $efforts);
                            foreach ($efforts as $a) {
                                if(!empty($arryLastTEID)){
                                    for ($ct=0;$ct<sizeof($arryLastTEID);$ct++){
                                        $insertEfforts = "INSERT INTO bitnami_redmine.custom_values (customized_type, customized_id, custom_field_id, value) "
                                                . "VALUES ('TimeEntry', " . $arryLastTEID[$ct] . ", 4, '" . trim($a) . "');";
                                        //echo '<br>SQL Insert Efforts: ' . $insertEfforts;
                                        RedmineDB::getInstance()->insertSQL($insertEfforts);
                                    }
                                }
                                else{
                                    $insertEfforts = "INSERT INTO bitnami_redmine.custom_values (customized_type, customized_id, custom_field_id, value) "
                                                . "VALUES ('TimeEntry', " . $te_id . ", 4, '" . trim($a) . "');";
                                        //echo '<br>SQL Insert Efforts: ' . $insertEfforts;
                                        RedmineDB::getInstance()->insertSQL($insertEfforts);
                                }
                            }
                        }
                    }

                    /*
                    if (isset($_POST[$triID])) {
                        $tri = $_POST[$triID];
                        #if (!empty($tri)) {
                            if ($tri[0] == 'tri') {
                                $t = 1;
                            } else {
                                $t = 0;
                            }
                            if($istri_cvid==null){
                            #if($action_type=="INSERT"){
                                if(!empty($arryLastTEID)){
                                    for ($ct=0;$ct<sizeof($arryLastTEID);$ct++){
                                        $sqlTRI = "INSERT INTO bitnami_redmine.custom_values (customized_type, customized_id, custom_field_id, value) "
                                        . "VALUES ('TimeEntry', " . $arryLastTEID[$ct] . ", 3, " . $t . ");";
                                        //echo '<br>SQL Insert TRI: ' . $sqlTRI;
                                        RedmineDB::getInstance()->insertSQL($sqlTRI);
                                    }
                                #$sqlTRI = "INSERT INTO bitnami_redmine.custom_values (customized_type, customized_id, custom_field_id, value) "
                                #        . "VALUES ('TimeEntry', " . $lastTEID . ", 3, " . $t . ");";
                                }
                            }
                            else{
                            #else if($action_type=="UPDATE"){
                                $sqlTRI = "UPDATE bitnami_redmine.custom_values SET value = ".$t." WHERE id = ".$istri_cvid." ;";
                                //echo "<br>SQL for TRI: " . $sqlTRI;
                                RedmineDB::getInstance()->insertSQL($sqlTRI);
                            } 
                        #}
                    } else {
                        if(empty($tri) && !empty($istri_cvid)){
                            $sqlTRI = "UPDATE bitnami_redmine.custom_values SET value = '0' WHERE id = ".$istri_cvid." ;";
                            //echo "<br>SQL for TRI: " . $sqlTRI;
                            RedmineDB::getInstance()->insertSQL($sqlTRI);
                        }
                    }
                    */

                    $tri = $_POST[$triID];
                    #echo $tri[0];
                    if ($tri[0] == 'tri') {
                        $t = 1;
                    } else {
                        $t = 0;
                    }
                    #echo "<br>Value: ". $t;

                    if($action_type=="INSERT"){
                        if(!empty($arryLastTEID)){
                            for ($ct=0;$ct<sizeof($arryLastTEID);$ct++){
                                $sqlTRI = "INSERT INTO bitnami_redmine.custom_values (customized_type, customized_id, custom_field_id, value) "
                                    . "VALUES ('TimeEntry', " . $arryLastTEID[$ct] . ", 3, " . $t . ");";
                                #echo '<br>SQL Insert TRI: ' . $sqlTRI;
                                RedmineDB::getInstance()->insertSQL($sqlTRI);
                            }
                        }
                    } else if ($action_type=="UPDATE"){
                        if (!empty($istri_cvid)){
                            $sqlTRI = "UPDATE bitnami_redmine.custom_values SET value = ".$t." WHERE id = ".$istri_cvid." ;";
                            #echo "<br>SQL Update 1: " . $sqlTRI;
                            RedmineDB::getInstance()->insertSQL($sqlTRI);
                        }
                        else
                        {
                            $sqlTRI = "INSERT INTO bitnami_redmine.custom_values (customized_type, customized_id, custom_field_id, value) "
                                . "VALUES ('TimeEntry', " . $te_id . ", 3, " . $t . ");";
                            #echo '<br>SQL Insert TRI: ' . $sqlTRI;
                            RedmineDB::getInstance()->insertSQL($sqlTRI);
                        }
                    }

                    #echo '<br>************************************************<br>';
                }
                else if($hr_flag==1){//if Hours is changed to null or 0
                    //echo '*************-------------- NO HOURS --------------***************<br>';
                    //echo 'HOURS: '.$hours. "<br>";
                    

                    if(!empty($_POST['istri_cvid'][$i])){// delete TRI CVID from custom_values 
                        $istri_cvid = $_POST['istri_cvid'][$i];
                        $deleteTRI = "DELETE FROM bitnami_redmine.custom_values WHERE id IN (".$istri_cvid.");";
                        //echo '<br> DELETE Exisitng Efforts: '.$deleteTRI;
                        RedmineDB::getInstance()->insertSQL($deleteTRI);
                    }

                    if(!empty($_POST['te_id'][$i])){//delete TE_ID from time_entries 
                        $te_id = $_POST['te_id'][$i];
                        $deleteTE = "DELETE FROM bitnami_redmine.time_entries WHERE id IN (".$te_id.");";
                        //echo '<br> DELETE Exisitng TE: '.$deleteTE;
                        RedmineDB::getInstance()->insertSQL($deleteTE);
                    }

                    if(!empty($_POST['effort_cvids'][$i])){//delete efforts CVID from custom_values 
                        $effort_cvids = $_POST['effort_cvids'][$i];
                        $deleteEff = "DELETE FROM bitnami_redmine.custom_values WHERE id IN (".$effort_cvids.");";
                        //echo '<br> DELETE Exisitng Efforts: '.$deleteEff;
                        RedmineDB::getInstance()->insertSQL($deleteEff);
                    }
                     
                    //echo '<br>*************-------------- NO HOURS --------------***************<br>';
                }
                unset($arryLastTEID);
            }
        }
        ?>

        <?php
        #sleep(2);
        #header('Location: /timetracker/index.php?date='.$dateFormatted);
        ?>
        <script type="text/javascript">
           window.location= 'index.php?date=<?php echo $dateFormatted; ?>'
        </script>
    </body>
</html>




