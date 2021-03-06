<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if (strlen($arResult["FatalErrorMessage"]) > 0)
{
	?>
	<span class='errortext'><?= $arResult["FatalErrorMessage"] ?></span><br /><br />
	<?
}
else
{
	if (strlen($arResult["ErrorMessage"]) > 0)
	{
		?>
		<span class='errortext'><?= $arResult["ErrorMessage"] ?></span><br /><br />
		<?
	}

	$arButtons = array(
		array(
			"TEXT"=>GetMessage("BPWTC_WRCT_2LIST"),
			"TITLE"=>GetMessage("BPWTC_WRCT_2LIST"),
			"LINK"=>$arResult["PATH_TO_LIST"],
			"ICON"=>"btn-list",
		),
	);
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.toolbar",
		"",
		array(
			"BUTTONS" => $arButtons
		),
		$component
	);
	?>
	<br>

	<form method="post" name="task_form1" action="<?= POST_FORM_ACTION_URI ?>" enctype="multipart/form-data">
		<input type="hidden" name="action" value="doTask">
		<input type="hidden" name="id" value="<?= intval($arResult["Task"]["ID"]) ?>">
		<input type="hidden" name="workflow_id" value="<?= htmlspecialchars($arResult["Task"]["WORKFLOW_ID"]) ?>">
		<input type="hidden" name="back_url" value="<?= htmlspecialchars($backUrl) ?>">
		<?= bitrix_sessid_post() ?>
		<table class="bpwiz1-view-form data-table" cellpadding="0" cellspacing="0">
			<tr>
				<th colspan="2"><?= GetMessage("BPWTC_WRCT_SUBTITLE") ?></th>
			</tr>
			<tr>
				<td align="right" valign="top" width="40%"><?= GetMessage("BPWC_WTCT_NAME") ?>:</td>
				<td width="60%" valign="top"><?= $arResult["Task"]["NAME"] ?></td>
			</tr>
			<tr>
				<td align="right" valign="top" width="40%"><?= GetMessage("BPWC_WTCT_DESCR") ?>:</td>
				<td width="60%" valign="top"><?= nl2br($arResult["Task"]["DESCRIPTION"]) ?></td>
			</tr>
			<?= $arResult["TaskForm"]; ?>
		</table>
		<?= $arResult["TaskFormButtons"]?>
	</form>
	<?
}
?>