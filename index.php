<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>OctoPub - Threads</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container shadow">
    <img src="Logo.png" class="logo">
    <h1>OctoPub - Threads</h1>
    <br>
    <div class="threads">

    </div>
</div>

<!-- Include the chance Library... This should be removed later. -->
<script src="http://chancejs.com/chance.min.js"></script>

<!-- Include the Jquery Library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

<!-- Include the PubNub Library -->
<script src="http://cdn.pubnub.com/pubnub-3.7.1.min.js"></script></script>

<!-- Instantiate PubNub -->
<script type="text/javascript">

    var PUBNUB_octopub = PUBNUB.init({
        publish_key: 'pub-c-78d5eae4-5c83-41cf-b133-59725788b284',
        subscribe_key: 'sub-c-fb2817c8-ce60-11e4-85c8-02ee2ddab7fe'
    });
    // Subscribe to the demo_tutorial channel
    PUBNUB_octopub.subscribe({
        channel: 'threads',
        message: function(m){console.log(m)}
    });

</script>
<script>
    function threadClicked(id){
        var numberOfItems = $('.item').length
        $(".item").each(function(i){
            $(this).delay(200*i);
            $(this).animate({"opacity" : "0", marginTop: "+=25px"},500);});
        setTimeout(function(){$('.item').remove();},200*numberOfItems)
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
        var thread='<div id = "'+id+'"class="item shadow card"><div style="display: inline-block; width: 92.5%;"> <h2>'+title+'</h2></div>'
        thread+='<div class="id" style="background-color:#'+id+'"><h3>'+idText+'</h3></div></div>';
        $('.threads').prepend(thread);
        $( "#"+id ).click(function() {
            threadClicked(id)
        });
    }
    addThread("BADA55", "This should be the most badass green ever -->")
    for (var i=0; i<6; i++) {
        addThread(generateId(),chance.sentence())
    }
</script>
</body>
</html>