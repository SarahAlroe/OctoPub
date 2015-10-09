<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
    <meta charset="utf-8">
    <title>OctoPub - Threads</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="http://octopub.tk/favicon.ico">
    <!-- Include the PlUpload library-->
    <script type="text/javascript" src="js/plupload.full.min.js"></script>
</head>
<body>
<div id="preload"></div>
<div class="background"></div>
<div class="container shadow">
    <img src="logo.png" class="logo" onclick="clearThread()">

    <h1>OctoPub</h1>

    <div id="newThread" class="card shadow button" title="Start a new thread"></div>
    <div id="helpButton" class="card shadow button" title="Octowut?"></div>
    <div id="newId" class="card shadow button" title="Generate a new id"></div>
    <div id="IdBox" class="card shadow" title="Your current id"></div>
    <br>

    <div class="threads">

    </div>
</div>

<!-- Include the Jquery Library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

<!-- Include markdown library -->
<script src="js/marked.js"></script>


<script>
    //Declare global vars

    //This is used to keep the messageGetter from getting previously fetched messages
    var latestMessageId = -1;
    //This is also used by the messageGetter to keep track of what tread is open
    var currentThread = "";
    //The message getter itself. global to make accessible from anywhere
    var messageGetter;
    //Base path for images.
    var imgWebPath = "http://octopub.tk/img/";
    //User id. This is done to make sure that the id is static at least throughout the session.
    var userId = "";

    //Animation stuff.
    //Time to complete most animations.
    var animationTime = 500;
    //Time delayed between item animations.
    var animDelayTime = 50;
    //Distance moved when fading out
    var animationDistance = "100vw";
    //Full fade out animation
    var fadeAnimation = {"opacity": "0", marginLeft: "+="+animationDistance, marginRight: "-="+animationDistance};

    //Define AAAAALLLL THE FUNCTIONS!
    function getCookie(cname) {
        //Get content of cookie cname
        var name = cname + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1);
            if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
        }
        return "";
    }

    function getQueryVariable(variable)
    {
        var query = window.location.search.substring(1);
        var vars = query.split("&");
        for (var i=0;i<vars.length;i++) {
            var pair = vars[i].split("=");
            if(pair[0] == variable){return pair[1];}
        }
        return(false);
    }

    function getUserId() {
        //Gets the currently set userId from cookie. If not already set, sets a new one.
        if (userId == "") {
            userId = getCookie("userId");
            if (userId == "") {
                userId = generateId();
                setUserId(userId);
            }
        }
        return userId;
    }

    function setUserId(newId) {
        //Set the userId to something new
        //Update #IdBox and cookie.
        var idBox = $("#IdBox");
        idBox.css({'background-color': "#" + newId});
        idBox.html(generateIdText(newId));
        //Save a new userId to cookie.
        var d = new Date();
        d.setTime(d.getTime() + (4 * 7 * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = "userId" + "=" + newId + "; " + expires;
    }

    function resetLatestPostDate() {
        //Set cookie latestPostDate to current timestamp
        //latestPostDate used by isNewPostTooSoon()
        var cDate = new Date();
        var expDate = new Date();
        expDate.setTime(expDate.getTime() + (12 * 4 * 7 * 24 * 60 * 60 * 1000));
        var expires = "expires=" + expDate.toUTCString();
        document.cookie = "latestPostDate=" + cDate.getTime() + "; " + expires;
    }

    function isNewPostTooSoon() {
        //Check if it is too soon to allow creation a new thread
        //If the the user has submitted a thread less than 2 minutes ago return true.
        var cDate = new Date();
        var timeLimit = 15 * 60 * 1000;
        var latestPostDate = getCookie("latestPostDate");
        if (latestPostDate == "") {
            return false;
        }
        return latestPostDate > (cDate.getTime() - timeLimit)
    }

    function threadClicked(id) {
        //Called when a thread is clicked. Clears what is currently on the screen and opens the thread.
        clearThreads();
        var numberOfItems = $('.thread').length;
        setTimeout(function () {
            getThread(id);
            window.currentThread = id;
        }, animDelayTime * numberOfItems + animationTime);
    }

    function clearAll() {
        //Just clear everything from .threads... Don't even bother with animations.
        $(".threads").empty();
    }

    function clearThreads() {
        //Removes the contents of .threads, used to remove the list of threads before opening a thread.
        var numberOfItems = $('.thread').length;
        //Animate each thread item, one after the other.
        $(".item").each(function (i) {
            $(this).delay(animDelayTime * i);
            $(this).animate(fadeAnimation, animationTime);
        });
        // Remove all thread items when all animations have been completed.
        setTimeout(function () {
            $('.thread').remove();
        }, animDelayTime * numberOfItems + animationTime);
    }

    function clearThread() {
        //Removes thread content from .threads. Used when closing a thread to remove whatever ended up there...
        //Make logo look unclickable again
        $(".logo").css("cursor", "auto");
        if (currentThread != "") {
            window.clearInterval(window.messageGetter);
            var numberOfItems = $('.header').length;
            var msgInputObject = $("#msgInput");
            msgInputObject.animate(fadeAnimation, animationTime-100);
            $("#browse").animate(fadeAnimation, animationTime-100);
            $("#sendMsg").animate(fadeAnimation, animationTime-100);
            $("#uploadBar").animate(fadeAnimation, animationTime-100);
            //Animate items in sequence.
            $(".item").each(function (i) {
                $(this).delay(animDelayTime * i);
                $(this).animate(fadeAnimation, animationTime);
            });
            //If creating new thread, remove relevant items.
            if (currentThread == "newThread") {
                setTimeout(function () {
                    $(".newThread").remove();
                }, animDelayTime * numberOfItems);
            }
            //Remove items after animation
            setTimeout(function () {
                clearAll();
                getThreads();
            }, animDelayTime * numberOfItems);
            //Reset active thread
            currentThread = "";
            window.latestMessageId = 0;
        }
    }

    function addImage(webPath) {
        //Add markdown formatted image from url to textInput.
        var formObject = $(".textInput")[0];
        if (formObject.value.replace(/(\r\n|\n|\r|" ")/gm, "") == "") {
        formObject.value = ' ![](' + webPath + ')';}
        else{
            formObject.value += '  \n![](' + webPath + ')';}
        //sendMessage(window.currentThread, '![An image: ' + webPath + '](' + webPath + ')');
    }

    function initializePlupload() {
        //Take care of PlUpload things.
        var uploader = new plupload.Uploader({
            browse_button: 'browse', // this can be an id of a DOM element or the DOM element itself.
            drop_element: 'msgInput',
            url: 'upload.php',
            filters: {
                max_file_size: '10mb',
                mime_types: [
                    {title: "Image files", extensions: "jpg,gif,png,jpeg"}
                ]
            },
            multi_selection: false,
            unique_names: true
        });
        uploader.init();
        uploader.bind('UploadProgress', function (up, file) {
            //Animate upload progress bar when progress happens.
            $("#uploadBar").animate({width: "" + file.percent + "%"}, "fast", "swing");
        });
        uploader.bind('FileUploaded', function (up, file, info) {
            //Get url of uploaded image.
            var obj = JSON.parse(info.response);
            var webPath = imgWebPath + obj.result.cleanFileName;
            console.log(webPath);
            //Add the uploaded image.
            addImage(webPath);
            //Reset upload progress bar.
            setTimeout(function () {
                $("#uploadBar").animate({width: "0%"});
            }, 3000);
        });
        uploader.bind('FilesAdded', function (up, files) {
            //Start uploader as soon as a file is added
            uploader.start()
        });
        //Make msgInput change when a file is dragged over it.
        var $dz = $('#msgInput');
        $dz.on({
            dragenter: dragenter,
            dragleave: dragleave,
            dragover: false,
            drop: drop
        });

        function dragenter() {
            $dz.addClass('active');
        }

        function dragleave() {
            $dz.removeClass('active');
        }

        function drop(e) {
            $dz.removeClass('active');
        }
    }

    function showThread(id, title, mkText) {
        //DONT USE THIS ALONE! USE threadClicked() instead!
        //Adds the header of the thread and the message input bar.
        //Also gets message history and initiates the messageGetter
        var text = marked(String(mkText));
        document.title = "OctoPub - " + title;
        window.history.pushState({"id":id,"title":title}, "OctoPub - " + title, "/?t="+id);
        $(".logo").css("cursor", "pointer");
        var idText = generateIdText(id);
        var thread = '<div id = "' + id + '"class="item header shadow card">' +
            '<div style="display: inline-block; width: 92.5%;"> <h2>' + title + '</h2>' +
            '<br><p class="messageText">' + text + '</p></div>';
        thread += '<div class="id" style="background-color:#' + id + '"><h3>' + idText + '</h3></div></div>' +
            '<textarea autofocus rows="3" name="" maxlength="1000" id="msgInput" class="textInput item shadow card"></textarea>' +
            '<div id="sendMsg" class="card shadow button"></div>' +
            '<div id="browse" class="card shadow button"></div>' +
            '<div id="uploadBar" class=progressBar></div>' +
            '<p><div id="messageContainer"></div></p>';
        $('.threads').prepend(thread);
        $("#" + id).fadeIn("slow");
        var msgInputObject = $("#msgInput");
        msgInputObject.animate({"opacity": "0.75"}, animationTime);
        $("#browse").animate({"opacity": "1"}, animationTime);
        $("#sendMsg").animate({"opacity": "1"}, animationTime);
        getThreadHistory(id);
        initializePlupload();
        msgInputObject.keypress(function (e) {
            if (e.which == 13) {
                if (e.shiftKey) {
                    //$("#msgInput").value+="\n";
                } else {
                    sendMessage(id, getMessageFromForm())
                }
            }
        });
        $("#sendMsg").click(function () {
            sendMessage(id, getMessageFromForm());
        });
        window.messageGetter = setInterval(function () {
            getNewMessages();
        }, 1500);
    }

    function showHelp(){
        clearThread();
        clearThreads();
        clearAll();
        document.title = "OctoPub - OctoWut";
        window.history.pushState({"id":"help","title":"Octowut"}, "OctoPub - Octowut", "/");
        $(".logo").css("cursor", "pointer");
        currentThread = "help";
        var thread = '<div id = "help" class="item header shadow card">' +
            '<div style="display: inline-block; width: 92.5%;"> <h2>Octowut</h2>' +
            '<br><p class="messageText">' + marked(String("<?php include 'octowut.md' ?>")) + '</p></div>';
        $('.threads').prepend(thread);
        $("#help").fadeIn("slow");
    }

    function addChatItem(userId, markDownMessage, timestamp, msgId) {
        //Add a chat item to the ui thread.
        //This is currently only messages, but could possibly be used for other things like images in the future.
        var message = marked(String(markDownMessage));
        var idText = generateIdText(userId);
        var date = new Date(timestamp * 1000);
        var chatMessage = '<div id = "' + timestamp + '"class="item header shadow card"><div style="display: inline-block; width: 92.5%;">' + message + '</p>' + date.toLocaleString() + '</div>';
        chatMessage += '<div class="id" style="background-color:#' + userId + '">' + idText + '</div></div>';
        $('#messageContainer').prepend(chatMessage);
        $("#" + timestamp).fadeIn("fast");
        window.latestMessageId = msgId + 1
    }

    function generateIdText(id) {
        idText = "<h3 class='";
        //Convert id to base 10 and add up.
        r = id.substr(0, 1);
        g = id.substr(2, 3);
        b = id.substr(4, 5);
        idBrightness = parseInt(r, 16) + parseInt(g, 16) + parseInt(b, 16);
        //If light background, make text black, else make text white
        if (idBrightness > 1000) {
            idText += "idDark";
        } else {
            idText += "idBright";
        }
        //Add id in two lines of 3
        idText += "'>";
        idText += id.substr(0, 3);
        idText += "<br />";
        idText += id.substr(3, 5);
        idText += "</h3>";
        return idText;
    }

    function getMessageFromForm() {
        //Get the contents of the textInput currently on the screen
        //This includes the message input and new thread input
        var formObject = $(".textInput")[0];
        var text = formObject.value;
        formObject.value = "";
        return text;
    }

    function sendMessage(thread, message) {
        //Submit a message to a thread.
        if (message.replace(/(\r\n|\n|\r|" ")/gm, "") == "") {
            //If message being sent is empty, return without sending.
            return
        } else {
            //Clean text to make sure nothing messes up in sending and storage.
            cleanMessage = encodeURIComponent(message);
            cleanThread = encodeURIComponent(thread);
            var xmlhttp = new XMLHttpRequest();
            var url = "api.php";
            var params = "addMessage=" + cleanMessage + "&thread=" + cleanThread + "&UserId=" + getUserId();
            xmlhttp.open("POST", url, true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.onreadystatechange = function () {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    console.log(xmlhttp.responseText)
                }
            };
            xmlhttp.send(params);
            return false;
        }
    }

    function generateId() {
        //Generate a random id and return it.
        var ciphers = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F"];
        var id = "";
        for (var i = 0; i < 6; i++) {
            id += ciphers[Math.floor(Math.random() * ciphers.length)];
        }
        return id;
    }

    function idToHTML(id) {
        //Return an id as formatted html
        var idText = "";
        for (var i = 0; i < id.length; i++) {
            idText += id.charAt(i);
            if (i == 2) {
                idText += "<br />"
            }
        }
        return idText;
    }

    function addThread(id, title) {
        //Add a clickable thread item to the page. This is used when listing threads.
        var idText = generateIdText(id);
        var thread = '<div id = "' + id + '"class="item shadow card thread"><div style="display: inline-block; width: 92.5%;"> <h2>' + title + '</h2></div>';
        thread += '<div class="id" style="background-color:#' + id + '">' + idText + '</div></div>';
        $('.threads').append(thread);
        var threadObject = $("#" + id);
        threadObject.fadeIn("slow");
        threadObject.click(function () {
            threadClicked(id);
        });
    }

    function submitNewThread(title, text) {
        //Submit a new thread to the database.
        //Only requires a title, the id is generated here and then set as new user id.
        //Clean thread title and text
        cleanTitle = encodeURIComponent(title);
        cleanText = encodeURIComponent(text);
        console.log("Creating new thread with title: " + cleanTitle);
        var threadId = generateId();
        setUserId(threadId);
        var xmlhttp = new XMLHttpRequest();
        var url = "api.php";
        var params = "addThread=" + threadId + "&title=" + cleanTitle + "&text=" + cleanText;
        xmlhttp.open("POST", url, true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                console.log(xmlhttp.responseText);
                clearAll();
                threadClicked(threadId, cleanTitle);
            }
        };
        xmlhttp.send(params);
        resetLatestPostDate();
    }

    function newThread() {
        //Opens the newThread segment
        if (isNewPostTooSoon()) {
            alert("Please wait a moment between posting new threads. \nWhy not keep the conversation running in an already existing thread?");
            return;
        }
        clearAll();
        //Change curser when hovering over logo to indicate clickable
        $(".logo").css("cursor", "pointer");
        currentThread = "newThread";
        var thread = '<div id = "newThreadHeader" class="item header shadow card newThread">';
        thread += '<h2>Thread Title:</h2></div><input type="text" name="Thread title: " maxlength="200" id="titleInput" class="textInput item shadow card newThread"><br class="newThread" ">' +
            '<div id="newThreadText" class="item header shadow card newThread" style="clear: both;"><h2>Thread text:</h2></div><textarea name="Thread text: " maxlength="1000" id="textInput" class="textInput item shadow card newThread"></textarea><br class="newThread"><br class="newThread">' +
            '<div id="submitButton" class="card shadow newThread" style="background-color: #e0f2f1; cursor: pointer;" title="Submit thread"><h2>SUBMIT!</h2></div>' + '<div id="messageContainer"></div></div>';
        $('.threads').prepend(thread);
        $("#newThreadHeader").fadeIn("slow");
        $("#newThreadText").fadeIn("slow");
        $("#titleInput").animate({"opacity": "0.75"}, animationTime);
        $("#textInput").animate({"opacity": "0.75"}, animationTime);
        $("#submitButton").click(function () {
            submitNewThread($("#titleInput").val(), $("#textInput").val())
        });
    }

    function getThreads() {
        //Requests all currently active threads and shows them on screen through addThread()
        document.title = "OctoPub - threads";
        window.history.pushState({"id":"","title":"threads"}, "OctoPub - threads", "/");
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var threads = JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText);
                for (var i = 0; i < threads.length; i++) {
                    addThread(threads[i][1], threads[i][0])
                }
            }
        };
        xmlhttp.open("GET", "api.php?getThreads=1", true);
        xmlhttp.send();
    }

    function getThreadHistory(id) {
        //Gets the (up to) 10 latest messages from a thread and shows them on the screen through addChatitem()
        console.log("Getting thread history");
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var threads = JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText);
                for (var i = 0; i < threads.length; i++) {
                    addChatItem(threads[i][1], threads[i][0], threads[i][2], threads[i][3])
                }
            }
        };
        xmlhttp.open("GET", "api.php?getHistoryFrom=" + id, true);
        xmlhttp.send();

    }

    function getNewMessages() {
        //Gets all messages from the current thread with an id above latestMessageId and shwos them on screen through addChatitem()
        console.log("Getting messages from id " + window.latestMessageId + " in thread " + window.currentThread);
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var messages = JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText);
                for (var i = 0; i < messages.length; i++) {
                    addChatItem(messages[i][1], messages[i][0], messages[i][2], messages[i][3])
                }
            }
        };
        xmlhttp.open("GET", "api.php?fromId=" + window.latestMessageId + "&thread=" + window.currentThread, true);
        xmlhttp.send();
    }

    function getThread(id) {
        //Get title and text of thread, then show it using showThread
        console.log("Getting messages from id " + window.latestMessageId + " in thread " + window.currentThread);
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var data = JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText);
                showThread(data[0], data[1], data[2]);
            }
        };
        xmlhttp.open("GET", "api.php?getThread=" + id, true);
        xmlhttp.send();
    }
    function updateBackground(i, max) {
        //Not in use anymore due to major performance issues.
        i++;
        if (i > max) {
            i = 0
        }
        var nextI = i + 1;
        if (nextI > max) {
            nextI = 0
        }
        var filename = "bgImages/bg" + i + ".png";
        var nextFilename = "bgImages/bg" + nextI + ".png";
        $(".background").css("background-image", "url(" + filename + ")");
        $("#preload").css("background-image", "url(" + nextFilename + ")");
        setTimeout(function () {
            updateBackground(i, max);
        }, 7700);
    }

    window.onpopstate = function(e){
        if(e.state){
            if (e.state.id != ""){
                threadClicked(e.state.id);
            }else{
                clearThread();
            }
        }
    };

    //Make the item with id #newThread, a button, run the newThread function
    $("#newThread").click(function () {
        newThread();
    });

    $("#helpButton").click(function () {
        showHelp();
    });

    //Generate and set new id when clicked
    $("#newId").click(function () {
        var newUserId = generateId();
        setUserId(newUserId);
        console.log(newUserId);
    });

    //start the dynamic background
    //setTimeout(function () {updateBackground(0,19);}, 7700);
    //Disabled temporarily as it tanks just about any computer.

    //Change the color and text of the IdBox and then show it.
    var idBox = $("#IdBox");
    idBox.css({'background-color': "#" + getUserId()});
    idBox.html(generateIdText(getUserId()));
    idBox.css({"opacity": "1"});
    //When the page has loaded, get available threads.
    if (getQueryVariable("t")==""){
        getThreads();
    }
    else{
        threadClicked(getQueryVariable("t"))
    }
</script>
</body>
</html>