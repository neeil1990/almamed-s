<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('lists'))
{
	ShowError(GetMessage("CC_BLF_MODULE_NOT_INSTALLED"));
	return;
}

$lists_perm = CListPermissions::CheckAccess(
	$USER,
	$arParams["~IBLOCK_TYPE_ID"],
	intval($arParams["~IBLOCK_ID"]),
	$arParams["~SOCNET_GROUP_ID"]
);
if($lists_perm < 0)
{
	switch($lists_perm)
	{
	case CListPermissions::WRONG_IBLOCK_TYPE:
		ShowError(GetMessage("CC_BLF_WRONG_IBLOCK_TYPE"));
		return;
	case CListPermissions::WRONG_IBLOCK:
		ShowError(GetMessage("CC_BLF_WRONG_IBLOCK"));
		return;
	default:
		ShowError(GetMessage("CC_BLF_UNKNOWN_ERROR"));
		return;
	}
}
elseif($lists_perm < CListPermissions::IS_ADMIN)
{
	ShowError(GetMessage("CC_BLF_ACCESS_DENIED"));
	return;
}

$arParams["CAN_EDIT"] = $lists_perm >= CListPermissions::IS_ADMIN;
$arIBlock = CIBlock::GetArrayByID(intval($arParams["~IBLOCK_ID"]));
$arResult["~IBLOCK"] = $arIBlock;
$arResult["IBLOCK"] = htmlspecialcharsex($arIBlock);
$arResult["IBLOCK_ID"] = $arIBlock["ID"];

if(isset($arParams["SOCNET_GROUP_ID"]) && $arParams["SOCNET_GROUP_ID"] > 0)
	$arParams["SOCNET_GROUP_ID"] = intval($arParams["SOCNET_GROUP_ID"]);
else
	$arParams["SOCNET_GROUP_ID"] = "";

$arResult["GRID_ID"] = "lists_fields";

$arResult["~LISTS_URL"] = str_replace(
	array("#group_id#"),
	array($arParams["SOCNET_GROUP_ID"]),
	$arParams["~LISTS_URL"]
);
$arResult["LISTS_URL"] = htmlspecialchars($arResult["~LISTS_URL"]);

$arResult["~LIST_URL"] = CHTTP::urlAddParams(str_replace(
	array("#list_id#", "#section_id#", "#group_id#"),
	array($arResult["IBLOCK_ID"], 0, $arParams["SOCNET_GROUP_ID"]),
	$arParams["~LIST_URL"]
), array("list_section_id" => ""));
$arResult["LIST_URL"] = htmlspecialchars($arResult["~LIST_URL"]);

$arResult["~LIST_EDIT_URL"] = str_replace(
	array("#list_id#", "#group_id#"),
	array($arResult["IBLOCK_ID"], $arParams["SOCNET_GROUP_ID"]),
	$arParams["~LIST_EDIT_URL"]
);
$arResult["LIST_EDIT_URL"] = htmlspecialchars($arResult["~LIST_EDIT_URL"]);

$arResult["~LIST_FIELDS_URL"] = str_replace(
	array("#list_id#", "#group_id#"),
	array($arResult["IBLOCK_ID"], $arParams["SOCNET_GROUP_ID"]),
	$arParams["~LIST_FIELDS_URL"]
);
$arResult["LIST_FIELDS_URL"] = htmlspecialchars($arResult["~LIST_FIELDS_URL"]);

$arResult["~LIST_FIELD_EDIT_URL"] = str_replace(
	array("#list_id#", "#field_id#", "#group_id#"),
	array($arResult["IBLOCK_ID"], "0", $arParams["SOCNET_GROUP_ID"]),
	$arParams["~LIST_FIELD_EDIT_URL"]
);
$arResult["LIST_FIELD_EDIT_URL"] = htmlspecialchars($arResult["~LIST_FIELD_EDIT_URL"]);

//Form submitted
if(
	$_SERVER["REQUEST_METHOD"] == "POST"
	&& check_bitrix_sessid()
	&& isset($_POST["action_button_".$arResult["GRID_ID"]])
)
{
	$obList = new CList($arIBlock["ID"]);

	if($_POST["action_button_".$arResult["GRID_ID"]] == "delete" && isset($_POST["ID"]) && is_array($_POST["ID"]))
	{
		foreach($_POST["ID"] as $ID)
			$obList->DeleteField($ID);

		//Clear components cache
		$GLOBALS["CACHE_MANAGER"]->ClearByTag("lists_list_".$arResult["IBLOCK_ID"]);
	}

	if($_POST["action_button_".$arResult["GRID_ID"]] == "edit" && isset($_POST["FIELDS"]) && is_array($_POST["FIELDS"]))
	{
		foreach($_POST["FIELDS"] as $ID => $arField)
			$obList->UpdateField($ID, $arField);

		//Clear components cache
		$GLOBALS["CACHE_MANAGER"]->ClearByTag("lists_list_".$arResult["IBLOCK_ID"]);
	}

	if(!isset($_POST["AJAX_CALL"]))
		LocalRedirect($arResult["LIST_FIELDS_URL"]);
}

