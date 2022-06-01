<?php

namespace Crwlr\QueryString;

use ArrayAccess;
use Closure;
use Exception;
use InvalidArgumentException;
use Iterator;

/**
 * @implements ArrayAccess<int|string, mixed>
 * @implements Iterator<int|string, mixed>
 */

final class Query implements ArrayAccess, Iterator
{
    /**
     * Dots and spaces in query strings are temporarily converted to these placeholders when converting a query string
     * to array. That's necessary because parse_str() would automatically convert them to underscores.
     *
     * @see https://github.com/php/php-src/issues/8639
     */
    private const TEMP_DOT_REPLACEMENT = '<crwlr-dot-replacement>';

    private const TEMP_SPACE_REPLACEMENT = '<crwlr-space-replacement>';

    private ?string $string = null;

    /**
     * @var null|mixed[]
     */
    private ?array $array = null;

    private string $separator = '&';

    private int $spaceCharacterEncoding = PHP_QUERY_RFC1738;

    /**
     * If true boolean values are converted to integer (0, 1). If false they are converted to string ('true', 'false').
     */
    private bool $boolToInt = true;

    private ?Query $parent = null;

    private bool $isDirty = false;

    private ?Closure $dirtyHookCallback = null;

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

    /**
     * @throws Exception
     */
    public function toString(): string
    {
        if ($this->string === null || $this->isDirty) {
            $array = $this->toArray();

            if (!$this->boolToInt) {
                $array = $this->boolsToString($array);
            }

            $this->string = http_build_query($array, '', $this->separator, $this->spaceCharacterEncoding);
        }

        return $this->string;
    }

    /**
     * @throws Exception
     */
    public function toStringWithUnencodedBrackets(): string
    {
        return str_replace(['%5B', '%5D'], ['[', ']'], $this->toString());
    }

    /**
     * @throws Exception
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return mixed[]
     * @throws Exception
     */
    public function toArray(): array
    {
        return $this->cleanArray($this->array());
    }

    /**
     * @param string|int $key
     * @return string|Query|null|mixed
     * @throws Exception
     */
    public function get(string|int $key): mixed
    {
        $queryArray = $this->array();

        if (!isset($queryArray[$key])) {
            return null;
        }

        if (is_array($queryArray[$key])) {
            $this->array[$key] = $this->newWithSameSettings($queryArray[$key]);

            return $this->array[$key];
        }

        return $queryArray[$key];
    }

    /**
     * @param string $key
     * @param string|mixed[] $value
     * @return self
     * @throws Exception
     */
    public function set(string $key, string|array $value): self
    {
        $this->initArray();

        $this->array[$key] = is_array($value) ? $this->newWithSameSettings($value) : $value;

        $this->setDirty();

        return $this;
    }

    /**
     * @throws Exception
     */
    public function has(int|string $key): bool
    {
        $this->initArray();

        return isset($this->array[$key]);
    }

    /**
     * @throws Exception
     */
    public function isArray(int|string $key): bool
    {
        $this->initArray();

        return isset($this->array[$key]) && (is_array($this->array[$key]) || $this->array[$key] instanceof Query);
    }

    /**
     * @throws Exception
     */
    public function isScalar(int|string $key): bool
    {
        return $this->has($key) && !$this->isArray($key);
    }

    /**
     * @param string|mixed[] $value
     * @throws Exception
     */
    public function appendTo(string $key, string|array $value): self
    {
        $currentValue = $this->get($key);

        $currentValueArray = $currentValue instanceof Query ? $currentValue->toArray() : [$currentValue];

        $newQueryArray = $this->appendToArray($currentValueArray, $value);

        $this->array[$key] = $this->newWithSameSettings($newQueryArray);

        $this->setDirty();

        return $this;
    }

    /**
     * @return mixed|string|bool|int|Query
     * @throws Exception
     */
    public function first(?string $key = null): mixed
    {
        $this->initArray();

        return $this->firstOrLast($key);
    }

    /**
     * @return mixed|string|bool|int|Query
     * @throws Exception
     */
    public function last(?string $key = null): mixed
    {
        $this->initArray();

        return $this->firstOrLast($key, false);
    }

