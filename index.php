<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
    <meta charset="utf-8">
    <title>OctoPub - Threads</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="https://octopub.tk/favicon.ico">
    <!-- Include the PlUpload library-->
    <script type="text/javascript" src="js/plupload.full.min.js"></script>
</head>
<body>
<!--<div id="preload"></div> -->
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
    //Thread title for use in tab header
    var currentThreadTitle = "";
    //The message getter itself. global to make accessible from anywhere
    var messageGetter;
    //Base path for images.
    var imgWebPath = "https://octopub.tk/img/";
    //User id. This is done to make sure that the id is static at least throughout the session.
    var userId = "";
    //Secure id. Used to verify user id.
    var secId = "";
    //Variable used to keep multilpe thing from happening at once
    var isSecure = true;

    //Notify sound audio element
    var notifySound = new Audio('blip.mp3');

    //Tine for a notification to stay alive in ms.
    var notificationTTL = 10000;

    //Time between new message requests.
    var baseActiveMessageInterval = 1500;   //1,5 seconds base when active
    var basePassiveMessageInterval = 6000;  //6 seconds base when inactive (minimized)
    var maxActiveMessageInterval = 4000;    //4 seconds max when active
    var maxPassiveMessageInterval = 10000;  //10 seconds max when inactive
    var inactiveModifierIterationAddition = 5; //Time added each iteration.

    var messageIntervalModifier = baseActiveMessageInterval;

    //Animation stuff.
    //Time to complete most animations.
    var animationTime = 500;
    //Time delayed between item animations.
    var animDelayTime = 50;
    //Distance moved when fading out
    var animationDistance = "100vw";
    //Full fade out animation
    var fadeAnimation = {"opacity": "0", marginLeft: "+=" + animationDistance, marginRight: "-=" + animationDistance};
    // Percentage to divide color values by. Used for background. Lower is darker.
    var colorBrightnessPercentage = 30;

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

    function setCookie(cname, value) {
        var d = new Date();
        d.setTime(d.getTime() + (4 * 7 * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + value + "; " + expires;
    }

    function getQueryVariable(variable) {
        var query = window.location.search.substring(1);
        var vars = query.split("&");
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split("=");
            if (pair[0] == variable) {
                return pair[1];
            }
        }
        return (false);
    }

    function getUserId() {
        //Gets the currently set userId from cookie. If not already set, sets a new one.
        if (userId == "") {
            userId = getCookie("userId");
            if (userId == "") {
                var oldUserId = userId;
                getNewId();
                var checkIfNew = function () {
                    if (oldUserId == userId) {
                        setTimeout(checkIfNew, 1000); // check again in a second
                    }
                };
                checkIfNew();
            }
        }
        return userId;
    }

    function getSecId() {
        if (secId == "") {
            secId = getCookie("secId");
            if (secId == "") {
                var oldSecId = secId;
                getNewId();
                var checkIfNew = function () {
                    if (oldSecId == secId) {
                        setTimeout(checkIfNew, 1000); // check again in a second
                    }
                };
                checkIfNew();
            }
        }
        return secId;
    }

    function setUserId(newId) {
        //Set the userId to something new
        userId = newId;
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

    function setSecId(newId) {
        //Set the secure Id to something new
        secId = newId;
        //Save a new userId to cookie.
        var d = new Date();
        d.setTime(d.getTime() + (4 * 7 * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = "secId" + "=" + newId + "; " + expires;
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

    function cleanString(inStr) {
        return inStr.replace("/\W/", "");
    }

    function setBackgroundColor(oldHex) {
        //Set the background color.
        var r = parseInt(oldHex.substr(0, 2), 16);
        var g = parseInt(oldHex.substr(2, 2), 16);
        var b = parseInt(oldHex.substr(4, 2), 16);

        r = Math.floor(r * colorBrightnessPercentage / 100);
        g = Math.floor(g * colorBrightnessPercentage / 100);
        b = Math.floor(b * colorBrightnessPercentage / 100);

        r = (r < 255) ? r : 255;
        g = (g < 255) ? g : 255;
        b = (b < 255) ? b : 255;

        hR = ((r.toString(16).length == 1) ? "0" + r.toString(16) : r.toString(16));
        hG = ((g.toString(16).length == 1) ? "0" + g.toString(16) : g.toString(16));
        hB = ((b.toString(16).length == 1) ? "0" + b.toString(16) : b.toString(16));

        newHex = hR + hG + hB;
        console.log("Background color: #" + newHex);
        $(".background").css("background-color", "#" + newHex);
    }

    function threadClicked(id) {
        //Called when a thread is clicked. Clears what is currently on the screen and opens the thread.
        if (isSecure) {
            isSecure = false;
            setBackgroundColor(id);
            clearThreads();
            var numberOfItems = $('.thread').length;
            setTimeout(function () {
                getThread(id);
                window.currentThread = id;
                isSecure = true;
            }, animDelayTime * numberOfItems + animationTime);
        }
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
        if (isSecure) {
            isSecure = false;
            $(".logo").css("cursor", "auto");
            if (currentThread != "") {
                window.clearInterval(window.messageGetter);
                var numberOfItems = $('.header').length;
                var msgInputObject = $("#msgInput");
                msgInputObject.animate(fadeAnimation, animationTime - 100);
                $("#browse").animate(fadeAnimation, animationTime - 100);
                $("#sendMsg").animate(fadeAnimation, animationTime - 100);
                $("#uploadBar").animate(fadeAnimation, animationTime - 100);
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
                    isSecure = true;
                }, animDelayTime * numberOfItems);
                //Reset active thread
                currentThread = "";
                window.latestMessageId = -1;
            }
        }
    }

    function addImage(webPath) {
        //Add markdown formatted image from url to textInput.
        var formObject = $(".textInput")[0];
        if (formObject.value.replace(/(\r\n|\n|\r|" ")/gm, "") == "") {
            insertAtCursor(formObject, ' ![](' + webPath + ')  \n')
        }
        else {
            insertAtCursor(formObject, '  \n![](' + webPath + ')  \n')
        }
        //sendMessage(window.currentThread, '![An image: ' + webPath + '](' + webPath + ')');
    }

    function insertAtCursor(field, value) {
        //IE support
        if (document.selection) {
            field.focus();
            sel = document.selection.createRange();
            sel.text = value;
        }
        //MOZILLA and others
        else if (field.selectionStart || field.selectionStart == '0') {
            var startPos = field.selectionStart;
            var endPos = field.selectionEnd;
            field.value = field.value.substring(0, startPos)
                + value
                + field.value.substring(endPos, field.value.length);
        } else {
            field.value += value;
        }
    }

    function initializePlupload() {
        //Take care of PlUpload things.￼
        var uploader = new plupload.Uploader({
            browse_button: 'browse', // this can be an id of a DOM element or the DOM element itself.
            drop_element: 'msgInput',
            url: 'upload.php',
            filters: {
                max_file_size: '10mb',
                mime_types: [
                    {title: "Image files", extensions: "jpg,gif,png,jpeg,webp,bmp"},
                    {title: "Video files", extensions: "webm,mp4"},
                    {title: "Audio files", extensions: "mp3,wav,ogg"}
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
        document.title = cleanString(title) + " - OctoPub";
        currentThreadTitle = title;
        window.history.pushState({"id": id, "title": title}, "OctoPub - " + title, "/?t=" + id);
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

        document.getElementById("msgInput").addEventListener("paste", function (e) {
            // cancel paste
            e.preventDefault();

            var extensions = ["jpg", "gif", "png", "jpeg", "webp", "bmp", "webm", "mp4", "ogg", "mp3", "wav"];
            // get text representation of clipboard
            var text = e.clipboardData.getData("text/plain");
            var baseURL = text.split("?")[0];
            var potentialFiletype = baseURL.split(".").pop().toLowerCase();

            if (extensions.indexOf(potentialFiletype) != -1 || text.indexOf("youtu.be") != -1 || text.indexOf("youtube.com") != -1) {
                text = "  ![](" + text + ")  ";
            }

            // insert text manually
            insertAtCursor(document.getElementById("msgInput"), text);
        });

        getMessageLooper(); //Start the self refreshing message getter
    }

    function showHelp() {
        if (isSecure) {
            isSecure = false;
            clearThread();
            clearThreads();
            clearAll();
            document.title = "OctoWut - OctoPub";
            window.history.pushState({"id": "help", "title": "Octowut"}, "OctoPub - Octowut", "/");
            $(".logo").css("cursor", "pointer");
            currentThread = "help";
            var thread = '<div id = "help" class="item header shadow card">' +
                '<div style="display: inline-block; width: 92.5%;"> <h2>Octowut</h2>' +
                '<br><p class="messageText">' + marked(String("<?php include 'octowut.md' ?>")) + '</p></div>';
            $('.threads').prepend(thread);
            $("#help").fadeIn("slow");
            isSecure = true;
        }

    }

    function soundNotify() {
        if (document.hidden) {
            var date_now = new Date();
            var time_now = date_now.getTime ();
            var time_diff = time_now - startTime;
            var seconds_elapsed = Math.floor (time_diff / 1000);
            if (seconds_elapsed > 5) {
                notifySound.play();
            }
        }
    }
    //Notification thing.
    // request permission on page load
    document.addEventListener('DOMContentLoaded', function () {
        if (Notification.permission !== "granted") {
            Notification.requestPermission();
        }

    });

    function notify(title, text) {
        if (Notification.permission !== "granted")
            Notification.requestPermission();
        else {
            var notification = new Notification(cleanString(title), {
                icon: 'https://octopub.tk/logo.png',
                body: cleanString(text)
            });

            notification.onclick = function () {
                window.focus();
            };
            setTimeout(function () {
                notification.close();
            }, notificationTTL);
        }

    }


    function addChatItem(userId, markDownMessage, timestamp, msgId) {
        //Add a chat item to the ui thread.
        //This is currently only messages, but could possibly be used for other things like images in the future.
        if (msgId > window.latestMessageId) {
            var message = marked(String(markDownMessage));
            var idText = generateIdText(userId);
            var date = new Date(timestamp * 1000);
            var chatMessage = '<div id = "' + timestamp + '"class="item header shadow card"><div style="display: inline-block; width: 92.5%;">' + message + '</p>' + date.toLocaleString() + '</div>';
            chatMessage += '<div class="id" style="background-color:#' + userId + '">' + idText + '</div></div>';
            $('#messageContainer').prepend(chatMessage);
            $("#" + timestamp).fadeIn("fast");
            window.latestMessageId = msgId;
            setCookie("lastRegFrom_" + window.currentThread, msgId);
            soundNotify();
            //If the thread is hidden, add * to the tab header
            if (document.hidden) {
                document.title = "* " + currentThreadTitle + " - OctoPub *";
                notify("New message in: " + currentThreadTitle, markDownMessage);
            }
        }
    }

    function generateIdText(id) {
        var idText = "<h3 class='";
        //Convert id to base 10 and add up.
        var r = id.substr(0, 1);
        var g = id.substr(2, 3);
        var b = id.substr(4, 5);
        var idBrightness = parseInt(r, 16) + parseInt(g, 16) + parseInt(b, 16);
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
            var cleanMessage = encodeURIComponent(message);
            var cleanThread = encodeURIComponent(thread);
            var xmlhttp = new XMLHttpRequest();
            var url = "api.php";
            var params = "addMessage=" + cleanMessage + "&thread=" + cleanThread + "&userId=" + getUserId() + "&secId=" + getSecId();
            xmlhttp.open("POST", url, true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.onreadystatechange = function () {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    console.log(xmlhttp.responseText);
                    if (xmlhttp.responseText == "ERR: OLDID") {
                        getNewId();
                        setTimeout(function () {
                            sendMessage(thread, message);
                        }, 1000);
                    }
                    getNewMessages();
                }
            };
            xmlhttp.send(params);
            return false;
        }
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

    function addThread(id, title, length) {
        //Add a clickable thread item to the page. This is used when listing threads.
        var idText = generateIdText(id);
        var readLength = getCookie("lastRegFrom_" + id);
        var thread = '<div id = "' + id + '"class="item sh;adow card thread"><div style="display: inline-block; width: 92.5%;"> <h2>' + title + '</h2>' +
            '<div style="float:left;"> Replies: ' + parseInt(1 + parseInt(length));
        if (length > readLength) {
            if (readLength != "") {
                var readDiff = length - readLength;
                thread += ' - </div><div style="float:left;color: darkred;">  ' + readDiff + ' New!</div>';
            }
            else {
                thread += ' - </div><div style="float:left;color: darkred;">  New thread!</div>';
            }
        }
        else {
            thread += '</div>'
        }
        thread += '</div><div class="id" style="background-color:#' + id + '">' + idText + '</div></div>';
        $('.threads').append(thread);
        var threadObject = $("#" + id);
        threadObject.fadeIn("slow");
        threadObject.click(function () {
            threadClicked(id);
        });
    }

    function getMessageInterval() {
        var rInterval = messageIntervalModifier;
        if (document.hidden) {//If document is hidden
            rInterval += basePassiveMessageInterval;
            if (rInterval > maxPassiveMessageInterval) {
                rInterval = maxPassiveMessageInterval;
                messageIntervalModifier -= inactiveModifierIterationAddition;
            }
        } else {
            rInterval += baseActiveMessageInterval;
            if (rInterval > maxActiveMessageInterval) {
                rInterval = maxActiveMessageInterval;
                messageIntervalModifier -= inactiveModifierIterationAddition;
            }
        }
        messageIntervalModifier += inactiveModifierIterationAddition;
        return rInterval;
    }
    function getMessageLooper() {
        if (currentThread != "") {
            getNewMessages();
            setTimeout(getMessageLooper, getMessageInterval());
            if ((currentThread != "") && (!document.hidden)) {
                document.title = currentThreadTitle + " - OctoPub"
            }
        }
    }

    function submitNewThread(title, text) {
        //Submit a new thread to the database.
        //Only requires a title, the id is generated here and then set as new user id.
        //Clean thread title and text
        var cleanTitle = encodeURIComponent(title);
        var cleanText = encodeURIComponent(text);
        console.log("Creating new thread with title: " + cleanTitle);
        var xmlhttp = new XMLHttpRequest();
        var url = "api.php";
        var params = "addThread=" + cleanTitle + "&text=" + cleanText;
        xmlhttp.open("POST", url, true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                console.log(xmlhttp.responseText);
                var message = JSON.parse(xmlhttp.responseText);
                //Set new id's
                setUserId(message[0]);
                setSecId(message[1]);
                //Load new thread
                clearAll();
                threadClicked(message[0], cleanTitle);
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
        //Set the background color to the users own id.
        setBackgroundColor(getUserId());
        document.title = "OctoPub";
        window.history.pushState({"id": "", "title": "threads"}, "OctoPub - threads", "/");
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var threads = JSON.parse(xmlhttp.responseText);
                console.log("Current threads: " + xmlhttp.responseText);
                for (var i = 0; i < threads.length; i++) {
                    addThread(threads[i][1], threads[i][0], threads[i][2])
                }
            }
        };
        xmlhttp.open("GET", "api.php?getThreads", true);
        xmlhttp.send();
    }

    function getThreadHistory(id) {
        //Gets the (up to) 10 latest messages from a thread and shows them on the screen through addChatitem()
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var threads = JSON.parse(xmlhttp.responseText);
                console.log("Thread history: " + xmlhttp.responseText);
                for (var i = 0; i < threads.length; i++) {
                    addChatItem(threads[i][1], threads[i][0], threads[i][2], threads[i][3])
                }
            }
        };
        xmlhttp.open("GET", "api.php?getHistoryFrom=" + id, true);
        xmlhttp.send();

    }

    function getNewMessages() {
        //Gets all messages from the current thread with an id above latestMessageId and shows them on screen through addChatitem()
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var messages = JSON.parse(xmlhttp.responseText);
                if (messages.length != 0) {
                    console.log("New messages in thead: " + xmlhttp.responseText);
                    messageIntervalModifier = 0;
                }
                for (var i = 0; i < messages.length; i++) {
                    try {
                        addChatItem(messages[i][1], messages[i][0], messages[i][2], messages[i][3]);
                    }
                    catch (err) {
                        console.log("ERR: Failed to add potential new message.");
                    }
                }
            }
        };
        xmlhttp.open("GET", "api.php?fromId=" + window.latestMessageId + "&thread=" + window.currentThread, true);
        xmlhttp.send();
    }

    function getNewId() {
        //Gets and sets a new id
        console.log("Getting new id");
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var message = JSON.parse(xmlhttp.responseText);
                if (message.indexOf("ERR:") > -1){
                    return;
                }
                console.log("New user id: " + xmlhttp.responseText);
                setUserId(message[0]);
                setSecId(message[1]);
                setBackgroundColor(message[0]);
            }
        };
        xmlhttp.open("GET", "api.php?newID", true);
        xmlhttp.send();
    }

    function getThread(id) {
        //Get title and text of thread, then show it using showThread
        console.log("Getting messages from id " + window.latestMessageId + " in thread " + window.currentThread);
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var data = JSON.parse(xmlhttp.responseText);
                console.log("Thread info: " + xmlhttp.responseText);
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
    window.onpopstate = function (e) {
        if (e.state) {
            if (e.state.id != "") {
                threadClicked(e.state.id);
            } else {
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
        getNewId();
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
    if (getQueryVariable("t") == "") {
        getThreads();
    }
    else {
        threadClicked(getQueryVariable("t"))
    }
    var startDate = new Date();
    var startTime = startDate.getTime();
</script>
</body>
</html>