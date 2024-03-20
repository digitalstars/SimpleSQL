<?php


namespace DigitalStars\SimpleSQL;

use DigitalStars\SimpleSQL\Components\From;
use DigitalStars\SimpleSQL\Components\Join;
use DigitalStars\SimpleSQL\Components\Where;

class Select {
    private array $select = [];
    private From $from;
    private array $join = [];
    private Where $where;
    private Where $having;
    private array $group_by = [];
    private array $order_by = [];
    private ?int $limit = null;
    private ?string $share_mode = '';
    private Parser $parser;

    public function __construct() {
        $this->from = From::create();
        $this->where = Where::create();
        $this->having = Where::create();
        $this->parser = Parser::create();
    }

    public static function create(): self {
        return new self();
    }

    // Геттеры и сеттеры

    public function getSelect(): array {
        return $this->select;
    }

    public function setSelect(array $sql): self {
        $this->clearSelectArray();
        $this->select = $sql;
        return $this;
    }

    public function getFrom(): From {
        return $this->from;
    }

    public function setFrom(string|Select|From $from = null, string $alias = null): self {
        $this->clearSelectArray();
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

    public function getGroupBy(): array {
        return $this->group_by;
    }

    /** Задаёт группировку запроса
     * @param array|null $group_by - массив полей для группировки (без алиасов)
     * @return $this
     */
    public function setGroupBy(array $group_by = null): self {
        if (is_null($group_by))
            $this->group_by = [];
        else
            $this->group_by = $group_by;
        return $this;
    }

    public function getHawing(): Where {
        return $this->having;
    }

    public function setHawing(Where $hawing = null): self {
        if (is_null($hawing))
            $this->having->clear();
        else
            $this->having = $hawing;
        return $this;
    }

    public function getOrderBy(): array {
        return $this->order_by;
    }

    /** Задаёт порядок сортировки
     * @param array|null $order - массив полей для сортировки (ключ - поле, значение - направление сортировки (ASC, DESC))
     * @return $this
     */
    public function setOrderBy(array $order = null): self {
        if (is_null($order))
            $this->order_by = [];
        else
            $this->order_by = $order;
        return $this;
    }

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function setLimit(?int $limit = null): self {
        $this->limit = $limit;
        return $this;
    }

    public function getShareMode(): ?string {
        return $this->share_mode ?: null;
    }

    public function setShareModeUpdate(): self {
        $this->share_mode = 'FOR UPDATE';
        return $this;
    }

    public function setShareModeShare(): self {
        $this->share_mode = 'FOR SHARE';
        return $this;
    }

    public function setShareModeDefault(): self {
        $this->share_mode = null;
        return $this;
    }

    // Методы

    private array $cache_select_array = [];
    private bool $is_cache_select_array = false;
    public function getSelectArray(): array {
        if ($this->is_cache_select_array)
            return $this->cache_select_array;

        $this->is_cache_select_array = true;
        $this->cache_select_array = [];
        $alias = $this->from->getAlias();

        foreach ($this->select as $key => $value) {
            if (is_numeric($key)) {
                if (str_contains($value, '.')) {
                    $field_alias = str_replace('.', '_', $value);
                    $this->cache_select_array[$field_alias] = $value;
                } else {
                    $field_alias = "{$alias}_$value";
                    $this->cache_select_array[$field_alias] = "$alias.$value";
                }
            } else {
                $this->cache_select_array[$key] = $value;
            }
        }

        return $this->cache_select_array;
    }

    public function getSelectFeels(): array {
        return array_keys($this->getSelectArray());
    }

    private function clearSelectArray() {
        $this->is_cache_select_array = false;
        $this->cache_select_array = [];
    }

    private function validateAndGenerateSelectList(array $select_list, string $lexema): array {
        $result_list = [];
        foreach ($select_list as $field => $value) {
            if (isset($this->select[$field])) {
                $result_list[$field] = $value;
                continue;
            }
            if (isset($this->cache_select_array[$field])) {
                $result_list[$this->cache_select_array[$field]] = $value;
                continue;
            }

            if (str_contains($field, '.')) {
                $field = str_replace('.', '_', $field);
            }

            if (isset($this->cache_select_array[$field])) {
                $result_list[$this->cache_select_array[$field]] = $value;
                continue;
            }

            $field = $this->getFrom()->getAlias() . '_' . $field;

            if (isset($this->cache_select_array[$field])) {
                $result_list[$this->cache_select_array[$field]] = $value;
                continue;
            }

            throw new Exception("$lexema: Field '$field' not found in SELECT list");
        }

        return $result_list;
    }

    private function generateResultSQL(): string {
        ['sql' => $result_sql, 'value' => $where_array] = $this->generateResultSQLRaw();

        if ($where_array)
            $result_sql = $this->parser->parse($result_sql, $where_array);

        return $result_sql;
    }

    private function generateResultSQLRaw(): array {
        $where_array = [];
        $alias = $this->from->getAlias();

        // Собираем SELECT
        $select = [];
        foreach ($this->getSelectArray() as $key => $value) {
            $select[] = "$value AS $key";
        }
        $select = implode(', ', $select);

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

        // Собираем WHERE
        $where_raw = $this->where->getSqlRaw();
        if (!empty($where_raw['value']))
            array_push($where_array, ...$where_raw['value']);
        $where = '';
        if (!empty($where_raw['sql']))
            $where = "WHERE $where_raw[sql]";

        // Собираем HAVING
        $hawing_raw = $this->having->getSqlRaw();
        if (!empty($hawing_raw['value']))
            array_push($where_array, ...$hawing_raw['value']);
        $hawing = '';
        if (!empty($hawing_raw['sql']))
            $hawing = "HAVING $hawing_raw[sql]";

        // Собираем GROUP BY
        $group_by = '';
        if (!empty($this->group_by)) {
            $group_by_list = array_keys(
                $this->validateAndGenerateSelectList(array_fill_keys($this->group_by, 1), 'GROUP_BY'));
            $group_by = "GROUP BY " . implode(', ', $group_by_list);
        }

        // Собираем ORDER BY
        $order_by = '';
        if (!empty($this->order_by)) {
            $order_by = "ORDER BY " . str_replace('=', ' ',
                http_build_query(
                    $this->validateAndGenerateSelectList(
                        $this->order_by, 'ORDER_BY'), null, ', '));
        }

        // Собираем LIMIT
        $limit = '';
        if (!is_null($this->limit))
            $limit = "LIMIT $this->limit";

        $share_mode = $this->share_mode ?: '';

        $result_sql = "SELECT $select FROM $from $join_raw_sql $where $group_by $hawing $order_by $limit $share_mode";

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
