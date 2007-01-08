<?php
/**
 * Squiz_Sniffs_Functions_FunctionDeclarationArgumentSpacingSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

require_once 'PHP/CodeSniffer/Sniff.php';

/**
 * Squiz_Sniffs_Functions_FunctionDeclarationArgumentSpacingSniff.
 *
 * Checks that arguments in function declarations are spaced correctly.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Squiz_Sniffs_Functions_FunctionDeclarationArgumentSpacingSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_FUNCTION);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $functionName = $phpcsFile->findNext(array(T_STRING), $stackPtr);
        $openBracket  = $phpcsFile->findNext(array(T_OPEN_PARENTHESIS), $functionName);
        if ($tokens[$openBracket]['line'] !== $tokens[$functionName]['line']) {
            return;
        }

        $closeBracket = $stackPtr;

        // Search through and find the closing bracket.
        $openers  = array($openBracket);
        $brackets = array(
                     T_OPEN_PARENTHESIS,
                     T_CLOSE_PARENTHESIS,
                    );
        $nextBracket = $openBracket;
        while (($nextBracket = $phpcsFile->findNext($brackets, $nextBracket + 1)) !== false) {
            if ($tokens[$nextBracket]['code'] === T_OPEN_PARENTHESIS) {
                $openers[] = $nextBracket;
            } else {
                array_pop($openers);
                if ($openers === array()) {
                    $closeBracket = $nextBracket;
                    break;
                }
            }
        }

        $nextParam = $openBracket;
        $params    = array();
        while (($nextParam = $phpcsFile->findNext(T_VARIABLE, $nextParam + 1, $closeBracket)) !== false) {

            $nextToken = $phpcsFile->findNext(T_WHITESPACE, $nextParam + 1, $closeBracket + 1, true);
            if ($nextToken === false) {
                break;
            }

            $nextCode = $tokens[$nextToken]['code'];

            if ($nextCode === T_EQUAL) {
                // Check parameter default spacing.
                if (($nextToken - $nextParam) > 1) {
                    $gap   = strlen($tokens[$nextParam + 1]['content']);
                    $arg   = $tokens[$nextParam]['content'];
                    $error = "Expected 0 spaces between argument \"$arg\" and equals sign; $gap found";
                    $phpcsFile->addError($error, $nextToken);
                }

                if ($tokens[($nextToken + 1)]['code'] === T_WHITESPACE) {
                    $gap   = strlen($tokens[($nextToken + 1)]['content']);
                    $arg   = $tokens[$nextParam]['content'];
                    $error = "Expected 0 spaces between default value and equals sign for argument \"$arg\"; $gap found";
                    $phpcsFile->addError($error, $nextToken);
                }
            }

            // Find and check the comma (if there is one)
            $nextComma = $phpcsFile->findNext(T_COMMA, $nextParam + 1, $closeBracket);
            if ($nextComma !== false) {
                // Comma found.
                if ($tokens[($nextComma - 1)]['code'] === T_WHITESPACE) {
                    $space = strlen($tokens[($nextComma - 1)]['content']);
                    $arg   = $tokens[$nextParam]['content'];
                    $error = "Expected 0 spaces between argument \"$arg\" and comma; $space found";
                    $phpcsFile->addError($error, $nextToken);
                }
            }

            if ($params !== array()) {
                if ($tokens[$nextParam - 1]['code'] !== T_WHITESPACE) {
                    $arg   = $tokens[$nextParam]['content'];
                    $error = "Expected 1 space between comma and argument \"$arg\"; 0 found";
                    $phpcsFile->addError($error, $nextToken);
                } else {
                    $space = $tokens[$nextParam - 1];
                    if (strlen($space['content']) !== 1) {
                        $gap   = strlen($space['content']);
                        $arg   = $tokens[$nextParam]['content'];
                        $error = "Expected 1 space between between comma and argument \"$arg\"; $gap found";
                        $phpcsFile->addError($error, $nextToken);
                    }
                }
            } else {
                if ($tokens[($nextParam - 1)]['code'] === T_WHITESPACE) {
                    $gap = strlen($tokens[($nextParam - 1)]['content']);
                    $arg = $tokens[$nextParam]['content'];

                    // Before we throw an error, make sure there is no type hint.
                    $bracket = $phpcsFile->findPrevious(T_OPEN_PARENTHESIS, ($nextParam - 1));
                    if ($bracket === ($nextParam - 3)) {
                        // There was a type hint, so just check the spacing between
                        // the hint and the variable.
                        if ($gap !== 1) {
                            $error = "Expected 1 space between type hint and argument \"$arg\"; $gap found";
                            $phpcsFile->addError($error, $nextToken);
                        }
                    } else {
                        $error = "Expected 0 spaces between opening bracket and argument \"$arg\"; $gap found";
                        $phpcsFile->addError($error, $nextToken);
                    }
                }
            }

            $params[] = $nextParam;

        }//end while

        if (empty($params) === true) {
            // There are no parameters for this function.
            if (($closeBracket - $openBracket) !== 1) {
                $space = strlen($tokens[($closeBracket - 1)]['content']);
                $error = "Expected 0 spaces between brackets of function declaration; $space found";
                $phpcsFile->addError($error, $stackPtr);
            }
        } else if ($tokens[($closeBracket - 1)]['code'] === T_WHITESPACE) {
            $lastParam = array_pop($params);
            $arg       = $tokens[$lastParam]['content'];
            $gap       = strlen($tokens[($closeBracket - 1)]['content']);
            $error     = "Expected 0 spaces between argument \"$arg\" and closing bracket; $gap found";
            $phpcsFile->addError($error, $closeBracket);
        }

    }//end process()


}//end class

?>