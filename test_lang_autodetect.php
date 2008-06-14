<?php
/* Copyright (c) 2008 Александр Лозовюк

Библиотека для автоматического определения языка строки или произвольного текста

Версия 1.0 детектирует русский, украинский и английский языки

*/


class Lang_Auto_Detect
{
	// основные переменные
	// сисок поддерживаемых языков
	public $lang = Array('en'=>array('English','http://en.wikipedia.org/wiki/English_language'),
						 'ru'=>array('Russian','http://ru.wikipedia.org/wiki/%D0%A0%D1%83%D1%81%D1%81%D0%BA%D0%B8%D0%B9_%D1%8F%D0%B7%D1%8B%D0%BA'),
						 'ua'=>array('Ukraine','http://uk.wikipedia.org/wiki/%D0%A3%D0%BA%D1%80%D0%B0%D1%97%D0%BD%D1%81%D1%8C%D0%BA%D0%B0_%D0%BC%D0%BE%D0%B2%D0%B0')
						);
	// порог чуствительности, сколько в % должно быть символов языка, чтобы он был определен
	public $detect_range = 75; 
	// обрабатывать ли многоязычные документы и возвращать массив используемых языков
	public $detect_multi_lang = false; // пока  не реализовано
	// возвращать все результаты и вероятности
	public $return_all_results = false; // в реальном применении лучше отключить
	// использовать дополнительно систему правил и исключений
	public $use_rules = false; 
	//применять только правила (быстрее намного, но результат менее вероятен, чем больше текста, тем достовернее)
	public $use_rules_only = false;
	// приоритет правил над статистикой -
	public $use_rules_priory = true; // true - правила приоритетнее статистики, false - статистика перед правилами	
	// искать только первое правило или максимум совпадений?
	public $match_all_rules = false; // только одно иначе = все
	//использовать % от алфавита или общее количество символов каждого алфавита
	public $use_str_len_per_lang = true; // true - использовать общую длину текста приоритетнее, чем % от символов алфавита, false - наоборот
	
	// минимальная длина строки для детектирования
	public $min_str_len_detect = 50;
	// для обеспечения нормальной производительности задайте максимальную длину в символах для сравнения
	public $max_str_len_detect = 1680; //
	

	// внутреняя непеременная - таблица алфавитов используемых при определении
	private $_langs = array(
					'en'=>array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'),
					'ru'=>array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я'),
					'ua'=>array('а','б','в','г','ґ','д','е','є','ж','з','и','і','ї','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ь','ю','я')
					);
	
	// хранит правила
	// правила  это символы или строки, наличие которой (любой или всех)  автоматически влечет идентификацию текста
	private $_lang_rules = array(
									'en'=>array('th', 'ir'),
									'ru'=>array('ъ', 'ё' ),
									'ua'=>array('ї', 'є')
								); 
	
	
	// конструктор класса
    public function __construct()
    {
		return true;		
    }


	// подготовка введенной строки для сраневния
	private function _prepare_str($tmp_str = null)
	{
		if ($tmp_str == null) return false; // если ничего не передали - выйти
		
		$tmp_str = trim($tmp_str);
		$tmp_encoding = mb_detect_encoding($tmp_str);
		
		if (mb_strlen($tmp_str, $tmp_encoding) > $this->max_str_len_detect)
		{
			//обрезать длину текста, для роизводительности
			$tmp_str = mb_substr($tmp_str, 0, $this->max_str_len_detect, $tmp_encoding);
		}
		else
			if (mb_strlen($tmp_str, $tmp_encoding) <= $this->min_str_len_detect) return false;
		
		// конвертируем кодировки
		$tmp_str = mb_convert_encoding($tmp_str, 'UTF-8', $tmp_encoding);
		
		// приводим все к нижнему регистру
		$tmp_str = mb_strtolower($tmp_str, 'UTF-8');
		
		return $tmp_str;
	}
	
