<?php
session_start();

/**
 * Эти глобальные переменные нужны, для того,
 * чтобы на форме можно было отражать статус действий пользователя,
 * а также оставлять корректно заполненые поля.
 */
$status = '';
$add_email = '';
$add_phone = '';
$ret_email = '';
/**
 * Также является глобальной,
 * шифруем несколько полей и храним один вектор для записи с полями.
 */
$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);

/**
 * Метод шифрует строку
 * @param string $key
 * @param string $text
 * @return array
 */
function encrypt($key, $text) {
    global $iv;
    return openssl_encrypt($text, 'aes128', $key, 0, $iv);
}

/**
 * Метод дешифрует строку
 * @param string $key
 * @param string $text
 * @return array
 */
function decrypt($key, $text, $iv) {
    return openssl_decrypt($text, 'aes128', $key, 0, $iv);
}

function get_connect_db() {
    $db = new mysqli("localhost", "root", "", "test");
    if ($db->connect_errno) {
        return false;
    }
    return $db;
}

function add_contact() {
    global $iv;
    global $status;
    global $add_email;
    global $add_phone;

    //Отфильтруем данные
    /**
     * Проверку телефона выполним так:
     * 1) Уберём все символы кроме цифр
     * 2) Проверим количество символов, полагаю для сотовых достаточно будет от 11 до 15
     */
    $add_phone = filter_input(INPUT_POST, 'add_phone', FILTER_SANITIZE_NUMBER_INT); //Такой подход оставляет плюсы и минусы, тем не менее, если переменная не передана, то вместо ошибки будет FALSE
    $add_phone = preg_replace('/[^0-9]/', '', $_POST['add_phone']); //Оставляем только цифры
    $add_email = filter_input(INPUT_POST, 'add_email', FILTER_SANITIZE_EMAIL);

    //Проверим данные
    $add_email = filter_var($add_email, FILTER_VALIDATE_EMAIL);
    $add_phone = (strlen($add_phone) >= 11 && strlen($add_phone) <= 15) ? $add_phone : false;

    //Если данные введены корректно
    if ($add_email !== false && $add_phone !== false) {
        $db = get_connect_db();

        //Шифруем данные
        $add_email_crypt = encrypt($add_email, $add_email);
        $add_phone_crypt = encrypt($add_email, $add_phone);
        
        //Записываем шифрованные данные
        $sql = "INSERT INTO email_phone (email,phone,iv) VALUES ('$add_email_crypt','$add_phone_crypt','$iv') ON DUPLICATE KEY UPDATE phone='$add_phone_crypt'";
        $stat_query = $db->query($sql);
        $db->close();//Закрываем соединение
        if ($stat_query) {
            $status = 'Запись успешна выполнена.';
        } else {
            $status = 'Не удалось сохранить изменения.' . $sql;
        }
    } else {
        $status = (!$add_email) ? 'Не корректно указан почтовый адрес в форме добавления.<br />' : '';
        $status .= (!$add_phone) ? 'Не корректно указан номер телефона в форме добавления.' : '';
    }
}

