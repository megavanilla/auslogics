<?php

/**
 * Комментарии пишу в избытке, для понимания моих мыслей при решении задачи
 * **/

/**
 * Логика:
 * 1) Определяем первоначальную размерность массива, по условию задачи она динамична,
 *    также из условия следует что в первоначальном массиве все элементы уникальны, поэтому он станет ещё меньще
 * 2) Генерируем массив со значениями
 * 3) Убираем возможные дубликаты
 * 4) Получаем два произвольных ключа из массива
 *    Первый ключ применяем для выбора заменяемого элемента,
 *    а второй для установки нового значения(который будет являтся дубликатом в массиве)
 * 5) Выводим результаты работы
 * **/

/**
 * Первоначальная размерность массива,
 * размерность должна быть больше одного элемента,
 * т.к. строками ниже из массива выбирается два элемента
 */
$rand_size = mt_rand(2, 100);

$a = array();
//Заполняем массив случайными числами
for($i=0; $i<$rand_size; $i++){
    $a[] = mt_rand(1, 1000000);
}

//Удаляем все дубликаты, т.к. их может быть несколько
$a = array_unique($a);

/**
 * Получаем случайные ключи массива,
 * первый изспользуем как ключ для заменяемого элемента,
 * а второй для подстановки значения.
 */
list($rand_key, $new_val) = array_rand($a, 2);
$a[$rand_key] = $a[$new_val];

/**
 * 
 * Определение дубликатов
 * 
 */
$unique     = array_unique($a); // Выбираем уникальные значения
$diffkeys   = array_diff_key($a, $unique);//Получаем расхождение массивов
$duplicates = array_unique($diffkeys);//Получаем дубликаты

/**
 * 
 * Выводим результаты работы
 * 
 */
echo 'Размерность массива: '.count($a).'<br /><br />';

echo 'Массив: ';?>
<p><textarea rows="10" cols="45" name="text">
<?php echo join(' ', $a); ?>
</textarea></p>

<?php echo 'Уникальные элементы: ';?>
<p><textarea rows="10" cols="45" name="text">
<?php echo join(' ', $unique); ?>
</textarea></p>

<?php echo 'Расхождение: ';?>
<p><textarea rows="10" cols="45" name="text">
<?php echo join(' ', $diffkeys); ?>
</textarea></p>

<?php echo "\n<br />".'Дубликаты: ' . join(' ', $duplicates) . "\n<br />";

?>