	// функция определения языка по правилам
	// правила однозначно определяют язык, однако могут оибаться :)
	private function _detect_from_rules($tmp_str = null)
	{
		if ($tmp_str == null) return false; // если ничего не передали - выйти
		if (!is_array($this->_lang_rules)) return false;
		
		// перебор всех правил
		foreach ($this->_lang_rules as $lang_code=>$lang_rules)
		{
			$tmp_freq = 0;
			
			foreach ($lang_rules as $rule)
			{
				$tmp_term = mb_substr_count($tmp_str, $rule);

				if ($tmp_term > 1) // то есть символ в строе 1 или более раз
				{
					$tmp_freq++; // увеличим счетчик символов языка, которые в этой строке есть
				}
				
				// теперь проверим
				if ($this->match_all_rules === true)
				{
					// нужно совпадение всех правил
					if ($tmp_freq == count($lang_rules)) return $lang_code;
				}
				else
					{
						// достаточно одного
						if ($tmp_freq > 0) return $lang_code;					
					}
			}
		}
	
		return false;	
	}

	// функция определения языка по таблице
	private function _detect_from_tables($tmp_str = null)
	{
		if ($tmp_str == null) return false; // если ничего не передали - выйти
		
		//мы уже должны ранее обработать строку для сравнения		
		// перебираем все языки и для каждого определим вероятность
		$lang_res = array();
		
		foreach ($this->lang as $lang_code=>$lang_name)
		{
			$lang_res[$lang_code] = 0; //по умолчанию 0, то есть не этот язык
			
			$tmp_freq = 0; // частота символов текущего языка
			$full_lang_symbols = 0; //полное количество символов этого языка
			
			// так как длина строки может быть произвольной, а алфавит одинаковый, то цикл по алфавитам
			$cur_lang = $this->_langs[$lang_code];
						
			foreach ($cur_lang as $l_item)
			{
				// теперь посмотреть количество вхождений символа в строку
				$tmp_term = mb_substr_count($tmp_str, $l_item);
				
				if ($tmp_term > 1) // то есть символ в строе 1 или более раз
				{
					$tmp_freq++; // увеличим счетчик символов языка, которые в этой строке есть
					$full_lang_symbols += $tmp_term;
				}
			}

			if ($this->use_str_len_per_lang === true)
			{
				//использовать общее количество символов
				$lang_res[$lang_code] = $full_lang_symbols;
			}
			else
				// Вычислить процент от всех символов алфавита
				$lang_res[$lang_code] = ceil((100 / count($cur_lang) ) * $tmp_freq);
			
		}
		
		// так, теперь посомтрим что вышло
		arsort($lang_res, SORT_NUMERIC); //сортируем массив первый элемент язык с большей вероятностью
		
		if ($this->return_all_results == true)
		{
			return $lang_res; // если вернуть все результаты - возвращаем, иначе выбрать лучший
		}
		else
			{
				// если больше указанного нами порога, возвратить код языка, иначе - null (то есть, мы не можем определить код языка)
				$key = key($lang_res);
				
				if ($lang_res[$key] >= $this->detect_range)
					return $key;
				else
					return null;
			}
		
	}


	// общая функция для определения языка
	public function lang_detect($tmp_str = null)
	{
		if ($tmp_str == null) return false; // если ничего не передали - выйти
		
		$tmp_str = $this->_prepare_str($tmp_str);
		
		if ($tmp_str === false) return false;
		
		// если правила применяем ДО таблицы
		if ($this->use_rules_only === true)
		{
			$res = $this->_detect_from_rules($tmp_str);
			
			return array($res, $this->lang[$res]);
		}
		else
			{
				// при использовании таблиц мы не можем получить полную раскладку по результатам, потому отключаем
				$this->return_all_results = false;
				
				$res = $this->_detect_from_tables($tmp_str);
		
				if ($tmp_str === false) return false;
				
				if ($this->use_rules === true)
				{
					$res_rules = $this->_detect_from_rules($tmp_str);
					
					// исходим из настроек приоритета правил и статистики
					if ($this->use_rules_priory === true)
					{
						//правила имеют бОльший вес, чем статистика
						return  array($res_rules, $this->lang[$res_rules]);
					}
					else
						{
							return array($res, $this->lang[$res]);
						}
				}
				else
					return array($res, $this->lang[$res]);
			}	
	}
}




?>




