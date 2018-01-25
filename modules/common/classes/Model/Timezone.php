<?php

namespace Common\Model;

use ORM;
use DateTime;
use DateTimeZone;

/**
 * @property int $id
 * @property string $name
 * @property string $utc
 * @property string $dst
 * @property string $code
 */
class Timezone extends ORM {

	const ID_MOSCOW = 300;

	const ID_KIEV = 355;

	var $_table_name = 'timezone';

	var $_table_columns = array(
		'id' => null,
			'name' => null,
			'country' => null,
			'utc' => null,
			'dst' => null,
			'code' => null,
	);

	/**
	 * Get offset in seconds
	 *
	 * @param Timezone $zone
	 * @return int
	 */
	public static function get_timezone_offset($zone_name)
	{
		$timezone = new DateTimeZone($zone_name);

		$timezone_utc = new DateTimeZone('UTC');
		$time = new DateTime('now', $timezone_utc);
		return $timezone->getOffset($time);
	}

	/**
	 * @param int $seconds
	 * @return string
	 */
	public static function format_offset($seconds)
	{
		$factor = $seconds >= 0 ? 1 : -1;

		return vsprintf('%+03d:%02d', array(
			floor($seconds / 3600 * $factor) * $factor,
			floor($seconds / 60 * $factor) % 60
		));
	}

	public static function format_utc($value)
	{
		return substr($value, 0, 3) . ':' . substr($value, 3);
	}

	public static function get_timezone_current_time($zone_name)
	{
		$timezone = new DateTimeZone($zone_name);
		$time = new DateTime('now', $timezone);
		return $time;
	}

	public function get_form_options_timezone()
	{
		$models = $this->order_by('name', 'ASC' )->find_all();

		$array = array();
		foreach($models as $model) {
			/* @var $model Timezone */
			$array[ $model->id ] = vsprintf('%s (%s)', array($model->name, self::format_utc($model->utc)));
		}

		return $array;
	}
}
