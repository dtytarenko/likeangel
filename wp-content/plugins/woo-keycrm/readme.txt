=== Woocommerce Keycrm.app ===
Contributors: Keycrm.app
Donate link: http://Keycrm.app/
Tags: Интеграция, Keycrm.app
Requires PHP: 5.3
Requires at least: 5.3
Tested up to: 5.7.2
Stable tag: 4.3.1
License: GPLv1 or later
License URI: http://www.gnu.org/licenses/gpl-1.0.html

## Описание

Передача заказов из WooCommerce в KeyCRM

## Установка

1. Скопировать каталог woo-keycrm в папку plugins Вашего сайта, активировать плагин в админпанели сайта. (Или загрузить через Плагины - Загрузить новый).
2. Ввести API ключ из KeyCRM. Сохранить и обновить страницу(!). 
3. Настроить соответствия справочников.

## Кастомизация

  Если вы используете кастомные плагины для свойств товаров, то нужно написать свой обработчик этих свойств, чтобы они в нужном Вам виде попадали в KeyCRM. Для этого добавьте файл `custom-property-handler.php` в корень плагина.
  Внутри должна быть одна функция - `customPropertyHandler`, которая на вход получает объект-свойство товара, а на выходе должен вернуть массив(!) объектов:
```
function customPropertyHandler($attributes) {
  // $attributes is an object like
  // [
  //  id: Number,
  //  key: "KeyName",
  //  value: "KeyValue" // here your custom property, which you need to parse
  // ]
  
  // do something

  // return // array like:
  /* [
    {
      name: "Размер",
      value: "XL"
    },
    {
      name: "Цвет",
      value: "Красный"
    }
  ] */
}
```

## Подробная инструкция

