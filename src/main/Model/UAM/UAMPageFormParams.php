<?php

namespace CloudflareBypass\Model\UAM;

use SimpleJavaScriptCompilation\DeclarationInterpreterImpl;
use SimpleJavaScriptCompilation\ExpressionInterpreterImpl;
use SimpleJavaScriptCompilation\Model\Context;
use SimpleJavaScriptCompilation\Model\DataType;
use SimpleJavaScriptCompilation\Model\DataType\CustomString;

/**
 * Class UAMPageFormParams
 *      - UAM page form parameters.
 *
 * @package CloudflareBypass\Model\UAM
 * @author Kyran Rana
 */
class UAMPageFormParams
{
    /**
     * Gets all input parameter values from UAM page.
     *
     * @param UAMPageAttributes $pageAttributes UAM page attributes
     * @return UAMPageFormParams UAM page form params.
     * @throws \ErrorException if JS evaluation fails
     */
    public static function getParamsFromPage(UAMPageAttributes $pageAttributes): UAMPageFormParams
    {
        $page = $pageAttributes->getPage();

        preg_match('/name="s" value="([^"]+)"/', $page, $sMatches);
        preg_match('/name="jschl_vc" value="([^"]+)"/', $page, $jschlVcMatches);
        preg_match('/name="pass" value="([^"]+)"/', $page, $passMatches);

        return new UAMPageFormParams($sMatches[1], $jschlVcMatches[1], $passMatches[1], self::getJschlAnswerFromPage($pageAttributes));
    }

    /**
     * Gets answer to JavaScript challenge.
     *
     * @param UAMPageAttributes $pageAttributes UAM page attributes.
     * @return float Answer to JavaScript challenge
     * @throws \ErrorException if JS evaluation fails
     */
    public static function getJschlAnswerFromPage(UAMPageAttributes $pageAttributes): float
    {
        $codeSnippets = UAMPageChallengeCode::getSnippetsFromPage($pageAttributes->getPage());

        $ctx = new Context();

        $ctx->setCtxFunc('e', 'SimpleJavaScriptCompilation\Model\FunctionMap\GlobalFunctionMap::atob');
        $ctx->setCtxFunc('g', 'SimpleJavaScriptCompilation\Model\FunctionMap\GlobalFunctionMap::stringFromCharCode');

        $ctx->setCtxVar('t', new CustomString(new DataType(['value' => '"' . $pageAttributes->getHost() . '"'])));
        $ctx->setCtxVar('k', ExpressionInterpreterImpl::instance()->interpretExpression($codeSnippets->getSecondaryChallengeCode(), new Context()));

        $ctx = DeclarationInterpreterImpl::instance()->interpretDeclarations($codeSnippets->getChallengeCode(), $ctx);

        return round($ctx->getCtxSum()->getDataType()->getValue(), 10);
    }

    // -------------------------------------------------------------------------------------------------------

    /**
     * S param
     *
     * @var string $s
     */
    private $s;

    /**
     * JSCHL VC param
     *
     * @var string $jschlVc
     */
    private $jschlVc;

    /**
     * PASS param
     *
     * @var string $pass
     */
    private $pass;

    /**
     * JSCHL answer param
     *
     * @var float $jschlAnswer
     */
    private $jschlAnswer;

    public function __construct(string $s, string $jschlVc, string $pass, float $jschlAnswer)
    {
        $this->s                = $s;
        $this->jschlVc          = $jschlVc;
        $this->pass             = $pass;
        $this->jschlAnswer      = $jschlAnswer;
    }

    /**
     * Gets S param
     *
     * @return string S param
     */
    public function getS(): string
    {
        return $this->s;
    }

    /**
     * Gets JSCHL VC param.
     *
     * @return string JSCHL VC param
     */
    public function getJschlVc(): string
    {
        return $this->jschlVc;
    }

    /**
     * Gets PASS param
     *
     * @return string
     */
    public function getPass(): string
    {
        return $this->pass;
    }

    /**
     * Gets JSCHL ANSWER param.
     *
     * @return float
     */
    public function getJschlAnswer(): float
    {
        return $this->jschlAnswer;
    }

    /**
     * Gets query string.
     *
     * @return string Query string.
     */
    public function getQueryString(): string
    {
        return http_build_query([
            's'                 => $this->getS(),
            'jschl_vc'          => $this->getJschlVc(),
            'pass'              => $this->getPass(),
            'jschl_answer'      => $this->getJschlAnswer()
        ]);
    }
}