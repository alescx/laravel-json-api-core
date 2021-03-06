<?php
/*
 * Copyright 2021 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Query;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Enumerable;
use LaravelJsonApi\Contracts\Query\QueryParameters as QueryParametersContract;
use LaravelJsonApi\Core\Support\Arr;
use UnexpectedValueException;
use function array_key_exists;
use function is_array;

class QueryParameters implements QueryParametersContract, Arrayable
{

    /**
     * @var IncludePaths|null
     */
    private ?IncludePaths $includePaths;

    /**
     * @var FieldSets|null
     */
    private ?FieldSets $fieldSets;

    /**
     * @var SortFields|null
     */
    private ?SortFields $sort;

    /**
     * @var array|null
     */
    private ?array $pagination;

    /**
     * @var array|null
     */
    private ?array $filters;

    /**
     * Cast a value to query parameters.
     *
     * @param QueryParametersContract|Enumerable|Request|array|null $value
     * @return QueryParameters
     */
    public static function cast($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value instanceof QueryParametersContract) {
            return new self(
                $value->includePaths(),
                $value->sparseFieldSets(),
                $value->sortFields(),
                $value->page(),
                $value->filter()
            );
        }

        if ($value instanceof Request) {
            return self::fromArray($value->query());
        }

        if (is_array($value) || $value instanceof Enumerable) {
            return self::fromArray($value);
        }

        throw new UnexpectedValueException('Expecting a valid query parameters value.');
    }

    /**
     * @param array|Enumerable $value
     * @return static
     */
    public static function fromArray($value): self
    {
        if ($value instanceof Enumerable) {
            $value = $value->all();
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException('Expecting an array or enumerable value.');
        }

        return new self(
            array_key_exists('include', $value) ? IncludePaths::fromArray($value['include']) : null,
            array_key_exists('fields', $value) ? FieldSets::fromArray($value['fields']) : null,
            array_key_exists('sort', $value) ? SortFields::fromArray($value['sort']) : null,
            array_key_exists('page', $value) ? $value['page'] : null,
            array_key_exists('filter', $value) ? $value['filter'] : null
        );
    }

    /**
     * @param QueryParametersContract|Enumerable|array|null $value
     * @return QueryParameters|null
     */
    public static function nullable($value): ?self
    {
        if (is_null($value)) {
            return null;
        }

        return self::cast($value);
    }

    /**
     * QueryParameters constructor.
     *
     * @param IncludePaths|null $includePaths
     * @param FieldSets|null $fieldSets
     * @param SortFields|null $sortFields
     * @param array|null $page
     * @param array|null $filters
     */
    public function __construct(
        IncludePaths $includePaths = null,
        FieldSets $fieldSets = null,
        SortFields $sortFields = null,
        array $page = null,
        array $filters = null
    ) {
        $this->includePaths = $includePaths;
        $this->fieldSets = $fieldSets;
        $this->sort = $sortFields;
        $this->pagination = $page;
        $this->filters = $filters;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $query = [];

        if ($this->includePaths && $this->includePaths->isNotEmpty()) {
            $query['include'] = $this->includePaths->toString();
        }

        if ($this->fieldSets && $this->includePaths->isNotEmpty()) {
            $query['fields'] = $this->fieldSets->toArray();
        }

        if ($this->sort && $this->sort->isNotEmpty()) {
            $query['sort'] = $this->sort->toString();
        }

        if (is_array($this->pagination) && !empty($this->pagination)) {
            $query['page'] = $this->pagination;
        }

        if (is_array($this->filters) && !empty($this->filters)) {
            $query['filter'] = $this->filters;
        }

        return Arr::query($query);
    }

    /**
     * @return IncludePaths|null
     */
    public function includePaths(): ?IncludePaths
    {
        return $this->includePaths;
    }

    /**
     * @param IncludePaths|array|string|null $paths
     * @return $this
     */
    public function setIncludePaths($paths): self
    {
        $this->includePaths = IncludePaths::nullable($paths);

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutIncludePaths(): self
    {
        $this->includePaths = null;

        return $this;
    }

    /**
     * @return FieldSets|null
     */
    public function sparseFieldSets(): ?FieldSets
    {
        return $this->fieldSets;
    }

    /**
     * Set the sparse field sets.
     *
     * @param FieldSets|array|null $fieldSets
     * @return $this
     */
    public function setSparseFieldSets($fieldSets): self
    {
        $this->fieldSets = FieldSets::nullable($fieldSets);

        return $this;
    }

    /**
     * Remove all sparse field sets.
     *
     * @return $this
     */
    public function withoutSparseFieldSets(): self
    {
        $this->fieldSets = null;

        return $this;
    }

    /**
     * Add sparse fields by resource type.
     *
     * @param string $type
     * @param array $fields
     * @return $this
     */
    public function setFieldSet(string $type, array $fields): self
    {
        $this->fieldSets = FieldSets::cast($this->fieldSets)
            ->push(new FieldSet($type, ...$fields));

        return $this;
    }

    /**
     * Remove field sets by resource type.
     *
     * @param string ...$types
     * @return $this
     */
    public function withoutFieldSet(string ...$types): self
    {
        if ($this->fieldSets) {
            $this->fieldSets->forget(...$types);
        }

        return $this;
    }

    /**
     * @return SortFields|null
     */
    public function sortFields(): ?SortFields
    {
        return $this->sort;
    }

    /**
     * @param SortFields|array|string|null $fields
     * @return $this
     */
    public function setSortFields($fields): self
    {
        $this->sort = SortFields::nullable($fields);

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutSortFields(): self
    {
        $this->sort = null;

        return $this;
    }

    /**
     * @return array|null
     */
    public function page(): ?array
    {
        return $this->pagination;
    }

    /**
     * Set pagination.
     *
     * @param Arrayable|array|null $pagination
     * @return $this
     */
    public function setPagination($pagination): self
    {
        $this->pagination = is_null($pagination) ? null : collect($pagination)->toArray();

        return $this;
    }

    /**
     * Remove pagination.
     *
     * @return $this
     */
    public function withoutPagination(): self
    {
        $this->pagination = null;

        return $this;
    }

    /**
     * @return array|null
     */
    public function filter(): ?array
    {
        return $this->filters;
    }

    /**
     * Set filters.
     *
     * @param Arrayable|array|null $filters
     * @return $this
     */
    public function setFilters($filters): self
    {
        $this->filters = is_null($filters) ? null : collect($filters)->toArray();

        return $this;
    }

    /**
     * Remove filters.
     *
     * @return $this
     */
    public function withoutFilters(): self
    {
        $this->filters = null;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $query = [];

        if ($this->includePaths && $this->includePaths->isNotEmpty()) {
            $query['include'] = $this->includePaths->toArray();
        }

        if ($this->fieldSets && $this->fieldSets->isNotEmpty()) {
            $query['fields'] = $this->fieldSets->toArray();
        }

        if ($this->sort && $this->sort->isNotEmpty()) {
            $query['sort'] = $this->sort->toArray();
        }

        if (is_array($this->pagination) && !empty($this->pagination)) {
            $query['page'] = $this->pagination;
        }

        if (is_array($this->filters) && !empty($this->filters)) {
            $query['filter'] = $this->filters;
        }

        return $query;
    }

}
