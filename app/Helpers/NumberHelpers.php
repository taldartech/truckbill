<?php

if (! function_exists('roundUpTdsAmount')) {
    function roundUpTdsAmount(float $amount): float
    {
        return ceil(round($amount, 10));
    }
}

if (! function_exists('formatIndianNumber')) {
    /**
     * Format a number using the Indian numbering system (lakh/crore grouping).
     * Example: 1050000 => "10,50,000.00".
     */
    function formatIndianNumber(float $number, int $decimals = 2): string
    {
        $isNegative = $number < 0;
        $number = abs($number);

        $rounded = number_format($number, $decimals, '.', '');
        [$integer, $fraction] = array_pad(explode('.', $rounded), 2, '');

        $lastThree = substr($integer, -3);
        $otherDigits = substr($integer, 0, -3);

        if ($otherDigits !== '') {
            $lastThree = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $otherDigits).','.$lastThree;
        }

        $formatted = $lastThree;
        if ($decimals > 0) {
            $formatted .= '.'.$fraction;
        }

        return ($isNegative ? '-' : '').$formatted;
    }
}

if (!function_exists('getIndianCurrency')) {
  function getIndianCurrency(float $number)
  {
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = [];
    $words = [
      0 => '',
      1 => 'one',
      2 => 'two',
      3 => 'three',
      4 => 'four',
      5 => 'five',
      6 => 'six',
      7 => 'seven',
      8 => 'eight',
      9 => 'nine',
      10 => 'ten',
      11 => 'eleven',
      12 => 'twelve',
      13 => 'thirteen',
      14 => 'fourteen',
      15 => 'fifteen',
      16 => 'sixteen',
      17 => 'seventeen',
      18 => 'eighteen',
      19 => 'nineteen',
      20 => 'twenty',
      30 => 'thirty',
      40 => 'forty',
      50 => 'fifty',
      60 => 'sixty',
      70 => 'seventy',
      80 => 'eighty',
      90 => 'ninety',
    ];
    $digits = ['', 'hundred', 'thousand', 'lakh', 'crore'];
    while ($i < $digits_length) {
      $divider = $i == 2 ? 10 : 100;
      $number = floor($no % $divider);
      $no = floor($no / $divider);
      $i += $divider == 10 ? 1 : 2;
      if ($number) {
        $plural = ($counter = count($str)) && $number > 9 ? 's' : null;
        $hundred = $counter == 1 && $str[0] ? ' and ' : null;
        $str[] =
          $number < 21
          ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred
          : $words[floor($number / 10) * 10] .
          ' ' .
          $words[$number % 10] .
          ' ' .
          $digits[$counter] .
          $plural .
          ' ' .
          $hundred;
      } else {
        $str[] = null;
      }
    }
    $Rupees = implode('', array_reverse($str));
    $paise = $decimal
      ? ($Rupees ? 'and ' : ' ') .
      ($decimal < 21
        ? $words[$decimal]
        : $words[floor($decimal / 10) * 10] . ' ' . $words[$decimal % 10]) .
      ' Paise'
      : '';
    return ucwords(($Rupees ? $Rupees . 'Rupees ' : '') . $paise);
  }
}
