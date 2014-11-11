<?php

# Make the app and l10n play nice with Windows.
if (substr(PHP_OS, 0, 3) === 'WIN') { // || strpos(@php_uname(), 'ARCH')
	define('WINDOWS', true);
} else {
	define('WINDOWS', false);
}

/**
 * Convenience function to check on "empty()"
 *
 * @param mixed $var
 * @return bool Result
 */
function isEmpty($var = null) {
	if (empty($var)) {
		return true;
	}
	return false;
}

/**
 * Return of what type the specific value is
 *
 * //TODO: use Debugger::exportVar() instead?
 *
 * @param mixed $value
 * @return type (NULL, array, bool, float, int, string, object, unknown) + value
 */
function returns($value) {
	if ($value === null) {
		return 'NULL';
	} elseif (is_array($value)) {
		return '(array)' . '<pre>' . print_r($value, true) . '</pre>';
	} elseif ($value === true) {
		return '(bool)TRUE';
	} elseif ($value === false) {
		return '(bool)FALSE';
	} elseif (is_numeric($value) && is_float($value)) {
		return '(float)' . $value;
	} elseif (is_numeric($value) && is_int($value)) {
		return '(int)' . $value;
	} elseif (is_string($value)) {
		return '(string)' . $value;
	} elseif (is_object($value)) {
		return '(object)' . get_class($value) . '<pre>' . print_r($value, true) .
			'</pre>';
	} else {
		return '(unknown)' . $value;
	}
}

/**
 * Returns htmlentities - string
 *
 * ENT_COMPAT	= Will convert double-quotes and leave single-quotes alone.
 * ENT_QUOTES	= Will convert both double and single quotes. !!!
 * ENT_NOQUOTES = Will leave both double and single quotes unconverted.
 *
 * @param string $text
 * @return string Converted text
 */
function ent($text) {
	return (!empty($text) ? htmlentities($text, ENT_QUOTES, 'UTF-8') : '');
}

/**
 * Convenience method for htmlspecialchars_decode
 *
 * @param string $text Text to wrap through htmlspecialchars_decode
 * @return string Converted text
 */
function hDec($text, $quoteStyle = ENT_QUOTES) {
	if (is_array($text)) {
		return array_map('hDec', $text);
	}
	return trim(htmlspecialchars_decode($text, $quoteStyle));
}

/**
 * Convenience method for html_entity_decode
 *
 * @param string $text Text to wrap through htmlspecialchars_decode
 * @return string Converted text
 */
function entDec($text, $quoteStyle = ENT_QUOTES) {
	if (is_array($text)) {
		return array_map('entDec', $text);
	}
	return (!empty($text) ? trim(html_entity_decode($text, $quoteStyle, 'UTF-8')) : '');
}

/**
 * Focus is on the filename (without path)
 *
 * @param string filename to check on
 * @param string type (extension/ext, filename/file, basename/base, dirname/dir)
 * @return mixed
 */
function extractFileInfo($filename, $type = null) {
	if ($info = extractPathInfo($filename, $type)) {
		return $info;
	}
	$pos = strrpos($filename, '.');
	$res = '';
	switch ($type) {
		case 'extension':
		case 'ext':
			$res = ($pos !== false) ? substr($filename, $pos + 1) : '';
			break;
		case 'filename':
		case 'file':
			$res = ($pos !== false) ? substr($filename, 0, $pos) : '';
			break;
		default:
			break;
	}
	return $res;
}

/**
 * Uses native PHP function to retrieve infos about a filename etc.
 * Improves it by not returning non-file-name characters from url files if specified.
 * So "filename.ext?foo=bar#hash" would simply be "filename.ext" then.
 *
 * @param string filename to check on
 * @param string type (extension/ext, filename/file, basename/base, dirname/dir)
 * @param bool $fromUrl
 * @return mixed
 */
