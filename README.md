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
    phing -f ./build/production/build.xml -Ddb.password=пароль_БД.
    
 3) добавлен отладочный тайминг. 
    Класс \App\Components\Timer - собственно класс для хранения и фиксации временных меток
    В Controllers добавлен трейт DebugTrait. В нем переопределена функция access класса Controllers. 
    В этом методе проверяем наличие GET параметра 'debug=on' и если он есть - пишем в объект юзера $user->debugMode = true. 
    Инстансы классов User и Timer передаются в twig через $this->data->user и $this->data->timer соответственно.
    В темплайте Index.html в начале фиксируем начало рендеринга, в конце - конец рендеринга и вывод в модальном окне результатов (если у юзера свойство debug = true)
    
    HOW TO USE:
        1) в контролере подключить трейт DebugTrait.
        2) взять инстанс таймера (например $timer = Timer::instance())
        3) в нужных местах экшена фиксируем временные метки ($timer->fix('контрольная точка 1'))
        4) для вывода результатов в URL добавляем GET параметр 'debug=on'