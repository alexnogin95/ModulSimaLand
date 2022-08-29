<?
Loc::loadMessages(__FILE__);
Class arteast_integration extends CModule
{
	var $MODULE_ID;
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;

	function __construct()
	{
		$arModuleVersion = array();
		include(__DIR__."/version.php");
		$this->MODULE_ID = 'arteast.integration';
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = Loc::getMessage("ARTEAST_INTEGRATION_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("ARTEAST_INTEGRATION_MODULE_DESC");

		$this->PARTNER_NAME = Loc::getMessage("ARTEAST_INTEGRATION_PARTNER_NAME");
		$this->PARTNER_URI = Loc::getMessage("ARTEAST_INTEGRATION_PARTNER_URI");
	}

	function InstallDB($arParams = array())
	{
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles($arParams = array())
	{
		$directories = array(
			"admin" => array(
				"install_dir" => "/bitrix/admin/",
				"copy_dir" => "/admin",
			),
			"themes" => array(
				"install_dir" => "/bitrix/themes/",
				"copy_dir" => "/install/themes",
			),
			"js" => array(
				"install_dir" => "/bitrix/js/".$this->MODULE_ID."/",
				"copy_dir" => "/install/assets/scripts",
			),
			"css" => array(
				"install_dir" => "/bitrix/panel/".$this->MODULE_ID."/",
				"copy_dir" => "/install/assets/styles",
			),
			"images" => array(
				"install_dir" => "/bitrix/images/".$this->MODULE_ID."/",
				"copy_dir" => "/install/assets/images",
			),
		);

		foreach($directories as $key => $directory)
		{
			if($key == "admin")
			{
				if (is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$this->MODULE_ID.'/admin'))
				{
					if ($dir = opendir($p))
					{
						while (false !== $item = readdir($dir))
						{
							if ($item == '..' || $item == '.' || $item == 'menu.php')
								continue;
							file_put_contents($file = $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$this->MODULE_ID.'_'.$item,
							'<'.'? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/'.$this->MODULE_ID.'/admin/'.$item.'");?'.'>');
						}
						closedir($dir);
					}
				}
			}
			else
			{
				CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID.$directory["copy_dir"], $_SERVER["DOCUMENT_ROOT"].$directory["install_dir"], true, true);
			}
		}
		
		return true;
	}

	function UnInstallFiles()
	{
		$directories = array(
			"admin" => array(
				"install_dir" => "/bitrix/admin/",
				"copy_dir" => "/admin",
			),
			"themes" => array(
				"install_dir" => "/bitrix/themes/",
				"copy_dir" => "/install/themes",
			),
			"js" => array(
				"install_dir" => "/bitrix/js/".$this->MODULE_ID."/",
				"copy_dir" => "/install/assets/scripts",
			),
			"css" => array(
				"install_dir" => "/bitrix/panel/".$this->MODULE_ID."/",
				"copy_dir" => "/install/assets/styles",
			),
			"images" => array(
				"install_dir" => "/bitrix/images/".$this->MODULE_ID."/",
				"copy_dir" => "/install/assets/images",
			),
		);

		foreach($directories as $key => $directory)
		{
			if($key == "admin")
			{
				if (is_dir($p = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$this->MODULE_ID.'/admin'))
				{
					if ($dir = opendir($p))
					{
						while (false !== $item = readdir($dir))
						{
							if ($item == '..' || $item == '.')
								continue;
							unlink($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$this->MODULE_ID.'_'.$item);
						}
						closedir($dir);
					}
				}
			}
			else
			{
				DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID.$directory["copy_dir"], $_SERVER["DOCUMENT_ROOT"].$directory["install_dir"]);
			}
		}

		return true;
	}
	public function isVersionD7()
    {
        return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'),'14.00.00');
    }

	function DoInstall()
	{
		global $APPLICATION;
		if ($this->isVersionD7())
        {
            $this->InstallFiles();
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        }else{
            $APPLICATION->ThrowException(Loc::getMessage("ARTEAST_INTEGRATION_INSTALL_ERROR_VERSION"));
        }
        }

	function DoUninstall()
	{
		global $APPLICATION;

        \Bitrix\Main\ModuleManager::unregisterModule($this->MODULE_ID);

        $this->UnInstallFiles();
	}
}
