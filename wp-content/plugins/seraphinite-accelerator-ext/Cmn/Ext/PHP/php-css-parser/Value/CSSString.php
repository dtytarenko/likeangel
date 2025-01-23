<?php

namespace seraph_accel\Sabberworm\CSS\Value;

use seraph_accel\Sabberworm\CSS\Parsing\ParserState;
use seraph_accel\Sabberworm\CSS\Parsing\SourceException;
use seraph_accel\Sabberworm\CSS\Settings;

class CSSString extends PrimitiveValue {

	private $sString;
//	private $sQuote;

	public function __construct($sString, $iPos = 0/*, $sQuote = ''*/) {
		$this->sString = $sString;
		//$this->sQuote = $sQuote;
		parent::__construct($iPos);
	}

	public static function parse(ParserState $oParserState) {
		$sResult = '';

		$sQuote = $oParserState->peek();
		if( $sQuote === '\'' || $sQuote === '"' ) {
			$oParserState->consume(1, false);

			while (!$oParserState->isEnd()) {
				$sResult .= $oParserState->consumeExpression('@\\G[^\\r\\n\\\\' . $sQuote . ']*@S', true, false);
				if (!$oParserState->comes('\\'))
					break;

				$sChar = $oParserState->consumeEscCharacter();
				if ($sChar === null)
					throw new SourceException(Settings::ParseErrHigh, "Non-well-formed quoted string {$oParserState->peek(3)}", $oParserState->currentPos());

				$sResult .= $sChar;
			}

			$oParserState->consume($sQuote, false);
		}
		else {
			// Unquoted strings end in whitespace or with braces, brackets, parentheses
			while (!$oParserState->isEnd()) {
				$sResult .= $oParserState->consumeExpression('@\\G[^\\s{}()<>\\[\\]\\\\]*@S', true, false);
				if (!$oParserState->comes('\\'))
					break;

				$sResult .= $oParserState->consumeEscCharacter();
			}
		}

		return new CSSString($sResult, $oParserState->currentPos()/*, $sQuote*/);
	}

	public function setString($sString) {
		$this->sString = $sString;
	}

	public function getString() {
		return $this->sString;
	}

	public function __toString() {
		return $this->renderWhole(new \seraph_accel\Sabberworm\CSS\OutputFormat());
	}

	public function render(string &$sResult, \seraph_accel\Sabberworm\CSS\OutputFormat $oOutputFormat) {
		//if(!strlen($this->sString) && $this->sQuote === '')
		//    return '';
		$stringQuotingType = $oOutputFormat->getStringQuotingType();
		$sString = str_replace("\\ ", " ", $this->sString);
		$sString = str_replace("\\", "\\\\", $sString);
		$sString = str_replace($stringQuotingType, "\\" . $stringQuotingType, $sString);
		$sString = str_replace(array("\n", "\xFF"/*Look at \Parsing\ParserState.php:consumeEscCharacter()*/), array('\A', "\\"), $sString);

		$sResult .= $stringQuotingType;
		$sResult .= $sString;
		$sResult .= $stringQuotingType;
	}

}