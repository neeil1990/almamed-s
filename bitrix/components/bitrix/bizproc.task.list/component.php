<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule("bizproc") || !CModule::IncludeModule("iblock"))
	return false;

if (!$GLOBALS["USER"]->IsAuthorized())
{
	$GLOBALS["APPLICATION"]->AuthForm("");
	die();
}

$arParams["USER_ID"] = intval(empty($arParams["USER_ID"]) ? $USER->GetID() : $arParams["USER_ID"]);
$arParams["WORKFLOW_ID"] = (empty($arParams["WORKFLOW_ID"]) ? $_REQUEST["WORKFLOW_ID"] : $arParams["WORKFLOW_ID"]);

$arResult["back_url"] = urlencode(empty($_REQUEST["back_url"]) ? $APPLICATION->GetCurPageParam() : $_REQUEST["back_url"]);

$arParams["TASK_EDIT_URL"] = trim($arParams["TASK_EDIT_URL"]);
if (empty($arParams["TASK_EDIT_URL"])):
	$arParams["TASK_EDIT_URL"] = $APPLICATION->GetCurPage()."?PAGE_NAME=task_edit&ID=#ID#&back_url=".$arResult["back_url"];
else:
	$arParams["TASK_EDIT_URL"] .= (strpos($arParams["TASK_EDIT_URL"], "?") === false ? "?" : "&")."back_url=".$arResult["back_url"];
endif;
$arParams["~TASK_EDIT_URL"] = $arParams["TASK_EDIT_URL"];
$arParams["TASK_EDIT_URL"] = htmlspecialchars($arParams["~TASK_EDIT_URL"]);

$arParams["PAGE_ELEMENTS"] = intVal(intVal($arParams["PAGE_ELEMENTS"]) > 0 ? $arParams["PAGE_ELEMENTS"] : 50);
$arParams["PAGE_NAVIGATION_TEMPLATE"] = trim($arParams["PAGE_NAVIGATION_TEMPLATE"]);
$arParams["SHOW_TRACKING"] = ($arParams["SHOW_TRACKING"] == "Y" ? "Y" : "N");

$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y"); //Turn on by default


$arResult["FatalErrorMessage"] = "";
$arResult["ErrorMessage"] = "";

$arResult["NAV_RESULT"] = "";
$arResult["NAV_STRING"] = "";
$arResult["TASKS"] = array();
$arResult["TRACKING"] = array();

