<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>OctoPub - Threads</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="http://octopub.tk/favicon.ico">
</head>
<body>
<div class="container shadow">
    <img src="logo_3.png" class="logo " onclick="clearThread()">

    <h1>OctoPub</h1>

    <div id="newThread" class="card shadow button" title="Start a new thread"></div>
    <div id="newId" class="card shadow button" title="Generate a new id"></div>
    <div id="IdBox" class="card shadow" title="Your current id"></div>
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

    //This is used to keep the messageGetter from getting previously fetched messages
    var latestMessageId = 0;
    //This is also used by the messageGetter to keep track of what tread is open
    var currentThread = "";
    //The message getter itself. global to make accessible from anywhere
    var messageGetter;


    function getUserId() {
        //Gets the currently set userId from cookie. If not already set, sets a new one.
        var name = "userId" + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1);
            if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
        }
        newId = generateId();
        setUserId(newId);
        return newId;
    }

    function setUserId(newId) {
        $("#IdBox").css({'background-color': "#" + newId});
        $("#IdBox").html(idToHTML(newId));
        //Save a new userId to cookie.
        var d = new Date();
        d.setTime(d.getTime() + (7 * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = "userId" + "=" + newId + "; " + expires;
    }

    function threadClicked(id, title) {
        //Called when a thread is clicked. Clears what is currently on the screen and opens the thread.
        document.title = "OctoPub - " + title;
        clearThreads();
        showThread(id, title);
        window.currentThread = id;
    }

    function clearThreads() {
        //Removes the contents of .threads, used to remove the list of threads before opening a thread.
        var numberOfItems = $('.thread').length;
        $(".item").each(function (i) {
            $(this).delay(50 * i);
            $(this).animate({"opacity": "0", marginTop: "+=25px"}, 500);
        });
        setTimeout(function () {
            $('.thread').remove();
        }, 50 * numberOfItems);
    }

    function clearThread() {
        //Removes thread content from .threads. Used when closing a thread to remove whatever ended up there...
        if (currentThread != "") {
            window.clearInterval(window.messageGetter);
            var numberOfItems = $('.header').length;
            $("#msgInput").animate({"opacity": "0"}, 200);
            $(this).delay(200);
            $('#msgInput').remove();
            $(".item").each(function (i) {
                $(this).delay(50 * i);
                $(this).animate({"opacity": "0", marginTop: "+=25px"}, 500);
            });
            setTimeout(function () {
                $('.header').remove();
                $("#messageContainer").remove();
                getThreads();
            }, 50 * numberOfItems);
            currentThread = "";
        }
    }

    function showThread(id, title) {
        //DONT USE THIS ALONE! USE threadClicked() instead!
        //Adds the header of the thread and the message input bar.
        //Also gets message history and initiates the messageGetter
        var idText = "";
        for (var i = 0; i < id.length; i++) {
            idText += id.charAt(i);
            if (i == 2) {
                idText += "<br />"
            }
        }
        var thread = '<div id = "' + id + '"class="item header shadow card"><div style="display: inline-block; width: 92.5%;"> <h2>' + title + '</h2></div>';
        thread += '<div class="id" style="background-color:#' + id + '"><h3>' + idText + '</h3></div></div>';
        thread += '<input type="text" name="" maxlength="1000" id="msgInput" class="textInput item shadow card"><div id="messageContainer"></div>';
        $('.threads').prepend(thread);
        $("#" + id).fadeIn("slow");
        $("#msgInput").animate({"opacity": "0.75"}, 500);
        getThreadHistory(id);
        $("#msgInput").keypress(function (e) {
            if (e.which == 13) {
                sendMessage(id, getMessageFromForm())
            }
        });
        window.messageGetter = setInterval(function () {
            getNewMessages();
        }, 1500);
    }

    function addChatItem(userId, message, timestamp, msgId) {
        //Add a chat item to the ui thread.
        //This is currently only messages, but could possibly be used for other things like images in the future.
        var idText = "";
        for (var i = 0; i < userId.length; i++) {
            idText += userId.charAt(i);
            if (i == 2) {
                idText += "<br />"
            }
        }
        var date = new Date(timestamp * 1000)
        var chatMessage = '<div id = "' + timestamp + '"class="item header shadow card"><div style="display: inline-block; width: 92.5%;"><p class="messageText">' + message + '</p>' + date.toLocaleString() + '</div>';
        chatMessage += '<div class="id" style="background-color:#' + userId + '"><h3>' + idText + '</h3></div></div>';
        $('#messageContainer').prepend(chatMessage);
        $("#" + timestamp).fadeIn("fast");
        window.latestMessageId = msgId + 1
    }

    function getMessageFromForm() {
        //Get the contents of the textInput currently on the screen
        //This includes the message input and new thread input
        var text = $(".textInput")[0].value;
        $(".textInput")[0].value = "";
        return text;
    }

    function sendMessage(thread, message) {
        //Submit a message to a thread.
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var threads = JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText)
            }
        };
        xmlhttp.open("GET", "api.php?addMessage=" + message + "&thread=" + thread + "&UserId=" + getUserId(), true);
        xmlhttp.send();
        return false;
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
        var idText = idToHTML(id);
        var thread = '<div id = "' + id + '"class="item shadow card thread"><div style="display: inline-block; width: 92.5%;"> <h2>' + title + '</h2></div>'
        thread += '<div class="id" style="background-color:#' + id + '"><h3>' + idText + '</h3></div></div>';
        $('.threads').append(thread);
        $("#" + id).fadeIn("slow");
        $("#" + id).click(function () {
            threadClicked(id, title);
        });
    }

    function submitNewThread(title) {
        //Submit a new thread to the database.
        //Only requires a title, the id is generated here and then set as new user id.
        console.log("Creating new thread with title: " + title);
        var threadId = generateId();
        setUserId(threadId);
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var messages = JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText);
            }
        };
        xmlhttp.open("GET", "api.php?addThread=" + threadId + "&text=" + title, true);
        xmlhttp.send();
        setTimeout(function () {
            threadClicked(threadId, title);
        }, 1500);
    }

    function newThread() {
        //Opens the newThread segment
        clearThreads()
        var thread = '<div id = "newThreadHeader" class="item header shadow card">';
        thread += '<input type="text" name="Thread name: " maxlength="200" id="titleInput" class="textInput item shadow card"><div id="messageContainer"></div></div>';
        $('.threads').prepend(thread);
        $("#newThreadHeader").fadeIn("slow");
        $("#titleInput").animate({"opacity": "0.75"}, 500);
        $("#titleInput").keypress(function (e) {
            if (e.which == 13) {
                submitNewThread(getMessageFromForm());
            }
            console.log("Opned newThread menu");
        });
    }

    function getThreads() {
        //Requests all currently active threads and shows them on screen through addThread()
        document.title = "OctoPub - threads";
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var threads = JSON.parse(xmlhttp.responseText);
                console.log(xmlhttp.responseText);
                for (i = 0; i < threads.length; i++) {
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
                for (i = 0; i < threads.length; i++) {
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
                for (i = 0; i < messages.length; i++) {
                    addChatItem(messages[i][1], messages[i][0], messages[i][2], messages[i][3])
                }
            }
        };
        xmlhttp.open("GET", "api.php?fromId=" + window.latestMessageId + "&thread=" + window.currentThread, true);
        xmlhttp.send();
    }

    //Make the item with id #newThread, a button, run the newThread function
    $("#newThread").click(function () {
        newThread();
    });
    $("#newId").click(function () {
        var newUserId = generateId();
        setUserId(newUserId);
        console.log(newUserId);
    });

    //Change the color and text of the IdBox and then show it.
    $("#IdBox").css({'background-color': "#" + getUserId()});
    $("#IdBox").html(idToHTML(getUserId()));
    $("#IdBox").animate({"opacity": "1"}, 500);

    //When the page has loaded, get available threads.
    getThreads();
</script>
</body>
</html>