<?php


namespace DigitalStars\SimpleSQL\Components;

use DigitalStars\SimpleSQL\Exception;

class Where {

    private $where_info = [];

    /**
     * sqlWhere constructor.
     * @param null|Where|string $lexema - поле с заполнителем или экземпляр Where
     * @param null|array|string|int $value - массив параметров
     */
    public function __construct(Where|string $lexema = null, array|string|int $value = null) {
        if ($lexema instanceof self) {
            $this->where_info[] = $lexema->getSqlRaw();
        } else if (is_string($lexema))
            $this->addWhereString($lexema, $value);
    }

    public static function create(Where|string $lexema = null, array|string|int $value = null): self {
        return new self($lexema, $value);
    }

    public function getSqlRaw(): ?array {
        if (empty($this->where_info))
            return null;

        $result_value = [
            'type' => 1,
            'sql' => [],
            'value' => []
        ];
        foreach ($this->where_info as $lexema) {
            if (!$lexema)
                continue;
            if ($lexema['type'] === 1) {
                $result_value['sql'][] = $lexema['sql'];
                if ($lexema['value'])
                    array_push($result_value['value'], ...$lexema['value']);
            } else if ($lexema['type'] === 2) {
                $result_value['sql'][] = $lexema['sql'];
            }
        }

        $result_value['sql'] = '('.join(' ', $result_value['sql']).')';

        return $result_value;
    }

    public function w_and(Where|string $lexema = null, $value = null): self {
        return $this->addWhereLexema($lexema, $value, 'AND');
    }

    public function w_or(Where|string $lexema = null, $value = null): self {
        return $this->addWhereLexema($lexema, $value, 'OR');
    }

    private function addWhereLexema(Where|string $lexema = null, $value = null, $separator = null): self {
        if (!empty($this->where_info) && $separator)
            $this->where_info[] = [
                'type' => 2, // SEPARATOR
                'sql' => $separator
            ];
        if ($lexema instanceof self) {
            $this->where_info[] = $lexema->getSqlRaw();
        } else if (is_string($lexema)) {
            $this->addWhereString($lexema, $value);
        } else
            throw new Exception('Lexema or value is invalid');

        return $this;
    }

    private function addWhereString(string $sql, $value = null): self {
        if (!is_null($value) && !is_array($value))
            $value = [$value];
        $this->where_info[] = [
            'type' => 1, // LEXEMA
            'sql' => $sql,
            'value' => $value
        ];
        return $this;
    }

    public function clear(): self {
        $this->where_info = [];
        return $this;
    }
}
