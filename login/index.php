<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>OctoPub - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container shadow">
    <img src="Logo.png" class="logo">

    <h1>OctoPub</h1>
    <br>

    <div class="item shadow card">
        <h2>Username</h2>

        <form>
            <input type="text" name="name" class="textInput shadow card"><br>
            <input type="submit" value="Login" class="button shadow card">
        </form>
    </div>
</div>
<!-- Include the PubNub Library -->
<script src="https://cdn.pubnub.com/pubnub.min-dev.js"></script>

<!-- Instantiate PubNub -->
<script type="text/javascript">

    var PUBNUB_octopub = PUBNUB.init({
        publish_key: 'pub-c-78d5eae4-5c83-41cf-b133-59725788b284',
        subscribe_key: 'sub-c-fb2817c8-ce60-11e4-85c8-02ee2ddab7fe'
    });
    // Subscribe to the demo_tutorial channel
    PUBNUB_octopub.subscribe({
        channel: 'threads',
        message: function (m) {
            console.log(m)
        }
    });

</script>
</body>
</html>