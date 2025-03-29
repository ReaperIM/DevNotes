# Заметка: Отправка HTTP-запроса
```php
<?php
function fetchData($url) {
    $response = file_get_contents($url);
    return json_decode($response, true);
}
?>