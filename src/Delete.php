<?php


namespace DigitalStars\SimpleSQL;

use DigitalStars\SimpleSQL\Components\From;
use DigitalStars\SimpleSQL\Components\Join;
use DigitalStars\SimpleSQL\Components\Where;

class Delete {
    private From $from;
    private ?int $limit = null;
    private Parser $parser;
    private Where $where;

    public function __construct() {
        $this->from = From::create();
        $this->where = Where::create();
        $this->parser = Parser::create();
    }

    public static function create(): self {
        return new self();
    }

    // Геттеры и сеттеры

    public function getFrom(): From {
        return $this->from;
    }

    public function setFrom(string|Select|From $from = null, string $alias = null): self {
        if ($from instanceof From)
            $this->from = $from;
        else
            $this->from = From::create($from, $alias);
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

        // Собираем TABLE_NAME
        $from = '`' . $this->from->getFrom() . '`';
        if ($from instanceof Select)
            throw new Exception('Delete not support Select as Table name');

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

        $result_sql = "DELETE FROM $from $where $limit";

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
