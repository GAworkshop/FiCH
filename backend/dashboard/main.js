//data
var all = ["50m蝶式", "50m仰式", "50m蛙式", "50m自由式", "100m蝶式", "100m仰式", "100m蛙式", "100m自由式"];


//initialize
window.onload=function(){
    
    document.getElementById("createUserSubmit").addEventListener("click", function(e){
        var form = document.getElementById("form-add");
        
        if(document.getElementById("pass").value.length < 4){
            alert("password must be at least 4chars!");
        }else{
            var name = document.getElementById("name").value;
            var pass = document.getElementById("pass").value;
            var phone = document.getElementById("phone").value;
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (xhttp.readyState == 4 && xhttp.status == 200) {
                    alert("Done!!");
                    document.getElementById("name").value = "";
                    document.getElementById("pass").value = "";
                    document.getElementById("phone").value = "";
                }
            };
            xhttp.open("POST", "../api/Member.php", true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            request = "action=create&name=" + name + "&pass=" + pass + "&phone=" + phone;
            xhttp.send(request);
        }
            
    });
    

    
    document.getElementById("addRow").addEventListener("click", addRow);
    
    document.getElementById("table").addEventListener("click", function(e){
        if(e.target && e.target.nodeName == "BUTTON"){
                
            var c = confirm("確認刪除 " + e.target.parentElement.parentElement.getElementsByClassName("name")[0].value + "?");
            
            if(c == true){
                e.target.parentElement.parentElement.parentElement.removeChild(e.target.parentElement.parentElement);
                renderRow();
                //alert("deleted!");
            }
            
        }
        //console.log("e.target: "+e.target);
        //console.log("e.target.nodeName: "+e.target.nodeName);
        
    });
    
    renderRow();
    
    
    
};

function ajax(request, target){
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (xhttp.readyState == 4 && xhttp.status == 200) {
            //document.getElementById("output").innerHTML = xhttp.responseText;
            var data = xhttp.responseText;
            console.log(data);
            var result = data.split("#r#");
            
            var rowCount = document.getElementById("table1").rows.length;
            for (var i = 1; i < rowCount; i++) {
                target.deleteRow(1);
            }
            
            for (var i = 0; i < result.length; i++){
                result[i] = result[i].split("#c#");
            }
            
            for(var i = 0; i < result.length; i++){
                var tr = target.insertRow(-1);
                for(var j = 0; j < result[i].length; j++){
                    td = tr.insertCell();
                    td.innerHTML = result[i][j];
                }
            }
            target.deleteRow(-1);
            
            target.className = "table table-hover";
        }
    };
    xhttp.open("POST", "../api/GetHtmlTable.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request = "name=" + request;
    xhttp.send(request);
}


function ajaxtest(target){
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (xhttp.readyState == 4 && xhttp.status == 200) {
            
            var data = xhttp.responseText;
            var result = JSON.parse(data);
            console.log(data);
            
            var rowCount = document.getElementById("table1").rows.length;
            
            for (var i = 1; i < rowCount; i++) {
                target.deleteRow(1);
            }
            
            
            for(var i = 0; i < result.length; i++){
                var tr = target.insertRow(-1);
                for(var j = 0; j < result[i].length; j++){
                    td = tr.insertCell();
                    td.innerHTML = result[i][j];
                }
            }
            target.deleteRow(-1);
            
            target.className = "table table-hover";
        }
    };
    xhttp.open("POST", "../api/GetHtmlTable.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request = "name=0";
    xhttp.send(request);
}


function buttonClicked(obj){
    switch(obj.id){
        case "refreash":
            break;
        case "overview":
            document.getElementsByClassName("active")[0].className = "";
            obj.parentElement.className = "active";
            break;
        case "member":
            document.getElementsByClassName("active")[0].className = "";
            obj.parentElement.className = "active";
            ajax("member", document.getElementById("table1"));
            break;
        case "export":
            ajaxtest(document.getElementById("table1"));
            break;
            
        default:
            
    }
    console.log(obj.id);
}


