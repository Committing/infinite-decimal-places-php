<?php


/**
 * 
 * Escape fundamental limits of computer design for decimal places. (standard floating-point arithmetic ESCAPE)
 * 
 * This class allows for calculations up to inf decimal places, escaping the fixed-precision limitations of native floating-point types that exist in all computers today.
 * 
 * -------------------------------------------------------------------------------------------------------
 * 
 * AVAILABLE FUNCTIONS:
 * 
 * divide($big_number, $divide_by)
 * multiply($num1, $num2)
 * plus($num1, $num2)
 * minus($num1, $num2)
 * sum($array) - of all numbers in an array (total)
 * gt($num1, $num2) - greater than
 * lt($num1, $num2) - less than
 * gte($num1, $num2) - greater than or equal to
 * lte($num1, $num2)
 * eq($num1, $num2) - equal to
 * square_root()
 * subconscious_square_root()
 * 
 */



class math {


    public $max_decimals = 30;


    // echo $this->divide('100', '3');
    public function divide($big_number, $divide_by, $max_decimals = null) {

        if ($max_decimals == null) {
            $max_decimals = $this->max_decimals;
        }

        $numerator = (string) $big_number;
        $denominator = (string) $divide_by;

        // Handle division by zero
        // if (trim($denominator, '-0.') === '') {
        //     throw new Exception("Division by zero");
        // }

        // Handle signs
        $sign = 1;
        if ($numerator[0] == '-') {
            $sign *= -1;
            $numerator = ltrim($numerator, '-');
        }
        if ($denominator[0] == '-') {
            $sign *= -1;
            $denominator = ltrim($denominator, '-');
        }
        $result_sign = $sign < 0 ? '-' : '';

        // Split into integer and fractional parts for both numbers
        $parse_number = function($num) {
            $parts = explode('.', $num, 2);
            $integer = isset($parts[0]) ? $parts[0] : '0';
            $fraction = isset($parts[1]) ? $parts[1] : '';
            $integer = ltrim($integer, '0') ?: '0'; // Preserve at least '0'
            return [$integer, $fraction];
        };

        list($num_int, $num_frac) = $parse_number($numerator);
        list($denom_int, $denom_frac) = $parse_number($denominator);

        // Calculate decimal places for each
        $num_decimal_places = strlen($num_frac);
        $denom_decimal_places = strlen($denom_frac);
        $max_decimal = max($num_decimal_places, $denom_decimal_places);

        // Adjust numerator and denominator to integers by multiplying by 10^max_decimal
        $adjusted_num = $num_int . $num_frac . str_repeat('0', $max_decimal - $num_decimal_places);
        $adjusted_denom = $denom_int . $denom_frac . str_repeat('0', $max_decimal - $denom_decimal_places);

        // Remove leading zeros to avoid issues with bc functions
        $adjusted_num = ltrim($adjusted_num, '0') ?: '0';
        $adjusted_denom = ltrim($adjusted_denom, '0') ?: '0';

        // if ($adjusted_denom === '0') {
        //     throw new Exception("Division by zero");
        // }

        // Compute integer part and remainder
        $integer_part = bcdiv($adjusted_num, $adjusted_denom, 0);
        $remainder = bcmod($adjusted_num, $adjusted_denom);

        if ($remainder === '0') {
            return $result_sign . $integer_part;
        }

        $decimal_part = '';
        $remainders = [];

        // Perform long division for the decimal part (always show up to 200 digits)
        for ($i = 0; $i < $max_decimals && bccomp($remainder, '0') !== 0; $i++) {
            $remainder = bcmul($remainder, '10');
            $digit = bcdiv($remainder, $adjusted_denom, 0);
            $decimal_part .= $digit;
            $remainder = bcmod($remainder, $adjusted_denom);
        }

        $result = $result_sign . $integer_part . '.' . $decimal_part;

        // Trim trailing zeros if not needed
        $result = rtrim(rtrim($result, '0'), '.');

        return $result;
    }



