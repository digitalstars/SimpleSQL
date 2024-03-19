<?php

namespace DigitalStars\SimpleSQL\Components;

use DigitalStars\SimpleSQL\Parser;

class Join {

    private string $type = 'INNER';
    private From $from;
    private Where $where;
    private bool $is_lateral = false;

    public function __construct(string $type = null, From|string $from = null, Where|string $where_or_lexema = null, $where_value = null) {
        if ($type)
            $this->setType($type);
        if ($from)
            $this->setFrom($from);
        if ($where_or_lexema) {
            if ($where_or_lexema instanceof Where)
                $this->setWhere($where_or_lexema);
            else
                $this->setWhere(Where::create($where_or_lexema, $where_value));
        } else
            $this->where = Where::create();
    }

    public static function create(string $type = null, From|string $from = null, Where|string $where_or_lexema = null, $where_value = null): self {
        return new self($type, $from, $where_or_lexema, $where_value);
    }

    public static function inner(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self {
        return new self('INNER', $from, $where_or_lexema, $where_value);
    }

    public static function left(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self {
        return new self('LEFT', $from, $where_or_lexema, $where_value);
    }

    public static function right(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self {
        return new self('RIGHT', $from, $where_or_lexema, $where_value);
    }

    public static function innerLateral(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self {
        return (new self('INNER', $from, $where_or_lexema, $where_value))->setLateral();
    }

    public static function leftLateral(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self {
        return (new self('LEFT', $from, $where_or_lexema, $where_value))->setLateral();
    }

    public static function rightLateral(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self {
        return (new self('RIGHT', $from, $where_or_lexema, $where_value))->setLateral();
    }

    public function setLateral(bool $is = true): self {
        $this->is_lateral = $is;
        return $this;
    }

    public function getLateral(): bool {
        return $this->is_lateral;
    }

    public function setType(string $type): self {
        $this->type = $type;
        return $this;
    }

    public function getType(): string {
        return $this->type;
    }

    public function setFrom(From|string $from): self {
        if (is_string($from))
            $from = From::create($from);
        $this->from = $from;
        return $this;
    }

    public function getFrom(): From {
        return $this->from;
    }

    public function setWhere(Where $where): self {
        $this->where = $where;
        return $this;
    }

    public function getWhere(): Where {
        return $this->where;
    }

    public function getSqlRaw(): ?array {
        $where_array = [];

        // Собираем FROM
        $from = $this->from->getSqlRaw();
        if (!empty($from['value']))
            array_push($where_array, ...$from['value']);
        $from = $from['sql'];

        // Собираем WHERE
        $where_raw = $this->where->getSqlRaw();
        $where = 'on 1';
        if (!empty($where_raw['value']))
            array_push($where_array, ...$where_raw['value']);
        if (!empty($where_raw['sql']))
            $where = "on $where_raw[sql]";

        // Обработка LATERAL
        $lateral = '';
        if ($this->is_lateral) {
            $lateral = 'LATERAL ';
        }

        return [
            'sql' => $this->getType() . ' JOIN ' . $lateral . $from . ' ' . $where,
            'value' => $where_array
        ];
    }

    public function getSql(): string {
        ['sql' => $result_sql, 'value' => $where_array] = $this->getSqlRaw();

        if ($where_array)
            $result_sql = Parser::create()->parse($result_sql, $where_array);

        return $result_sql;
    }
}
