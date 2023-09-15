<?php
namespace Worksection;

class xmlToArray
{
	/**
	 * The array created by the parser which can be assigned to a variable with: $varArr = $domObj->array.
	 * @var array
	 */
	public $array;
	private $parser;
	private $pointer;


	/**
	* $domObj = new xmlToArrayParser($xml);
	* @param string $xml file/string
	*/
	public function __construct(string $xml)
	{
		$this->pointer =& $this->array;
		$this->parser = xml_parser_create("UTF-8");
		xml_set_object($this->parser, $this);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($this->parser, "tag_open", "tag_close");
		xml_set_character_data_handler($this->parser, "cdata");
		xml_parse($this->parser, ltrim($xml));
	}


	/**
	 * @param $parser
	 * @param $tag
	 * @param $attributes
	 */
	private function tag_open($parser, $tag, $attributes)
	{
		$this->convert_to_array($tag, '_');
		$idx = $this->convert_to_array($tag, 'cdata');
		if (isset($idx))
		{
			$this->pointer[$tag][$idx] = [
				'@idx' => $idx,
				'@parent' => &$this->pointer
			];
			$this->pointer =& $this->pointer[$tag][$idx];
		}
		else
		{
			$this->pointer[$tag] = ['@parent' => &$this->pointer];
			$this->pointer =& $this->pointer[$tag];
		}
		if (!empty($attributes)) $this->pointer['_'] = $attributes;
	}


	/**
	 * Adds the current elements content to the current pointer[cdata] array.
	 * @param $parser
	 * @param $cdata
	 */
	private function cdata($parser, $cdata)
	{
		if (isset($this->pointer['cdata'])) $this->pointer['cdata'] .= $cdata;
		else $this->pointer['cdata'] = $cdata;
	}


	/**
	 * @param $parser
	 * @param $tag
	 */
	private function tag_close($parser, $tag)
	{
		$current = &$this->pointer;
		if (isset($this->pointer['@idx'])) unset($current['@idx']);
		$this->pointer = &$this->pointer['@parent'];
		unset($current['@parent']);
		if (isset($current['cdata']) && count($current) == 1) $current = $current['cdata'];
		elseif (empty($current['cdata'])) unset($current['cdata']);
	}


	/**
	 * Converts a single element item into array(element[0]) if a second element of the same name is encountered.
	 * @param $tag
	 * @param $item
	 * @return mixed
	 */
	private function convert_to_array($tag, $item)
	{
		if (isset($this->pointer[$tag][$item]))
		{
			$content = $this->pointer[$tag];
			$this->pointer[$tag] = [(0) => $content];
			$idx = 1;
		}
		elseif (isset($this->pointer[$tag]))
		{
			if (is_array($this->pointer[$tag])) {
				$idx = count($this->pointer[$tag]);
			}
			if (!isset($this->pointer[$tag][0]))
			{
				foreach ($this->pointer[$tag] as $key => $value)
				{
					unset($this->pointer[$tag][$key]);
					$this->pointer[$tag][0][$key] = $value;
				}
			}
		}
		else $idx = null;
		return $idx;
	}
}