    public function multiply($num1, $num2) {

        // if ( ! is_string($num1) ) { pr('not a string!'); }
        // if ( ! is_string($num2) ) { pr('not a string!'); }

        $num1 = (string) $num1;
        $num2 = (string) $num2;

        // Handle multiplication by zero
        if (trim($num1, '-0.') === '' || trim($num2, '-0.') === '') {
            return '0';
        }

        // Determine the result sign
        $sign = 1;
        if ($num1[0] === '-') {
            $sign *= -1;
            $num1 = ltrim($num1, '-');
        }
        if ($num2[0] === '-') {
            $sign *= -1;
            $num2 = ltrim($num2, '-');
        }
        $result_sign = $sign === -1 ? '-' : '';

        // Parse numbers into integer and fractional parts
        $parse_number = function($num) {
            $parts = explode('.', $num, 2);
            $integer = isset($parts[0]) ? ltrim($parts[0], '0') : '0';
            $integer = $integer === '' ? '0' : $integer;
            $fraction = isset($parts[1]) ? $parts[1] : '';
            return [$integer, $fraction];
        };

        list($num1_int, $num1_frac) = $parse_number($num1);
        list($num2_int, $num2_frac) = $parse_number($num2);

        // Calculate decimal places and scale numbers to integers
        $decimals1 = strlen($num1_frac);
        $decimals2 = strlen($num2_frac);
        $total_decimals = $decimals1 + $decimals2;

        $scale_num1 = bcadd(bcmul($num1_int, bcpow('10', $decimals1)), $num1_frac);
        $scale_num2 = bcadd(bcmul($num2_int, bcpow('10', $decimals2)), $num2_frac);

        $product = bcmul($scale_num1, $scale_num2);

        // Handle zero product
        if (bccomp($product, '0') === 0) {
            return '0';
        }

        // Format the result with proper decimal placement
        $product_len = strlen($product);
        if ($total_decimals === 0) {
            $result = $product;
        } else {
            if ($product_len > $total_decimals) {
                $integer_part = substr($product, 0, $product_len - $total_decimals);
                $decimal_part = substr($product, $product_len - $total_decimals);
            } else {
                $integer_part = '0';
                $decimal_part = str_pad($product, $total_decimals, '0', STR_PAD_LEFT);
            }

            // Clean up leading/trailing zeros
            $integer_part = ltrim($integer_part, '0') ?: '0';
            $decimal_part = rtrim($decimal_part, '0');
            
            $result = $decimal_part === '' 
                ? $integer_part 
                : $integer_part . '.' . $decimal_part;
        }

        return $result_sign . $result;
    }


    public function plus($num1, $num2)
    {
        return $this->add($num1, $num2);
    }

    public function add($num1, $num2) {
        // if ( ! is_string($num1) ) { pr('not a string!'); }
        // if ( ! is_string($num2) ) { pr('not a string!'); }

        $num1 = (string) $num1;
        $num2 = (string) $num2;

        if ($this->is_zero($num1)) return $this->custom_trim_zeros($num2);
        if ($this->is_zero($num2)) return $this->custom_trim_zeros($num1);

        $scale = max($this->get_decimal_places($num1), $this->get_decimal_places($num2));
        $result = bcadd($num1, $num2, $scale);
        return $this->custom_trim_zeros($result);
    }

    public function subtract($num1, $num2)
    {
        return $this->minus($num1, $num2);
    }

    public function minus($num1, $num2) {
        // if ( ! is_string($num1) ) { pr('not a string!'); }
        // if ( ! is_string($num2) ) { pr('not a string!'); }

        $num1 = (string) $num1;
        $num2 = (string) $num2;

        if ($this->is_zero($num2)) return $this->custom_trim_zeros($num1);
        if ($this->is_zero($num1)) return $this->custom_trim_zeros('-' . ltrim($num2, '-'));

        $scale = max($this->get_decimal_places($num1), $this->get_decimal_places($num2));
        $result = bcsub($num1, $num2, $scale);
        return $this->custom_trim_zeros($result);
    }

    // Helper functions
    public function is_zero($num) {
        return trim($num, '-0.') === '';
    }

    public function get_decimal_places($num) {
        $num = ltrim($num, '-');
        $parts = explode('.', $num, 2);
        return isset($parts[1]) ? strlen($parts[1]) : 0;
    }

    public function custom_trim_zeros($number) {
        $number = (string)$number;
        if (strpos($number, '.') !== false) {
            $number = rtrim(rtrim($number, '0'), '.');
        }
        return $number === '' ? '0' : $number;
    }


