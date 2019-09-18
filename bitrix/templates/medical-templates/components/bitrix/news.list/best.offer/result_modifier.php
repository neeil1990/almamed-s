<?php
foreach($arResult["ITEMS"] as &$arItem){

    $VALUES = array();
    $res = CIBlockElement::GetProperty($arItem['IBLOCK_ID'], $arItem['ID'], "sort", "asc", array("CODE" => "ARTICLS"));
    while ($ob = $res->GetNext())
    {
        $arItem["ARTICLS"]['DESCRIPTION'][] = $ob['DESCRIPTION'];
        $arItem["ARTICLS"]['VALUE'][] = $ob['VALUE'];
    }

}

global $APPLICATION;
$cp = $this->__component; // объект компонента
if (is_object($cp))
{
    $cp->SetResultCacheKeys(array('ITEMS'));
}