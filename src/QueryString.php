<?php

namespace Crwlr\QueryString;

use Exception;
use InvalidArgumentException;

class QueryString
{
    /**
     * @var null|string
     */
    private $string = null;

    /**
     * @var null|mixed[]
     */
    private $array = null;

    /**
     * @var string
     */
    private $separator = '&';

    /**
     * @var int
     */
    private $spaceCharacterEncoding = PHP_QUERY_RFC1738;

    /**
     * @param string|mixed[] $query
     */
    public function __construct($query)
    {
        if (!is_string($query) && !is_array($query)) {
            throw new InvalidArgumentException('Query string argument must be of type string or array.');
        }

        if (is_string($query)) {
            $this->string = $this->encode($query);
        }

        if (is_array($query)) {
            $this->array = $this->encodeArray($query);
        }
    }

    public function string(): string
    {
        if ($this->string === null) {
            $this->string = http_build_query($this->array ?? [], '', $this->separator, $this->spaceCharacterEncoding);
        }

        return $this->string;
    }

    /**
     * @return mixed[]
     * @throws Exception
     */
    public function array(): array
    {
        if ($this->array === null) {
            parse_str($this->string ?? '', $array);

            if ($this->separator !== '&') {
                throw new Exception(
                    'Converting a query string to array with custom separator isn\'t implemented. ' .
                    'If you\'d need this reach out on github or twitter.'
                );
            }

            $this->array = $this->fixKeysContainingDots($array);
        }

        return $this->array;
    }

    public function __toString(): string
    {
        return $this->string();
    }

    /**
     * Correctly encode a query string
     *
     * @see https://www.rfc-editor.org/rfc/rfc3986#section-3.4
     */
    private function encode(string $query): string
    {
        $query = self::encodePercentCharacter($query);

        return preg_replace_callback(
            '/[^a-zA-Z0-9-._~!$&\'()*+,;=:@\/%]/',  // pchar + / and %
            function (array $match) {
                return $match[0] === ' ' ? $this->spaceCharacter() : rawurlencode($match[0]);
            },
            $query
        ) ?? $query;
    }

    /**
     * @param mixed[] $query
     * @return mixed[]
     */
    private function encodeArray(array $query): array
    {
        $encodedQuery = [];

        foreach ($query as $key => $value) {
            $encodedQuery[$this->encode($key)] = is_array($value) ? $this->encodeArray($value) : $this->encode($value);
        }

        return $encodedQuery;
    }

    /**
     * Encode percent character in path, query or fragment
     *
     * If the string (path, query, fragment) contains a percent character that is not part of an already percent
     * encoded character it must be encoded (% => %25). So this method replaces all percent characters that are not
     * followed by a hex code.
     *
     * @param string $string
     * @return string
     */
    private function encodePercentCharacter(string $string): string
    {
        return preg_replace('/%(?![0-9A-Fa-f][0-9A-Fa-f])/', '%25', $string) ?: $string;
    }

    /**
     * When keys within a query string contain dots, PHP's parse_str() method converts them to underscores.
     * This method works around this issue so the requested query array returns the proper keys with dots.
     *
     * @param string[] $array
     * @return string[]
     */
    private function fixKeysContainingDots(array $array): array
    {
        if (preg_match('/(?:^|&)([^\[=&]*\.)/', $this->string ?? '')) { // Matches keys in the query containing a dot
            // Regex to find keys in query string.
            preg_match_all('/(?:^|&)([^=&\[]+)(?:[=&\[]|$)/', $this->string ?? '', $matches);

            $brokenKeys = $fixedArray = [];

            // Create mapping of broken keys to original proper keys.
            foreach ($matches[1] as $value) {
                if (strpos($value, '.') !== false) {
                    $brokenKeys[$this->replaceDotWithUnderscore($value)] = $value;
                }
            }

            // Recreate the array with the proper keys.
            foreach ($array as $key => $value) {
                if (isset($brokenKeys[$key])) {
                    $fixedArray[$brokenKeys[$key]] = $value;
                } else {
                    $fixedArray[$key] = $value;
                }
            }

            return $fixedArray;
        }

        return $array;
    }

    private function spaceCharacter(): string
    {
        if ($this->spaceCharacterEncoding === PHP_QUERY_RFC1738) {
            return '+';
        }

        return '%20';
    }

    private function replaceDotWithUnderscore(string $value): string
    {
        return str_replace('.', '_', $value);
    }
}
