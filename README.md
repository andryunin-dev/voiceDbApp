1) обработка кнопок:
    кнопки оформлять как ссылки или как button
    атрибуты:
    
    role="button"
    
    href="addRegion | editRegion?id=2 | delRegion?id=2
    
    data-action = "add|edit|delete|close"
    в модальном окне обрабатываем кнопки по событиям:
    submit - отправляем данные формы 
    click - закрываем окно
    
 2) строка запуска скрипта сборки:
    phing -f ./build/production/build.xml -Ddb.password=пароль_БД
    
    