    public function sum($array) {
        $sum = '0';
        $sum_decimals = 0;

        foreach ($array as $element) {

            // Convert to string and validate numeric format
            $num_str = (string) $element;

            if (!preg_match('/^[-+]?(?:\d+\.?\d*|\.\d+)$/', $num_str)) {
                continue; // Skip invalid numbers
            }

            // Parse sign and components
            $sign = 1;
            if ($num_str[0] === '-') {
                $sign = -1;
                $num_str = substr($num_str, 1);
            } elseif ($num_str[0] === '+') {
                $num_str = substr($num_str, 1);
            }

            $parts = explode('.', $num_str, 2);
            $integer = ltrim($parts[0] ?? '0', '0') ?: '0';
            $fraction = $parts[1] ?? '';
            $current_decimals = strlen($fraction);

            // Build normalized number string
            $clean_num = $integer . ($fraction !== '' ? ".$fraction" : '');
            if ($sign === -1 && $clean_num !== '0') {
                $clean_num = '-' . $clean_num;
            }

            // Calculate required scale and perform addition
            $scale = max($sum_decimals, $current_decimals);
            $sum = bcadd($sum, $clean_num, $scale);
            
            // Update decimal count based on actual result
            $sum_parts = explode('.', $sum);
            $sum_decimals = isset($sum_parts[1]) ? strlen($sum_parts[1]) : 0;
        }

        return $this->custom_trim_zeros_sum($sum);
    }


    public function custom_trim_zeros_sum($number) {
        $number = (string)$number;
        if (strpos($number, '.') !== false) {
            $number = rtrim(rtrim($number, '0'), '.');
        }
        return $number === '' || $number === '-' ? '0' : $number;
    }















    public function gt($a, $b) {
        $a = (string) $a;
        $b = (string) $b;

        return $this->custom_compare($a, $b) === 1;
    }

    public function gte($a, $b) {
        $a = (string) $a;
        $b = (string) $b;
        
        return $this->custom_compare($a, $b) >= 0;
    }

    public function lt($a, $b) {
        $a = (string) $a;
        $b = (string) $b;
        
        return $this->custom_compare($a, $b) === -1;
    }

    public function lte($a, $b) {
        $a = (string) $a;
        $b = (string) $b;
        
        return $this->custom_compare($a, $b) <= 0;
    }

    public function eq($a, $b) {
        $a = (string) $a;
        $b = (string) $b;
        
        return $this->custom_compare($a, $b) === 0;
    }


    public function custom_compare($a, $b) {
        // Handle zero values quickly
        $a_zero = $this->is_zero($a);
        $b_zero = $this->is_zero($b);
        if ($a_zero && $b_zero) return 0;
        if ($a_zero) return $this->custom_compare_zero($b);
        if ($b_zero) return -$this->custom_compare_zero($a);

        // Parse both numbers
        $a_parsed = $this->parse_number($a);
        $b_parsed = $this->parse_number($b);

        // Handle different signs
        if ($a_parsed['sign'] !== $b_parsed['sign']) {
            return $a_parsed['sign'] > $b_parsed['sign'] ? 1 : -1;
        }

        // Align decimal places and compare
        $max_decimal = max($a_parsed['decimals'], $b_parsed['decimals']);
        $a_aligned = $a_parsed['integer'] . str_pad($a_parsed['fraction'], $max_decimal, '0');
        $b_aligned = $b_parsed['integer'] . str_pad($b_parsed['fraction'], $max_decimal, '0');

        // Compare lengths first (quick check)
        $a_len = strlen($a_aligned);
        $b_len = strlen($b_aligned);
        
        if ($a_len !== $b_len) {
            $result = $a_len > $b_len ? 1 : -1;
        } else {
            // Full string comparison
            $result = strcmp($a_aligned, $b_aligned);
            $result = $result > 0 ? 1 : ($result < 0 ? -1 : 0);
        }

        // Invert result for negative numbers
        return $a_parsed['sign'] === -1 ? -$result : $result;
    }

    public function custom_compare_zero($num) {
        $num = ltrim($num, '+');
        return ($num[0] === '-') ? 1 : -1;
    }

    public function parse_number($num) {
        $sign = 1;
        $num = ltrim((string)$num, '+');
        
        if ($num[0] === '-') {
            $sign = -1;
            $num = substr($num, 1);
        }

        $parts = explode('.', $num, 2);
        $integer = ltrim($parts[0] ?? '0', '0') ?: '0';
        $fraction = rtrim($parts[1] ?? '', '0');
        
        return [
            'sign' => $sign,
            'integer' => $integer,
            'fraction' => $fraction,
            'decimals' => strlen($parts[1] ?? '')
        ];
    }































