<?
header('Content-Type: application/json');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

CModule::IncludeModule("arteast.integration");

$module = new AIntegration;

if ($_GET["request"] == "message") {
    echo $module->GetInfoMessage($_GET["message_id"]);
}

if ($_GET["request"] == "GetImportStatus") {
    echo $module->GetImportStatus();
}

if ($_GET["event"] == "CategoriesProcessing") {
    $module->CategoriesProcessing();
}

if ($_GET["event"] == "MulticategoryProcessing") {
    // $module->ProductsProcessing();
    $module->SaveData("processing", "multicategory");
}

if ($_GET["event"] == "ProductsProcessing") {
    // $module->ProductsProcessing();
    $module->SaveData("processing", "products");
}

if ($_GET["event"] == "AttributesProcessing") {
    // $module->AttributesProcessing();
    $module->SaveData("processing", "attributes");
}