function setDropdown(target){
    for(var i = 0; i < all.length; i++){
        var o = new Option(all[i]);
        o.value = i+1;
        target.options.add(o);
    }
}

function addRow(){
    //add a new row to the end of table
    document.getElementById("addRow").innerHTML = "新增一名選手";
    
    var tr = document.getElementById("table").insertRow();
    td = tr.insertCell();
    td.innerHTML = '<input name="name[]" class="name" type="text" />';
    td = tr.insertCell();
    td.innerHTML = '<input name="std_grade[]" class="grade" type="text" />';
    td = tr.insertCell();
    td.innerHTML = '<select name="gender[]" class="gender"><option value="MALE">男</option><option value="FEMALE">女</option></select>';
    td = tr.insertCell();
    td.innerHTML = '<select name="event1[]" class="event1"><option>選擇項目</option></select>';
    td = tr.insertCell();
    td.innerHTML = '<select name="event2[]" class="event2"><option>選擇項目</option></select>';
    td = tr.insertCell();
    td.innerHTML = '<select name="event3[]" class="event3"><option>選擇項目</option></select>';
    td = tr.insertCell();
    td.innerHTML = '<button class="delete" type="button" >移除</button>';
    
    var lastAdd = document.getElementsByClassName("event1");
    setEvent(lastAdd[lastAdd.length-1]);
    
    lastAdd = document.getElementsByClassName("event2");
    setEvent(lastAdd[lastAdd.length-1]);
    
    lastAdd = document.getElementsByClassName("event3");
    setEvent(lastAdd[lastAdd.length-1]);
    //console.log("tr:"+document.getElementById("table").getElementsByTagName("tr").length);
    renderRow();
    
    document.getElementById("table").style.marginLeft = "auto";
}

function renderRow(){
    var table = document.getElementById("table");
    var trs = table.getElementsByTagName("tr");

    trs[0].style.backgroundColor = "#ffdd91";
    for(i = 1; i < trs.length; i++){        
        
        if(i%2 == 1)
            trs[i].style.backgroundColor = "aquamarine";
        else
            trs[i].style.backgroundColor = "#ffdd91";
    }
}

function submit_click(){
    
    var inputArray = document.getElementById("form1").getElementsByTagName("INPUT");
    for(i = 0; i < inputArray.length; i++){
        console.log(inputArray[i].value);
        if(inputArray[i].value.trim() == ""){
            alert("請確認是否有漏填的資料!");
            return;
        }
        //if(inputArray[i].value.trim().match("[\'+\"+<+>+|+\?+\`+]")){
        if(inputArray[i].value.trim().match(/[-\\+'"?|<>`?^*]+/g)){
            alert("內容請勿包含特殊字元");
            console.log("invalid char: " + inputArray[i].value);
            return;
        }
    }

    var sel = document.getElementsByClassName("event1");
    var sel2 = document.getElementsByClassName("event2");
    var sel3 = document.getElementsByClassName("event3");
    
    for(i = 0; i < sel.length; i++){
        if(sel[i].selectedIndex == 0){
            alert("項目一為必填!");
            return;
        }
        if(sel[i].selectedIndex == sel2[i].selectedIndex || sel2[i].selectedIndex == sel3[i].selectedIndex || sel3[i].selectedIndex == sel[i].selectedIndex){
            console.log("sel:"+sel[i].selectedIndex+"\nsel2:"+sel2[i].selectedIndex+"\nsel3"+sel3[i].selectedIndex);
            if(sel2[i].selectedIndex == 0 || sel3[i].selectedIndex == 0){
                
            }
            else{
                alert("單一選手請勿重複選取相同項目!");
                return;
            }
        }
    }
    
    
    //alert("報名成功!");
    
    //execute php file
    document.getElementById("form1").submit();
    
}