    /**
     * @throws Exception
     */
    public function remove(string $key): self
    {
        $this->initArray();

        if (isset($this->array[$key])) {
            unset($this->array[$key]);

            $this->setDirty();
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function removeValueFrom(string $fromKey, mixed $removeValue): self
    {
        $fromKeyValue = $this->get($fromKey);

        if ($fromKeyValue instanceof Query) {
            $isAssociativeArray = $this->isAssociativeArray($fromKeyValue->array ?? []);

            foreach ($fromKeyValue as $key => $value) {
                if ($value === $removeValue) {
                    unset($fromKeyValue->array[$key]);

                    $fromKeyValue->setDirty();
                }
            }

            if (!$isAssociativeArray && is_array($fromKeyValue->array)) {
                $fromKeyValue->array = array_values($fromKeyValue->array);
            }
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function boolToInt(): self
    {
        if ($this->boolToInt) {
            return $this;
        }

        $this->boolToInt = true;

        foreach ($this->array() as $value) {
            if ($value instanceof Query) {
                $value->boolToInt();
            }
        }

        $this->setDirty();

        return $this;
    }

    /**
     * @throws Exception
     */
    public function boolToString(): self
    {
        if (!$this->boolToInt) {
            return $this;
        }

        $this->boolToInt = false;

        foreach ($this->array() as $value) {
            if ($value instanceof Query) {
                $value->boolToString();
            }
        }

        $this->setDirty();

        return $this;
    }

    /**
     * @throws Exception
     */
    public function separator(string $separator): self
    {
        if ($this->separator === $separator) {
            return $this;
        }

        $this->separator = $separator;

        foreach ($this->array() as $value) {
            if ($value instanceof Query) {
                $value->separator($separator);
            }
        }

        $this->setDirty();

        return $this;
    }

    /**
     * @throws Exception
     */
    public function spaceCharacterEncoding(int $spaceCharacterEncodingConst): self
    {
        if (in_array($spaceCharacterEncodingConst, [PHP_QUERY_RFC1738, PHP_QUERY_RFC3986], true)) {
            if ($spaceCharacterEncodingConst === $this->spaceCharacterEncoding) {
                return $this;
            }

            $this->spaceCharacterEncoding = $spaceCharacterEncodingConst;

            foreach ($this->array() as $value) {
                if ($value instanceof Query) {
                    $value->spaceCharacterEncoding($spaceCharacterEncodingConst);
                }
            }

            $this->setDirty();
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function spaceCharacterPlus(): self
    {
        return $this->spaceCharacterEncoding(PHP_QUERY_RFC1738);
    }

    /**
     * @throws Exception
     */
    public function spaceCharacterPercentTwenty(): self
    {
        return $this->spaceCharacterEncoding(PHP_QUERY_RFC3986);
    }

    /**
     * @throws Exception
     */
    public function filter(Closure $filterCallback): self
    {
        $this->array = array_filter($this->toArray(), $filterCallback, ARRAY_FILTER_USE_BOTH);

        $this->setDirty();

        return $this;
    }

    /**
     * @throws Exception
     */
    public function map(Closure $mappingCallback): self
    {
        $this->array = array_map($mappingCallback, $this->toArray());

        $this->setDirty();

        return $this;
    }

    public function setDirtyHook(Closure $callback): self
    {
        $this->dirtyHookCallback = $callback;

        return $this;
    }

    /**
     * @param int|string $offset
     * @throws Exception
     */
    public function offsetExists(mixed $offset): bool
    {
        if (!is_int($offset) && !is_string($offset)) {
            throw new InvalidArgumentException('Argument offset must be of type int or string.');
        }

        return array_key_exists($offset, $this->array());
    }

    /**
     * @param int|string $offset
     * @return mixed
     * @throws Exception
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!is_int($offset) && !is_string($offset)) {
            throw new InvalidArgumentException('Argument offset must be of type int or string.');
        }

        return $this->array()[$offset];
    }

    /**
     * @param int|string $offset
     * @throws Exception
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_int($offset) && !is_string($offset)) {
            throw new InvalidArgumentException('Argument offset must be of type int or string.');
        }

        $queryArray = $this->array();

        $queryArray[$offset] = $value;

        $this->array = $queryArray;
    }

    /**
     * @param mixed $offset
     * @return void
     * @throws Exception
     */
    public function offsetUnset(mixed $offset): void
    {
        if (!is_int($offset) && !is_string($offset)) {
            throw new InvalidArgumentException('Argument offset must be of type int or string.');
        }

        $queryArray = $this->array();

        if (array_key_exists($offset, $queryArray)) {
            unset($queryArray[$offset]);
        }
    }

    /**
     * @return false|mixed
     * @throws Exception
     */
    public function current(): mixed
    {
        $this->initArray();

        return current($this->array ?? []);
    }

    /**
     * @throws Exception
     */
    public function next(): void
    {
        $this->initArray();

        if (is_array($this->array)) {
            next($this->array);
        }
    }

    /**
     * @throws Exception
     */
    public function key(): int|string|null
    {
        $this->initArray();

        if (is_array($this->array)) {
            return key($this->array);
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function valid(): bool
    {
        $this->initArray();

        if ($this->key() === null) {
            return false;
        }

        return isset($this->array[$this->key()]);
    }

    /**
     * @throws Exception
     */
    public function rewind(): void
    {
        if ($this->array === null) {
            $this->array();
        } else {
            reset($this->array);
        }
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
     * @throws Exception
     */
    private function fixKeysContainingDotsOrSpaces(): array
    {
        $queryWithDotAndSpaceReplacements = $this->replaceDotsAndSpacesInKeys($this->toStringWithUnencodedBrackets());

        parse_str($queryWithDotAndSpaceReplacements, $array);

        return $this->revertDotAndSpaceReplacementsInKeys($array);
    }

    /**
     * @throws Exception
     */
    private function containsDotOrSpaceInKey(): bool
    {
        return preg_match('/(?:^|&)([^\[=&]*\.)/', $this->toStringWithUnencodedBrackets()) ||
            preg_match('/(?:^|&)([^\[=&]* )/', $this->toStringWithUnencodedBrackets());
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

    /**
     * @throws Exception
     */
    private function initArray(): void
    {
        if ($this->array === null) {
            $this->array();
        }
    }

    /**
     * @return mixed[]
     * @throws Exception
     */
    private function array(): array
    {
        if ($this->array === null) {
            if ($this->separator !== '&') {
                throw new Exception(
                    'Converting a query string to array with custom separator isn\'t implemented, because PHP\'s ' .
                    'parse_str() function doesn\'t have that functionality. If you\'d need this reach out to crwlr ' .
                    'on github or twitter.'
                );
            }

            if ($this->containsDotOrSpaceInKey()) {
                return $this->fixKeysContainingDotsOrSpaces();
            }

            parse_str($this->string ?? '', $array);

            $this->array = $array;

            return $array;
        }

        return $this->array;
    }

    /**
     * @param mixed[] $array
     * @return mixed[]
     * @throws Exception
     */
    private function cleanArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if ($value instanceof Query) {
                $array[$key] = $value->toArray();
            }
        }

        return $array;
    }

    /**
     * @return mixed|string|bool|int|Query
     * @throws Exception
     */
    private function firstOrLast(?string $key = null, bool $first = true): mixed
    {
        if (!is_array($this->array) || ($key && !isset($this->array[$key]))) {
            return null;
        }

        if ($key === null) {
            $value = $first ? reset($this->array) : end($this->array);

            return is_array($value) ? $this->newWithSameSettings($value) : $value;
        }

        if ($this->array[$key] instanceof Query || is_array($this->array[$key])) {
            if (is_array($this->array[$key])) {
                $this->array[$key] = $this->newWithSameSettings($this->array[$key]);
            }

            return $first ? $this->array[$key]->first() : $this->array[$key]->last();
        }

        return $this->array[$key];
    }

    /**
     * @param string|mixed[] $query
     * @return $this
     */
    private function newWithSameSettings(string|array $query): self
    {
        $instance = new self($query);

        $instance->parent = $this;

        $instance->boolToInt = $this->boolToInt;

        $instance->spaceCharacterEncoding = $this->spaceCharacterEncoding;

        $instance->separator = $this->separator;

        return $instance;
    }

    private function setDirty(): void
    {
        $this->isDirty = true;

        $this->parent?->setDirty();

        if ($this->dirtyHookCallback) {
            $dirtyHookCallback = $this->dirtyHookCallback;

            $dirtyHookCallback();
        }
    }

    /**
     * @param mixed[] $array
     * @param string|mixed[] $value
     * @return mixed[]
     */
    private function appendToArray(array $array, string|array $value): array
    {
        if (is_array($value)) {
            if ($this->isAssociativeArray($value)) {
                $array = $this->appendAssociativeArrayToArray($array, $value);
            } else {
                array_push($array, ...$value);
            }
        } else {
            $array[] = $value;
        }

        return $array;
    }

    /**
     * @param mixed[] $array
     */
    private function isAssociativeArray(array $array): bool
    {
        foreach (array_keys($array) as $key => $value) {
            if ($key !== $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed[] $appendTo
     * @param mixed[] $associativeArray
     * @return mixed[]
     */
    private function appendAssociativeArrayToArray(array $appendTo, array $associativeArray): array
    {
        foreach ($associativeArray as $key => $val) {
            if (isset($appendTo[$key])) {
                if (!is_array($appendTo[$key])) {
                    $appendTo[$key] = [$appendTo[$key]];
                }

                $appendTo[$key][] = $val;
            } else {
                $appendTo[$key] = $val;
            }
        }

        return $appendTo;
    }

    /**
     * @param Query<mixed[]>|mixed[] $array
     * @return Query<mixed[]>|mixed[]
     */
    private function boolsToString(Query|array $array): Query|array
    {
        foreach ($array as $key => $value) {
            if (is_bool($value)) {
                $array[$key] = $value ? 'true' : 'false';
            } elseif ($value instanceof Query || is_array($value)) {
                $array[$key] = $this->boolsToString($value);
            }
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
}
