<!DOCTYPE html>
<html>
  <head>
    <title>Тестовое задание</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

  </head>
  <style>
    body{
        width: 750px;
        margin: 0 auto;
    }
  </style>
  <body>
      <fieldset>
        <legend>Проверка с другого сайта:</legend>
        <section>
          <h1><a href="">Задача 1:</a></h1>
          <p>
            <strong>Описание:</strong>
            Для проверки достаточно разместить ссылку: <a href="http://auslogics.local/files/myfile.exe">Скачать файл myfile.exe.</a>
            Обращение по ссылке /files/<имя файла>.exe должно возвратить EXE файл расположенный
            по адресу /file.exe, а также установить cookie с параметром referrer равным домену, с
            которого пришел данный пользователь для закачки этого файла.
            Например, если на сайте www.cnet.com поместили прямую ссылку на
            http://www.auslogics.com/files/myfile.exe и посетитель щелкает по ней, то он сможет скачать
            файл /files/myfile.exe (а на самом деле /file.exe) и на его компьютере будет оставлена cookie с
            referrer = cnet.com.
            Написать скрипт (PHP), реализующий этот функционал, привести текст .htaccess, если нужен.
          </p>
        </section>
      </fieldset>
  </body>
</html>
