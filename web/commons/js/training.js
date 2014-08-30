/**
 * Created by leonard mpande on 8/19/14.
 */
$(document).ready(function(){
   window.header = "Add";
   window.formname = "";
   window.instancename = "";
   window.instanceId =  -1;


    /// global arrays

    window.Sex = new Array();
    window.Profession = new Array();
    window.Employer = new Array();


    ///////// click options handling
    $("ul#tabableOption li a").on("click",function(){

        var tab_menu_id = $(this).attr("id");
        LoadContents(tab_menu_id,5,"users_submit_button",safeCallBack);//load with default form id of 5.

    });


    // triggering participants add on page load
    $("ul#tabableOption li a#add_participants").trigger("click");

    (function (){
        var returnType
        $.ajax({ // ajax call starts
            url: "getInstance/json/"+window.instanceId, // JQuery loads
            dataType:'json',
            success: function(data) // Variable data contains the data we get from serverside
            {

                window.instancename = data.instance_name['coursename']+" Of "+ data.instance_region +" ("+formattDate(data.instance_startdate)+")";

            }
        });

    })()

});

function formattDate(dates){
    return dates.substring(0,dates.indexOf("T"));
}
function LoadContents(tabId,formId,submit_button,safeCallBack){
    var instanceId = $("div.parent_container").attr("id");//tabId == "add_facilitators "
    window.instanceId = instanceId;
    if(tabId == "add_participants"){

        $("div.buttons_grouped div.btn-group a").on("click",function(){
            $("div."+tabId+"").html("<i class='fa fa-refresh fa-spin fa-3x'></i>&nbsp; <b>loading contents</b>");
            var formId = getFormId($(this).attr("id"));
            $.ajax({ // ajax call starts
                url: 'records/json/'+formId+'/'+instanceId+'/'+tabId, // JQuery loads serverside.php
                data: "", // Send value of the clicked button
                dataType: 'json', // Choosing a JSON datatype
                success: function(data) // Variable data contains the data we get from serverside
                {
                    safeCallBack(tabId,data,submit_button);
                }
            });
        });

        $("div#participants div.btn-group a#participants_public_employee").trigger("click");
        $("div#participants div.btn-group a#facilitators_public_employee").trigger("click");

    }

  if(tabId == "add_facilitators"){

        $("div.buttons_grouped div.btn-group a").on("click",function(){
            $("div."+tabId+"").html("<i class='fa fa-refresh fa-spin fa-3x'></i>&nbsp; <b>loading contents</b>");
            var formId = getFormId($(this).attr("id"));
            $.ajax({ // ajax call starts
                url: 'records/json/'+formId+'/'+instanceId+'/'+tabId, // JQuery loads serverside.php
                data: "", // Send value of the clicked button
                dataType: 'json', // Choosing a JSON datatype
                success: function(data) // Variable data contains the data we get from serverside
                {
                    safeCallBack(tabId,data,submit_button);
                }
            });
        });

        $("div#participants div.btn-group a#participants_public_employee").trigger("click");
        $("div#participants div.btn-group a#facilitators_public_employee").trigger("click");

    }

    if(tabId == "add_trainers"){
            $("div."+tabId+"").html("<i class='fa fa-refresh fa-spin fa-3x'></i>&nbsp; <b>loading contents</b>");
            var formId = getFormId($(this).attr("id"));
            $.ajax({ //ajax call starts
                url: 'list_trainers/json/'+instanceId+'/'+tabId, //JQuery
                data: '', //Send value
                dataType: 'json', //Choosing
                success: function(data) // Variable
                {
                    safeCallBack(tabId,data,submit_button);
                }
            });
 }

  }
