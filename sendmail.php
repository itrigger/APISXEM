<?php
$to      = 'mkey87@mail.ru';
$subject = 'ВНИМАНИЕ!!! ПРОБЛЕМА С КУРСАМИ!!!';
$message = 'Проблемы с курсами';
$headers = 'From: webmaster@sxematika.ru' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

mail($to, $subject, $message, $headers);
?> 