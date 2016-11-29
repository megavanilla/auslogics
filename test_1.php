<?php
//Устанавливаем куку
if (isset($_SERVER['HTTP_REFERER'])) {
    setcookie("referrer", parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST), time() + 3600);
}

$file = __DIR__.'\file.exe';
print($file);
if (is_file($file)) {
    // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
    // если этого не сделать файл будет читаться в память полностью!
    
    if (ob_get_level()) {
        ob_end_clean();
    }
    // заставляем браузер показать окно сохранения файла
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($file));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    // читаем файл и отправляем его пользователю
    if ($fd = fopen($file, 'rb')) {
        while (!feof($fd)) {
            print fread($fd, 1024);
        }
        fclose($fd);
    }
    exit;
}else{
    print('Такого файла не существует');
}
?>