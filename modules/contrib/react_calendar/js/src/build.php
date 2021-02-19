#!/usr/local/bin/php
<?php

$test = shell_exec('yarn run build --color=always');
echo $test;
$js = shell_exec('ls ../build/static/js/');
$css = shell_exec('ls ../build/static/css/');

$js = explode("\n", $js)[0];
$css = explode("\n", $css)[0];

echo "\n$js\n$css\n\n";

shell_exec("cp ../build/static/js/{$js} ../build/static/js/react_calendar.js");
shell_exec("cp ../build/static/css/{$css} ../build/static/css/react_calendar.css");
