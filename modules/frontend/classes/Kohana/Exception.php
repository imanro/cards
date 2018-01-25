<?php

use Frontend\Renderer\MainTemplate;

class Kohana_Exception extends Kohana_Kohana_Exception {

	use MainTemplate;

	public static $error_view = 'kohana/exception';

	public static function response(\Exception $e, $error_view = NULL)
	{
		$parent_response = self::gen_content($e, $error_view);

		if(strpos(self::$error_view_content_type,'text/html') === FALSE) {
			return self::response_json($e, $parent_response);
		} else {
			return self::response_html($e, $parent_response);
		}
	}

	public static function response_html(\Exception $e, Response $parent_response)
	{
		$response = Response::factory();

		// Copy parent response's status
		$response->status($parent_response->status());
		// Copy parent response's headers
		$response->headers($parent_response->headers());
		// set again content type (somewhile its not copyed)
		$response->headers('Content-Type', Kohana_Exception::$error_view_content_type.'; charset='.Kohana::$charset);
		// taking main template
		self::init_template();
		self::add_template_assets();
		self::$template->title = 'Error';
		self::$template->header = self::_render_header(FALSE);
		self::$template->content = $parent_response->body();
		$response->body(self::$template->render());
		return $response;
	}

	public static function response_json(\Exception $e, Response $parent_response)
	{
		$response = Response::factory();

		// Copy parent response's status
		$response->status($parent_response->status());
		// Copy parent response's headers
		$response->headers($parent_response->headers());
		$response->headers('Content-Type', Kohana_Exception::$error_view_content_type.'; charset='.Kohana::$charset);
		$response->body(json_encode(array('error' => $e->getMessage())));

		echo $response->send_headers()->body();
		// exiting here to prevent additional content to be added to json
		exit(1);
	}

	/**
	 * Get a Response object representing the exception
	 *
	 * @uses    Kohana_Exception::text
	 * @param   Exception  $e
	 * @return  Response
	 */
	public static function gen_content(Exception $e, $error_view = NULL)
	{
		try
		{
			// Get the exception information
			$class   = get_class($e);
			$code    = $e->getCode();
			$message = $e->getMessage();
			$file    = $e->getFile();
			$line    = $e->getLine();

			// Prepare the response object.
			$response = Response::factory();

			if (strpos(self::$error_view_content_type, 'text/html') !== FALSE)
			{
				// we need trace only in html exceptions, not json ones
				$trace = $e->getTrace();

				/**
				 * HTTP_Exceptions are constructed in the HTTP_Exception::factory()
				 * method.
				 * We need to remove that entry from the trace and overwrite
				 * the variables from above.
				 */
				if ($e instanceof HTTP_Exception and $trace[0]['function'] == 'factory')
				{
					extract(array_shift($trace));
				}

				if ($e instanceof ErrorException)
				{

					/**
					 * If XDebug is installed, and this is a fatal error,
					 * use XDebug to generate the stack trace
					 */
					if (function_exists('xdebug_get_function_stack') and $code == E_ERROR)
					{
						$trace = array_slice(array_reverse(xdebug_get_function_stack()), 4);

						foreach ($trace as & $frame)
						{
							/**
							 * XDebug pre 2.1.1 doesn't currently set the call type key
							 * http://bugs.xdebug.org/view.php?id=695
							 */
							if (!isset($frame['type']))
							{
								$frame['type'] = '??';
							}

							// Xdebug returns the words 'dynamic' and 'static' instead of using '->' and '::' symbols
							if ('dynamic' === $frame['type'])
							{
								$frame['type'] = '->';
							}
							elseif ('static' === $frame['type'])
							{
								$frame['type'] = '::';
							}

							// XDebug also has a different name for the parameters array
							if (isset($frame['params']) and !isset($frame['args']))
							{
								$frame['args'] = $frame['params'];
							}
						}
					}

					if (isset(Kohana_Exception::$php_errors[$code]))
					{
						// Use the human-readable error name
						$code = Kohana_Exception::$php_errors[$code];
					}
				}

				/**
				 * The stack trace becomes unmanageable inside PHPUnit.
				 *
				 * The error view ends up several GB in size, taking
				 * serveral minutes to render.
				 */
				if (defined('PHPUnit_MAIN_METHOD') or defined('PHPUNIT_COMPOSER_INSTALL') or defined('__PHPUNIT_PHAR__'))
				{
					$trace = array_slice($trace, 0, 2);
				}

				// Instantiate the error view.
				//self::
				if (is_null($error_view))
				{
					$error_view = self::$error_view;
				}

				$view = View::factory($error_view, get_defined_vars());

				self::$template_css[] = '/assets/css/exception.css';
				self::$template_js[] = '/assets/js/exception.js';
				// Set the response body
				$response->body($view->render());
			}

			// Set the response status
			$response->status(($e instanceof HTTP_Exception) ? $e->getCode() : 500);

			// Set the response headers
			$response->headers('Content-Type', Kohana_Exception::$error_view_content_type.'; charset='.Kohana::$charset);

		}
		catch (Exception $e)
		{
			/**
			 * Things are going badly for us, Lets try to keep things under control by
			 * generating a simpler response object.
			 */
			$response = Response::factory();
			$response->status(500);
			$response->headers('Content-Type', 'text/plain');
			$response->body(Kohana_Exception::text($e));
		}

		return $response;
	}

}