---
layout: default
title: Урок №2. Первое знакомство с API
nav_order: 2
parent: Быстрый старт, уроки для разработчиков
grand_parent: Руководство разработчика
---

# Урок №2. Первое знакомство с API

Попробуем наполнить главную страницу более осмысленной информацией. Я хочу дать правильный title главной странице и вывести вверху логотип и название сайта, со ссылкой на главную страницу.

По умолчанию в Cetera CMS в разделах нет поля для привязки рисунков. Однако разделы являются наследниками объекта с произвольным набором полей, поэтому мы легко можем добавить любое необходимое поле. Вперед. Откроем панель «Сервис > Типы материалов». Затем откроем панель редактирования dir_data «Разделы»:

![Типы материалов]({{site.baseurl}}/images/pic3-1.png)

Видим, что список пользовательских полей пуст. Добавим поле «Рисунок»:

![Список пользовательсих полей]({{site.baseurl}}/images/pic4-1.png)

Теперь, если открыть свойства корневого раздела сайта, то на вкладке «Свойства» мы увидим только что созданное поле. Теперь легким движением руки загрузим логотип сайта:

![Свойства раздела CeteraLabs]({{site.baseurl}}/images/pic5.png)

Вернемся к фронт-офису. Настало время реализации поставленной в начале главы задачи. Изменим файл www/.templates/default.php:

```
<?php
$application = Application::getInstance();
$server = $application->getServer();
?>
<html>
  <head>
      <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
      <title><?php echo strip_tags($server->name); ?></title>
  </head>
  <body>
      <a href="/" title="На главную"><img src="<?php echo $server->picture; ?>" alt="LOGO" align="left"></a>
      <h1><?php echo $server->name; ?></h1>
  </body>
</html>
```

Немного пояснений:

Application — основной класс, практически все начинается с него. Application реализует шаблон singleton, чтобы предоставить общий доступ к свойства и ресурсам сайта. Следовательно, только один экземпляр Application может быть создан. Базовые методы, которые вам обязательно надо знать:

Application::getServer() возвращает текущий виртуальный сервер

Application::getCatalog() возвращает текущий раздел сайта

Application::getUser() возвращает авторизованного пользователя

Application::getUnparsedUrl() возвращает неразобранную часть url запрошенной страницы. Т.е. на запрос http://server/a/b/c/d система попытается найти виртуальный сервер server, затем в нем раздел «a», в нем — «b» и т.д. Если раздел «с» отсутствует в «b», то getUnparsedUrl вернет «c/d». Если раздела «a» не существует, то getUnparsedUrl вернет «a/b/c/d»

Application::getUserVar($name, $server = 0) возвращает пользовательскую переменную, определенную для сервера (по умолчанию для текущего). Пользовательские переменные можно определять на вкладке «Переменные» в свойствах раздела root и в свойствах серверов.

Итак, получив объект, предоставляющий доступ к текущему серверу, мы можем получить нужные нам свойства сервере, в том числе, определенные пользователем:

$server→name — имя сервера (сайта) — встроенное свойство

$server→picture — логотип — определенное пользователем свойство

Результат:

![Результат работы]({{site.baseurl}}/images/pic6.png)