    public function new_add($num1, $num2) {
        // if ( ! is_string($num1) ) { pr('not a string!'); }
        // if ( ! is_string($num2) ) { pr('not a string!'); }

        $num1 = (string) $num1;
        $num2 = (string) $num2;

        if ($this->is_zero($num1)) return $this->custom_trim_zeros($num2);
        if ($this->is_zero($num2)) return $this->custom_trim_zeros($num1);

        $a_parsed = $this->parse_number($num1);
        $b_parsed = $this->parse_number($num2);

        $a_sign = $a_parsed['sign'];
        $a_abs = $this->format_absolute($a_parsed);
        $b_sign = $b_parsed['sign'];
        $b_abs = $this->format_absolute($b_parsed);

        if ($a_sign === $b_sign) {
            $sum_abs = $this->add_positive($a_abs, $b_abs);
            $result = ($a_sign === -1 ? '-' : '') . $sum_abs;
        } else {
            $comparison = $this->custom_compare($a_abs, $b_abs);
            if ($comparison === 0) {
                return '0';
            } elseif ($comparison > 0) {
                $diff_abs = $this->subtract_positive($a_abs, $b_abs);
                $result = ($a_sign === -1 ? '-' : '') . $diff_abs;
            } else {
                $diff_abs = $this->subtract_positive($b_abs, $a_abs);
                $result = ($b_sign === -1 ? '-' : '') . $diff_abs;
            }
        }

        return $this->custom_trim_zeros($result);
    }

    public function new_minus($num1, $num2) {
        $num1 = (string) $num1;
        $num2 = (string) $num2;

        $negative_num2 = $this->negate_number($num2);
        return $this->new_add($num1, $negative_num2);
    }

    private function format_absolute($parsed) {
        $integer = $parsed['integer'];
        $fraction = $parsed['fraction'];
        $absolute = $integer;
        if ($fraction !== '') {
            $absolute .= '.' . $fraction;
        }
        return $absolute;
    }

    private function negate_number($num) {
        if ($this->is_zero($num)) {
            return '0';
        }
        if ($num[0] === '-') {
            return ltrim($num, '-');
        } else {
            return '-' . $num;
        }
    }

    private function add_positive($a, $b) {
        list($int_a, $frac_a) = $this->split_number($a);
        list($int_b, $frac_b) = $this->split_number($b);

        $max_frac = max(strlen($frac_a), strlen($frac_b));
        $frac_a = str_pad($frac_a, $max_frac, '0', STR_PAD_RIGHT);
        $frac_b = str_pad($frac_b, $max_frac, '0', STR_PAD_RIGHT);

        $max_int = max(strlen($int_a), strlen($int_b));
        $int_a = str_pad($int_a, $max_int, '0', STR_PAD_LEFT);
        $int_b = str_pad($int_b, $max_int, '0', STR_PAD_LEFT);

        $num_a = $int_a . $frac_a;
        $num_b = $int_b . $frac_b;

        $sum = $this->add_strings($num_a, $num_b);

        $total_length = strlen($sum);
        $decimal_position = $total_length - $max_frac;

        if ($decimal_position <= 0) {
            $sum = str_pad($sum, $max_frac + 1, '0', STR_PAD_LEFT);
            $decimal_position = 1;
        }

        $integer_part = substr($sum, 0, $decimal_position);
        $fraction_part = substr($sum, $decimal_position);

        $integer_part = ltrim($integer_part, '0') ?: '0';
        $fraction_part = rtrim($fraction_part, '0');

        $result = $integer_part;
        if ($fraction_part !== '') {
            $result .= '.' . $fraction_part;
        }

        return $result;
    }

    private function subtract_positive($a, $b) {
        list($int_a, $frac_a) = $this->split_number($a);
        list($int_b, $frac_b) = $this->split_number($b);

        $max_frac = max(strlen($frac_a), strlen($frac_b));
        $frac_a = str_pad($frac_a, $max_frac, '0', STR_PAD_RIGHT);
        $frac_b = str_pad($frac_b, $max_frac, '0', STR_PAD_RIGHT);

        $max_int = max(strlen($int_a), strlen($int_b));
        $int_a = str_pad($int_a, $max_int, '0', STR_PAD_LEFT);
        $int_b = str_pad($int_b, $max_int, '0', STR_PAD_LEFT);

        $num_a = $int_a . $frac_a;
        $num_b = $int_b . $frac_b;

        $comparison = $this->compare_strings($num_a, $num_b);

        if ($comparison < 0) {
            $result = $this->subtract_strings($num_b, $num_a);
            $sign = '-';
        } else {
            $result = $this->subtract_strings($num_a, $num_b);
            $sign = '';
        }

        $total_length = strlen($result);
        $decimal_position = $total_length - $max_frac;

        if ($decimal_position <= 0) {
            $result = str_pad($result, $max_frac + 1, '0', STR_PAD_LEFT);
            $decimal_position = 1;
        }

        $integer_part = substr($result, 0, $decimal_position);
        $fraction_part = substr($result, $decimal_position);

        $integer_part = ltrim($integer_part, '0') ?: '0';
        $fraction_part = rtrim($fraction_part, '0');

        $result_str = $integer_part;
        if ($fraction_part !== '') {
            $result_str .= '.' . $fraction_part;
        }

        return $sign . $result_str;
    }

