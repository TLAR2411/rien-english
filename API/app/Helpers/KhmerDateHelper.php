<?php

namespace App\Helpers;

use Carbon\Carbon;

class KhmerDateHelper
{
    private static function convertToKhmerNumerals($number)
    {
        $numerals = [
            0 => '០',
            1 => '១',
            2 => '២',
            3 => '៣',
            4 => '៤',
            5 => '៥',
            6 => '៦',
            7 => '៧',
            8 => '៨',
            9 => '៩',
        ];

        $khmerNumeral = '';

        do {
            $digit = $number % 10;
            $khmerNumeral = $numerals[$digit].$khmerNumeral;
            $number = intdiv($number, 10);
        } while ($number > 0);

        return $khmerNumeral;
    }

    public static function getKhmerYear($gregorianYear)
    {
        return self::convertToKhmerNumerals($gregorianYear);
    }

    public static function getKhmerDayNumber($day)
    {
        return self::convertToKhmerNumerals($day);
    }

    public static function getDate($date)
    {
        try {
            // Assuming $date is in the format 'd/m/Y'
            $khmerDate = Carbon::createFromFormat('d/m/Y', $date);

            if (! $khmerDate) {
                throw new \Exception('Failed to parse the date.');
            }

            $khmerDate->locale('km_KH'); // Set the locale after parsing

            $khmerYear = self::getKhmerYear($khmerDate->year);
            $khmerWeekday = $khmerDate->translatedFormat('l'); // Use translatedFormat for localized day names
            $khmerMonth = $khmerDate->translatedFormat('F'); // Use translatedFormat for localized month names
            $khmerDayNumber = self::getKhmerDayNumber($khmerDate->day);

            $result = 'ថ្ងៃ'.$khmerWeekday.' ទី'.$khmerDayNumber.' ខែ'.$khmerMonth.' ឆ្នាំ'.$khmerYear;

            return $result;
        } catch (\Throwable $e) {
            // Log or handle the exception
            return 'Error: '.$e->getMessage();
        }
    }

    public static function getDayDate($date)
    {
        try {
            // Assuming $date is in the format 'd/m/Y'
            $khmerDate = Carbon::createFromFormat('d/m/Y', Carbon::parse($date)->format('d/m/Y'));

            if (! $khmerDate) {
                throw new \Exception('Failed to parse the date.');
            }

            $khmerDate->locale('km_KH'); // Set the locale after parsing

            $khmerYear = self::getKhmerYear($khmerDate->year);
            $khmerWeekday = $khmerDate->translatedFormat('l'); // Use translatedFormat for localized day names
            $khmerMonth = $khmerDate->translatedFormat('F'); // Use translatedFormat for localized month names
            $khmerDayNumber = self::getKhmerDayNumber($khmerDate->day);

            $result = 'ថ្ងៃទី'.$khmerDayNumber.' ខែ'.$khmerMonth.' ឆ្នាំ'.$khmerYear;

            return $result;
        } catch (\Throwable $e) {
            // Log or handle the exception
            return 'Error: '.$e->getMessage();
        }
    }

    public static function getNameDayDate($date)
    {
        try {
            // Assuming $date is in the format 'd/m/Y'
            $khmerDate = Carbon::createFromFormat('d/m/Y', Carbon::parse($date)->format('d/m/Y'));

            if (! $khmerDate) {
                throw new \Exception('Failed to parse the date.');
            }

            $khmerDate->locale('km_KH'); // Set the locale after parsing

            $khmerYear = self::getKhmerYear($khmerDate->year);
            $khmerWeekday = $khmerDate->translatedFormat('l'); // Use translatedFormat for localized day names
            $khmerMonth = $khmerDate->translatedFormat('F'); // Use translatedFormat for localized month names
            $khmerDayNumber = self::getKhmerDayNumber($khmerDate->day);

            $result = $khmerWeekday;

            return $result;
        } catch (\Throwable $e) {
            // Log or handle the exception
            return 'Error: '.$e->getMessage();
        }
    }

    public static function getMonthYear($date)
    {
        try {
            // Assuming $date is in the format 'd/m/Y'
            $khmerDate = Carbon::createFromFormat('d/m/Y', $date);

            if (! $khmerDate) {
                throw new \Exception('Failed to parse the date.');
            }

            $khmerDate->locale('km_KH'); // Set the locale after parsing

            $khmerYear = self::getKhmerYear($khmerDate->year);
            $khmerMonth = $khmerDate->translatedFormat('F'); // Use translatedFormat for localized month names

            $result = ' ខែ'.$khmerMonth.' ឆ្នាំ'.$khmerYear;

            return $result;
        } catch (\Throwable $e) {
            // Log or handle the exception
            return 'Error: '.$e->getMessage();
        }
    }

    public static function getKhmerMonth($date)
    {
        try {
            // Assuming $date is in the format 'd/m/Y'
            $khmerDate = Carbon::createFromFormat('d/m/Y', $date);

            if (! $khmerDate) {
                throw new \Exception('Failed to parse the date.');
            }

            $khmerDate->locale('km_KH'); // Set the locale after parsing

            $khmerMonth = $khmerDate->translatedFormat('F'); // Use translatedFormat for localized month names

            $result = $khmerMonth;

            return $result;
        } catch (\Throwable $e) {
            // Log or handle the exception
            return 'Error: '.$e->getMessage();
        }
    }
}
