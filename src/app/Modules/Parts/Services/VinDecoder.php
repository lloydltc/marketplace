<?php

namespace App\Modules\Parts\Services;

/**
 * PM4: basic, deterministic VIN decode — NO AI, no external data source. Extracts
 * the model year (position 10) and a manufacturer hint from the WMI (first 3
 * chars) using a small built-in map. The buyer always confirms/adjusts the
 * decoded vehicle; deeper decoding is a later, data-gated enhancement.
 */
class VinDecoder
{
    /** Year codes for VIN position 10 (1980–2030 cycle). */
    private const YEAR_CODES = [
        'A' => 2010, 'B' => 2011, 'C' => 2012, 'D' => 2013, 'E' => 2014, 'F' => 2015,
        'G' => 2016, 'H' => 2017, 'J' => 2018, 'K' => 2019, 'L' => 2020, 'M' => 2021,
        'N' => 2022, 'P' => 2023, 'R' => 2024, 'S' => 2025, 'T' => 2026, 'V' => 2027,
        'W' => 2028, 'X' => 2029, 'Y' => 2030,
        '1' => 2001, '2' => 2002, '3' => 2003, '4' => 2004, '5' => 2005,
        '6' => 2006, '7' => 2007, '8' => 2008, '9' => 2009,
    ];

    /** Common WMI prefixes → make name (best-effort, Zimbabwe-market skew). */
    private const WMI_MAKES = [
        'JT' => 'Toyota', 'SB1' => 'Toyota', 'MR0' => 'Toyota', 'AHT' => 'Toyota',
        'JN' => 'Nissan', 'VSK' => 'Nissan', 'MNT' => 'Nissan',
        'JH' => 'Honda', 'JM' => 'Mazda', 'JMB' => 'Mitsubishi', 'JA' => 'Isuzu',
        'WVW' => 'Volkswagen', 'WBA' => 'BMW', 'WDB' => 'Mercedes-Benz', 'WDD' => 'Mercedes-Benz',
        'WAU' => 'Audi', 'AFA' => 'Ford', 'SAL' => 'Land Rover', 'KMH' => 'Hyundai', 'KNA' => 'Kia',
    ];

    /**
     * @return array{valid: bool, year: ?int, make_hint: ?string}
     */
    public function decode(string $vin): array
    {
        $vin = strtoupper(preg_replace('/[^A-HJ-NPR-Z0-9]/', '', $vin));

        if (strlen($vin) !== 17) {
            return ['valid' => false, 'year' => null, 'make_hint' => null];
        }

        $year = self::YEAR_CODES[$vin[9]] ?? null;

        $makeHint = null;
        foreach ([3, 2] as $len) {
            $prefix = substr($vin, 0, $len);
            if (isset(self::WMI_MAKES[$prefix])) {
                $makeHint = self::WMI_MAKES[$prefix];
                break;
            }
        }

        return ['valid' => true, 'year' => $year, 'make_hint' => $makeHint];
    }
}
