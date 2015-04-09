<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>OctoPub - Threads</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container shadow">
    <img src="Logo.png" class="logo" onclick="clearThread()">
    <h1>OctoPub - Threads</h1>
    <div id="newThread" class="card shadow button">+</div>
    <br>
    <div class="threads">

    </div>
</div>

<!-- Include the chance Library... This should be removed later. -->
<script src="http://chancejs.com/chance.min.js"></script>

<!-- Include the Jquery Library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>


<script>
    //Declare global vars
    var latestMessageId = 0;
    var currentThread = "";
    var messageGetter;
    function getUserId() {
        var name = "userId" + "=";
        var ca = document.cookie.split(';');
        for(var i=0; i<ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1);
            if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
        }
        newId = generateId();
        setUserId(newId);
        return newId;
    }
    function setUserId(newId){
        var d = new Date();
        d.setTime(d.getTime() + (7*24*60*60*1000));
        var expires = "expires="+d.toUTCString();
        document.cookie = "userId" + "=" + newId + "; " + expires;
    }
    function threadClicked(id, title){
        document.title = "OctoPub - "+title;
        clearThreads();
        showThread(id, title);
        window.currentThread = id;
    }
    function clearThreads(){
        var numberOfItems = $('.thread').length;
        $(".item").each(function(i){
            $(this).delay(200*i);
            $(this).animate({"opacity" : "0", marginTop: "+=25px"},500);});
        setTimeout(function(){$('.thread').remove();},200*numberOfItems);
    }
    function clearThread(){
        window.clearInterval(window.messageGetter);
        var numberOfItems = $('.header').length;
        $("#msgInput").animate({"opacity" : "0"},200);
        $(this).delay(200);
        $('#msgInput').remove();
        $(".item").each(function(i){
            $(this).delay(200*i);
            $(this).animate({"opacity" : "0", marginTop: "+=25px"},500);});
        setTimeout(function(){$('.header').remove(); getThreads()},200*numberOfItems);

    }
    function showThread(id, title){
        var idText = "";
        for(var i = 0; i < id.length; i++){
            idText += id.charAt(i);
            if (i==2){idText+="<br />"}
        }
        var thread='<div id = "'+id+'"class="item header shadow card"><div style="display: inline-block; width: 92.5%;"> <h2>'+title+'</h2></div>';
        thread+='<div class="id" style="background-color:#'+id+'"><h3>'+idText+'</h3></div></div>';
        thread+='<input type="text" name="" id="msgInput" class="textInput item shadow card"><div id="messageContainer"></div>';
        $('.threads').prepend(thread);
        $("#"+id).fadeIn( "slow" );
        $("#msgInput").animate({"opacity" : "0.75"},500);
        getThreadHistory(id);
        $("#msgInput").keypress(function(e) {
            if(e.which == 13) {
                sendMessage(id, getMessageFromForm())
            }
            });
        window.messageGetter = setInterval(function(){getNewMessages();},1500);
    }
    function addChatItem(userId, message, timestamp, msgId){
        var idText = "";
        for(var i = 0; i < userId.length; i++){
            idText += userId.charAt(i);
            if (i==2){idText+="<br />"}
        }
        var chatMessage='<div id = "'+userId+'"class="item header shadow card"><div style="display: inline-block; width: 92.5%;"><p class="messageText">'+message+'</p>'+timestamp+'</div>';
        chatMessage+='<div class="id" style="background-color:#'+userId+'"><h3>'+idText+'</h3></div></div>';
        $('#messageContainer').prepend(chatMessage);
        $("#"+userId).fadeIn( "fast" );
        window.latestMessageId = msgId+1
    }
    function getMessageFromForm(){
        var text = $(".textInput")[0].value;
        $(".textInput")[0].value = "";
        return text;
    }
    function sendMessage(thread, message){
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var threads=JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText)
            }
        };
        xmlhttp.open("GET", "api.php?addMessage="+message+"&thread="+thread+"&UserId="+getUserId(), true);
        xmlhttp.send();
        return false;
    }
    function generateId(){
        var ciphers = ["0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F"];
        var id = "";
        for (var i=0; i<6; i++) {
            id += ciphers[Math.floor(Math.random() * ciphers.length)];
        }
        return id;
    }
    function addThread(id, title){
        var idText = "";
        for(var i = 0; i < id.length; i++){
            idText += id.charAt(i);
            if (i==2){idText+="<br />"}
        }
        var thread='<div id = "'+id+'"class="item shadow card thread"><div style="display: inline-block; width: 92.5%;"> <h2>'+title+'</h2></div>'
        thread+='<div class="id" style="background-color:#'+id+'"><h3>'+idText+'</h3></div></div>';
        $('.threads').append(thread);
        $("#"+id).fadeIn( "slow" );
        $( "#"+id ).click(function() {
            threadClicked(id, title);
        });
    }
    function submitNewThread(title){
        console.log("Creating new thread with title: "+title);
        var threadId = generateId();
        setUserId(threadId);
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var messages=JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText);
                }
            };
        xmlhttp.open("GET", "api.php?addThread="+threadId+"&text="+title, true);
        xmlhttp.send();
        setTimeout(function(){threadClicked(threadId,title);},1500);
    }
    function newThread(){
        //alert("Not yet implemented!");
        clearThreads()
        var thread='<div id = "newThreadHeader" class="item header shadow card">';
        thread+='<input type="text" name="Thread name: " id="titleInput" class="textInput item shadow card"><div id="messageContainer"></div></div>';
        $('.threads').prepend(thread);
        $("#newThreadHeader").fadeIn( "slow" );
        $("#titleInput").animate({"opacity" : "0.75"},500);
        $("#titleInput").keypress(function(e) {
            if(e.which == 13) {
                submitNewThread(getMessageFromForm());
            }
        console.log("Opned newThread menu");
        });
    }
    $("#newThread").click(function(){
        newThread();
    });
    function getThreads() {
        document.title = "OctoPub - threads";
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var threads=JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText);
                for (i = 0; i < threads.length; i++) {
                    addThread(threads[i][1],threads[i][0])
                }
            }
        };
        xmlhttp.open("GET", "api.php?getThreads=1", true);
        xmlhttp.send();
    }
    function getThreadHistory(id){
        console.log("Getting thread history");
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var threads=JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText);
                for (i = 0; i < threads.length; i++) {
                    addChatItem(threads[i][1],threads[i][0],threads[i][2],threads[i][3])
                }
            }
        };
        xmlhttp.open("GET", "api.php?getHistoryFrom="+id, true);
        xmlhttp.send();

    }
    function getNewMessages(){
        console.log("Getting messages from id "+window.latestMessageId+" in thread "+window.currentThread);
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var messages=JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText);
                for (i = 0; i < messages.length; i++) {
                    addChatItem(messages[i][1],messages[i][0],messages[i][2],messages[i][3])
                }
            }
        };
        xmlhttp.open("GET", "api.php?fromId="+window.latestMessageId+"&thread="+window.currentThread, true);
        xmlhttp.send();
    }
    getThreads();
    //for (var i=0; i<6; i++) {
    //    addThread(generateId(),chance.sentence())
    //}
</script>
</body>
</html>