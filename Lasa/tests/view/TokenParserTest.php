<?php


use lasa\view\builder\PHPTokenParser;
/*
 * TokenParserTest.php
 */

class TokenParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @test
	 */
	function 普通のPHPコードのパース() {

		$codes = $this->getCodes();

		$parser = PHPTokenParser::getParser($codes);
		$code = $parser->cleanup();


		$this->assertStringContainsString("あいうえお", $code);
		$this->assertStringContainsString("{ return; }", $code);
	}

	/**
	 * @test
	 */
	function docCommentの受け取り() {

		/* Comment 形式 */
		$codes = [];
		$codes[] = "<?php ";
		$codes[] = '/*';
		$codes[] = ' * @hogehoge';
		$codes[] = ' */';
		$codes[] = 'return [];';
		$codes[] = '?>';
		$codes[] = '<h1>this is body</h1>';
		$codes = implode("\n", $codes);

		$parser = PHPTokenParser::getParser($codes);
		$code = $parser->cleanup();

		$this->assertStringContainsString("this is body", $code);

		$comment = $parser->getDocComment();
		$this->assertNotEmpty($comment);
		$this->assertStringContainsString("@hogehoge", $comment);

		/* DocComment形式 ----- */

		$codes = [];
		$codes[] = "<?php ";
		$codes[] = '/**';
		$codes[] = ' * @hogehoge';
		$codes[] = ' */';
		$codes[] = 'return [];';
		$codes[] = '?>';
		$codes[] = '<h1>this is body</h1>';
		$codes = implode("\n", $codes);

		$parser = PHPTokenParser::getParser($codes);
		$code = $parser->cleanup();

		$this->assertStringContainsString("this is body", $code);

		$comment = $parser->getDocComment();
		$this->assertNotEmpty($comment);
		$this->assertStringContainsString("@hogehoge", $comment);


		//inline comment test
		$codes = [];
		$codes[] = "<?php ";
		$codes[] = '// @hogehoge';
		$codes[] = '// @fugafuga';
		$codes[] = 'return [];';
		$codes[] = '?>';
		$codes[] = '<h1>this is body</h1>';
		$codes = implode("\n", $codes);

		$parser = PHPTokenParser::getParser($codes);
		$code = $parser->cleanup();

		$this->assertStringContainsString("this is body", $code);

		$comment = $parser->getDocComment();
		$this->assertNotEmpty($comment);
		$this->assertStringContainsString("@hogehoge", $comment);
		$this->assertStringContainsString("@fugafuga", $comment);
	}



	function getCodes() {
		return <<<'HTML'
ほげほげ<?php echo 100; ?>
<?php if($hoge == 100){ return; } ?>
あいうえお
HTML;
	}
}
