<?php

namespace core\classes;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Removes active content from user-supplied HTML (e.g. dashboard/view layouts
 * built in the browser and stored server side) so it can be rendered to other
 * users without running script in their session.
 *
 * Removed: script-capable elements (script, iframe, object, embed, svg, ...),
 * every on* event handler attribute, and javascript:/vbscript:/data: URLs in
 * URL attributes.  Ordinary markup, classes, ids, data-* attributes, inline
 * styles and form inputs are kept as they are.
 */
class HtmlSanitizer {

	/**
	 * Elements removed together with everything inside them
	 */
	const REMOVED_ELEMENTS = [
		'script', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
		'base', 'link', 'meta', 'style', 'svg', 'math', 'template', 'noscript',
		'form', 'portal',
	];

	/**
	 * Attributes that hold a URL
	 */
	const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'xlink:href', 'background', 'poster', 'srcset', 'data', 'codebase', 'cite', 'lowsrc', 'dynsrc'];

	/**
	 * Sanitise an HTML fragment
	 * @param $html \b string The HTML fragment
	 * @return \b string The sanitised fragment
	 */
	public static function sanitize($html) {
		if (!is_string($html) || trim($html) === '') {
			return is_string($html) ? $html : '';
		}

		$previous = libxml_use_internal_errors(TRUE);
		$doc = new DOMDocument('1.0', 'UTF-8');
		$doc->loadHTML(
			'<?xml encoding="UTF-8"><div id="__sanitize_root__">'.$html.'</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
		);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		$root = $doc->getElementById('__sanitize_root__');
		if (!$root) {
			// couldn't parse it; refuse to pass it through
			return htmlspecialchars($html);
		}

		self::cleanNode($root);

		// Serialise ourselves rather than with saveHTML(), which would switch
		// attributes containing '"' to single quotes; this keeps the same
		// "...&quot;..." form the browser's innerHTML produced
		return self::serializeChildren($root);
	}

	const VOID_ELEMENTS = ['area', 'br', 'col', 'embed', 'hr', 'img', 'input', 'keygen', 'source', 'track', 'wbr', 'param'];

	protected static function serializeChildren(DOMNode $node) {
		$output = '';
		foreach ($node->childNodes as $child) {
			if ($child instanceof DOMElement) {
				$name = strtolower($child->nodeName);
				$output .= '<'.$name;
				foreach ($child->attributes as $attribute) {
					$output .= ' '.$attribute->nodeName.'="'.htmlspecialchars($attribute->nodeValue, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8').'"';
				}
				$output .= '>';
				if (!in_array($name, self::VOID_ELEMENTS, TRUE)) {
					$output .= self::serializeChildren($child).'</'.$name.'>';
				}
			}
			elseif ($child->nodeType === XML_TEXT_NODE) {
				$output .= htmlspecialchars($child->nodeValue, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
			}
		}
		return $output;
	}

	protected static function cleanNode(DOMNode $node) {
		// iterate over a copy, since we remove nodes as we go
		$children = [];
		foreach ($node->childNodes as $child) {
			$children[] = $child;
		}

		foreach ($children as $child) {
			if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
				// conditional comments can carry markup for old browsers
				$node->removeChild($child);
				continue;
			}
			if (!($child instanceof DOMElement)) {
				continue;
			}

			$name = strtolower($child->nodeName);
			if (in_array($name, self::REMOVED_ELEMENTS, TRUE) || strpos($name, ':') !== FALSE) {
				$node->removeChild($child);
				continue;
			}

			self::cleanAttributes($child);
			self::cleanNode($child);
		}
	}

	protected static function cleanAttributes(DOMElement $element) {
		$remove = [];
		foreach ($element->attributes as $attribute) {
			$name = strtolower($attribute->nodeName);
			$value = $attribute->nodeValue;

			if (strpos($name, 'on') === 0) {
				$remove[] = $attribute->nodeName;
			}
			elseif (in_array($name, self::URL_ATTRIBUTES, TRUE) && self::isDangerousUrl($value)) {
				$remove[] = $attribute->nodeName;
			}
			elseif ($name == 'style' && preg_match('/expression\s*\(|javascript:|vbscript:|-moz-binding|behavior\s*:/i', self::normalise($value))) {
				$remove[] = $attribute->nodeName;
			}
		}
		foreach ($remove as $name) {
			$element->removeAttribute($name);
		}
	}

	protected static function normalise($value) {
		// strip whitespace/control characters browsers ignore inside schemes, e.g. "java\tscript:"
		return preg_replace('/[\x00-\x20]+/', '', html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	}

	protected static function isDangerousUrl($value) {
		// check each candidate in a srcset-style list as well as the whole value
		foreach (preg_split('/,/', self::normalise($value)) as $url) {
			if (preg_match('/^(javascript|vbscript|livescript):/i', $url)) {
				return TRUE;
			}
			// inline raster images are fine; any other data: URL is not
			if (preg_match('/^data:/i', $url) && !preg_match('/^data:image\/(png|gif|jpe?g|webp|bmp);/i', $url)) {
				return TRUE;
			}
		}
		return FALSE;
	}
}