function safeCallBack(tabId,data,submit_button){
var header = "Add"
    var title = "";if(tabId=="add_facilitators"){title="already a facilitators";header+=" Facilitators From " +formname+" For "+window.instancename;}else if(tabId=="add_participants"){title="already a participants";header+=" Participants From "+formname+" For "+window.instancename;}else if(tabId=="add_trainers"){title="already a trainer";header+=" Trainers "+" For "+window.instancename;}
    var table = "<h4 style='color:#ccc;'>"+header+"</h4>";
    table += "<table id='table_"+tabId+"' class='table table-condensed table-hover table-bordered table-stripped'>";
   if(tabId == "add_trainers"){
       table += "<thead><tr><th>#</th><th>Name</th><th>Sex</th><th>Area Of Work</th><th>Proffession</th><th>Highest Level Of Qualification</th><th>Employer</th><th>Experience</th><th>Trainer Type</th><th>Check</th></tr></thead>";

   }else{
       table += "<thead><tr><th>#</th><th>Name</th><th>Sex</th><th>Area Of Work</th><th>Proffession</th><th>Check</th></tr></thead>";

   }
    table += "<tbody>";
    j=0;
    if(tabId == "add_facilitators"){
    var recordList = arrayOfRecord(data.insertedRecords,tabId);
    jQuery.each(data.Records, function(i, val) {

        jQuery.each(val, function(i, vals) {

            if(vals['5289e934b4e25']==""){

            }else{
                if(i == "id"){

                }else{
                    j++;
                    if(jQuery.inArray( val['id'], recordList)>-1){

                        table += "<tr><td>"+j+"</td><td >"+vals['5289e934b4e25']+"</td><td></td><td></td><td></td><td><input title='"+title+"' type='checkbox' id='"+val['id']+"' checked disabled /></td></tr>";

                    }else{
                        if(jQuery.inArray( val['id'], arrayOfRecord(data.insertedAlready,"inserted"))>-1){
                            j--;
                        }else{
                            table += "<tr><td>"+j+"</td><td >"+vals['5289e934b4e25']+"</td><td></td><td></td><td></td><td><input type='checkbox' id='"+val['id']+"' /></td></tr>";
                        }
                    }
                    }
            }
        });

    });



    }
    if(tabId == "add_participants"){

    var recordList = arrayOfRecord(data.insertedRecords,tabId);
        optionLists(data.Records,data.fieldOption);
    jQuery.each(data.Records, function(i, val) {

        jQuery.each(val, function(i, vals) {

            if(vals['5289e934b4e25']==""){

            }else{
                if(i == "id"){

                }else{
                    j++;
                    if(jQuery.inArray( val['id'], recordList)>-1){

                        table += "<tr><td>"+j+"</td><td >"+vals['5289e934b4e25']+"</td><td></td><td></td><td></td><td><input title='"+title+"' type='checkbox' id='"+val['id']+"' checked disabled /></td></tr>";

                    }else{
                    if(jQuery.inArray( val['id'], arrayOfRecord(data.insertedAlready,"inserted"))>-1){
                         j--;
                    }else{
                        table += "<tr><td>"+j+"</td><td >"+vals['5289e934b4e25']+"</td><td></td><td></td><td></td><td><input type='checkbox' id='"+val['id']+"' /></td></tr>";
                    }
                  }
               }
            }
        });

    });



    }

    if(tabId == "add_trainers"){
        var recordList = arrayOfRecord(data.insertedTrainers,tabId);
        jQuery.each(data.trainers, function(i, val) {
        window.header = j;
                        j++;
                        if(jQuery.inArray( val['id'], recordList)>-1){

                            table += "<tr><td>"+j+"</td><td >"+val['firstname']+"  "+val['middlename']+"  "+val['lastname']+"</td><td></td><td>"+val['place_of_work']+"</td><td>"+val['profession']+"</td><td></td><td></td><td></td><td></td><td><input title='"+title+"' type='checkbox' id='"+val['id']+"' checked disabled /></td></tr>";

                        }else{
                            table += "<tr><td>"+j+"</td><td >"+val['firstname']+"  "+val['middlename']+"  "+val['lastname']+"</td><td></td><td>"+val['place_of_work']+"</td><td>"+val['profession']+"</td><td></td><td></td><td></td><td></td><td><input type='checkbox' id='"+val['id']+"' /></td></tr>";
                        }

        });


    }

    table += "</tbody>";
    $("."+tabId).html(table);
    var dataTbale =  $("#table_"+tabId).dataTable({
        "sDom": "<'row'<'span6'TRl><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
        "oTableTools": {
            "aButtons": [
//                    {"sExtends":"copy","mColumns":"visible"},
//                    {"sExtends":"xls","mColumns":"visible"},
//                    {"sExtends":"pdf","mColumns":"visible"}
            ],
            "sSwfPath": "{{ asset('commons/swf/copy_cvs_xls_pdf.swf') }}"
        },
        "sPdfOrientation": "landscape",
        "sPaginationType": "bootstrap",
        "oLanguage": {
            "sLengthMenu": "_MENU_ records per page"
        },
        "aLengthMenu": [[10, 25, 50,100,200, -1], [10, 25, 50,100,200, "All"]]
    });
    processTable(dataTbale,submit_button,tabId);
}


function getFormId(form_name){
    var  form_id = 5;
    if(form_name == "participants_public_employee" || form_name == "facilitators_public_employee"){
        form_id = 5;
        window.formname = "Public Employee Form";
    }

    if(form_name == "participants_private_employee" || form_name == "facilitators_private_employee"){
        form_id = 6;
        window.formname = "Private Employee Form";
    }

    if(form_name == "participants_referal_employee" || form_name == "facilitators_referal_employee"){
        form_id = 7;
        window.formname = "Referral Hospital Employee Form";
    }

    if(form_name == "participants_instituion_employee" || form_name == "facilitators_instituion_employee"){
        form_id = 8;
        window.formname = "Training Institution Employee Form";
    }


    return form_id;
}

