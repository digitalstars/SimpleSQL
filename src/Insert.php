<?php


namespace DigitalStars\SimpleSQL;

use DigitalStars\SimpleSQL\Components\From;
use DigitalStars\SimpleSQL\Components\Join;
use DigitalStars\SimpleSQL\Components\Where;

class Insert {
    private array $insert_fields = [];
    private array|Select $insert_values = [];
    private From $from;
    private ?int $limit = null;
    private Parser $parser;

    public function __construct() {
        $this->from = From::create();
        $this->parser = Parser::create();
    }

    public static function create(): self {
        return new self();
    }

    // Геттеры и сеттеры

    public function setFields(array $sql = []): self {
        $this->insert_fields = [];
        foreach ($sql as $key => $placeholder) {
            $this->insert_fields[$key] = $placeholder ?: 'NULL';
        }

        return $this;
    }

    public function addField(string $field, string|int|float $placeholder_or_raw_value = null): self {
        $this->insert_fields[$field] = $placeholder_or_raw_value ?: 'NULL';
        return $this;
    }

    public function getFields(): array {
        return $this->insert_fields;
    }

    public function setValues(array|Select $values = []): self {
        $this->insert_values = $values;
        return $this;
    }

    public function addValues(array $values = []): self {
        $this->insert_values[] = $values;
        return $this;
    }

    public function getValues(): array|Select {
        return $this->insert_values;
    }

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
        $from = $this->from->getFrom();
        if ($from instanceof Select)
            throw new Exception('Insert not support Select as Table name');

        // Собираем FIELDS
        $fields = implode(', ', array_keys($this->insert_fields));

        // Собираем VALUES
        if ($this->insert_values instanceof Select) {
            ['sql' => $values, 'value' => $where_array] = $this->insert_values->getSqlRaw();
        } else {
            $values = 'VALUES ?v[' . implode(',', $this->insert_fields) . ']';
            $where_array[] = $this->insert_values;
        }

        $result_sql = "INSERT INTO $from ($fields) $values";

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