    private function split_number($num) {
        $parts = explode('.', $num, 2);
        $integer = isset($parts[0]) ? $parts[0] : '0';
        $fraction = isset($parts[1]) ? $parts[1] : '';
        $integer = ltrim($integer, '0') ?: '0';
        return array($integer, $fraction);
    }

    private function compare_strings($a, $b) {
        $len_a = strlen($a);
        $len_b = strlen($b);

        if ($len_a > $len_b) return 1;
        if ($len_a < $len_b) return -1;

        for ($i = 0; $i < $len_a; $i++) {
            $digit_a = (int) $a[$i];
            $digit_b = (int) $b[$i];
            if ($digit_a > $digit_b) return 1;
            if ($digit_a < $digit_b) return -1;
        }

        return 0;
    }

    private function subtract_strings($a, $b) {
        $length = strlen($a);
        $result = '';
        $borrow = 0;

        for ($i = $length - 1; $i >= 0; $i--) {
            $digit_a = (int) $a[$i];
            $digit_b = (int) $b[$i];
            $digit_a -= $borrow;
            $borrow = 0;

            if ($digit_a < $digit_b) {
                $digit_a += 10;
                $borrow = 1;
            }

            $result = (string) ($digit_a - $digit_b) . $result;
        }

        $result = ltrim($result, '0') ?: '0';
        return $result;
    }

    private function add_strings($a, $b) {
        $length = strlen($a);
        $result = '';
        $carry = 0;

        for ($i = $length - 1; $i >= 0; $i--) {
            $digit_a = (int) $a[$i];
            $digit_b = (int) $b[$i];
            $sum = $digit_a + $digit_b + $carry;
            $carry = (int) ($sum / 10);
            $result = (string) ($sum % 10) . $result;
        }

        if ($carry > 0) {
            $result = (string) $carry . $result;
        }

        return $result;
    }







    public function square_root($number, $max_decimals = null) {

        if ($max_decimals == null) {
            $max_decimals = $this->max_decimals;
        }

        if ($this->lt($number, '0')) {
            throw new Exception("Square root of negative number is not real.");
        }
        if ($this->eq($number, '0')) {
            return '0';
        }

        $parts = explode('.', (string)$number, 2);
        $integer_part = isset($parts[0]) ? $parts[0] : '0';
        $fractional_part = isset($parts[1]) ? $parts[1] : '';

        $integer_part = ltrim($integer_part, '0') ?: '0'; // Ensure '0' if empty

        $integer_pairs = $this->split_integer_into_pairs($integer_part);
        $fractional_pairs = $this->split_fractional_into_pairs($fractional_part);

        // Add enough pairs to reach max_decimals
        $existing_fractional = count($fractional_pairs);
        $additional = max(0, $max_decimals - $existing_fractional);
        $fractional_pairs = array_merge($fractional_pairs, array_fill(0, $additional, '00'));

        $all_pairs = array_merge($integer_pairs, $fractional_pairs);

        $current_remainder = '0';
        $current_result = '0'; // Tracks the current result as a string number
        $result_digits = '';

        foreach ($all_pairs as $pair) {
            $current_remainder = $this->multiply($current_remainder, '100');
            $current_remainder = $this->add($current_remainder, $pair);

            $x = '0';
            for ($candidate = 9; $candidate >= 0; $candidate--) {
                $candidate_str = (string)$candidate;
                $temp = $this->add($this->multiply($current_result, '20'), $candidate_str);
                $product = $this->multiply($temp, $candidate_str);
                if ($this->lte($product, $current_remainder)) {
                    $x = $candidate_str;
                    break;
                }
            }

            $current_remainder = $this->subtract($current_remainder, $this->multiply($this->add($this->multiply($current_result, '20'), $x), $x));
            $result_digits .= $x;
            $current_result = $this->add($this->multiply($current_result, '10'), $x);
        }

        $integer_digit_count = count($integer_pairs);
        $integer_part_result = substr($result_digits, 0, $integer_digit_count);
        $fraction_part_result = substr($result_digits, $integer_digit_count);

        // Format the result
        $formatted = $integer_part_result;
        if ($fraction_part_result !== '') {
            $formatted .= '.' . $fraction_part_result;
        }

        // Trim leading zeros in the integer part (but leave at least one zero)
        $formatted = ltrim($formatted, '0') ?: '0';
        if (strpos($formatted, '.') === 0) {
            $formatted = '0' . $formatted;
        }

        // Trim trailing zeros after the decimal point
        if (($dot_pos = strpos($formatted, '.')) !== false) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $formatted;
    }

