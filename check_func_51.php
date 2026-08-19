<?php
$lines = file('functions.php');
for($i=40; $i<60; $i++) {
    echo ($i+1) . ": " . $lines[$i];
}
