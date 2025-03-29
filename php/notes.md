# Поиск значения в массиве, игнорируя регистр
```php
# bySanya@
/*
Описание: Поиск значения в массиве, игнорируя регистр.
Параметры: 
	$needle - значение для поиска
	$haystack - массив, в котором осуществляется поиск
Возвращает:
	- true, если значение найдено
	- false, если значение не найдено
*/
function in_array_strtolower( string $needle, array $haystack ) :bool
{
	$needle = mb_strtolower($needle);
	$strtolower_arr = array_map('mb_strtolower', $haystack);

	return in_array($needle, $strtolower_arr);
}
```