function extractPathInfo($filename, $type = null, $fromUrl = false) {
	switch ($type) {
		case 'extension':
		case 'ext':
			$infoType = PATHINFO_EXTENSION;
			break;
		case 'filename':
		case 'file':
			$infoType = PATHINFO_FILENAME;
			break;
		case 'basename':
		case 'base':
			$infoType = PATHINFO_BASENAME;
			break;
		case 'dirname':
		case 'dir':
			$infoType = PATHINFO_DIRNAME;
			break;
		default:
			$infoType = null;
	}
	$result = pathinfo($filename, $infoType);
	if ($fromUrl) {
		if (($pos = strpos($result, '#')) !== false) {
			$result = substr($result, 0, $pos);
		}
		if (($pos = strpos($result, '?')) !== false) {
			$result = substr($result, 0, $pos);
		}
	}
	return $result;
}

/**
 * Shows pr() messages, even with debug = 0.
 * Also allows additional customization.
 *
 * @param mixed $content
 * @param bool $collapsedAndExpandable
 * @param array $options
 * - class, showHtml, showFrom, jquery, returns, debug
 * @return string HTML
 */
function pre($var, $collapsedAndExpandable = false, $options = array()) {
	$defaults = array(
		'class' => 'cake-debug',
		'showHtml' => false, // Escape < and > (or manually escape with h() prior to calling this function)
		'showFrom' => false, // Display file + line
		'jquery' => null, // null => Auto - use jQuery (true/false to manually decide),
		'returns' => false, // Use returns(),
		'debug' => false // Show only with debug > 0
	);
	$options += $defaults;
	if ($options['debug'] && !Configure::read('debug')) {
		return '';
	}
	if (php_sapi_name() === 'cli') {
		return sprintf("\n%s\n", $options['returns'] ? returns($var) : print_r($var, true));
	}

	$res = '<div class="' . $options['class'] . '">';

	$pre = '';
	if ($collapsedAndExpandable) {
		$js = 'if (this.parentNode.getElementsByTagName(\'pre\')[0].style.display==\'block\') {this.parentNode.getElementsByTagName(\'pre\')[0].style.display=\'none\'} else {this.parentNode.getElementsByTagName(\'pre\')[0].style.display=\'block\'}';
		$jsJquery = 'jQuery(this).parent().children(\'pre\').slideToggle(\'fast\')';
		if ($options['jquery'] === true) {
			$js = $jsJquery;
		} elseif ($options['jquery'] !== false) {
			// auto
			$js = 'if (typeof jQuery == \'undefined\') {' . $js . '} else {' . $jsJquery . '}';
		}
		$res .= '<span onclick="' . $js . '" style="cursor: pointer; font-weight: bold">Debug</span>';
		if ($options['showFrom']) {
			$calledFrom = debug_backtrace();
			$from = '<em>' . substr(str_replace(ROOT, '', $calledFrom[0]['file']), 1) . '</em>';
			$from .= ' (line <em>' . $calledFrom[0]['line'] . '</em>)';
			$res .= '<div>' . $from . '</div>';
		}
		$pre = ' style="display: none"';
	}

	if ($options['returns']) {
		$var = returns($var);
	} else {
		$var = print_r($var, true);
	}
	$res .= '<pre' . $pre . '>' . $var . '</pre>';
	$res .= '</div>';
	return $res;
}

/**
 * Checks if the string [$haystack] contains [$needle]
 *
 * @param string $haystack Input string.
 * @param string $needle Needed char or string.
 * @return bool
 */
function contains($haystack, $needle, $caseSensitive = false) {
	$result = !$caseSensitive ? stripos($haystack, $needle) : strpos($haystack, $needle);
	return ($result !== false);
}

/**
 * Checks if the string [$haystack] starts with [$needle]
 *
 * @param string $haystack Input string.
 * @param string $needle Needed char or string.
 * @return bool
 */
function startsWith($haystack, $needle, $caseSensitive = false) {
	if ($caseSensitive) {
		return (mb_strpos($haystack, $needle) === 0);
	}
	return (mb_stripos($haystack, $needle) === 0);
}

/**
 * Checks if the String [$haystack] ends with [$needle]
 *
 * @param string $haystack Input string.
 * @param string $needle Needed char or string
 * @return bool
 */
function endsWith($haystack, $needle, $caseSensitive = false) {
	if ($caseSensitive) {
		return mb_strrpos($haystack, $needle) === mb_strlen($haystack) - mb_strlen($needle);
	}
	return mb_strripos($haystack, $needle) === mb_strlen($haystack) - mb_strlen($needle);
}
