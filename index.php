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
        <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
        <title>Redmine Time Tracker</title>
        <?php include 'header.php'; ?>
        <style>
            .ui-datepicker select.ui-datepicker-month, .ui-datepicker select.ui-datepicker-year {
                background-color: black;
            }

            body {
                padding-top: 40px;
                padding-bottom: 50px;
            }

            .dropdown-menu {
                margin-bottom: 70px;
            }


        </style>
        <script type="text/javascript" src="public/javascripts/loggingTime.php"></script>
    </head>
    <body>
        <br/>
        <form name="form1" method="post" action="submit.php">
            <div class="navbar navbar-default navbar-fixed-top">
                <div class="container-fluid">
                    <table width="100%">
                        <tr>
                            <td style="width:200px">
                                <div class="navbar-header">
                                    <a class="navbar-brand" href="index.php">Redmine Time Tracker</a>
                                </div>
                            </td>
                            <td style="width:150px">
                                <input class="form-control" type="text" style="width:150px" id="selecteddate" name="selecteddate" value="<?php
                                if (isset($_POST['selecteddate'])) {
                                    echo $_POST['selecteddate'];
                                } else if (isset($_GET['date'])) {
                                    echo date('m/d/Y', strtotime($_GET['date']));;
                                }else {
                                    echo date('m/d/Y');
                                }
                                ?>">
                            </td>
                            <td>
                                <ul class="nav navbar-nav">
                                    <li><a href="reports/" target="_blank">Reports</a></li>
                                </ul>
                            </td>
                            <td>
                                <div class="navbar-form navbar-right">
                                    <?php
                                    echo "Logged in as: <b>".strtolower($_SESSION['username'])."</b> | ";
                                    echo "<a id='signout' href='login.php?action=signout'>Signout</a>";
                                    ?>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="container-fluid">
                <div class="panel panel-default">
                    <input type="hidden" value="" id="newCol">

                    <div class="panel-body">
                        <?php
                        require_once 'db.php';
                        $pid = $_SESSION['userid'];
                        $_SESSION['weekhours'] = 10;

                        ## Setting date for calendar
                        if (isset($_POST['selecteddate'])) {
                            $date = $_POST['selecteddate'];
                        } else if (isset($_GET['date'])) {
                            $date = $_GET['date'];
                        } else {
                            $date = date('m/d/Y');
                            #$date = '2015-06-04';
                        }

                        ## Setting date for SQL parameter
                        $date = date('Y-m-d', strtotime($date));
                        #echo "********DATE  " . $date . "**********";

                        ## Execute SQLs to populate table
                        $listProjects = RedmineDB::getInstance()->projectsByUsers($pid, $date);
                        $listActivities = RedmineDB::getInstance()->listActivities();
                        $listEfforts = RedmineDB::getInstance()->listEfforts();

                        ## Process Efforts to be in an array
                        $efforts = explode('- ', $listEfforts);
                        #print_r($efforts);

                        ## Process activities to be in an array
                        while ($row = mysqli_fetch_array($listActivities)) {
                            $activities[] = $row['name'];
                            $activity_ids[] = $row['id'];
                        }
                        #print_r(array_unique($activities));
                        ?>

                        <table class='table table-striped' id='myTable'>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Name</th>
                                    <th style="text-align:center">Time</th>
                                    <th style="text-align:center">Activity</th>
                                    <th style="text-align:center">Effort</th>
                                    <th style="text-align:center">TRI</th>
                                    <th style="text-align:center">Note</th>
                                    <th style="text-align:center">Additional Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $effortFunctions = "";
                                while ($row1 = mysqli_fetch_array($listProjects)) {
                                    if ($row1['issue_id'] == null) {
                                        $issue_num = 0;
                                    } else {
                                        $issue_num = $row1['issue_id'];
                                    }
                                    if ($row1['istri_cvid'] == null) {
                                        $istri_cvid = null;
                                    } else {
                                        $istri_cvid = "value='".$row1['istri_cvid']."'";
                                    }
                                    if ($row1['effort_cvids'] == null) {
                                        $effort_cvids = null;
                                    } else {
                                        $effort_cvids = "value='".$row1['effort_cvids']."'";
                                    }
                                    if ($row1['time_entry_id'] == null) {
                                        $te_id = null;
                                    } else {
                                        $te_id = "value='".$row1['time_entry_id']."'";
                                    }
                                    if ($row1['hours'] == null) {
                                        $hrFlag = null;
                                    } else {
                                        $hrFlag = "value='1'";
                                    }


                                    echo "<tr>";

                                    #Populate Project
                                    if (($row1['allowTimeInput'] == '0')) {

                                        $name = "";
                                        $depth = $row1['depth'];
                                        if ($depth > 0){
                                            $pxs = 25 * $depth;
                                            $name = "<td><div style='width:100%;padding-left:" . $pxs . "px;'>" . $row1['name'] . "</div></td>";
                                        }
                                        else{
                                            $name = "<td>" . $row1['name'] . "</td>";
                                        }

                                        echo "<td></td>" . $name
                                        . "<td></td>"
                                        . "<td></td>"
                                        . "<td></td>"
                                        . "<td></td>"
                                        . "<td></td>"
                                        . "<td></td>"
                                        . "</tr>";
                                        continue;

                                    } else {
                                        #Populate + sign for adding row
                                        echo "<input type='hidden' id = 'row_id' name='row_id[]' value='" . $row1['row'] . "' >"
                                            . "<input type='hidden' id = 'project_id' name='project_id[]' value='" . $row1['project_id'] . "' >"
                                            . "<input type='hidden' id = 'issue_id' name='issue_id[]' value='" . $issue_num . "' >"
                                            . "<input type='hidden' id = 'action_type' name='action_type[]' value='" . $row1['action_type'] . "' >"
                                            . "<input type='hidden' id = 'istri_cvid' name='istri_cvid[]' " . $istri_cvid . " >"
                                            . "<input type='hidden' id = 'effort_cvids' name='effort_cvids[]' " . $effort_cvids . " >"
                                            . "<input type='hidden' id = 'te_id' name='te_id[]' " . $te_id . " >"
                                            . "<input type='hidden' id = 'hrFlag' name='hrFlag[]' " . $hrFlag . " >";
                                        echo "<td class='plus' name='plus'>+</td>";

                                        $depth = $row1['depth'];
                                        if ($depth > 0){
                                            $pxs = 25 * $depth;
                                            echo "<td><div style='width:100%;padding-left:" . $pxs . "px;'>" . $row1['name'] . "</div></td>";
                                        }
                                        else{
                                            echo "<td name='title[]'>" . $row1['name'] . "</td>";
                                        }
                                    }




                                    #Populate hour input box
                                    echo "<td><input id='hours' class='hours form-control' style='width:50px' maxlength='3' type = \"text\" name = \"hours[]\" value='" . $row1['hours'] . "' onkeypress=\"return isNumberKey(event)\" onchange=\"calculateSum()\" /></td>";

                                    #Populate list of activities as single select dropdown
                                    echo "<td><select class='form-control' style='width:175px' name = \"activity[]\">";
                                    for ($j = 0; $j < sizeof($activities); $j++) {
                                        if ($row1['activity_id'] == $activity_ids[$j]) {
                                            $selectedAct = "selected";
                                        } else {
                                            $selectedAct = null;
                                        }
                                        echo "<option value='" . $activity_ids[$j] . "-" . $activities[$j] . "' " . $selectedAct . ">" . $activities[$j] . "</option>";
                                    }
                                    echo "</select></td>";


                                    #Populate list of efforts as multi select dropdown
                                    echo "<td>";
                                    echo "<select id = \"" . $row1['row'] . "efforts\" name = \"" . $row1['row'] . "efforts[]\" multiple='multiple' size='1' >";
                                    $effortFunction = "$(document).ready(function () { $('#" . $row1['row'] . "efforts').multiselect();});";
                                    //$effortFunction = "$('#" . $row1['row'] . "efforts').multiselect();"
                                    //echo($effortFunction);
                                    //echo "<script type='text/javascript'>alert('".$effortFunction."');</script>";

                                    for ($i = 0; $i < sizeof($efforts); $i++) {
                                        if ($i == 0) {
                                            continue;
                                        } else {
                                                if (strpos($row1['effort_values'], trim($efforts[$i]))!==false) {
                                                    $selectedEff = "selected";
                                                } else {
                                                    $selectedEff = null;
                                                }
                                                echo "<option value='" . $efforts[$i] . "' " . $selectedEff . ">" . $efforts[$i] . "</option>";
                                        }
                                    }
                                    echo "</select>";
                                    echo("<script type='text/javascript'>".$effortFunction."</script>");
                                    echo "</td>";

                                    #Populate isTRI
                                    if($row1['istri_value']=='1'){
                                        $checkedTRI = "checked";
                                    }
                                    else {
                                        $checkedTRI = null;
                                    }
                                    echo "<td style='text-align:center'><input id='tri' name = \"" . $row1['row'] ."tri[]\" type='checkbox' value='tri' ".$checkedTRI."/></td>";

                                    $problem = array("'",'"');
                                    $replace = array("&#39;","&#34;");
                                    //Populate Notes
                                    echo "<td><input class='form-control' style='width:200px' id='notes' name = \"notes[]\" type='text' value = '".str_replace($problem,$replace,$row1['comments'])."' /></td>";

                                    //Add days
                                    echo "<td>";
                                    if($hrFlag!=null){
                                        echo "<input id='addDays' type='checkbox' name = \"" . $row1['row'] . "addDays[]\" value='addDays' style='display:none' />";
                                        echo "<select id='daysAdded' name = \"daysAdded[]\" style='display:none' >
                                                                <option>0</option>
                                                                <option>1</option>
                                                                <option>2</option>
                                                                <option>3</option>
                                                                <option>4</option>
                                                            </select>
                                                            </td></tr>";
                                    }
                                    else {
                                        echo "<input id='addDays' type='checkbox' name = \"" . $row1['row'] . "addDays[]\" value='addDays' /> Add to next ";
                                        echo "<select id='daysAdded' name = \"daysAdded[]\" >
                                                                    <option>0</option>
                                                                    <option>1</option>
                                                                    <option>2</option>
                                                                    <option>3</option>
                                                                    <option>4</option>
                                                                </select>
                                                                days</td></tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="navbar navbar-default navbar-fixed-bottom">
                <div class="container-fluid" style="padding-top: 5px;padding-bottom: 5px">
                    <div style="float: left">
                        <table width="100%">
                            <tr>
                                <td style="text-align:right;vertical-align:middle;font-weight: bold;text-decoration: underline" >
                                    Day Total:
                                </td>
                                <td id='total' style="text-align:left;vertical-align:middle;font-weight: bold; padding-left: 5px" value='0'></td>
                            </tr>
                            <tr>
                                <td style="text-align:right;vertical-align:middle;font-weight: bold;text-decoration: underline" >
                                    Week Total:
                                </td>
                                <div style="float: right">
                                    <td id='weektotal' style="text-align:left;vertical-align:middle;font-weight: bold; padding-left: 5px" value='0'></td>
                                </div>
                            </tr>
                        </table>
                    </div>
                    <div style="float: right">
                        <button class="btn btn-lg btn-primary btn-block" type="submit" name="submit" value="Submit" style="width:250px">Submit</button>
                    </div>

                </div>
            </div>
        </form>
        <!--<textarea id="bugtext" rows="4" cols="50"></textarea>-->
        <script type="text/javascript">

        </script>
    </body>
</html>