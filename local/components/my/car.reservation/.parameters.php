<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

if (!Loader::includeModule('iblock'))
{
    return;
}

$arIBlocks = [];

$iblockFilter = [
    'ACTIVE' => 'Y',
];
if (isset($_REQUEST['site']))
{
    $iblockFilter['SITE_ID'] = $_REQUEST['site'];
}
$db_iblock = CIBlock::GetList(["SORT"=>"ASC"], $iblockFilter);
while($arRes = $db_iblock->Fetch())
{
    $arIBlocks[$arRes["ID"]] = "[" . $arRes["ID"] . "] " . $arRes["NAME"];
}

$arComponentParameters = [
    "GROUPS" => [],
    "PARAMETERS" => [
        "AJAX_MODE" => [],
        "CAR_IBLOCK_ID" => [
            "PARENT" => "BASE",
            "NAME" => "Инфоблок автомобилей",
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
        ],
        "RESERVATION_IBLOCK_ID" => [
            "PARENT" => "BASE",
            "NAME" => "Инфоблок бронирований",
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
        ],
        "POST_IBLOCK_ID" => [
            "PARENT" => "BASE",
            "NAME" => "Инфоблок должностей",
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
        ],
        "DRIVER_IBLOCK_ID" => [
            "PARENT" => "BASE",
            "NAME" => "Инфоблок водителей",
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
        ],
        "CATEGORIES_IBLOCK_ID" => [
            "PARENT" => "BASE",
            "NAME" => "Инфоблок категорий",
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
        ],
        "CACHE_TIME"  =>  ["DEFAULT"=>36000000],
        "CACHE_FILTER" => [
            "PARENT" => "CACHE_SETTINGS",
            "NAME" => GetMessage("IBLOCK_CACHE_FILTER"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        "CACHE_GROUPS" => [
            "PARENT" => "CACHE_SETTINGS",
            "NAME" => GetMessage("CP_BNL_CACHE_GROUPS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
    ],
];
?>