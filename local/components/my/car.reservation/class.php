<?php

namespace MyComponents\TestTask;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class CarReservation extends \CBitrixComponent
{
    private ?int $userPost = null;

    private ?\DateTime $startDT = null;
    private ?\DateTime $endDT = null;

    public function executeComponent(): void
    {
        $this->userPost = $this->currentUser();
        if ($this->userPost === false) {
            return;
        }

        if (!$this->checkTime()) {
            return;
        }

        if ($this->startResultCache())
        {
            $this->initResult();

            if (empty($this->arResult))
            {
                $this->abortResultCache();
                ShowError('Элементы не найдены');

                return;
            }

            print_r($this->arResult);
        }
    }

    private function initResult(): void
    {

        $this->arResult = [];

        $postIBlockId = $this->arParams['POST_IBLOCK_ID'];
        $iblockCarId = $this->arParams['CAR_IBLOCK_ID'];
        $iblockReservationId = $this->arParams['RESERVATION_IBLOCK_ID'];
        $iblockDriverId = $this->arParams['DRIVER_IBLOCK_ID'];
        $iblockCategoriesId = $this->arParams['CATEGORIES_IBLOCK_ID'];

        $permittedCategories = ($this->getArrProp($postIBlockId, [$this->userPost], ['PROPERTY_PERMITTED_CATEGORIES']));

        $carsId = $this->getArrEls($iblockCarId, ['PROPERTY_COMFORT_CATEGORY' => $permittedCategories], ['ID']);

        $resId = $this->getArrEls($iblockReservationId, ['PROPERTY_CAR' => $carsId], ['ID']);

        $carReservations = $this->getArrProp($iblockReservationId, $resId, [
            'PROPERTY_RESERVATION_START',
            'PROPERTY_RESERVATION_END',
            'PROPERTY_CAR'
        ]);

        $carsAvailable = $this->arrCarsAvailable($carReservations, $carsId);

        $categoriesNames = $this->getArrEls($iblockCategoriesId, [], ['ID', 'NAME']);

        if (!empty($carsAvailable)) {
            $carsAvailableInfo = $this->getArrProp($iblockCarId, $carsAvailable,
                [
                    'PROPERTY_MODEL',
                    'PROPERTY_COMFORT_CATEGORY',
                    'PROPERTY_DRIVER'
                ]);

            foreach ($carsAvailableInfo as $carId => $carData) {

                $this->arResult[$carId]['Model'] = $carData['PROPERTY_MODEL'];

                $this->arResult[$carId]['ComfortCategory'] = $categoriesNames[$carData['PROPERTY_COMFORT_CATEGORY']];

                $this->arResult[$carId]['Driver'] =  $this->getArrProp(
                    $iblockDriverId,
                    [$carData['PROPERTY_DRIVER']],
                    ['PROPERTY_FULL_NAME']
                );
            }
        } else {
            $this->arResult['NoCarsAvailable'] = 'Нет свободных машин на это время';
        }
    }



    private function currentUser()
    {
        global $USER;

        $user = \CUser::GetByID($USER->GetID());
        $user = $user->Fetch();

        if (!$user || !isset($user['UF_POST'])) {
            ShowError('Не удалось определить должность пользователя.');
            return false;
        }

        return $user['UF_POST'];
    }

    private function checkTime(): bool
    {
        $newStart = $_GET['newStart'] ?? null;
        $newEnd   = $_GET['newEnd']   ?? null;

        if ($newStart === null || $newEnd === null) {
            ShowError("Не переданы параметры newStart или newEnd");
            return false;
        }

        try {
            $this->startDT = new \DateTime($newStart);
            $this->endDT   = new \DateTime($newEnd);

        } catch (\Exception $e) {
            ShowError("Неверный формат даты: " . $e->getMessage());
            $this->startDT = null;
            $this->endDT   = null;
            return false;
        }

        return true;
    }

    private function getArrEls(int $iblockId, array $conditions, array $selectFields)
    {
        $res = \CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                $conditions
            ],
            false,
            false,
            $selectFields
        );

        $arr = [];
        while ($item = $res->GetNext()) {
            if (in_array('ID', $selectFields) && in_array('NAME', $selectFields)) {
                $arr[$item['ID']] = $item['NAME'];
            } else {
                foreach ($selectFields as $selectField) {
                    $arr[] = $item[$selectField];
                }
            }
        }

        return $arr;
    }

    private function getArrProp(int $iblockId, array $elementIds, array $props)
    {
        $result = [];
        $selectFields = ['ID', 'IBLOCK_ID'];

        foreach ($props as $prop) {
            $selectFields[] = $prop;
        }

        $res = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $elementIds],
            false,
            false,
            $selectFields
        );

        while ($ob = $res->GetNextElement()) {
            $item = $ob->GetFields();
            $id = $item['ID'];
            $elementProps = $ob->GetProperties();

            $row = [];

            foreach ($props as $prop) {
                $propCode = substr($prop, 9);

                if (isset($elementProps[$propCode])) {
                    $row[$prop] = $elementProps[$propCode]['VALUE'];
                }
            }

            $result[$id] = $row;
        }

        if (count($elementIds) === 1 && count($props) === 1) {
            $firstElement = reset($result);
            return reset($firstElement);
        }

        return $result;
    }


    private function arrCarsAvailable(array $reservations, $allCars): array
    {
        $unavailable = [];

        foreach ($reservations as $res) {

            $carId = $res['PROPERTY_CAR'];

            if (in_array($carId, $unavailable)) {
                continue;
            }

            $resStart = new \DateTime($res['PROPERTY_RESERVATION_START']);
            $resEnd   = new \DateTime($res['PROPERTY_RESERVATION_END']);

            if ($resStart < $this->endDT && $resEnd > $this->startDT) {
                $unavailable[] = $carId;
            }
        }

        return array_diff($allCars, $unavailable);
    }

}
?>