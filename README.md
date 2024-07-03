### Установка

требуется установить: 

Git https://git-scm.com/download/win/

VCRedist https://aka.ms/vs/17/release/vc_redist.x64.exe

PHP https://windows.php.net/downloads/releases/php-7.4.33-Win32-vc15-x64.zip
* распаковать
* переименовать php.ini-development в php.ini
* убрать комментарий в файле с
* extension_dir = "ext"
* extension=curl
* добавить установленный каталог в переменные окружения Path (винды), что бы cmd "видела" php в любом каталоге


### Каталоги

out - каталог формируемых pdf, xls

### Конфиги

config-sample.php нужно скопировать в config.php 
скорректировать значение токена (ключа доступа)

### Запуск

`$ php parser.php`

или

`run.cmd`

### Обновление

`update.cmd`

### Сортировка

После первого запуска появляется файл sort.txt, в него заполняется порядок сортировки (по имени)