    private function split_integer_into_pairs($integer_part) {
        $len = strlen($integer_part);
        $pairs = [];
        $start = 0;

        if ($len % 2 != 0) {
            $pairs[] = substr($integer_part, 0, 1);
            $start = 1;
        }

        for ($i = $start; $i < $len; $i += 2) {
            $pairs[] = substr($integer_part, $i, 2);
        }

        return $pairs;
    }

    private function split_fractional_into_pairs($fractional_part) {
        $len = strlen($fractional_part);
        if ($len % 2 != 0) {
            $fractional_part .= '0';
        }
        $pairs = [];
        for ($i = 0; $i < strlen($fractional_part); $i += 2) {
            $pairs[] = substr($fractional_part, $i, 2);
        }
        return $pairs;
    }






    public function subconscious_square_root($number, $max_decimals = null) {

        if ($max_decimals == null) {
            $max_decimals = $this->max_decimals;
        }

        if ($this->lt($number, '0')) {
            throw new Exception("Square root of negative number is not real.");
        }
        if ($this->eq($number, '0')) {
            return ['root' => '0', 'remainder' => '0'];
        }

        $parts = explode('.', (string)$number, 2);
        $integer_part = isset($parts[0]) ? $parts[0] : '0';
        $fractional_part = isset($parts[1]) ? $parts[1] : '';

        $integer_part = ltrim($integer_part, '0') ?: '0'; // Ensure '0' if empty

        $integer_pairs = $this->split_integer_into_pairs($integer_part);
        $fractional_pairs = $this->split_fractional_into_pairs($fractional_part);

        // Add enough pairs to reach max_decimals
        $existing_fractional = count($fractional_pairs);
        $additional = max(0, $max_decimals - $existing_fractional);
        $fractional_pairs = array_merge($fractional_pairs, array_fill(0, $additional, '00'));

        $all_pairs = array_merge($integer_pairs, $fractional_pairs);

        $current_remainder = '0';
        $current_result = '0'; // Tracks the current result as a string number
        $result_digits = '';

        foreach ($all_pairs as $pair) {
            $current_remainder = $this->multiply($current_remainder, '100');
            $current_remainder = $this->add($current_remainder, $pair);

            $x = '0';
            for ($candidate = 9; $candidate >= 0; $candidate--) {
                $candidate_str = (string)$candidate;
                $temp = $this->add($this->multiply($current_result, '20'), $candidate_str);
                $product = $this->multiply($temp, $candidate_str);
                if ($this->lte($product, $current_remainder)) {
                    $x = $candidate_str;
                    break;
                }
            }

            $current_remainder = $this->subtract($current_remainder, $this->multiply($this->add($this->multiply($current_result, '20'), $x), $x));
            $result_digits .= $x;
            $current_result = $this->add($this->multiply($current_result, '10'), $x);
        }

        $integer_digit_count = count($integer_pairs);
        $integer_part_result = substr($result_digits, 0, $integer_digit_count);
        $fraction_part_result = substr($result_digits, $integer_digit_count);

        // Format the result
        $formatted = $integer_part_result;
        if ($fraction_part_result !== '') {
            $formatted .= '.' . $fraction_part_result;
        }

        // Trim leading zeros in the integer part (but leave at least one zero)
        $formatted = ltrim($formatted, '0') ?: '0';
        if (strpos($formatted, '.') === 0) {
            $formatted = '0' . $formatted;
        }

        // Trim trailing zeros after the decimal point
        if (($dot_pos = strpos($formatted, '.')) !== false) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        // After calculating $formatted (the root approximation)
        $root = $formatted;
        $root_squared = $this->multiply($root, $root);
        $remainder = $this->subtract($number, $root_squared);

        return [
            'root' => $root,
            'remainder' => $remainder
        ];

        return $formatted;
    }





}





