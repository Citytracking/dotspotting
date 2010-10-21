<?php

$GLOBALS['foo'] = 'rasmus';
echo("<pre>" . var_dump($_ENV, 1) . "</pre>");

var_export($foo);

?>