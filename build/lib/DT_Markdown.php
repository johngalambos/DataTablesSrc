<?php
require_once('markdown_extended.php');// or die('Run ./make.sh thirdparty');

/*
 * Extensions to Markdown Extra Extended, specifically for the DataTables
 * project:
 *
 * * Inline code blocks:
 *    * `dt-init {DataTables initialisation parameter}` - Link to init parameter
 *      documentation showing as inline code.
 *    * `dt-api {DataTables API method}` - Link to API method documentation
 *      showing as inline code.
 *    * `tag {code}` - code tag with class "tag" (for styling HTML tags)
 *    * `path {code}` - code tag with class "path" (for styling file system
 *      paths)
 */

function DT_Markdown($text, $default_claases = array()){
	$parser = new DT_Markdown_Parser($default_claases);
	return $parser->transform($text);
}

class DT_Markdown_Parser extends MarkdownExtraExtended_Parser {
	function DT_Markdown_Parser()
	{
		$this->block_gamut['doColumns'] = 13;
		$this->block_gamut['doGrid'] = 12;
		parent::MarkdownExtra_Parser();
	}


	function makeCodeSpan( $code )
	{
		$that = $this;
		$text = preg_replace_callback(
			'/^(dt\-init |dt\-api |dt\-event |dt\-tag |tag |dt\-path |dt\-type |path |dt\-string |string )?(.*)$/m',
			function ( $matches ) use (&$that) {
				$html = htmlspecialchars(trim($matches[2]), ENT_NOQUOTES);

				if ( $matches[1] === 'dt-init ' ) {
					$formatted =
						'<a href="//datatables.net/reference/option/'.$this->_doUrlEncode($matches[2]).'">'.
							'<code class="option" title="Initialisation option">'.$html.'</code>'.
						'</a>';
				}
				else if ( $matches[1] === 'dt-api ' ) {
					$formatted =
						'<a href="//datatables.net/reference/api/'.$this->_doUrlEncode($matches[2]).'">'.
							'<code class="api" title="API method">'.$html.'</code>'.
						'</a>';
				}
				else if ( $matches[1] === 'dt-event ' ) {
					$formatted =
						'<a href="//datatables.net/reference/event/'.$this->_doUrlEncode($matches[2]).'">'.
							'<code class="event" title="Event">'.$html.'</code>'.
						'</a>';
				}
				else if ( $matches[1] === 'dt-type ' ) {
					$formatted =
						'<a href="//datatables.net/reference/type/'.$this->_doUrlEncode($matches[2]).'">'.
							'<code class="type" title="Parameter type">'.$html.'</code>'.
						'</a>';
				}
				else if ( $matches[1] === 'tag ' || $matches[1] === 'dt-tag ' ) {
					$formatted = '<code class="tag" title="HTML tag">'.$html.'</code>';
				}
				else if ( $matches[1] === 'path ' || $matches[1] === 'dt-path ' ) {
					$formatted = '<code class="path" title="File path">'.$html.'</code>';
				}
				else if ( $matches[1] === 'string ' || $matches[1] === 'dt-string ' ) {
					$formatted = '<code class="string" title="String">'.$html.'</code>';
				}
				else {
					$formatted = '<code>'.$html.'</code>';
				}

				return $that->hashPart( $formatted );
			},
			$code
		);

		return $text;
	}

	function _doUrlEncode ( $url ) {
		// rfc3986 says that ( and ) and valid in a URL and it makes the methods look nicer
		$str = urlencode( $url );
		$str = str_replace('%28', '(', $str);
		$str = str_replace('%29', ')', $str);
		return $str;
	}

	// Automatically add anchor tags to headers
	function _doHeaders_callback_setext($matches) {
		if ($matches[3] == '-' && preg_match('{^- }', $matches[1]))
			return $matches[0];
		$level = $matches[3]{0} == '=' ? 1 : 2;
		$attr  = $this->_doHeaders_attr($id =& $matches[2]);

		$text = $this->runSpanGamut($matches[1]);
		$anchor = $this->_doHeaderAnchor( $text );
		$block =
			'<a name="'.$anchor.'"></a>'.
			"<h$level$attr data-anchor=\"".$anchor."\">".$text."</h$level>";
		return "\n" . $this->hashBlock($block) . "\n\n";
	}

	function _doHeaders_callback_atx($matches) {
		$level = strlen($matches[1]);
		$attr  = $this->_doHeaders_attr($id =& $matches[3]);

		$text = $this->runSpanGamut($matches[2]);

		if ( $level <= 3 ) {
			$anchor = $this->_doHeaderAnchor( $text );
			$block =
				'<a name="'.$anchor.'"></a>'.
				"<h$level$attr data-anchor=\"".$anchor."\">".$text."</h$level>";
		}
		else {
			$block = "<h$level$attr>".$text."</h$level>";
		}
		return "\n" . $this->hashBlock($block) . "\n\n";
	}


	function _doHeaderAnchor( $text ) {
		$text = strip_tags( $text );
		return str_replace(' ', '-', $text);
	}



	function doGrid ( $text ) {
		$that = $this;
		$text = preg_replace_callback('
			{
				^
				# 1: Opening marker
				(
					\|{3,} # Marker: three or more pipes
				)

				# 2: Content
				(
					(?>
						(?!\1 [ ]* \n)	# Not a closing marker.
						.*\n+
					)+
				)
				
				# Closing marker.
				\1 [ ]* \n
			}xm',
			function ($matches) use ($that) {
				$grid = '<div class="grid">'.$this->runBlockGamut($matches[2]).'</div>';
				return "\n\n".$that->hashBlock($grid)."\n\n";
			},
			$text
		);
		
		return $text;
	}


	function doColumns ( $text ) {
		$that = $this;
		$text = preg_replace_callback('
			{
				^
				# 1: Opening marker
				(
					\|{2} # Marker: two pipes
				)

				# 2: Column class
				[ ]?(.*?) [ ]* \n # Whitespace and newline following marker.
				
				# 3: Content
				(
					(?>
						(?!\1 [ ]* \n)	# Not a closing marker.
						.*\n+
					)+
				)
				
				# Closing marker.
				\1 [ ]* \n
			}xm',
			function ($matches) use ($that) {
				$column = '<div class="unit w-'.$matches[2].'">'.$this->runBlockGamut($matches[3]).'</div>';
				return "\n\n".$that->hashBlock($column)."\n\n";
			},
			$text
		);
		
		return $text;
	}
}
?>