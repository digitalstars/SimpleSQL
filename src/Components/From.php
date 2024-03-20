<?php

namespace DigitalStars\SimpleSQL\Components;

use DigitalStars\SimpleSQL\Select;

class From {
    private string|Select|null $from = null;
    private string $alias = '';

    public function __construct(string|Select $sql = null, string $alias = null) {
        if (is_string($sql) && !is_null($alias)) {
            $this->from = $sql;
            $this->alias = $alias;
            return;
        }

        if ($sql) {
            $this->setFrom($sql);
        }

        if ($alias) {
            $this->setAlias($alias);
        }
    }

    public static function create(string|Select $sql = null, string $alias = null): self {
        return new self($sql, $alias);
    }

    public function getFrom(): string|Select {
        return $this->from;
    }

    public function setFrom(string|Select $sql = null): self {
        if ($sql instanceof Select) {
            $this->from = $sql;
            if (empty($this->alias))
                $this->alias = $sql->getFrom()->getAlias();
            return $this;
        }

        if (is_null($sql)) {
            $this->from = null;
            $this->alias = '';
            return $this;
        }

        if (str_contains($sql, ' ')) {
            [$this->from, $this->alias] = explode(' ', $sql, 2);
            return $this;
        }

        $this->from = $sql;

        return $this;
    }

    public function getAlias(): string {
        if (empty($this->alias) && !empty($this->from)){
            if ($this->from instanceof Select) {
                $this->alias = $this->from->getFrom()->getAlias();
            } else {
                $this->alias = $this->from[0];
                if (preg_match_all("/_([^_])/", $this->from, $matches))
                    $this->alias .= implode('', $matches[1]);
            }
        }

        return $this->alias;
    }

    public function setAlias(string $alias): self {
        $this->alias = $alias;
        return $this;
    }

    public function getSql(): string {
        if (is_null($this->from))
            return '';
        if ($this->from instanceof Select)
            return "({$this->from->getSql()}) $this->alias";
        return "$this->from $this->alias";
    }

    public function getSqlRaw(): array {
        if (is_null($this->from))
            return [];
        if ($this->from instanceof Select) {
            ['sql' => $sql, 'value' => $value] = $this->from->getSqlRaw();
            return [
                'sql' => "($sql) $this->alias",
                'value' => $value
            ];
        }

        return [
            'sql' => "$this->from $this->alias",
            'value' => []
        ];
    }
}
