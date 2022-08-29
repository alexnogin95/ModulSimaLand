<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpApplication;

$APPLICATION->setTitle(GetMessage("MODULE_NAME"));

$request = HttpApplication::getInstance()->getContext()->getRequest();
$module = "arteast.integration";

Loader::includeModule($module);

Asset::getInstance()->addJs("https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js");
Asset::getInstance()->addJs("/bitrix/modules/arteast.integration/install/assets/scripts/AIntegration.js");
Asset::getInstance()->addString("<link rel='stylesheet' type='text/css' href='/bitrix/panel/arteast.integration/admin.css'>");

$moduleInit = new AIntegration;
$tabs = $moduleInit->GetTabs();
$tabControl = new CAdminTabControl("tabControl", $tabs);
?>

<input type="submit" name="import" value="<?= GetMessage('BUTTON_IMPORT_CATS'); ?>" class="adm-btn-save import-categories" />
<input type="submit" name="import" value="<?= GetMessage('BUTTON_IMPORT_PRODS'); ?>" class="adm-btn-save import-products" />
<input type="submit" name="import" value="<?= GetMessage('BUTTON_IMPORT_ATTRS'); ?>" class="adm-btn-save import-attributes" />
<input type="submit" name="import" value="Импорт мультикатегорий" class="adm-btn-save import-multicategory" />
<div class="responseMessage"></div>
<br>
<form action="<?= $APPLICATION->getCurPage(); ?>?mid=<?= $module; ?>&lang=<?= LANGUAGE_ID; ?>" method="post">
    <?php
    $tabControl->begin();

    foreach ($tabs as $tab) {
        $tabControl->beginNextTab();
    ?>
    <?if($tab["DIV"] == "INSTRUCTION"):?>
        <div style="font-size:18px;font-weight:bold;line-height:1;margin-bottom:20px">Описание работы модуля</div>
        <p style="line-height:1.5;font-size:14px;">
            Работа с модулем очень проста и не требует от Вас постоянного внимания. Процесс полностью автоматизирован и на каждом этапе выводит информационное сообщение.
        </p>
        <p style="line-height:1.5;font-size:14px;">
            После нажатия одной из кнопок импорта, модуль сообщит о начале работы и заблокирует кнопки импорта до окончания обработки. Когда импорт будет запущен, можно закрыть страницу и вернуться позднее. В любое время при заходе снова в модуль, будет показано сообщение со статусом импорта. <br><br><b>Обратите внимание!</b> Сообщение о завершении обработки выводится только один раз и при повторном посещении страницы или её обновлении сообщение больше не будет показано.
        </p>

        <br><br>

        <div style="font-size:18px;font-weight:bold;line-height:1;margin-bottom:20px">Как пользоваться</div>
        <p style="line-height:1.5;font-size:14px;">
            <b>Шаг 1.</b> Во вкладке "Настройки" заполните информацию для работы модуля.
            <br>
            <b>Шаг 2.</b> Произведите импорт категорий, нажав кнопку "Импорт категорий".
            <br>
            <b>Шаг 3.</b> Произведите импорт товаров, нажав кнопку "Импорт товаров".
            <br>
            <b>Шаг 4.</b> Произведите импорт атрибутов, нажав кнопку "Импорт атрибутов".
        </p>

        <br><br>

        <div style="font-size:18px;font-weight:bold;line-height:1;margin-bottom:20px">Настройки перед использованием</div>
        <p style="line-height:1.5;font-size:14px;">
            <b>Отключите проверку символьного кода разделов на уникальность</b>
            <br>
            Некоторые категории повторяются, поэтому, чтобы Битрикс не пропустил повторяющиеся категории следует выключить данную настройку в инфоблоке
        </p>

        <br><br>

        <div style="font-size:18px;font-weight:bold;line-height:1;">Ошибки/Проблемы</div>
        <p>
            <?=BeginNote();?>
            <div style="line-height:1.5;font-size:14px;">
                <b>Вопрос: Не работает модуль/что-то сломалось</b>
                <br>
                <span>Ответ: Обратитесь к разработчику модуля, предоставив скриншот ошибки, подробно опишите суть проблемы.</span>
            </div>
            <?=EndNote();?>
        </p>
    <?endif;?>
        <? foreach ($tab["OPTIONS"] as $option):?>
            <? if ($option[3][0] == "checkbox"):?>
                <tr>
                    <td width="50%" class="adm-detail-content-cell-l">
                        <label for="<?= $option[0] ?>"><?= $option[1] ?></label>
                    </td>
                    <td width="50%" class="adm-detail-content-cell-r">
                        <input type="checkbox" id="<?= $option[0] ?>" name="<?= $option[0] ?>" value="Y" class="adm-designed-checkbox" <? if (COption::GetOptionString($module, $option[0]) == "Y") echo "checked"; ?>>
                        <label class="adm-designed-checkbox-label" for="<?= $option[0] ?>" title=""></label>
                    </td>
                </tr>
                <?if($option[0] == "module_parent_section"):?>
                    <tr>
                        <td colspan="2" align="center">
                            <div class="adm-info-message" style="text-align:left">
                                Если включено, то в каталоге будет создан раздел "Игрушки", в который будут импортироваться разделы и товары.
                                <br>Не устанавливайте данную опцию, если в каталоге используются или будут использоваться другие разделы.
                            </div>
                        </td>
                    </tr>
                <?endif?>
            <? elseif ($option[3][0] == "selectbox"):?>
                <tr>
                    <td width="50%" class="adm-detail-content-cell-l"><?= $option[1] ?></td>
                    <td width="50%" class="adm-detail-content-cell-r">
                        <select name="<?= $option[0] ?>">
                            <? foreach ($option[3][1] as $k => $e):?>
                                <option value="<?= $k ?>" <? if (COption::GetOptionString($module, $option[0]) == $k) echo "selected"; ?>><?= $e ?></option>
                            <? endforeach ?>
                        </select>
                    </td>
                </tr>
            <? elseif ($option[3][0] == "text"):?>
                <tr>
                    <td width="50%" class="adm-detail-content-cell-l"><?= $option[1] ?></td>
                    <td width="50%" class="adm-detail-content-cell-r">
                        <input type="text" size="25" maxlength="255" value="<? if (COption::GetOptionString($module, $option[0])) {
                                                                                echo COption::GetOptionString($module, $option[0]);
                                                                            } else {
                                                                                echo $option[3][1];
                                                                            } ?>" name="<?= $option[0] ?>">
                    </td>
                </tr>
            <? elseif ($option[3][0] == "password"):?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l"><?= $option[1] ?></td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <input type="password" size="25" maxlength="255" value="<? if (COption::GetOptionString($module, $option[0])) {
                                                                            echo COption::GetOptionString($module, $option[0]);
                                                                        } else {
                                                                            echo $option[3][1];
                                                                        } ?>" name="<?= $option[0] ?>">
                </td>
            </tr>
            <? else:?>
                <tr class="heading">
                    <td colspan="2"><?= $option ?></td>
                </tr>
            <? endif ?>
        <? endforeach ?>
    <?
    }
    $tabControl->end();
    echo bitrix_sessid_post();
    ?>
    <input type="submit" name="apply" value="<?= GetMessage('BUTTON_APPLY'); ?>" class="adm-btn-save" />
    <input type="submit" name="default" value="<?= GetMessage('BUTTON_DEFAULT'); ?>" />
</form>
<?
if ($request->isPost() && check_bitrix_sessid()) {
    foreach ($tabs as $tab) {
        foreach ($tab['OPTIONS'] as $option) {
            if ($request['apply']) {
                $value = $request->getPost($option[0]);
                Option::set($module, $option[0], is_array($value) ? implode(',', $value) : $value);
            } elseif ($request['default']) {
                Option::set($module, $option[0], $option[2]);
            }
        }
    }
    $returnUrl = $_GET["return_url"] ? "&return_url=" . urlencode($_GET["return_url"]) : "";
    LocalRedirect($APPLICATION->getCurPage() . '?mid=' . $module . '&lang=' . LANGUAGE_ID . "&" . $tabControl->ActiveTabParam());
}
?>