function retrieve_contact() {
    global $iv;
    global $status;
    global $ret_email;

    //Статус поиска данных
    $stat_find = false;
    
    //Отфильтруем данные
    $ret_email = filter_input(INPUT_POST, 'ret_email', FILTER_SANITIZE_EMAIL);

    //Проверим данные
    $ret_email = filter_var($ret_email, FILTER_VALIDATE_EMAIL);

    //Если данные введены корректно
    if ($ret_email !== false) {
        $db = get_connect_db();

        /**
         * Ищем данные, для этого:
         * Извлекаем данные частями по 10 строк
         */
        //Получаем количество записей в БД
        $stat_query = $db->query('SELECT COUNT(*) FROM email_phone');
        $row = $stat_query->fetch_assoc();
        $count_table = (!empty($row['COUNT(*)'])) ? $row['COUNT(*)'] : 0;

        for ($i = 0; $i < $count_table; $i++) {
            /**
             * Получаем по одной записи,
             * в случае большого объема данных есть вероятность,
             * что мы закончим работу раньше, чем дойдём до конца строк.
             * Обычной выборкой не получается, т.к. данные в БД зашифрованы,
             * и их следует расшифровать прежде, чем проверять.
             */
            $stat_query = $db->query("SELECT email, phone, iv FROM email_phone LIMIT $i,1");
            $row = $stat_query->fetch_assoc();
            if (!empty($row['email']) && !empty($row['phone']) && !empty($row['iv'])) {
                //Попробуем расшифровать email
                $decrypt_email = decrypt($ret_email, $row['email'], $row['iv']);

                //Проверяем совпадение введённого занчения с зашифрованным
                if ($ret_email == $decrypt_email) {
                    //Дешифруем номер телефона
                    $decrypt_phone = decrypt($ret_email, $row['phone'], $row['iv']);

                    $to = $ret_email;
                    $subject = 'Phone retrieve';
                    $message = "Your phone number: {$decrypt_phone}";
                    $headers = 'From: admin@localhost.ru' . "\r\n" .
                            'Reply-To: admin@localhost.ru' . "\r\n" .
                            'X-Mailer: PHP/' . phpversion();

                    $status_mail = mail($to, $subject, $message, $headers);

                    if ($status_mail) {
                        $status = 'Ваш номер телефона успешно отправлен на Ваш электронный адрес.';
                    } else {
                        $status = 'Не удалось отправить письмо на Ваш электронный адрес.';
                    }
                    $stat_find = true;
                    break;//Выходим из цикла, если удалось найти запись
                } else {
                    //Если не совпали данные, то смотрим следующую строку
                    continue;
                }
            } else {
                //Если полей нет, то выйдем цикла
                $status = 'Не удалось получить данные.';
                break;//Выходим из цикла, если не нашли нужных полей и таблице
            }
        }
        //Если полей нет, то выйдем цикла
        $status = ($stat_find == false)?'Не удалось найти Ваш электронный адрес.':$status;
        
        $db->close();//Закрываем соединение
    } else {
        $status = (!$ret_email) ? 'Не корректно указан почтовый адрес в форме восстановления.' : '';
    }
}

//Отфильтруем данные
$operation = filter_input(INPUT_POST, 'operation', FILTER_SANITIZE_STRING);


/**
 * С помощью токена избавляемся от csrf атак.
 * Операцию добавления или изменения,
 * выполняем только в случае успешной проверки токена.
 * Если не выполнялась никакой операции то генерируем/обновляем токен.
 */
switch ($operation) {
    case 'add':
        add_contact();
        break;
    case 'retrieve':
        retrieve_contact();
        break;
}

?>
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
    fieldset{
        vertical-align: top;
        height: 300px;
        width: 250px;
        display: inline-block;
        margin: 15px 10px;
    }
    fieldset legend{
        color: #4090FF;
    }
    fieldset p{
        font-weight: bold;
        width: 200px;
    }
    fieldset form input[type="text"]{
        height: 20px;
        width: 220px;
        border:1px solid #4090FF;
    }
    .p_note{
        width: 225px;
        font-weight: normal;
        float:right;
    }
  </style>
  <body>
    <fieldset>
      <legend>Add your phone number</legend>
      <p>Option 1. Add your phone number</p>
      <form  method="POST">
        <label for="add_phone">Enter your PHONE:</label><br />
        <input type="text" name="add_phone" value="<?php print($add_phone); ?>" />
        <br />
        <br />
        <label for="add_email">Enter your e-mail*:</label><br />
        <input type="text" name="add_email" value="<?php print($add_email); ?>" />
        <p class="p_note">You will be able to retrieve your phone number later on using your e-mail.</p>
        <input type="hidden" name="operation" value="add" />
        <input type="submit" />
      </form>

    </fieldset>
    <fieldset>
      <legend>Retrieve your phone number</legend>
      <p>Option 2. Retrieve your phone number</p>
      <form method="POST">
        <label for="ret_email">Enter your e-mail*:</label><br />
        <input type="text" name="ret_email" value="<?php print($ret_email); ?>" />
        <p class="p_note">The phone number will be e-mailed to you.</p>
        <input type="hidden" name="operation" value="retrieve" />
        <input type="submit"/> 
      </form>      
    </fieldset>
    <div id="status">
<?php print($status); ?>
    </div>
  </body>
</html>
