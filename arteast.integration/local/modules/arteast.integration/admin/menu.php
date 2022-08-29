<?php
IncludeModuleLangFile(__FILE__);

AddEventHandler('main', 'OnBuildGlobalMenu', 'addMenuItem');

function addMenuItem(&$aGlobalMenu, &$aModuleMenu) {
	
	global $USER;
	
    if ($USER->IsAdmin()) {
		if(!$aGlobalMenu['global_menu_arteast_digital'])
		{
			$aGlobalMenu['global_menu_arteast_digital'] = [
				'menu_id' => 'arteast_digital',
				'text' => 'Артист Digital',
				'title' => 'Артист Digital',
				'url' => 'arteast_digital_settings.php?lang=ru',
				'sort' => 120,
				'items_id' => 'arteast_digital_item',
				'help_section' => 'arteast_digital',
				"index_icon" => "arteast_simaland_icon",
				'page_icon' => "arteast_simaland_icon",
				'items' => [
					[
						'parent_menu' => 'arteast_digital_item',
						'sort'        => 10,
						'url'         => 'arteast.integration_admin.php?lang=ru',
						'text'        => 'Sima-Land',
						'title'       => 'Sima-Land',
						'icon'        => 'arteast_simaland_icon',
						'page_icon'   => 'arteast_simaland_icon',
						'items_id'    => 'menu_simaland',
					],
				],
			];
		}
	}
}

// $aMenu = array();

// $aMenu[] = array(
// 	'parent_menu' => 'global_menu_store',
// 	'section'     => 'arteast.integration',
// 	'sort'        => 100,
// 	'text'        => GetMessage("MODULE_NAME"),
// 	'title'       => GetMessage("MODULE_NAME"),
// 	'url'         => 'arteast.integration_admin.php?lang=' . LANG,
// 	'icon'        => 'arteast_menu_icon',
// 	'page_icon'   => 'arteast_menu_icon',
// 	'items_id'    => 'menu_integration',
// 	'more_url'    => array('arteast.integration_admin.php'),
// 	'items'       => array()
// );

// return !empty($aMenu) ? $aMenu : false;
