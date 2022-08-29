<?
session_write_close();
ignore_user_abort(true);
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set("allow_url_fopen", "On");

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');


// модуль работает с помощью агентов на cron
	
// 	Для импорта товаров
// 	Модуль: arteast.integration
// 	Функция агента: AIntegration::Producter();
// 	Интервал: 5

// 	Для импорта атрибутов
// 	Модуль: arteast.integration
// 	Функция агента: AIntegration::Attributer();
// 	Интервал: 5

// 	Для обновления JWT токена
// 	Модуль: arteast.integration
// 	Функция агента: AIntegration::UpdateJWT();
// 	Интервал: 604800