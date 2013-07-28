<!DOCTYPE html>
<html>
    <head>
        <title>SimulEdit</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            body,html,#simulEdit {
                width: 100%;
                height: 100%;
            }
            body {
                overflow: hidden;
                padding: 0px;
                margin: 0px;
            }
            #simulEdit {
                box-sizing: border-box;
                border: solid #CCC 1px;
            }
        </style>
        <script src="http://code.jquery.com/jquery-1.10.1.min.js" type="text/javascript"></script>
        <script src="http://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.4.4/underscore-min.js" type="text/javascript"></script>
        <script src="include/diff_match_patch_uncompressed.js" type="text/javascript"></script>
        <script>
            function readableCharFromInt(num) {
                num = num % 62;
                if (num < 10) {
                    return String.fromCharCode(48 + num);
                }
                if (num < 36) {
                    return String.fromCharCode(65 + (num - 10));
                }
                return String.fromCharCode(97 + (num - 36));
            }
            function uniqId(length) {
                if (typeof length == "undefined" || length == null)
                    length = 16;

                var str = "";
                for (var i = 0; i < length; i++) {
                    str += readableCharFromInt(Math.random() * 100000);
                }
                return str;
            }
            var diff = new (function() {
                var dmp = new diff_match_patch();
                this.parse = function(text1, text2) {
                    var diffs = dmp.diff_main(text1, text2, false);
                    return diffs;
                };
            })();

            var instanceId = uniqId();
            var refId = -1;
            var debugMode = false;
            var splitsize = 15;

            $(document).ready(function() {
                console.log("Instance ID: %s", instanceId);

                function listen() {
                    var oldValue = $('#simulEdit').val();
                    $.post("server_listen.php", {instanceId: instanceId, refId: refId}, function(data) {
                        if (debugMode)
                            console.log("[Received | %s]: %s", instanceId, data);
                        if (data.trim() != "null") {
                            data = JSON.parse(data);
                            window.refId = data.refId;

                            var newValue = $('#simulEdit').val();
                            if (oldValue == newValue) {
                                // The user did not type anything between this request and update
                                $('#simulEdit').val(data.text);
                            }
                            else {
                                // The user typed something between the request and the update, to maintain the user's input
                                // we need to figure out what the user typed, what the "other user" typed, merge those two and put our merged content in
                                var incValue = data.text;
                                //console.log(oldValue, newValue);
                                var diffNew = diff.parse(oldValue, newValue);
                                var diffInc = diff.parse(oldValue, incValue);
                                
                                // Remove unnneeded data, add useful meta data
                                var addedNew = [];
                                var addedInc = [];
                                var removeNew = [];
                                var removeInc = [];
                                
                                var posN = 0;
                                var posI = 0;
                                for (var i = 0; true; i++) {
                                    if (diffNew.length <= i && diffInc.length <= i)
                                        break;
                                    if (diffNew.length > i) {
                                        if (diffNew[i][0] === 1) addedNew.push({Byte: posN, Length: diffNew[i][1].length});
                                        if (diffNew[i][0] === -1) removeNew.push({Byte: posN, Length: diffNew[i][1].length});
                                        posN += diffNew[i][1].length;
                                    }
                                    if (diffInc.length > i) {
                                        if (diffInc[i][0] === 1) addedInc.push({Byte: posN, Length: diffNew[i][1].length});
                                        if (diffInc[i][0] === -1) removeInc.push({Byte: posI, Length: diffInc[i][1].length});
                                        posI += diffInc[i][1].length;
                                    }
                                }
                                var pos = 0;
                                for(var i in removeNew) {
                                    // Lets first deal with all things deleted locally
                                    // For anything deleted, first check if the textrange was also deleted externally, if so
                                }
                                //console.log(changeInc, changeNew);
                            }
                        }

                        listen();
                    });
                }
                listen();

                $("#simulEdit").keyup(_.throttle(function() {
                    if (debugMode)
                        console.log("[Sent|%s | %s]: %s", refId, instanceId, JSON.stringify({instanceId: instanceId, text: $('#simulEdit').val()}));
                    $.post("server_update.php", {instanceId: instanceId, text: $('#simulEdit').val()}, function(data) {
                        refId = data;
                    });
                }, 2000));
            });
        </script>
    </head>
    <body>
        <textarea id="simulEdit"></textarea>
    </body>
</html>