function processTable(tableContainer,submit_button,tabId){
    var checkedArray = getCheckedArray(tableContainer);
    var instance_id = $("div.parent_container").attr("id");
    tableContainer.on("click","tr td input",function(){
    });

    //// SUBMISSION OF THE CHECKBOXES
    $("."+submit_button).on("click",function(){

        $("."+submit_button).parent().find(".loadImages").html("<span style='color:green;' class='check'><i class='fa fa-spin fa-spinner fa-2x'></i></span>");
        checkedArray = getCheckedArray(tableContainer);
        var countNewCheck = 0;
        $.each(tableContainer.fnGetNodes(), function(index, value) {

            $(this).find("td:last").find("span.load").remove();
            $(this).find("td:last").find("span.check").remove();
            if($(value).find("td:last input").prop("checked")&&!$(value).find("td:last input").prop("disabled")){

              $(this).find("td:last").append("<span style='color:orange;' class='load'><i class='fa fa-spin  fa-spinner '></i></span>");
              countNewCheck ++;
            }
        });

        if(countNewCheck>0){
            $.ajax({ // ajax call starts
                url: getUrl(tabId), // JQuery loads
                data: 'ary='+checkedArray+"&instance_id="+instance_id, // Send value of the clicked button
                method:'POST'
            }).done(function(data) // Variable data contains the data we get from serverside
            {

                if(data=='success'){
                    $.each(tableContainer.fnGetNodes(), function(index, value){
                        if($(value).find("td:last input").prop("checked")&&!$(value).find("td:last input").prop("disabled")){
                            $(this).find("td:last").find("span.load").remove();
                            $(this).find("td:last input").prop("disabled", true);
                            $(this).find("td:last").append("<span style='color:green;' class='check'><i class='fa fa-check'></i></span>");
                            setInterval(removeTick($(this).find("td:last").find("span.check").remove()), 1000);
                        }
                    });
                    $("."+submit_button).parent().find(".loadImages").html("&nbsp;<div class='alert alert-success '><button type='button' class='close' data-dismiss='alert'>&times;</button><h4 ><i class='fa fa-times '></i>&nbsp; adding succeeded</h4></div>");
                    setInterval(removeTick($("."+submit_button).parent().find(".loadImages").html()), 1000);

                }else{
                    $.each(tableContainer.fnGetNodes(), function(index, value){
                        if($(value).find("td:last input").prop("checked")&&!$(value).find("td:last input").prop("disabled")){
                            $(this).find("td:last").find("span.load").remove();
                            $(this).find("td:last").append("<span style='color:red;' class='check' title='adding failed'><i class='fa fa-times'></i></span>");
                          }
                    });
                    $("."+submit_button).parent().find(".loadImages").html("&nbsp;<div class='alert alert-error '><button type='button' class='close' data-dismiss='alert'>&times;</button><h4 ><i class='fa fa-times '></i>&nbsp; adding failed</h4></div>");

                }

            }).fail(function(data) // Variable data contains the data we get from serverside
            {

                $.each(tableContainer.fnGetNodes(), function(index, value){
                    if($(value).find("td:last input").prop("checked")&&!$(value).find("td:last input").prop("disabled")){
                        $(this).find("td:last").find("span.load").remove();
                        $(this).find("td:last").append("&nbsp;<span style='color:red;' class='check'><i class='fa fa-times'></i></span>");
                    }
                });
                $("."+submit_button).parent().find(".loadImages").html("&nbsp;<div class='alert alert-error '><button type='button' class='close' data-dismiss='alert'>&times;</button><h4 ><i class='fa fa-times '></i>&nbsp; adding failed</h4></div>");

            });
        }else{
        }


    });

}

function removeTick(removeTicks){
    removeTicks;
}

function getCheckedArray(tableContainer){
    var secondCellArray = new Array();
    var i = 0;
    $.each(tableContainer.fnGetNodes(), function(index, value){

       if($(value).find("td:last input").prop("checked")&&!$(value).find("td:last input").prop("disabled")){
           secondCellArray[i] = $(this).find("td:last input").attr("id");
           i++;
       }

    });
    return secondCellArray;
}

function getUrl(tabId){
     var url = "";
    if(tabId == "add_participants"){
        url  = "addparticipants";
    }
    if(tabId == "add_facilitators"){
        url  = "addfacilitators";
    }
    if(tabId == "add_trainers"){
        url  = "addtrainers";
    }

    return url;
}

function arrayOfRecord(data,tabId){
    var arry = new Array();
    var  i   = 0;
    if(tabId=="add_trainers"){
        jQuery.each(data, function(i, val) {
            arry[i] = val['trainer_id'];
            i++;
        });
    }else{
        jQuery.each(data, function(i, val) {
            arry[i] = val['record_id'];
            i++;
        });
    }

    return arry;
}

function optionLists(entity,fieldoptions){

//    jQuery.each(entity, function(index, vals) {
//        jQuery.each(vals.value, function(i, val){
//            console.log(i+"  ==>  "+val);
//        });
//
//    });

    jQuery.each(fieldoptions, function(index, vals) {
        jQuery.each(vals, function(i, val){
//            console.log(i+"  ==>  "+val);
            if(val =="Sex"){
                window.Sex[vals.fieldOptionUid] = vals.fieldOptionValue

            }

            if(val == "Employer"){
                window.Employer[vals.fieldOptionUid] = vals.fieldOptionValue
            }

            if(val == "Profession"){
                window.Profession[vals.fieldOptionUid] = vals.fieldOptionValue
            }
        });

    });

    console.log(window.Profession['528a0adfa726a']);

}