global $CACHE_MANAGER;
if($this->StartResultCache(false))
{
	$CACHE_MANAGER->StartTagCache($this->GetCachePath());
	$CACHE_MANAGER->RegisterTag("lists_list_".$arIBlock["ID"]);

	$obList = new CList($arIBlock["ID"]);

	$arResult["TYPES"] = $obList->GetAllTypes();

	$arFields = $obList->GetFields();
	$arResult["ROWS"] = array();
	foreach($arFields as $ID => $arField)
	{
		$data = array();
		foreach($arField as $key => $value)
		{
			$data["~".$key] = $value;
			if(is_array($value))
			{
				foreach($value as $key1=>$value1)
					if(!is_array($value1))
						$value[$key1] = htmlspecialchars($value1);
				$data[$key] = $value;
			}
			else
			{
				$data[$key] = htmlspecialchars($value);
			}
		}

		$data["~LIST_FIELD_EDIT_URL"] = str_replace(
			array("#list_id#", "#field_id#", "#group_id#"),
			array($arResult["IBLOCK_ID"], $ID, $arParams["SOCNET_GROUP_ID"]),
			$arParams["~LIST_FIELD_EDIT_URL"]
		);
		$data["LIST_FIELD_EDIT_URL"] = htmlspecialchars($data["~LIST_FIELD_EDIT_URL"]);

		$aCols = array(
			"TYPE" => $arResult["TYPES"][$data["TYPE"]],
			"NAME" => '<a target="_self" href="'.$data["LIST_FIELD_EDIT_URL"].'">'.$data["NAME"].'</a>',
		);

		$aActions = array(
			array(
				"ICONCLASS" => "edit",
				"TEXT" => GetMessage("CC_BLF_ACTION_MENU_EDIT"),
				"ONCLICK" => "jsUtils.Redirect(arguments, '".CUtil::JSEscape($data["~LIST_FIELD_EDIT_URL"])."')",
				"DEFAULT" => true,
			),
		);

		if($data["TYPE"] != "NAME")
		{
			$aActions[] = array("SEPARATOR" => true);
			$aActions[] = array(
				"ICONCLASS" => "delete",
				"TEXT" => GetMessage("CC_BLF_ACTION_MENU_DELETE"),
				"ONCLICK" => "bxGrid_".$arResult["GRID_ID"].".DeleteItem('".$ID."', '".GetMessage("CC_BLF_ACTION_MENU_DELETE_CONF")."')",
			);
		}

		$aEditable = array();
		if($obList->is_field($arField["TYPE"]))
		{
			$aEditable["MULTIPLE"] = false;
			$data["MULTIPLE"] = "N";
		}

		if($obList->is_readonly($ID))
		{
			$aEditable["IS_REQUIRED"] = false;
			$data["IS_REQUIRED"] = "N";
		}
		elseif($ID == "NAME")
		{
			$aEditable["IS_REQUIRED"] = false;
			$data["IS_REQUIRED"] = "Y";
		}

		$arResult["ROWS"][] = array("id" => $ID, "data"=>$data, "actions"=>$aActions, "columns"=>$aCols, "editable"=>$aEditable);
	}

	$CACHE_MANAGER->EndTagCache();
	$this->EndResultCache();
}

$this->IncludeComponentTemplate();

$APPLICATION->SetTitle(GetMessage("CC_BLF_TITLE_EDIT", array("#NAME#" => $arResult["IBLOCK"]["NAME"])));

$APPLICATION->AddChainItem($arResult["IBLOCK"]["NAME"], $arResult["~LIST_URL"]);

$APPLICATION->AddChainItem(GetMessage("CC_BLF_CHAIN_EDIT"), $arResult["~LIST_EDIT_URL"]);
if($arResult["IBLOCK_ID"])
	$APPLICATION->AddChainItem(GetMessage("CC_BLF_CHAIN_FIELDS"), $arResult["~LIST_FIELDS_URL"]);
?>