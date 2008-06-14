<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Auto detect lang</title>
</head>
<?php
// подключим библиотеку
include_once 'test_lang_autodetect.php';
?>
<body>
<p>Система автоматического определения языка введенного текста (или основного языка в случае многоязычного текста).<br />
Пока мы работаем с: <strong>Русским</strong>, <strong>Английским</strong> и <strong>Украинским</strong> </p>
<form id="form1" name="form1" method="post" action="">
  <p>
    <textarea name="tmp_text" id="tmp_text" cols="100" rows="5"></textarea> 
    (мин. 50 символов, макс unlim (detect only 1680 symbols)</p>
  <fieldset>
  <legend>Настройки</legend>
  <p>
    <input type="checkbox" name="return_all_rules" id="return_all_rules" value="true" /> Возвращать всю таблицу результатов<br />

    <input type="checkbox" name="use_rules" id="use_rules" value="true" /> Использовать эвристические правила<br />
    
    <input type="checkbox" name="use_rules" id="use_rules_only" value="true" /> Использовать ТОЛЬКО правила (быстрее, лучше для больших моноязычных текстов)<br />
    
    <input type="checkbox" name="use_rules" id="use_rules_priory" value="true" /> Правила приоритетнее статистики <br />
    
    <input type="checkbox" name="use_rules" id="match_all_rules" value="true" /> Требовать совпадения всех правил для определения (иначе - любого правила)<br />
  
    <input type="checkbox" name="use_rules" id="use_str_len_per_lang" value="true" /> Учитывать общую длину текста каждого алфавита или % использования входящих в него букв<br />
    
  
  </fieldset>
  <p>
    <input type="submit" name="button" id="button" value="Проверить!" />
</p>
</form>
<p>&nbsp; </p>
Результат проверки:<br />

<?php
// это наша строка
$tmp_str = $_REQUEST['tmp_text'];

if (!empty($tmp_str))
{

// обьект детектора
$test_obj = new Lang_Auto_Detect();

// применим настройки
if ((isset($_REQUEST['return_all_rules'])) && ($_REQUEST['return_all_rules'] == 'true'))
	$test_obj->return_all_rules = true;
else
	$test_obj->return_all_rules = false;

if ((isset($_REQUEST['use_rules'])) && ($_REQUEST['use_rules'] == 'true'))
	$test_obj->use_rules = true;
else
	$test_obj->use_rules = false;
	
if ((isset($_REQUEST['use_rules_only'])) && ($_REQUEST['use_rules_only'] == 'true'))
	$test_obj->use_rules_only = true;
else
	$test_obj->use_rules_only = false;
	
if ((isset($_REQUEST['use_rules_priory'])) && ($_REQUEST['use_rules_priory'] == 'true'))
	$test_obj->use_rules_priory = true;
else
	$test_obj->use_rules_priory = false;	

if ((isset($_REQUEST['match_all_rules'])) && ($_REQUEST['match_all_rules'] == 'true'))
	$test_obj->match_all_rules = true;
else
	$test_obj->match_all_rules = false;
	
if ((isset($_REQUEST['use_str_len_per_lang'])) && ($_REQUEST['use_str_len_per_lang'] == 'true'))
	$test_obj->use_str_len_per_lang = true;
else
	$test_obj->use_str_len_per_lang = false;	
	
	
// теперь проверим 
$res = $test_obj->lang_detect($tmp_str);

if (($res == false) || ($res == null))	
{
	echo '<font color="red"><b>извините, язык определить не удалось или роизошла ошибка</b></font>';
	exit;
}

	
	echo '<h3>Основной язык текста: <a href="' . $res[1][1] . '">' . $res[1][0] . '</a></h3>';
	
	
}

exit;
	
	
	
?>
</body>

</html>
