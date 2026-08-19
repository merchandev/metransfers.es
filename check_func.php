<?php
$lines = file('functions.php');
for($i=2570; $i<count($lines); $i++) {
    echo ($i+1) . ": " . $lines[$i];
}
