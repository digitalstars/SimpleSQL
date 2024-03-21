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

    private bool $is_ignore_duplicate = false;

    private array $update_fields_on_duplicate = [];

    public function __construct() {
        $this->from = From::create();
        $this->parser = Parser::create();
    }

    public static function create(): self {
        return new self();
    }

    // Геттеры и сеттеры

    public function setFieldsUpdateOnDuplicate(array $fields = []): self {
        $this->update_fields_on_duplicate = $fields;
        return $this;
    }

    public function addFieldsUpdateOnDuplicate(array|string $fields): self {
        if (is_array($fields))
            array_push($this->update_fields_on_duplicate, ...$fields);
        else
            $this->update_fields_on_duplicate[] = $fields;
        return $this;
    }

    public function getFieldsUpdateOnDuplicate(): array {
        return $this->update_fields_on_duplicate;
    }

    public function setIgnoreDuplicate(bool $is_ignore_duplicate = true): self {
        $this->is_ignore_duplicate = $is_ignore_duplicate;
        return $this;
    }

    public function getIgnoreDuplicate(): bool {
        return $this->is_ignore_duplicate;
    }

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
        if ($this->insert_values instanceof Select)
            $this->insert_values = [];
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

        // Собираем INSERT IGNORE
        $result_sql = $this->is_ignore_duplicate ? 'IGNORE' : '';

        // Собираем TABLE_NAME
        $from = $this->from->getFrom();
        if ($from instanceof Select)
            throw new Exception('Insert not support Select as Table name');

        // Собираем FIELDS
        $fields_list = array_keys($this->insert_fields);
        $fields = implode(', ', $fields_list);

        // Собираем VALUES
        if ($this->insert_values instanceof Select) {
            ['sql' => $values, 'value' => $where_array] = $this->insert_values->getSqlRaw();
        } else {
            $values = 'VALUES ?v[' . implode(',', $this->insert_fields) . ']';
            $where_array[] = $this->insert_values;
        }

        // Собираем ON DUPLICATE KEY UPDATE
        $on_duplicate = '';
        if (!empty($this->update_fields_on_duplicate)) {
            $update_fields = [];
            $update_fields_correct = array_intersect($fields_list, $this->update_fields_on_duplicate);
            foreach ($update_fields_correct as $field) {
                $update_fields[] = "$field = VALUES($field)";
            }
            $on_duplicate = " ON DUPLICATE KEY UPDATE " . implode(', ', $update_fields);
        }

        $result_sql = "INSERT $result_sql INTO $from ($fields) $values $on_duplicate";

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
