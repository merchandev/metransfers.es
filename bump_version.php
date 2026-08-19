<?php
$f = 'style.css';
$c = file_get_contents($f);
$c = str_replace('Version:     4.1.5', 'Version:     4.2.0', $c);
file_put_contents($f, $c);
echo "Bumped to 4.2.0";
