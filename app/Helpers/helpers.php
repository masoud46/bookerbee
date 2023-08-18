<?php

// // default parameters for currency operations
// define('DEFAULT_CURRENCY_PARAMS', [
// 	'locale' => "fr_FR",
// 	'currency' => "EUR",
// 	'symbol' => false,
// ]);

// // form errors
// define('ERRORS', [
// 	'form' => __("Please correct the fields marked in red."),
// 	'all_required' => __("All fields are mandatory."),
// 	'required' => __("This field is required."),
// 	'email' => __("Please verify the email address."),
// 	'regex' =>
// 	[
// 		'global' => __("Please verify this field."),
// 		'code' => __("Please check the registration number."),
// 		'price' => __("Invalid price format."),
// 		'location' => __("Please fill in the secondary address, or choose another location."),
// 	],
// 	'unique' => [
// 		'email' => __("This email address is already in use."),
// 		'code' => __("This registration number is already in use."),
// 		'user_code' => __("This code is already in use."),
// 	],
// 	'numeric' => __("This field must be numeric."),
// 	'date' => __("Please enter a valid date."),
// ]);

/**
 * parse locale-aware string to amount
 *
 * @param String $amount
 * @param Array $params
 * @return Integer
 */

use Illuminate\Support\Facades\App;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

if (!function_exists('currency_parse')) {
    function currency_parse($amount, $params = null) {
        if (!$params) $params = app('DEFAULT_CURRENCY_PARAMS');

        $locale = LaravelLocalization::getCurrentLocaleRegional();

        $has_symbol = isset($params['symbol']) && $params['symbol'] === true;

        if ($has_symbol) {
            $formatter = new NumberFormatter($params['locale'] ?? $locale, NumberFormatter::CURRENCY);
            $formatter->setAttribute(NumberFormatter::GROUPING_USED, $params['grouping_used']);
            $currency = $params['currency'] ?? 'EUR';
            $output = $formatter->parseCurrency($amount, $currency);
        } else {
            $formatter = new NumberFormatter($params['locale'] ?? $locale, NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::GROUPING_USED, $params['grouping_used']);
            $formatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, "");
            $formatter->setSymbol(NumberFormatter::INTL_CURRENCY_SYMBOL, "");
            $output = $formatter->parse($amount);
        }

        return $output * 100;
    }
}

/**
 * format amount to locale-aware string
 *
 * @param Integer $amount
 * @param Array $params
 * @return String
 */
if (!function_exists('currency_format')) {
    function currency_format($amount, $fraction = false, $params = null) {
        if (!is_numeric($amount)) return 0;
        if (!$params) $params = app('DEFAULT_CURRENCY_PARAMS');

        $locale = LaravelLocalization::getCurrentLocaleRegional();

        $amount = intval($amount);
        $has_symbol = isset($params['symbol']) && $params['symbol'] === true;
        $formatter = new NumberFormatter($params['locale'] ?? $locale, NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::GROUPING_USED, $params['grouping_used']);

        if ($fraction || $amount % 100) {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        } else {
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
        }

        if (!$has_symbol) {
            $formatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, "");
            $formatter->setSymbol(NumberFormatter::INTL_CURRENCY_SYMBOL, "");
            $output = $formatter->format($amount / 100);
        } else {
            $output = $formatter->formatCurrency($amount / 100, $params['currency'] ?? "EUR");
        }

        return $output;
        // return $has_symbol
        // 	? $output
        // 	: preg_replace("/\s+/u", "", $output); // remove whitespace chars
    }
}


/**
 * currency regex
 *
 * @return String
 */
if (!function_exists('currency_regex')) {
    function currency_regex() {
        // TODO: write accurate locale aware regex validator for currency
        return App::getLocale() === "en"
            ? "/^(\d+(.\d{0,2})?)?$/"
            : "/^(\d+(,\d{0,2})?)?$/";
    }
}
