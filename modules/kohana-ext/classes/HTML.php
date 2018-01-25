<?php

/**
 * Extendeding kohana's html with some of yiisoft...
 */
class HTML extends Kohana_HTML {

	public static $void_elements = array(
		'area' => 1,
		'base' => 1,
		'br' => 1,
		'col' => 1,
		'command' => 1,
		'embed' => 1,
		'hr' => 1,
		'img' => 1,
		'input' => 1,
		'keygen' => 1,
		'link' => 1,
		'meta' => 1,
		'param' => 1,
		'source' => 1,
		'track' => 1,
		'wbr' => 1
	);

	/**
	 * Create HTML link anchors. Note that the title is not escaped, to allow
	 * HTML elements within links (images, etc).
	 *
	 * @param   string  $uri        URL or URI string
	 * @param   string  $title      link text
	 * @param   array   $attributes HTML anchor attributes
	 * @param   mixed   $protocol   protocol to pass to URL::base()
	 * @param   boolean $index      include the index page
	 *
	 * @return  string
	 */
	public static function link($uri, $title = NULL, array $attributes = NULL, $protocol = NULL, $index = TRUE)
	{
		return self::anchor($uri, $title, $attributes, $protocol, $index);
	}

	/**
	 * Creates tag
	 *
	 * @param string $name
	 * @param string $content
	 * @param array $options
	 *
	 * @return string
	 */
	public static function tag($name, $content = '', $options = array())
	{
		foreach($options as $key => $value){
			//$options[$key] = str_replace('"', '\'', $value);
			//$options[$key] = str_replace('"', '\'', $value);
			$options[$key] = addslashes($value);
			//$options[$key] = self::chars($value);
		}

		$html = "<$name" . self::attributes($options) . '>';
		return isset(static::$void_elements[strtolower($name)]) ? $html : "$html$content</$name>";
	}
}