if (strlen($arResult["FatalErrorMessage"]) <= 0)
{
	$arResult["GRID_ID"] = "bizproc_tasksList_".$arParams["USER_ID"];

	$arSelectFields = array("ID", "WORKFLOW_ID", "PARAMETERS");

	$gridOptions = new CGridOptions($arResult["GRID_ID"]);
	$gridColumns = $gridOptions->GetVisibleColumns();
	$gridSort = $gridOptions->GetSorting(array("sort"=>array("ID" => "desc")));

	$arResult["HEADERS"] = array(
		array("id" => "ID", "name" => "ID", "default" => false, "sort" => "ID"),
		array("id" => "NAME", "name" => GetMessage("BPATL_NAME"), "default" => true, "sort" => "NAME"),
		array("id" => "DESCRIPTION", "name" => GetMessage("BPATL_DESCRIPTION"), "default" => false, "sort" => ""),
		array("id" => "MODIFIED", "name" => GetMessage("BPATL_MODIFIED"), "default" => true, "sort" => "MODIFIED"),
		array("id" => "OVERDUE_DATE", "name" => GetMessage("BPATL_OVERDUE_DATE"), "default" => false, "sort" => "OVERDUE_DATE"),
		array("id" => "WORKFLOW_NAME", "name" => GetMessage("BPATL_WORKFLOW_NAME"), "default" => false, "sort" => ""),
		array("id" => "WORKFLOW_STATE", "name" => GetMessage("BPATL_WORKFLOW_STATE"), "default" => false, "sort" => ""),
	);

	foreach ($arResult["HEADERS"] as $h)
	{
		if (count($gridColumns) <= 0 || in_array($h["id"], $gridColumns))
			$arSelectFields[] = $h["id"];
	}

	$arResult["FILTER"] = array(
		array("id" => "NAME", "name" => GetMessage("BPATL_NAME"), "type" => "string"),
		array("id" => "MODIFIED", "name" => GetMessage("BPATL_MODIFIED"), "type" => "date"),
		//array("id" => "OVERDUE_DATE", "name" => GetMessage("BPATL_OVERDUE_DATE"), "type" => "date"),
	);

	$arFilter = array("USER_ID" => $arParams["USER_ID"]);
	if (!empty($arParams["WORKFLOW_ID"]))
		$arFilter["WORKFLOW_ID"] = $arParams["WORKFLOW_ID"];

	$gridFilter = $gridOptions->GetFilter($arResult["FILTER"]);
	foreach ($gridFilter as $key => $value)
	{
		if (substr($key, -5) == "_from")
		{
			$op = ">=";
			$newKey = substr($key, 0, -5);
		}
		elseif (substr($key, -3) == "_to")
		{
			$op = "<=";
			$newKey = substr($key, 0, -3);
		}
		else
		{
			$op = "";
			$newKey = $key;
		}

		if (!in_array($newKey, array("NAME", "MODIFIED", "OVERDUE_DATE")))
			continue;

		if ($newKey == "NAME" && $op == "")
		{
			$op = "~";
			$value = "%".$value."%";
		}

		$arFilter[$op.$newKey] = $value;
	}

	$arResult["SORT"] = $gridSort["sort"];

	$arResult["RECORDS"] = array();

	$dbRecordsList = CBPTaskService::GetList(
		$gridSort["sort"],
		$arFilter,
		false,
		$gridOptions->GetNavParams(),
		$arSelectFields
	);
	while ($arRecord = $dbRecordsList->Fetch())
	{
		$arRecord["DOCUMENT_URL"] = CBPDocument::GetDocumentAdminPage($arRecord["PARAMETERS"]["DOCUMENT_ID"]);

		$arRecord["MODULE_ID"] = $arRecord["PARAMETERS"]["DOCUMENT_ID"][0];
		$arRecord["ENTITY"] = $arRecord["PARAMETERS"]["DOCUMENT_ID"][1];
		$arRecord["DOCUMENT_ID"] = $arRecord["PARAMETERS"]["DOCUMENT_ID"][2];

		$arRecord["URL"] = array(
			"~TASK" => CComponentEngine::MakePathFromTemplate($arParams["~TASK_EDIT_URL"], $arRecord), 
			"TASK" => CComponentEngine::MakePathFromTemplate($arParams["TASK_EDIT_URL"], $arRecord)
		);

		if (array_key_exists("DESCRIPTION", $arRecord))
			$arRecord["DESCRIPTION"] = nl2br($arRecord["DESCRIPTION"]);

		if (count(array_intersect($arSelectFields, array("WORKFLOW_NAME", "WORKFLOW_STATE"))) > 0)
		{
			$arState = CBPStateService::GetWorkflowState($arRecord["WORKFLOW_ID"]);
			$arRecord["WORKFLOW_NAME"] = $arState["TEMPLATE_NAME"];
			$arRecord["WORKFLOW_STATE"] = $arState["STATE_TITLE"];
		}

		$aActions = array(
			array("ICONCLASS"=>"edit", "DEFAULT" => true, "TEXT"=>GetMessage("BPTL_C_DETAIL"), "ONCLICK"=>"window.location='".$arRecord["URL"]["TASK"]."';"),
		);
		if (strlen($arRecord["DOCUMENT_URL"]) > 0)
			$aActions[] = array("ICONCLASS"=>"", "DEFAULT" => false, "TEXT"=>GetMessage("BPTL_C_DOCUMENT"), "ONCLICK"=>"window.open('".$arRecord["DOCUMENT_URL"]."');");

		$arResult["RECORDS"][] = array("data" => $arRecord, "actions" => $aActions, "columns" => $aCols, "editable" => false);
	}

	$arResult["ROWS_COUNT"] = $dbRecordsList->SelectedRowsCount();
	$arResult["NAV_STRING"] = $dbRecordsList->GetPageNavStringEx($navComponentObject, GetMessage("INTS_TASKS_NAV"), "", false);
	$arResult["NAV_CACHED_DATA"] = $navComponentObject->GetTemplateCachedData();
	$arResult["NAV_RESULT"] = $dbRecordsList;
}

