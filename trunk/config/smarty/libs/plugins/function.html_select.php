<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {html_options} function plugin
 *
 * Type:     function<br>
 * Name:     html_options<br>
 * Purpose:  Prints the list of <option> tags generated from
 *           the passed parameters<br>
 * Params:
 * <pre>
 * - name       (optional) - string default "select"
 * - values     (required) - if no options supplied) - array
 * - options    (required) - if no values supplied) - associative array
 * - selected   (optional) - string default not set
 * - output     (required) - if not options supplied) - array
 * - id         (optional) - string default not set
 * - class      (optional) - string default not set
 * </pre>
 *
 * @link http://www.smarty.net/manual/en/language.function.html.options.php {html_image}
 *      (Smarty online manual)
 * @author Monte Ohrt <monte at ohrt dot com>
 * @author Ralf Strehle (minor optimization) <ralf dot strehle at yahoo dot de>
 * @param array                    $params   parameters
 * @param Smarty_Internal_Template $template template object
 * @return string
 * @uses smarty_function_escape_special_chars()
 */
function smarty_function_html_select($params, $template)
{
	require_once(SMARTY_PLUGINS_DIR . 'shared.escape_special_chars.php');

	$name = null;
	$options = null;
	$selected = null;
	$id = null;
	$class = null;
	$valueKey = 'id';
	$textKey = 'name';

	$extra = '';

	foreach ($params as $_key => $_val) {
		switch ($_key) {
			case 'name':
			case 'class':
			case 'id':
				$$_key = (string)$_val;
				break;

			case 'options':
				$options = (array)$_val;
				break;

			case 'value':
				$valueKey = $_val;
				break;
			
			case 'text':
				$textKey = $_val;
				break;

			case 'selected':
				if (is_array($_val)) {
					$selected = array_map('strval', array_values((array)$_val));
				} else {
					$selected = $_val;
				}
				break;

			default:
				if (!is_array($_val)) {
					$extra .= ' ' . $_key . '="' . smarty_function_escape_special_chars($_val) . '"';
				} else {
					trigger_error("html_options: extra attribute '$_key' cannot be an array", E_USER_NOTICE);
				}
				break;
		}
	}

	if (!isset($options)){ return ''; }
	
	/* raise error here? */

	$_html_result = '<select'. (empty($class) ? '' : ' class="'. $class .'"') . 
			(empty($id) ? '' : ' id="'. $id .'"') .
			(empty($name) ? '' : ' name="'. $name .'"') .'>';
	$_idx = 0;

	foreach ($options as $_val) {
		$_html_result .= smarty_function_html_options_optoutput($_val[$valueKey], $_val[$textKey], $selected, $id, $class, $_idx);
	}
	$_html_result .= '</select>';

	return $_html_result;
}

function smarty_function_html_options_optoutput($key, $value, $selected, $id, $class, &$idx)
{
	if (!is_array($value)) {
		$_html_result = '<option value="' . smarty_function_escape_special_chars($key) . '"';
		if (is_array($selected)) {
			if (in_array((string)$key, $selected)) {
				$_html_result .= ' selected="selected"';
			}
		} elseif ($key == $selected) {
			$_html_result .= ' selected="selected"';
		}
//		$_html_class = !empty($class) ? ' class="'.$class.' option"' : '';
//		$_html_id = !empty($id) ? ' id="'.$id.'-'.$idx.'"' : '';
		$_html_result .= '>' . smarty_function_escape_special_chars($value) . '</option>' . "\n";
		$idx++;
	} else {
		$_idx = 0;
		$_html_result = smarty_function_html_options_optgroup($key, $value, $selected, !empty($id) ? ($id.'-'.$idx) : null, $class, $_idx);
		$idx++;
	}
	return $_html_result;
}

function smarty_function_html_options_optgroup($key, $values, $selected, $id, $class, &$idx)
{
	$optgroup_html = '<optgroup label="' . smarty_function_escape_special_chars($key) . '">' . "\n";
	foreach ($values as $key => $value) {
		$optgroup_html .= smarty_function_html_options_optoutput($key, $value, $selected, $id, $class, $idx);
	}
	$optgroup_html .= "</optgroup>\n";
	return $optgroup_html;
}

?>