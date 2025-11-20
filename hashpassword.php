<?php
$return = password_hash('password', PASSWORD_DEFAULT, ['cost' => 10]);
echo($return);