<?php

    namespace Bitfalls\Utilities;

    /**
     * Class Parser
     * The Parser component is used to replace tags from a string
     * of text with actual values provided through a simple associative
     * array. It is used in rendering personalized content for emails,
     * CMS pages and PDF documents, among other things
     *
     * @package Bitfalls\Utilities
     */
    class Parser
    {

        const AS_INPUT = 1;
        const AS_INPUT_ASSOC = 2;
        const MERGED_UNIQUE = 3;

        /**
         * Extracts tags from a given piece of text or an array of texts
         *
         * The $mContent can be:
         *
         *     $input = (string) 'Some kind of {tag}'
         *         when the input is a string, method returns a one-dimensional array of extracted tags
         *
         *     $input = array ('Some kind of {tag}', 'And more {tags}');
         *         when the input is an indexed array
         *             if $cReturnType is MERGED_UNIQUE returns a one-dimensional array of unique extracted tags
         *             if $cReturnType is AS_INPUT returns an array with X elements where X = count($input) and each element is a one-dimensional array of extracted tags, like so: array(array('tag'), array('tags'));
         *             if $cReturnType is AS_INPUT_ASSOC returns an assoc. array with X elements where X = count($input) and each element is a $key=>$value pair where $key is the input element and $value is a one-dimensional array of extracted tags from that element, like so: array('Some kind of {tag}' => array('tag'), 'And more {tags}' => array('tags'));
         *
         *     $input = array ('body' => 'Some kind of {tag}', 'signature' => 'And more {tags}');
         *         when the input is an associative array
         *             if $cReturnType is MERGED_UNIQUE returns a one-dimensional array of unique extracted tags
         *             if $cReturnType is AS_INPUT or AS_INPUT_ASSOC (no difference in this case) returns an array with X elements where X = count($input) and each element is a $key=>$value pair in which $key is the $input[$key] whereas $value is a oe-dimensional vector of unique extracted tags
         *
         * @param string|array $mContent    Either a string or an array of strings
         * @param int          $cReturnType This option is ignored if $mContent is a string, and the method returns a simple vector of unique tags found. <br /> Parser::AS_INPUT : returns the data as received. For example, sending a single string will send back a single vector of tags. Sending an array of strings will send back an array of arrays, each of which is a vector of tags. <br /> <br /> Parser::AS_INPUT_ASSOC : Choosing AS_INPUT_ASSOC will return an associative array of key => value pairs in which keys are the actual content given, and the value is a vector of tags found in that content. For example, sending in <pre>array('This is a {tag}', 'And yet {another}')</pre> will return <pre>array('This is a {tag}' => array('tag'), 'And yet {another}' => array('another'))</pre><br /> <br /> Parser::MERGED_UNIQUE : returns a single vector of ALL tags found in all texts provided, with duplicates removed.
         * @param bool         $bTrim       If true, the curly braces will be removed from the tags
         *
         * @return array
         * @throws ParserException
         */
        public function extractTags($mContent, $cReturnType = Parser::MERGED_UNIQUE, $bTrim = true)
        {

            if (!$this->verifyContentArray($mContent)) {
                $e = new ParserException('The content you provided for tag extraction is neither a string nor a valid array!');
                throw $e->setContent($mContent);
            }

            $aResult = array();

            if (!empty($mContent)) {
                if (is_string($mContent)) {
                    $aTags = array();
                    preg_match_all("/\{\w+([.]|)\w+\}/s", $mContent, $aTags);
                    if ($bTrim) {
                        foreach ($aTags[0] as &$sTag) {
                            $sTag = trim($sTag, '{}');
                        }
                    }
                    $aResult = array_unique($aTags[0]);
                } else {
                    foreach ($mContent as $mKey => $sContent) {

                        $aTags = $this->extractTags($sContent, Parser::MERGED_UNIQUE, $bTrim);
                        switch ($cReturnType) {

                            case Parser::AS_INPUT:
                                if (is_string($mKey)) {
                                    $aResult[$mKey] = $aTags;
                                } else {
                                    $aResult[] = $aTags;
                                }
                                break;

                            case Parser::AS_INPUT_ASSOC:
                                if (is_string($mKey)) {
                                    $aResult[$mKey] = $aTags;
                                } else {
                                    $aResult[$sContent] = $aTags;
                                }
                                break;

                            case Parser::MERGED_UNIQUE:
                            default:
                                $aResult = array_unique(array_merge($aResult, $aTags));
                                break;
                        }
                    }
                }
            }

            return $aResult;
        }

        /**
         * Replaces tags in content text with their corresponding values from the provided array.<br />
         * Does some limited checks to make sure everything went fine during replacing.
         *
         * This method does NOT take a reference to $mContent and as such does not change the original
         * text, unlike doParseRef. Therefore, you must store the returning content into a new variable
         * in order to use it. Also note that this method does not allow chaining of the parser object.
         *
         * @param array $aTagValues An associative array of tags and their values
         * @param mixed $mContent   The content containing tags to be parsed
         *
         * @return mixed
         * @throws ParserException Invalid Content triggers a Parser Exception
         * @throws ParserException Invalid Tag value triggers a Parser Exception
         * @throws ParserException Detecting unparsed tags in the final content triggers a Parser Exception
         */
        public function doParse($aTagValues, $mContent)
        {

            if (!$this->verifyContentArray($mContent)) {
                $e = new ParserException('The provided content is invalid. It needs to be a string, an array of strings, or an assoc. array of strings.');
                throw $e->setContent($mContent)->setTagValues($aTagValues);
            }

            $aTags = $this->extractTags($mContent);
            foreach ($aTagValues as $sTag => $sValue) {
                if (in_array($sTag, $aTags)) {
                    $sValue = trim($sValue);
                    if (empty($sValue) || !is_string($sTag) || (!is_numeric($sValue) && !is_string($sValue))) {
                        $e = new ParserException('Tag ' . $sTag . ' is invalid.');
                        throw $e->setExtraInfo(
                            array(
                                'content'       => $mContent,
                                'unparsed_tags' => $aTags,
                                'given_values'  => $aTagValues
                            )
                        );
                    }

                    if (is_string($mContent)) {
                        $mContent = preg_replace('/\{' . $sTag . '\}/s', $sValue, $mContent);
                    } else {
                        foreach ($mContent as &$mText) {
                            $mText = preg_replace('/\{' . $sTag . '\}/s', $sValue, $mText);
                        }
                    }
                }
            }

            // Check if there's any unparsed tags left
            $aTags = $this->extractTags($mContent);

            if (!empty($aTags)) {
                $e = new ParserException('Some unparsed tags were detected in the content after parsing.');
                throw $e->setExtraInfo(
                    array(
                        'content'       => $mContent,
                        'unparsed_tags' => $aTags,
                        'given_values'  => $aTagValues
                    )
                );
            }

            $this->finalContentCheck($mContent);

            return $mContent;
        }

        /**
         * This is an alias for ::doParse with one major difference.
         * It takes a reference to the content array/string, and returns
         * the actual instance of the Parser - this means the Parser object
         * can be chained in your code, and it also means it changes the
         * original content array/string
         *
         * @see           Parser::doParse
         *
         * @param array $aTagValues
         * @param mixed $mContent
         *
         * @return Parser
         */
        public function doParseRef($aTagValues, &$mContent)
        {
            $mContent = $this->doParse($aTagValues, $mContent);
            return $this;
        }

        /**
         * Checks the final content for erroneous data before sending it back to the caller.
         * If the data is not ok, throws an exception
         *
         * @param mixed $mContent
         *
         * @return Parser
         */
        protected function finalContentCheck(&$mContent)
        {
            if (is_string($mContent)) {
                $this->invalidCharactersCheck($mContent);
            } else {
                foreach ($mContent as &$mText) {
                    $this->invalidCharactersCheck($mText);
                }
            }

            return $this;
        }

        /**
         * Throws a ParserException if the string content contains any kind of invalid character
         *
         * @param string $sString
         *
         * @throws ParserException
         * @return Parser
         */
        protected function invalidCharactersCheck(&$sString)
        {
            $aInvalidCharacters = array('{', '}');
            foreach ($aInvalidCharacters as $sInvalidCharacter) {
                if (strpos($sString, $sInvalidCharacter) !== false) {
                    $e = new ParserException('Invalid characters detected in the content! :: "' . $sInvalidCharacter . '"');
                    throw $e->setExtraInfo($sString);
                }
            }

            return $this;
        }

        /**
         * Validates the format of the input data
         *
         * @param string|array $mContent
         *
         * @return boolean
         */
        protected function verifyContentArray($mContent)
        {
            $valid = false;
            if (is_array($mContent)) {
                foreach ($mContent as $key => $value) {
                    if ((is_string($key) || is_numeric($key)) && is_string($value)) {
                        $valid = true;
                    } else {
                        $valid = false;
                    }
                }
            }
            return ($valid || is_string($mContent) || $mContent == null);
        }
    }

    /**
     * Class ParserException
     * @package Bitfalls\Utilities
     */
    class ParserException extends \Exception
    {

        /** @var array */
        protected $aParams;
        /** @var mixed */
        protected $mExtraInfo;

        /** @var array */
        protected $aTagValues;
        /** @var mixed */
        protected $mContent;

        /**
         * Sets any kind of extra information
         *
         * @param mixed $mInfo
         *
         * @return ParserException
         */
        public function setExtraInfo($mInfo = array())
        {
            $this->mExtraInfo = $mInfo;
            return $this;
        }

        /**
         * Gets the extra information
         * @return mixed
         */
        public function getExtraInfo()
        {
            return $this->mExtraInfo;
        }

        /**
         * Sets passed content
         *
         * @param mixed $mContent
         *
         * @return ParserException
         */
        public function setContent($mContent)
        {
            $this->mContent = $mContent;
            return $this;
        }

        /**
         * Gets the passed content
         * @return mixed
         */
        public function getContent()
        {
            return $this->mContent;
        }

        /**
         * Sets passed Tag Value pairs
         *
         * @param array $aTagValues
         *
         * @return ParserException
         */
        public function setTagValues($aTagValues = array())
        {
            $this->aTagValues = $aTagValues;
            return $this;
        }

        /**
         * Gets passed tag value pairs
         * @return array
         */
        public function getTagValues()
        {
            return $this->aTagValues;
        }

        /**
         * Returns the unparsed tags formed into a string
         *
         * @return string
         */
        public function getUnparsedTagsString()
        {
            $sUnparsedTagsString = '';
            $aUnparsedTags = $this->getExtraInfo();
            if (isset($aUnparsedTags['unparsed_tags']) && !empty($aUnparsedTags['unparsed_tags'])) {
                $sUnparsedTagsString .= ': ';
                foreach ($aUnparsedTags['unparsed_tags'] as &$sTag) {
                    $sUnparsedTagsString .= $sTag . ', ';
                }
                $sUnparsedTagsString = trim($sUnparsedTagsString, ', ');
            }
            return $sUnparsedTagsString;
        }
    }