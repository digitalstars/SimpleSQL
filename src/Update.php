<?php


namespace DigitalStars\SimpleSQL;

use DigitalStars\SimpleSQL\Components\From;
use DigitalStars\SimpleSQL\Components\Join;
use DigitalStars\SimpleSQL\Components\Where;

class Update {
    private array $set = [];
    private From $from;
    private array $join = [];
    private Where $where;
    private ?int $limit = null;
    private Parser $parser;

    public function __construct() {
        $this->from = From::create();
        $this->where = Where::create();
        $this->parser = Parser::create();
    }

    public static function create(): self {
        return new self();
    }

    // Геттеры и сеттеры

    private array $cache_set_raw = [];
    private bool $is_cache_set_raw = false;
    public function getSet(): array {
        return $this->set;
    }

    public function getSetSQLRaw(): array {
        if ($this->is_cache_set_raw)
            return $this->cache_set_raw;

        $this->is_cache_set_raw = true;
        $sql = [];
        $value = [];
        $alias = $this->getFrom()->getAlias();

        foreach ($this->set as $set) {
            if (!str_contains($set['field'], '.'))
                $set['field'] = "$alias.$set[field]";

            $sql[] = "$set[field] = $set[placeholder]";
            if (!is_null($set['value']))
                $value[] = $set['value'];
        }

        $this->cache_set_raw = [
            'sql' => implode(', ', $sql),
            'value' => $value
        ];
        return $this->cache_set_raw;
    }

    public function setSet(array $sql = [], array $values = []): self {
        $this->set = [];
        $this->clearSetCache();

        foreach ($sql as $key => $placeholder) {
            $value = null;
            if (str_starts_with($placeholder, '?'))
                $value = array_shift($values);

            $this->set[$key] = [
                'field' => $key,
                'placeholder' => $placeholder ?: 'NULL',
                'value' => $value
            ];
        }

        return $this;
    }

    public function addSet(string $field, string|int|float $placeholder_or_raw_value = null, $value = null): self {
        $this->clearSetCache();

        $this->set[$field] = [
            'field' => $field,
            'placeholder' => $placeholder_or_raw_value ?: 'NULL',
            'value' => $value
        ];

        return $this;
    }

    private function clearSetCache(): self {
        $this->is_cache_set_raw = false;
        return $this;
    }

    public function getFrom(): From {
        return $this->from;
    }

    public function setFrom(string|Select|From $from = null, string $alias = null): self {
        $this->clearSetCache();
        if ($from instanceof From)
            $this->from = $from;
        else
            $this->from = From::create($from, $alias);
        return $this;
    }

    public function getJoinList(): array {
        return $this->join;
    }

    public function setJoin(array|Join $join = null): self {
        if (is_null($join)) {
            $this->join = [];
            return $this;
        }
        $this->join = is_array($join) ? $join : [$join];
        return $this;
    }

    public function addJoin(array|Join $join): self {
        if (is_array($join))
            array_push($this->join, ...$join);
        else
            $this->join[] = $join;
        return $this;
    }

    public function getWhere(): Where {
        return $this->where;
    }

    public function setWhere(Where $where = null): self {
        if (is_null($where))
            $this->where->clear();
        else
            $this->where = $where;
        return $this;
    }

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function setLimit(?int $limit = null): self {
        $this->limit = $limit;
        return $this;
    }

    // Методы

    private function generateResultSQL(): string {
        ['sql' => $result_sql, 'value' => $where_array] = $this->generateResultSQLRaw();

        if ($where_array)
            $result_sql = $this->parser->parse($result_sql, $where_array);

        return $result_sql;
    }

    private function generateResultSQLRaw(): array {
        $where_array = [];

        // Собираем FROM
        $from = $this->from->getSqlRaw();
        if (!empty($from['value']))
            array_push($where_array, ...$from['value']);
        $from = $from['sql'];

        // Собираем JOIN
        $join_raw_sql = [];
        /** @var Join $join */
        foreach ($this->join as $join) {
            $join_raw = $join->getSqlRaw();
            $join_raw_sql[] = $join_raw['sql'];
            if (!empty($join_raw['value']))
                array_push($where_array, ...$join_raw['value']);
        }
        $join_raw_sql = "\n" . join("\n", $join_raw_sql);

        // Собираем SET
        ['sql' => $update, 'value' => $update_value] = $this->getSetSQLRaw();
        if (!empty($update_value))
            array_push($where_array, ...$update_value);

        // Собираем WHERE
        $where_raw = $this->where->getSqlRaw();
        if (!empty($where_raw['value']))
            array_push($where_array, ...$where_raw['value']);
        $where = '';
        if (!empty($where_raw['sql']))
            $where = "WHERE $where_raw[sql]";

        // Собираем LIMIT
        $limit = '';
        if (!is_null($this->limit))
            $limit = "LIMIT $this->limit";

        $result_sql = "UPDATE $from $join_raw_sql SET $update $where $limit";

        return [
            'sql' => $result_sql,
            'value' => $where_array
        ];
    }

    public function getSql(): string {
        return $this->generateResultSQL();
    }

    public function getSqlRaw(): array {
        return $this->generateResultSQLRaw();
    }
}
