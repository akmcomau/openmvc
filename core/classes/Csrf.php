<?php

namespace core\classes;

/**
 * Cross-site request forgery protection.
 *
 *  - Every non-GET request must carry the session's token, either as the
 *    'csrf_token' form field or the 'X-CSRF-Token' header.
 *  - Controller methods that change state on a GET request (delete links,
 *    run-action links, ...) must additionally come from this site, which is
 *    checked with the Sec-Fetch-Site header, falling back to the Referer.
 *  - injectIntoHtml() adds the token to every POST form in a page, and a
 *    small script that adds it to XHR/fetch requests and to forms built or
 *    submitted from JavaScript, so templates don't need changing.
 */
class Csrf {

	const FIELD = 'csrf_token';

	const HEADER = 'HTTP_X_CSRF_TOKEN';

	const SESSION_KEY = 'csrf_token';

	/**
	 * Get (creating if needed) this session's token
	 * @return \b string The token, or NULL if there is no session
	 */
	public static function getToken() {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			return NULL;
		}
		if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
			$_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
		}
		return $_SESSION[self::SESSION_KEY];
	}

	/**
	 * Get the token submitted with this request
	 * @return \b string The submitted token or NULL
	 */
	public static function getSubmittedToken() {
		if (isset($_SERVER[self::HEADER]) && is_string($_SERVER[self::HEADER])) {
			return $_SERVER[self::HEADER];
		}
		if (isset($_POST[self::FIELD]) && is_string($_POST[self::FIELD])) {
			return $_POST[self::FIELD];
		}
		if (isset($_GET[self::FIELD]) && is_string($_GET[self::FIELD])) {
			return $_GET[self::FIELD];
		}
		return NULL;
	}

	/**
	 * Does this request carry this session's token
	 * @return \b bool TRUE if the token is present and correct
	 */
	public static function hasValidToken() {
		$expected = isset($_SESSION[self::SESSION_KEY]) && is_string($_SESSION[self::SESSION_KEY]) ? $_SESSION[self::SESSION_KEY] : NULL;
		$submitted = self::getSubmittedToken();
		return $expected && $submitted && hash_equals($expected, $submitted);
	}

	/**
	 * Is this request method one that must carry a token
	 * @return \b bool TRUE for anything other than GET/HEAD/OPTIONS
	 */
	public static function isUnsafeMethod() {
		$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
		return !in_array($method, ['GET', 'HEAD', 'OPTIONS'], TRUE);
	}

	/**
	 * Did this request come from a page on this site (or was it typed in /
	 * bookmarked), as opposed to being triggered by another site
	 * @return \b bool TRUE if the request is same-origin
	 */
	public static function isSameOrigin() {
		// Modern browsers say exactly where a request came from
		if (isset($_SERVER['HTTP_SEC_FETCH_SITE'])) {
			return in_array($_SERVER['HTTP_SEC_FETCH_SITE'], ['same-origin', 'none'], TRUE);
		}

		// Otherwise fall back to the Origin / Referer
		$host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : NULL;
		foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $header) {
			if (!empty($_SERVER[$header]) && is_string($_SERVER[$header])) {
				$parts = parse_url($_SERVER[$header]);
				if (!$parts || !isset($parts['host'])) {
					return FALSE;
				}
				$origin = strtolower($parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '');
				return $host !== NULL && ($origin === $host || strtolower($parts['host']) === $host);
			}
		}

		// No evidence either way: only allow it with the token
		return FALSE;
	}

	/**
	 * Add the token to an HTML page: a hidden field in every POST form, a
	 * meta tag, and a script for requests made from JavaScript.  Only full
	 * documents are changed (JSON, CSV, fragments etc. are left alone).
	 * @param $html \b string The page
	 * @return \b string The page with the token added
	 */
	public static function injectIntoHtml($html) {
		$token = self::getToken();
		if (!$token || !is_string($html) || stripos($html, '</head>') === FALSE) {
			return $html;
		}

		// only touch responses that are (or default to) HTML
		foreach (headers_list() as $header) {
			if (stripos($header, 'content-type:') === 0 && stripos($header, 'text/html') === FALSE) {
				return $html;
			}
		}

		$escaped = htmlspecialchars($token, ENT_QUOTES);
		$field = '<input type="hidden" name="'.self::FIELD.'" value="'.$escaped.'" />';

		$html = preg_replace_callback('/<form\b[^>]*>/i', function($matches) use ($field) {
			if (preg_match('/\bmethod\s*=\s*["\']?post\b/i', $matches[0])) {
				return $matches[0].$field;
			}
			return $matches[0];
		}, $html);

		$script = '<meta name="csrf-token" content="'.$escaped.'" />'
			.'<script>'.self::clientScript($token).'</script>';

		return preg_replace('/<\/head>/i', $script.'</head>', $html, 1);
	}

	/**
	 * The JavaScript that adds the token to same-origin XHR/fetch requests
	 * and to POST forms built or submitted from script
	 * @param $token \b string The token
	 * @return \b string JavaScript source
	 */
	protected static function clientScript($token) {
		$t = json_encode($token, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		$f = json_encode(self::FIELD);
		return <<<JS
(function(){
var T={$t},F={$f};
function same(u){try{return new URL(u,location.href).origin===location.origin;}catch(e){return false;}}
function unsafe(m){return !/^(GET|HEAD|OPTIONS)$/i.test(m||'GET');}
var o=XMLHttpRequest.prototype.open,s=XMLHttpRequest.prototype.send;
XMLHttpRequest.prototype.open=function(m,u){this.__csrf=unsafe(m)&&same(u);return o.apply(this,arguments);};
XMLHttpRequest.prototype.send=function(){if(this.__csrf){try{this.setRequestHeader('X-CSRF-Token',T);}catch(e){}}return s.apply(this,arguments);};
if(window.fetch){var f=window.fetch;window.fetch=function(i,n){n=n||{};var u=(typeof i==='string')?i:(i&&i.url),m=n.method||(i&&i.method)||'GET';if(unsafe(m)&&same(u)){var h=new Headers(n.headers||(i&&i.headers)||{});h.set('X-CSRF-Token',T);n.headers=h;}return f.call(this,i,n);};}
function add(form){if(!form||!unsafe(form.getAttribute('method')||form.method)||!same(form.action||location.href))return;if(!form.querySelector('input[name="'+F+'"]')){var e=document.createElement('input');e.type='hidden';e.name=F;e.value=T;form.appendChild(e);}}
document.addEventListener('submit',function(e){add(e.target);},true);
var fs=HTMLFormElement.prototype.submit;HTMLFormElement.prototype.submit=function(){add(this);return fs.apply(this,arguments);};
})();
JS;
	}
}
