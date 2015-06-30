/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/*$(function () {
 $("#myTable").tablesorter();
 });
 */

function sendAlert(text) {
    alert(text);
}

function isNumberKey(evt)
{
    var charCode = (evt.which) ? evt.which : event.keyCode
    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57))
        return false;

    return true;
}

/*
$(function () {
    $("#selecteddate").datepicker({
        changeMonth: true,
        changeYear: true,
        numberOfMonths: 1,
        onSelect: function (date) {
            //alert(date);
            window.location="index.php?date="+date;
            
        }
    });
});
*/

$(function () {

    var a = document.getElementById("signout");

    a.onclick = function () {

        window.location.href = "login.php";
    }
});

$(function () {
    $("#selecteddate").datepicker({
        format: "mm/dd/yyyy",
        orientation: "top auto",
        autoclose: true,
        todayHighlight: true,
        todayBtn: "linked"
    })
    .on('changeDate', function (ev) {
        //alert(ev.format());
        window.location.href = "?date=" + ev.format();
    });
});


counter = 0;
cnt = [];

$(function () {
    /*when i click on the + button*/
    $(document).on('click', '.plus', function (e) {
        e.preventDefault();
        counter++;
        var idex = $(this).closest('tr').index();
        //alert('Row index is ' + idex);
        
        //var rowhtml = $(this).closest('tr').html();
        
        var row_id = $(this).closest('tr').find('#row_id').val();
        var hidden_rowID = "<input type='hidden' id = 'row_id' name='row_id[]' value='"+row_id+"-"+counter+"' >";
        
        var p_id = $(this).closest('tr').find('#project_id').val();
        var hidden_projID = "<input type='hidden' id = 'project_id' name='project_id[]' value='"+p_id+"' >";
        
        var issue_id = $(this).closest('tr').find('#issue_id').val();
        var hidden_issueID = "<input type='hidden' id = 'issue_id' name='issue_id[]' value='"+issue_id+"' >";
        
        var hidden_action_type = "<input type='hidden' id = 'action_type' name='action_type[]' value='INSERT' >";
        var hidden_te_id = "<input type='hidden' id = 'te_id' name='te_id[]' >";
        var hidden_effort_cvids = "<input type='hidden' id = 'effort_cvids' name='effort_cvids[]' >";
        var hidden_istri_cvid = "<input type='hidden' id = 'istri_cvid' name='istri_cvid[]' >";
        
        var hrFlag = $(this).closest('tr').find('#hrFlag').val();
        
        //$bugCheck = "";
        
        //var idex1 = $(this).closest('tr').find('td:eq(0)').html();
        var idex1 = $(this).closest('tr').find('td:eq(1)').html();//Name
        //$bugCheck = idex1;
        
        var idex2 = $(this).closest('tr').find('td:eq(2)').html();//Hours
        //$bugCheck += "Hours(Before):" + idex2;
        var idex2_val = $(this).closest('tr').find('.hours').val();
        idex2 = idex2.replace('value="' + idex2_val + '"', 'value=""');
        idex2 = idex2.replace('value=' + idex2_val, 'value=""');
        idex2 = idex2.replace('VALUE="' + idex2_val + '"', 'value=""');
        idex2 = idex2.replace('VALUE=' + idex2_val, 'value=""');
        //$bugCheck += "Hours(After):" + idex2;
        
        var idex3 = $(this).closest('tr').find('td:eq(3)').html();//Activities
        //$bugCheck += "Activities(Before):" + idex3;
        idex3 = idex3.replace("selected", "");
        idex3 = idex3.replace("SELECTED", "");
        //$bugCheck += "Activities(After):" + idex3;

        var idex4 = $(this).closest('tr').find('td:eq(4)').html();//Efforts
        //$bugCheck += "Efforts(Before):" + idex4;
        idex4 = idex4.replace(/<div[\s\S]*?<\/div>/, '');
        idex4 = idex4.replace(/<DIV[\s\S]*?<\/DIV>/, '');
        idex4 = idex4.replace(/selected=""/g, "");
        idex4 = idex4.replace(/SELECTED=""/g, "");
        idex4 = idex4.replace(/selected/g, "");
        idex4 = idex4.replace(/SELECTED/g, "");
        idex4 = idex4.replace(/([0-9]){1,3}efforts/g, row_id + "-" + counter + "efforts");
        //$bugCheck += "Efforts(After):" + idex4;
        //alert(idex4);
       
        var idex5 = $(this).closest('tr').find('td:eq(5)').html();//TRI
        //$bugCheck += "TRI(Before):" + idex5;
        idex5 = idex5.replace(/checked=""/, "");
        idex5 = idex5.replace(/CHECKED=""/, "");
        idex5 = idex5.replace(/checked/, "");
        idex5 = idex5.replace(/CHECKED/, "");
        idex5 = idex5.replace(row_id, row_id + "-" + counter);
        //$bugCheck += "TRI(After):" + idex5;
        
        var idex6 = $(this).closest('tr').find('td:eq(6)').html();//Notes
        //$bugCheck += "Note(Before):" + idex6;
        var idex6_val = $(this).closest('tr').find('#notes').val();
        idex6 = idex6.replace('value="' + idex6_val + '"', 'value=""');
        idex6 = idex6.replace('value=' + idex6_val, 'value=""');
        idex6 = idex6.replace('VALUE="' + idex6_val + '"', 'value=""');
        idex6 = idex6.replace('VALUE=' + idex6_val, 'value=""');
        //$bugCheck += "Note(After):" + idex6;
        
        var idex7 = $(this).closest('tr').find('td:eq(7)').html();//Additional Days
        //$bugCheck += "Days(Before):" + idex7;
        
        if(hrFlag == '1'){
            idex7 = "<input id='addDays' type='checkbox' name = \"" + row_id + "-" + counter + "addDays[]\" value='addDays' /> Add to next ";
            idex7 = idex7 + "<select id='daysAdded' name = 'daysAdded[]' >";
            idex7 = idex7 + "<option>0</option><option>1</option><option>2</option><option>3</option><option>4</option></select> days";
        }
        else {
            idex7 = idex7.replace("selected", "");
            idex7 = idex7.replace("SELECTED", "");
            idex7 = idex7.replace(row_id, row_id + "-" + counter);
        }

        //$bugCheck += "Days(After):" + idex7;
        
        //var s = document.getElementById("bugtext");
        //s.value = $bugCheck

        //$('.output').html('<p><b>index:</b>' + idex +' <br>project_id:' + p_id +' <br>issue_id:' + issue_id + ' <br>index2:' + idex2 + ' <br>index3:' + idex3 + ' <br>index4:' + idex4 + ' <br>index5:' + idex5 + ' <br>index6:' + idex6 + ' <br>index7:' + idex7 + ' <br>index8:' + idex8 + '</p>');
        //$('.output').html('<p><b>index:</b>' + idex + '<br>'+text);
        
        //alert($('#myTable tbody tr').length);
            
        var row = '<tr>' + hidden_rowID + hidden_projID + hidden_issueID + hidden_action_type + hidden_istri_cvid + hidden_effort_cvids + hidden_te_id + '<td></td><td>' + idex1 + '</td><td>' + idex2 + '</td><td>' + idex3 + '</td><td>' + idex4 + '</td><td style="text-align:center">' + idex5 + '</td><td>' + idex6 + '</td><td>' + idex7 + '</td></tr>';

        //$('.output').html(row);
        
        //document.getElementById("myTable").insertRow(idex + 2).innerHTML = row;
        
        var newRow = $(row);
        newRow.insertAfter($("#myTable >tbody > tr:nth("+idex+")"));

    });
});

$(function () {
            calculateSum();
});

function calculateSum() {
    var sum = 0;
    var weeksum = 0;
    $(".hours").each(function () {
        if (!isNaN(this.value) && this.value.length != 0) {
            sum += parseFloat(this.value);
        }
    });
    $("#total").html(sum.toFixed(2));


    weeksum = sum + <?php session_start(); echo $_SESSION['weektotal'] ?>

    $("#weektotal").html(weeksum.toFixed(2));
}