<?php
declare(strict_types=1);

namespace Enm\JsonApi\Client\Model\Request;

use Enm\JsonApi\Model\Common\KeyValueCollectionInterface;

/**
 * @author Philipp Marien <marien@eosnewmedia.de>
 */
interface FetchInterface
{
    /**
     * The value for the url
     * @return string
     */
    public function getType(): string;

    /**
     * The values for the include parameter
     * @return array
     */
    public function getRelationships(): array;

    /**
     * @return array
     */
    public function getFields(): array;

    /**
     * @return array
     */
    public function getHeaders(): array;

    /**
     * @return array
     */
    public function getSorting(): array;

    /**
     * @param string $relationship
     * @return FetchInterface
     */
    public function include (string $relationship): FetchInterface;

    /**
     * @param string $type
     * @param string $attribute
     * @return FetchInterface
     */
    public function withField(string $type, string $attribute): FetchInterface;

    /**
     * @param string $name
     * @param string $value
     * @return FetchInterface
     */
    public function withHeader(string $name, string $value): FetchInterface;

    /**
     * @param string $attribute
     * @param bool $asc
     * @return FetchInterface
     */
    public function sortBy(string $attribute, bool $asc = true): FetchInterface;

    /**
     * @return KeyValueCollectionInterface
     */
    public function pagination(): KeyValueCollectionInterface;

    /**
     * @return KeyValueCollectionInterface
     */
    public function filter(): KeyValueCollectionInterface;
}
