<?php
$f = 'functions.php';
$c = file_get_contents($f);
$c = str_replace("'4.1.5'", "'4.2.0'", $c);
file_put_contents($f, $c);
echo "Bumped functions.php to 4.2.0";
