<?php

namespace Crwlr\QueryString;

use Exception;

final class Query
{
    /**
     * Dots and spaces in query strings are temporarily converted to these placeholders when converting a query string
     * to array. That's necessary because parse_str() automatically converts them to underscores.
     *
     * @see https://github.com/php/php-src/issues/8639
     */
    private const TEMP_DOT_REPLACEMENT = '<crwlr-dot-replacement>';
    private const TEMP_SPACE_REPLACEMENT = '<crwlr-space-replacement>';

    /**
     * @var null|string
     */
    private ?string $string = null;

    /**
     * @var null|mixed[]
     */
    private ?array $array = null;

    /**
     * @var string
     */
    private string $separator = '&';

    /**
     * @var int
     */
    private int $spaceCharacterEncoding = PHP_QUERY_RFC1738;

    /**
     * @param string|mixed[] $query
     */
    public function __construct(string|array $query)
    {
        if (is_string($query)) {
            $this->string = $this->encode($query);
        }

        if (is_array($query)) {
            $this->array = $query;
        }
    }

    public static function fromString(string $queryString): self
    {
        return new Query($queryString);
    }

    /**
     * @param mixed[] $queryArray
     * @return static
     */
    public static function fromArray(array $queryArray): self
    {
        return new Query($queryArray);
    }

    public function string(bool $unencodedBrackets = false): string
    {
        if ($this->string === null) {
            $this->string = http_build_query($this->array ?? [], '', $this->separator, $this->spaceCharacterEncoding);
        }

        return !$unencodedBrackets ? $this->string : str_replace(['%5B', '%5D'], ['[', ']'], $this->string);
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

            if ($this->containsDotOrSpaceInKey()) {
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
     * @return mixed[]
     */
    private function fixKeysContainingDotsOrSpaces(): array
    {
        $queryWithDotAndSpaceReplacements = $this->replaceDotsAndSpacesInKeys($this->string(true));

        parse_str($queryWithDotAndSpaceReplacements, $array);

        return $this->revertDotAndSpaceReplacementsInKeys($array);
    }

    private function containsDotOrSpaceInKey(): bool
    {
        return preg_match('/(?:^|&)([^\[=&]*\.)/', $this->string(true)) ||
            preg_match('/(?:^|&)([^\[=&]* )/', $this->string(true));
    }

    private function replaceDotsAndSpacesInKeys(string $queryString): string
    {
        return preg_replace_callback(
            '/(?:^|&)([^=&\[]+)(?:[=&\[]|$)/',
            function ($match) {
                return str_replace(
                    ['.', ' ', $this->spaceCharacter()],
                    [self::TEMP_DOT_REPLACEMENT, self::TEMP_SPACE_REPLACEMENT, self::TEMP_SPACE_REPLACEMENT],
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
            if (str_contains($key, self::TEMP_DOT_REPLACEMENT) || str_contains($key, self::TEMP_SPACE_REPLACEMENT)) {
                $fixedKey = str_replace([self::TEMP_DOT_REPLACEMENT, self::TEMP_SPACE_REPLACEMENT], ['.', ' '], $key);

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
