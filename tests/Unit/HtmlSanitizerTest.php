<?php

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use core\classes\HtmlSanitizer;

class HtmlSanitizerTest extends TestCase {

	public function testKeepsViewBuilderMarkupIntact(): void {
		$html = '<div class="grid-stack-item" data-gs-x="0" data-gs-width="4" id="widget-3" style="left: 0px">'
			.'<input type="hidden" name="config" value="{&quot;device&quot;:&quot;12&quot;,&quot;title&quot;:&quot;Tank&quot;}">'
			.'<div class="top">Level é</div></div>';
		$this->assertSame($html, HtmlSanitizer::sanitize($html));
	}

	public function testRemovesScriptCapableElements(): void {
		$out = HtmlSanitizer::sanitize('<div>a</div><script>alert(1)</script><iframe src="//x"></iframe><svg onload=alert(1)></svg><object data="x"></object>');
		$this->assertSame('<div>a</div>', $out);
	}

	public function testRemovesEventHandlers(): void {
		$out = HtmlSanitizer::sanitize('<img src="x" onerror="alert(1)"><div ONCLICK="x" class="k">b</div>');
		$this->assertSame('<img src="x"><div class="k">b</div>', $out);
	}

	public function testRemovesJavascriptUrlsIncludingObfuscated(): void {
		$out = HtmlSanitizer::sanitize('<a href="javascript:alert(1)">a</a><a href="java&#x09;script:alert(1)">b</a><a href=" JaVaScRiPt:x">c</a><a href="/ok">d</a>');
		$this->assertSame('<a>a</a><a>b</a><a>c</a><a href="/ok">d</a>', $out);
	}

	public function testDataUrlsOnlyForRasterImages(): void {
		$out = HtmlSanitizer::sanitize('<img src="data:image/png;base64,AAAA"><a href="data:text/html,<script>x</script>">x</a>');
		$this->assertSame('<img src="data:image/png;base64,AAAA"><a>x</a>', $out);
	}

	public function testRemovesCommentsAndDangerousStyles(): void {
		$out = HtmlSanitizer::sanitize('<!--[if IE]><script>x</script><![endif]--><div style="width: expression(alert(1))">x</div>');
		$this->assertSame('<div>x</div>', $out);
	}

	public function testEscapesTextContent(): void {
		$this->assertSame('<p>&lt;b&gt; &amp; "q"</p>', HtmlSanitizer::sanitize('<p>&lt;b&gt; &amp; "q"</p>'));
	}

	public function testEmptyInput(): void {
		$this->assertSame('', HtmlSanitizer::sanitize(''));
		$this->assertSame('', HtmlSanitizer::sanitize(NULL));
	}
}
