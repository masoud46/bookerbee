<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class Country extends Model {
	use HasFactory;

	/**
	 * Get translated and sorted country list.
	 *
	 * @return Illuminate\Database\Eloquent\Collection $countries
	 */
	public static function sortedList() {
		$countries = Country::orderBy('name')->get();

		if (LaravelLocalization::getCurrentLocale() !== "en") {
			// foreach ($countries as $country) {
			// 	$country->name = __($country->name);
			// }
			// $countries = $countries->sortBy("name", SORT_LOCALE_STRING);


			// not the best solution but it almost works!
			foreach ($countries as $country) {
				$country->name = __($country->name);
				$country->no_accents = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $country->name);
			}

			$countries = $countries->sortBy('no_accents');

			// // setlocale(LC_ALL, 'fr_FR.utf8');
			// // // $sorted = $countries->sortBy('name', SORT_LOCALE_STRING);
			// // $sorted = $countries->sort(function($a, $b) {
			// // 	return strcmp($a->name, $b->name);
			// // });
			// // dd($sorted->toArray());

			// $arr = array_column($countries->toArray(), 'name');
			// $collator = new Collator(LaravelLocalization::getCurrentLocaleRegional());
			// $collator->asort($arr);

			// $copy = $countries;
			// $i = 0;
			// // dd($countries[0], $copy[0]);
			// foreach (array_keys($arr) as $key) {
			// 	// $countries->replace([$i => $copy[$key]]);
			// 	$countries[$i] = $copy[$key];
			// 	$i++;
			// }
		}

		return $countries;
	}
}