if ($arParams["SHOW_TRACKING"] == "Y")
{
	function __bwl_ParseStringParameterTmp1($matches, $documentType)
	{
		static $varCache = array();
		$result = "";
		if ($matches[1] == "user")
		{
			$user = $matches[2];

			$l = strlen("user_");
			if (substr($user, 0, $l) == "user_")
			{
				$result = CBPHelper::ConvertUserToPrintableForm(intval(substr($user, $l)));
			}
			else
			{
				$v = implode(",", $documentType);
				if (!array_key_exists($v, $varCache))
					$varCache[$v] = CBPDocument::GetAllowableUserGroups($documentType);

				$result = $varCache[$v][$user];
			}
		}
		elseif ($matches[1] == "group")
		{
			$v = implode(",", $documentType);
			if (!array_key_exists($v, $varCache))
				$varCache[$v] = CBPDocument::GetAllowableUserGroups($documentType);

			$result = $varCache[$v][$matches[2]];
		}
		else
		{
			$result = $matches[0];
		}
		return $result;
	}

	$arResult["H_GRID_ID"] = "bizproc_tasksListH_".$arParams["USER_ID"];

	$hgridOptions = new CGridOptions($arResult["H_GRID_ID"]);
	$hgridColumns = $hgridOptions->GetVisibleColumns();
	$hgridSort = $hgridOptions->GetSorting(array("sort"=>array("ID" => "desc")));

	$arResult["H_HEADERS"] = array(
		array("id" => "MODIFIED", "name" => GetMessage("BPATL_MODIFIED"), "default" => true, "sort" => ""),
		array("id" => "ACTION_NOTE", "name" => GetMessage("BPATL_DESCRIPTION"), "default" => true, "sort" => ""),
	);

	$arResult["H_SORT"] = $hgridSort["sort"];

	$arResult["H_RECORDS"] = array();

	$arFilter = array("MODIFIED_BY" => $arParams["USER_ID"]);
	if (!empty($arParams["WORKFLOW_ID"]))
		$arFilter["WORKFLOW_ID"] = $arParams["WORKFLOW_ID"];

	$dbRecordsList = CBPTrackingService::GetList(
		$hgridSort["sort"],
		$arFilter
	);
	while ($arRecord = $dbRecordsList->Fetch())
	{
		$arRecord["ACTION_NOTE"] = preg_replace_callback(
			"/\{=([A-Za-z0-9_]+)\:([A-Za-z0-9_]+)\}/i",
			"__bwl_ParseStringParameterTmp1",
			$arRecord["ACTION_NOTE"]
		);

		if (strlen($arRecord["WORKFLOW_ID"]) > 0)
		{
			$arRecord["STATE"] = CBPStateService::GetWorkflowState($arRecord["WORKFLOW_ID"]);
			$arRecord["DOCUMENT_URL"] = CBPDocument::GetDocumentAdminPage($arRecord["STATE"]["DOCUMENT_ID"]);
		}

		$aActions = array();
		if (strlen($arRecord["DOCUMENT_URL"]) > 0)
			$aActions[] = array("ICONCLASS"=>"", "DEFAULT" => false, "TEXT"=>GetMessage("BPTL_C_DOCUMENT"), "ONCLICK"=>"window.open('".$arRecord["DOCUMENT_URL"]."');");

		$arResult["H_RECORDS"][] = array("data" => $arRecord, "actions" => $aActions, "columns" => array(), "editable" => false);
	}

	$arResult["H_ROWS_COUNT"] = $dbRecordsList->SelectedRowsCount();
	$arResult["H_NAV_STRING"] = $dbRecordsList->GetPageNavStringEx($navComponentObject, GetMessage("INTS_TASKS_NAV"), "", false);
	$arResult["H_NAV_CACHED_DATA"] = $navComponentObject->GetTemplateCachedData();
	$arResult["H_NAV_RESULT"] = $dbRecordsList;
}

if (strlen($arResult["FatalErrorMessage"]) <= 0)
{
	if($arParams["SET_TITLE"] == "Y")
		$APPLICATION->SetTitle(GetMessage("BPABS_TITLE"));
	if ($arParams["SET_NAV_CHAIN"] == "Y")
		$APPLICATION->AddChainItem(GetMessage("BPABS_TITLE"));
}
else
{
	if ($arParams["SET_TITLE"] == "Y")
		$APPLICATION->SetTitle(GetMessage("BPWC_WLC_ERROR"));
	if ($arParams["SET_NAV_CHAIN"] == "Y")
		$APPLICATION->AddChainItem(GetMessage("BPWC_WLC_ERROR"));
}


$this->IncludeComponentTemplate();
?>