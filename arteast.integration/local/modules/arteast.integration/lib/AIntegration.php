<?

use Bitrix\Main\Config\Option;

IncludeModuleLangFile(__FILE__);
CModule::IncludeModule('iblock');

class AIntegration
{
    // general
    public $module = "arteast.integration";
    public $path;
    public $catalog;

    // settings
    public $max_per_processing;

    // data
    public $data_tmp = array();
    public $sections = array();
    public $products = array();

    // counters
    public $counter = 0;
    public $requests = 0;
    public $crash = 0;
    public $page = 1;
    public $page_cnt = 0;
    public $repeat_cats = true;

    public function __construct()
    {
        $this->catalog = Option::get($this->module, "module_catalog");
        $this->path = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/arteast.integration/data/";

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * Возвращает массив данных
     */
    public function GetData($page, $type)
    {
        $query = array(
            'p' => $page,
        );

        $url = "https://www.sima-land.ru/api/v5/" . $type . "?" . http_build_query($query);

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => "GET",
                    'header' => file_get_contents($this->path . "token.txt"),
                    'protocol_version' => '1.1',
                )
            ),
        );

        $result = json_decode(file_get_contents($url, false, $context));

        return $result;
    }

    /**
     * Процесс импорта категорий
     */
    public function CategoriesProcessing()
    {
        $this->PropertiesSetup();

        while ($this->repeat_cats == true) {
            while (is_array($data = $this->GetData($this->page++, "category"))) {
                foreach ($data as $item) {
                    $item->path = "0." . $item->path;
                    $item->parent_id = $this->GetParentID($item->path);

                    $arr_parent = CIBlockSection::GetList(array(), array("IBLOCK_ID" => $this->catalog, "UF_SIMALAND_SECTION_ID" => $item->parent_id), false, array("ID", "UF_SIMALAND_SECTION_ID"));
                    $parent = $arr_parent->GetNext();

                    if ($parent) {
                        $arr_children = CIBlockSection::GetList(array(), array("IBLOCK_ID" => $this->catalog, "UF_SIMALAND_SECTION_ID" => $item->id), false, array("ID", "UF_SIMALAND_SECTION_ID"));
                        $children = $arr_children->GetNext();

                        if (!$children) {
                            $this->AddCategory($item, $parent["ID"]);
                        }
                    } else {
                        if ($item->parent_id == 0) {
                            $arr_children = CIBlockSection::GetList(array(), array("IBLOCK_ID" => $this->catalog, "UF_SIMALAND_SECTION_ID" => $item->id), false, array("ID", "UF_SIMALAND_SECTION_ID"));
                            $children = $arr_children->GetNext();

                            if (!$children) {
                                $this->AddCategory($item, "");
                            }
                        }
                    }
                }

                $this->SaveData("processing", "categories");
            }

            if ($this->counter_tmp > 0) {
                $this->counter_tmp = 0;
                $this->page = 1;
            } else {
                $this->repeat_cats = false;
            }
        }

        $this->SaveData("complete", "categories");
    }

    /**
     * Процесс мультикатегории товаров
     */
    public function MulticategoryProcessing()
    {
        $this->max_per_processing = Option::get($this->module, "module_pages_per_processing");

        $agent_data = $this->LoadData();

        if ($agent_data) {
            $this->page = $agent_data->page;
            $this->counter = $agent_data->items;
            $this->crash = $agent_data->crashes;
        }

        $CIBlockElement = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $this->catalog), false, false, array("ID", "PROPERTY_SIMALAND_PRODUCT_ID"));

        while ($ob = $CIBlockElement->GetNext()) {
            $SID = $ob["PROPERTY_SIMALAND_PRODUCT_ID_VALUE"];

            $this->products[$SID] = $ob["ID"];
        }

        unset($CIBlockElement);

        while ($data = $this->GetData($this->page++, "item-category")) {
            foreach ($data as $item) {
                $SID = $this->products[$item->item_id];

                if ($SID) {
                    $sections = array();

                    $CIBlockElement = new CIBlockElement;
                    $query = $CIBlockElement->GetElementGroups($SID, false, array("ID"));

                    while($array = $query->Fetch()){
                        $sections[] = $array["ID"];
                    }

                    $arSections = CIBlockSection::GetList(array(), array("IBLOCK_ID" => $this->catalog, "UF_SIMALAND_SECTION_ID" => $item->category_id), false, array("ID", "UF_SIMALAND_SECTION_ID"));
                    $arSection = $arSections->Fetch();

                    $sections[] = $arSection["ID"];

                    $arFields = array(
                        "IBLOCK_SECTION" => $sections,
                    );

                    $result = $CIBlockElement->Update($SID, $arFields);
                    
                    if($result)
                    {
                        $this->counter++;
                    }
                }
            }

            $this->SaveData("processing", "multicategory");

            $this->$page_cnt++;

            if ($this->$page_cnt == $this->max_per_processing) {
                return true;
            }
        }

        if($this->crash >= 3) {
            $this->SaveData("complete", "multicategory");
            return true;
        }

        if(!is_array($data))
        {
            $this->crash++;
            $this->page -= 1;
            $this->SaveData("processing", "multicategory");
            return true;
        }else{
            $this->crash = 0;
        }
    }

    /**
     * Процесс импорта товаров
     */
    public function ProductsProcessing()
    {
        $this->max_per_processing = Option::get($this->module, "module_pages_per_processing");

        $agent_data = $this->LoadData();

        if ($agent_data) {
            $this->page = $agent_data->page;
            $this->counter = $agent_data->items;
            $this->crash = $agent_data->crashes;
        }

        $CIBlockElement = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $this->catalog), false, false, array("ID", "PROPERTY_SIMALAND_PRODUCT_ID"));

        while ($ob = $CIBlockElement->GetNext()) {
            $SID = $ob["PROPERTY_SIMALAND_PRODUCT_ID_VALUE"];

            $this->products[$SID] = $ob["ID"];
        }

        unset($CIBlockElement);

        while ($data = $this->GetData($this->page++, "item"))
        {
            foreach ($data as $item) {
                if ($item->balance != "0") {
                    $arSections = CIBlockSection::GetList(array(), array("GLOBAL_ACTIVE" => "Y", "ACTIVE" => "Y", "IBLOCK_ID" => $this->catalog, "UF_SIMALAND_SECTION_ID" => $item->category_id), false, array("ID", "UF_SIMALAND_SECTION_ID"));
                    $arSection = $arSections->GetNext();

                    if ($arSection) {
                        $SID = $this->products[$item->id];

                        if (!$SID) {
                            $this->AddProduct($item, $arSection["ID"]);
                        }
                    }
                }
            }

            $this->SaveData("processing", "products");

            $this->$page_cnt++;

            if ($this->$page_cnt == $this->max_per_processing) {
                return true;
            }
        }
        
        if($this->crash >= 3) {
            $this->SaveData("complete", "products");
            return true;
        }
        
        if(!is_array($data))
        {
            $this->crash++;
            $this->page -= 1;
            $this->SaveData("processing", "products");
            return true;
        }else{
            $this->crash = 0;
        }
    }

    /**
     * Процесс импорта атрибутов
     */
    public function AttributesProcessing()
    {
        $this->max_per_processing = Option::get($this->module, "module_pages_per_processing");

        $agent_data = $this->LoadData();

        if ($agent_data) {
            $this->page = $agent_data->page;
            $this->counter = $agent_data->items;
            $this->crash = $agent_data->crashes;
        }

        $CIBlockElement = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $this->catalog), false, false, array("ID", "PROPERTY_SIMALAND_PRODUCT_ID"));

        while ($ob = $CIBlockElement->GetNext()) {
            $SID = $ob["PROPERTY_SIMALAND_PRODUCT_ID_VALUE"];

            $this->products[$SID] = $ob["ID"];
        }

        unset($CIBlockElement);

        while ($data = $this->GetData($this->page++, "item-attribute")) {
            foreach ($data as $item) {
                $SID = $this->products[$item->item_id];

                if ($SID) {
                    $attribute = $this->GetData(1, "attribute/" . $item->attribute_id);

                    if ($attribute->data_type_id == 6) {
                        $this->AddProperty($attribute);
                        $this->AddAttribute($SID, $item->attribute_id, $this->GetData(1, "option/" . $item->option_value));
                    }
                }
            }

            $this->SaveData("processing", "attributes");

            $this->$page_cnt++;

            if ($this->$page_cnt == $this->max_per_processing) {
                return true;
            }

            if($this->crash == 3) {
                break;
            }

            if(is_null($data))
            {
                $this->crash++;
                return true;
            }else{
                $this->crash = 0;
            }
        }

        $this->SaveData("complete", "attributes");
    }

    /**
     * Добавить категорию
     */
    public function AddCategory($data, $section_id)
    {
        $section = new CIBlockSection;

        $slug = explode("/", $data->slug);

        $fields = array(
            "ACTIVE" => "Y",
            "IBLOCK_ID" => $this->catalog,
            "IBLOCK_SECTION_ID" => $section_id,
            "NAME" => $data->name,
            // "CODE" => Cutil::translit($data->name, "ru"),
            "CODE" => array_pop($slug),
            "PICTURE" => CFile::MakeFileArray($data->icon),
            "UF_SIMALAND_SECTION_ID" => $data->id,
        );

        $category_id = $section->Add($fields);

        if ($category_id) {
            $this->counter++;
            $this->counter_tmp++;
        }
    }

    /**
     * Добавить продукт
     */
    public function AddProduct($item, $section)
    {
        $CIBlockElement = new CIBlockElement;

        foreach ($item->agg_photos as $image) {
            $images[] = CFile::MakeFileArray($item->base_photo_url . "/" . $image . "/700.jpg");
        }

        $arFields = array(
            "SIMALAND_PRODUCT_ID" => $item->id,
            // "MORE_PHOTO" => $images,
            "ARTICUL" => $item->sid,
        );

        $product_id = $CIBlockElement->Add(
            array(
                "ACTIVE" => "Y",
                "IBLOCK_ID" => $this->catalog,
                "NAME" => $item->name,
                "CODE" => $item->slug,
                "IBLOCK_SECTION" => $section,
                // "PREVIEW_PICTURE" => CFile::MakeFileArray($item->base_photo_url . "/0/280.jpg"),
                "DETAIL_TEXT" => $item->description,
                "DETAIL_TEXT_TYPE" => 'html',
                "PROPERTY_VALUES" => $arFields,
            )
        );

        if ($product_id) {
            $this->counter++;
        }

        CCatalogProduct::Add(
            array(
                "ID" => $product_id,
                "QUANTITY" => "10000",
                "WEIGHT" => $item->weight,
                "WIDTH" => $item->width,
                "HEIGHT" => $item->height,
                "LENGTH" => $item->depth,
            )
        );

        $overprice = Option::get($this->module, "module_overprice");

        CPrice::Add(
            array(
                "CURRENCY" => "RUB",
                "PRICE" => $item->price + ($item->price / 100 * $overprice),
                "CATALOG_GROUP_ID" => 1,
                "PRODUCT_ID" => $product_id,
            )
        );
    }

    /**
     * Добавить свойство
     */
    public function AddProperty($data)
    {
        $CIBlockProperty = CIBlockProperty::GetList(array("SORT" => "ASC"), array("IBLOCK_ID" => $this->catalog, "CODE" => "SIMALAND_ATTR_" . $data->id), false, $arSelect = array("IBLOCK_ID", "SIMALAND_ATTR_" . $data->id));
        $property = $CIBlockProperty->GetNext();

        if ($property == false) {
            $fields = array(
                "IBLOCK_ID" => $this->catalog,
                "NAME" => $data->name,
                "CODE" => "SIMALAND_ATTR_" . $data->id,
                "HINT" => $data->description,
                "ACTIVE" => "Y",
                "SORT" => "500",
                "PROPERTY_TYPE" => "S",
                "MULTIPLE" => "N",
                "FEATURES" => array(
                    array(
                        'IS_ENABLED' => 'Y',
                        'MODULE_ID' => 'iblock',
                        'FEATURE_ID' => 'LIST_PAGE_SHOW'
                    ),
                    array(
                        'IS_ENABLED' => 'Y',
                        'MODULE_ID' => 'iblock',
                        'FEATURE_ID' => 'DETAIL_PAGE_SHOW'
                    ),
                ),
            );

            $property = new CIBlockProperty;
            $property->Add($fields);
        }
    }

    /**
     * Присваивает значение атрибута товару
     */
    public function AddAttribute($id, $attribute_id, $data)
    {
        $CIBlockElement = new CIBlockElement;

        $properties = array(
            "SIMALAND_ATTR_" . $attribute_id => $data->name,
        );

        $CIBlockElement->SetPropertyValuesEx($id, $this->catalog, $properties);

        $this->counter++;
    }

    /**
     * Возвращает массив вкладок и настроек
     */
    public function GetTabs()
    {
        $object = CIBlock::GetList(
            array(),
            array(
                'TYPE' => 'catalog', // Тип инфоблока
                'ACTIVE' => 'Y',
                "CNT_ACTIVE" => "Y",
            ),
            true
        );

        $catalogs[""] = "";

        while ($catalog = $object->Fetch()) {
            $catalogs[$catalog['ID']] = $catalog['NAME'];
        }

        $mainOptions = array(
            "Настройки Sima-Land",
            array('simaland_email', "E-mail", '', array('text', '')),
            array('simaland_password', "Пароль", '', array('password', '')),
            "Настройки модуля",
            array('module_catalog', "Каталог", '', array('selectbox', $catalogs)),
            array('module_overprice', "Наценка %", '10', array('text', '')),
            "Настройки для разработчиков",
            array('module_pages_per_processing', "Обработка страниц за шаг", '500', array('text', '500')),
        );

        $tabs[] = array(
            "DIV" => "MAIN",
            "TAB" => GetMessage("TAB_MAIN"),
            "TITLE" => GetMessage("TAB_MAIN"),
            "OPTIONS" => $mainOptions
        );

        $tabs[] = array(
            "DIV" => "INSTRUCTION",
            "TAB" => GetMessage("TAB_INSTRUCTION"),
            "TITLE" => GetMessage("TAB_INSTRUCTION"),
        );

        return $tabs;
    }

    /**
     * Вернуть статус импорта
     */
    public function GetImportStatus()
    {
        return file_get_contents($this->path . "status.txt");
    }

    /**
     * Возвращает информационное сообщение
     */
    public function GetInfoMessage($id)
    {
        $status = json_decode(file_get_contents($this->path . "status.txt"), true);

        if ($id == 2 || $id == 6 || $id == 9 || $id == 13) {
            if ($status["status"] == "complete") {
                unlink($this->path . "status.txt");
            }
        }

        $message = array(
            0 => array(
                "title" => GetMessage("START_PROCESSING_PRODUCTS_TITLE"),
                "message" => GetMessage("START_PROCESSING_PRODUCTS_MSG"),
                "page" => "",
            ),
            1 => array(
                "title" => GetMessage("PROCESSING_PRODUCTS_TITLE"),
                "message" => GetMessage("PROCESSING_PRODUCTS_MSG") . $status["items"],
                "page" => "Страница: " . $status["page"],
            ),
            2 => array(
                "title" => GetMessage("END_PROCESSING_PRODUCTS_TITLE"),
                "message" => GetMessage("END_PROCESSING_PRODUCTS_MSG") . $status["items"],
                "page" => "Страница: " . $status["page"],
            ),
            3 => array(
                "title" => GetMessage("STATUS_ERROR_TITLE"),
                "message" => GetMessage("STATUS_ERROR_MSG"),
                "page" => "",
            ),
            4 => array(
                "title" => GetMessage("START_PROCESSING_SECTIONS_TITLE"),
                "message" => GetMessage("START_PROCESSING_SECTIONS_MSG"),
                "page" => "",
            ),
            5 => array(
                "title" => GetMessage("PROCESSING_SECTIONS_TITLE"),
                "message" => GetMessage("PROCESSING_SECTIONS_MSG") . $status["items"],
                "page" => "Страница: " . $status["page"],
            ),
            6 => array(
                "title" => GetMessage("END_PROCESSING_SECTIONS_TITLE"),
                "message" => GetMessage("END_PROCESSING_SECTIONS_MSG") . $status["items"],
                "page" => "Страница: " . $status["page"],
            ),
            7 => array(
                "title" => GetMessage("START_PROCESSING_ATTRIBUTES_TITLE"),
                "message" => GetMessage("START_PROCESSING_ATTRIBUTES_MSG"),
                "page" => "",
            ),
            8 => array(
                "title" => GetMessage("PROCESSING_ATTRIBUTES_TITLE"),
                "message" => GetMessage("PROCESSING_ATTRIBUTES_MSG") . $status["items"],
                "page" => "Страница: " . $status["page"],
            ),
            9 => array(
                "title" => GetMessage("END_PROCESSING_ATTRIBUTES_TITLE"),
                "message" => GetMessage("END_PROCESSING_ATTRIBUTES_MSG") . $status["items"],
                "page" => "Страница: " . $status["page"],
            ),
            10 => array(
                "title" => "Попытка соединения #" . $status["attempt"],
                "message" => "Потеряно соединение с сервером Sima-Land.",
                "page" => "",
            ),
            11 => array(
                "title" => "Нет соединения",
                "message" => "Не удалось загрузить информацию с сервера Sima-Land. Попробуйте позже.",
                "page" => "",
            ),
            12 => array(
                "title" => "Обработка...",
                "message" => "Настройка товарных категорий: " . $status["items"],
                "page" => "Страница: " . $status["page"],
            ),
            13 => array(
                "title" => "Обработка завершена",
                "message" => "Настройка товарных категорий: " . $status["items"],
                "page" => "Страница: " . $status["page"],
            ),
        );

        return json_encode($message[$id], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Обновляет JWT токен
     */
    public function GetJWTToken()
    {

        $body = array(
            'email' => Option::get($this->module, "simaland_email"),
            'password' => Option::get($this->module, "simaland_password"),
            'regulation' => true,
        );

        $url = "https://www.sima-land.ru/api/v5/signin";

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => "POST",
                    'header' => "Content-Type: application/json",
                    'content' => json_encode($body),
                    'protocol_version' => '1.1',
                )
            ),
        );

        file_get_contents($url, false, $context);

        file_put_contents($this->path . "token.txt", $http_response_header[1]);
    }

    /**
     * Возвращает родительский ID товара
     */
    public function GetParentID($path)
    {
        $path = explode(".", $path);
        array_pop($path);

        return array_pop($path);
    }

    /**
     * Добавляет необходимые свойства для работы модуля
     */
    public function PropertiesSetup()
    {
        // Создать свойство товара
        $object = CIBlockProperty::GetList(array("SORT" => "ASC"), array("IBLOCK_ID" => $this->catalog, "CODE" => "SIMALAND_PRODUCT_ID"), false, $arSelect = array("IBLOCK_ID", "SIMALAND_PRODUCT_ID"));
        $data = $object->GetNext();

        if ($data == false) {
            $fields = array(
                "NAME" => "ID SimaLand",
                "ACTIVE" => "Y",
                "SORT" => "500",
                "CODE" => "SIMALAND_PRODUCT_ID",
                "PROPERTY_TYPE" => "S",
                "IBLOCK_ID" => $this->catalog,
                "MULTIPLE" => "N"
            );

            $property = new CIBlockProperty;
            $product = $property->Add($fields);
        }

        // Создать свойство раздела
        $object = CUserTypeEntity::GetList(array(), array("FIELD_NAME" => "UF_SIMALAND_SECTION_ID"));
        $data = $object->GetNext();

        if ($data == false) {
            $fields = array(
                "ENTITY_ID" => "IBLOCK_" . $this->catalog . "_SECTION",
                "FIELD_NAME" => "UF_SIMALAND_SECTION_ID",
                "USER_TYPE_ID" => "string",
                "EDIT_FORM_LABEL" => array("ru" => "ID SimaLand", "en" => "ID SimaLand"),
                "LIST_COLUMN_LABEL" => array("ru" => "ID SimaLand", "en" => "ID SimaLand"),
            );
            $property = new CUserTypeEntity;
            $section = $property->Add($fields);
        }
    }

    /**
     * Агент для добавления атрибутов
     */
    public static function Attributer()
    {
        $agent_data = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/arteast.integration/data/status.txt"));

        if ($agent_data) {
            if($agent_data->status == "processing" && $agent_data->type == "attributes")
            {
                $module = new AIntegration;
                $module->AttributesProcessing();
            }
        }

        return "AIntegration::Attributer();";
    }

    public static function Multicater()
    {
        $agent_data = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/arteast.integration/data/status.txt"));

        if ($agent_data) {
            if($agent_data->status == "processing" && $agent_data->type == "multicategory")
            {
                $module = new AIntegration;
                $module->MulticategoryProcessing();
            }
        }

        return "AIntegration::Multicater();";
    }

    /**
     * Агент для добавления товаров
     */
    public static function Producter()
    {
        $agent_data = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/arteast.integration/data/status.txt"));

        if ($agent_data) {
            if($agent_data->status == "processing" && $agent_data->type == "products")
            {
                $module = new AIntegration;
                $module->ProductsProcessing();
            }
        }

        return "AIntegration::Producter();";
    }

    /**
     * Агент для обновления JWT токена
     */
    public static function UpdateJWT()
    {
        $module = new AIntegration;
        $module->GetJWTToken();

        return "AIntegration::UpdateJWT();";
    }

    public function SaveData($status, $type)
    {
        $status = array(
            "status" => $status,
            "type" => $type,
            "items" => $this->counter,
            "page" => $this->page,
            "crashes" => $this->crash,
        );

        file_put_contents($this->path . "status.txt", json_encode($status, JSON_UNESCAPED_UNICODE));
    }

    public function LoadData()
    {
        return json_decode(file_get_contents($this->path . "status.txt"));
    }
}
