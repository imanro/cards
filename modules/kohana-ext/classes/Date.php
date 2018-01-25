<?php

class Date extends Kohana_Date {
	public static function to_format($value, $format = 'Y-m-d H:i:s')
	{
		return date($format, $value);
	}
}