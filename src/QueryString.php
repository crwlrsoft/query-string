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
            if ($this->separator !== '&') {
                throw new Exception(
                    'Converting a query string to array with custom separator isn\'t implemented. ' .
                    'If you\'d need this reach out on github or twitter.'
                );
            }

            if ($this->containsDotOrSpaceInKey($this->string())) {
                return $this->fixKeysContainingDotsOrSpaces();
            }

            parse_str($this->string ?? '', $array);

            return $array;
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
     * @return string[]
     */
    private function fixKeysContainingDotsOrSpaces(): array
    {
        $queryStringWithUnencodedBrackets = str_replace(['%5B', '%5D'], ['[', ']'], $this->string());

        $queryWithDotAndSpaceReplacements = $this->replaceDotsAndSpacesInKeys($queryStringWithUnencodedBrackets);

        parse_str($queryWithDotAndSpaceReplacements, $array);

        return $this->revertDotAndSpaceReplacementsInKeys($array);
    }

    private function containsDotOrSpaceInKey(string $queryString): bool
    {
        $queryStringWithUnencodedBrackets = str_replace(['%5B', '%5D'], ['[', ']'], $queryString);

        return preg_match('/(?:^|&)([^\[=&]*\.)/', $queryStringWithUnencodedBrackets) ||
            preg_match('/(?:^|&)([^\[=&]* )/', $queryStringWithUnencodedBrackets);
    }

    private function replaceDotsAndSpacesInKeys(string $queryString): string
    {
        return preg_replace_callback(
            '/(?:^|&)([^=&\[]+)(?:[=&\[]|$)/',
            function ($match) {
                return str_replace(
                    ['.', ' ', $this->spaceCharacter()],
                    ['-*-crwlr-dot-replacement-*-', '-*-crwlr-space-replacement-*-', '-*-crwlr-space-replacement-*-'],
                    $match[0]
                );
            },
            $queryString
        ) ?? $queryString;
    }

    /**
     * @param mixed[] $queryStringArray
     * @return mixed[]
     */
    private function revertDotAndSpaceReplacementsInKeys(array $queryStringArray): array
    {
        foreach ($queryStringArray as $key => $value) {
            if (
                strpos($key, '-*-crwlr-dot-replacement-*-') !== false ||
                strpos($key, '-*-crwlr-space-replacement-*-') !== false
            ) {
                $fixedKey = str_replace(
                    ['-*-crwlr-dot-replacement-*-', '-*-crwlr-space-replacement-*-'],
                    ['.', ' '],
                    $key
                );

                $queryStringArray[$fixedKey] = $value;

                unset($queryStringArray[$key]);
            }
        }

        return $queryStringArray;
    }

    private function spaceCharacter(): string
    {
        if ($this->spaceCharacterEncoding === PHP_QUERY_RFC1738) {
            return '+';
        }

        return '%20';
    }
}
