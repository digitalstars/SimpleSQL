# SimpleSQL

## Подключение

```composer require digitalstars/simple-sql```

## В общих словах

Библиотека представляет собой удобный интерфейс для конструирования SQL запросов.

## Быстрый старт

Ниже приведён пример подключения библиотеки и настройки, необходимой для начала работы. Все примеры будут приведены без
этих строк инициализации.

```php
require_once __DIR__ . "/lib/autoload.php";

use DigitalStars\SimpleSQL\Parser;

$pdo = new \PDO(DB_DSN, DB_LOGIN, DB_PASS);

Parser::setPDO($pdo);
```

## Основные компоненты

### Parser

Класс Parser - это постобработка запроса. Он заполняет типизированные заполнители значениями.

#### static function setPDO(\PDO $pdo)

Метод устанавливает объект PDO, который будет использоваться для подготовки параметров запроса.

> **Внимание!** Метод setPDO должен быть вызван до начала использования библиотеки.

### From

Компонент From является обязательным для всех запросов. Он представляет из себя конструктор таблицы в запросе, к
которой идёт обращение.

#### static create(string|Select $sql = null, string $alias = null): self

Статический метод, инициализация объекта From.

Аргументы:

1) $sql - строка с именем таблицы в формате 'table_name' или сразу с алиасом 'table_name tn', или объект Select, который
   будет использован в качестве подзапроса.
2) $alias - алиас таблицы.

Если не указан алиас, то он будет сгенерирован автоматически.

#### getFrom(): string|Select

Возвращает имя таблицы или объект Select.

#### setFrom(string|Select $sql = null): self

Устанавливает имя таблицы или объект Select, который будет использован в качестве подзапроса.

#### getAlias(): string

Возвращает алиас таблицы.

#### setAlias(string $alias): self

Устанавливает алиас таблицы.

#### getSql(): string

Возвращает строку запроса, которая была сгенерирована и все заполнители заменены на значения.

#### getSqlRaw(): array

Метод используется для внутреннего взаимодействия внутри библиотеки. Но может быть полезен для отладки.

Возвращает массив:

1) 'sql' => string - строка запроса, которая была сгенерирована, но заполнители не были заменены на значения.
2) 'values' => array - массив значений для заполнителей.

### Join

Компонент Join представляет из себя конструктор JOIN-а в запросе.

#### static create(string $type = null, From|string $from = null, Where|string $where_or_lexema = null, $where_value = null): self

Статический метод, инициализация объекта Join.

Аргументы:

1) $type - тип JOIN-а. Возможные значения: 'INNER', 'LEFT', 'RIGHT'.
2) $from - объект From или строка с именем таблицы в формате 'table_name' или сразу с алиасом 'table_name tn'.
3) $where_or_lexema - объект Where или строка с условием JOIN-а.
4) $where_value - значение для заполнителя в условии JOIN-а.

Также есть статические методы для инициализации объекта Join для каждого типа JOIN-а. в них нет параметра $type:

- static function inner(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self - **INNER
  JOIN**
- static function left(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self - **LEFT JOIN
  **
- static function right(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self - **RIGHT
  JOIN**
- static function innerLateral(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self - *
  *INNER JOIN LATERAL**
- static function leftLateral(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self - *
  *LEFT JOIN LATERAL**
- static function rightLateral(From|string $from, Where|string $where_or_lexema = null, $where_value = null): self - *
  *RIGHT JOIN LATERAL**

#### getLateral(): bool

Возвращает true, если JOIN является LATERAL.

#### setLateral(bool $is = true): self

Устанавливает JOIN как LATERAL.

#### setType(string $type): self

Устанавливает тип JOIN-а. Возможные значения: 'INNER', 'LEFT', 'RIGHT'.

#### getType(): string

Возвращает тип JOIN-а.

#### setFrom(string|Select|From $from = null, string $alias = null): self

Устанавливает таблицу для запроса.

Параметры:

- $from - имя таблицы (в формате с алиасом или без), объект Select (подзапрос) или объект From (созданный заранее).
- $alias - алиас таблицы. (не обязательный). Если не указан, то будет сгенерирован автоматически.

#### getFrom(): From

Возвращает объект From, который был установлен в запросе.

#### setWhere(Where $where): self

Устанавливает объект Where для JOIN-а. По сути, это условие ON.

#### getWhere(): Where

Возвращает объект Where, который был установлен в запросе.

#### getSql(): string

Возвращает строку запроса, которая была сгенерирована и все заполнители заменены на значения.

#### getSqlRaw(): array

Метод используется для внутреннего взаимодействия внутри библиотеки. Но может быть полезен для отладки.

Возвращает массив:

1) 'sql' => string - строка запроса, которая была сгенерирована, но заполнители не были заменены на значения.
2) 'values' => array - массив значений для заполнителей.

### Where

Компонент Where представляет из себя конструктор условия. Он может быть использован как для WHERE, так и для ON.

> Внимание! Whare активно использует заполнители, и поддерживает все заполнители из digitalstars/DataBase. Подробнее
> о них можно прочитать в документации к библиотеке [digitalstars/DataBase](https://github.com/digitalstars/DataBase/).

#### static create(Where|string $lexema = null, array|string|int $value = null): self

Статический метод, инициализация объекта Where.

Аргументы:

1) $lexema - строка с условием. Может быть как другим объектом Where, так и строкой, содержащей заполнители.
2) $value - значение для заполнителя в условии. Если нужно передать несколько значений, то передаётся массив. Если нужно
   передать массив в качестве 1 значения (например для IN), то он должен быть обёрнут в массив.

#### w_and($lexema = null, $value = null): self

Добавляет к текущему объекту Where условие через AND.

#### w_or($lexema = null, $value = null): self

Добавляет к текущему объекту Where условие через OR.

#### clear(): self

Очищает текущий объект Where от условий.

#### getSqlRaw(): ?array

Метод используется для внутреннего взаимодействия внутри библиотеки. Но может быть полезен для отладки.

Возвращает массив:

1) 'sql' => string - строка запроса, которая была сгенерирована, но заполнители не были заменены на значения.
2) 'values' => array - массив значений для заполнителей.

## SELECT запросы

Класс Select представляет из себя конструктор запросов SELECT.

### static create(): self

Статический метод, инициализация объекта Select.

### setSelect(array $sql)

Устанавливает список полей для выборки. Принимает как массив полей, так и ассоциативный массив, где ключи - это алиасы,
а значения - это поля таблицы или функции.

#### Примеры:

Передаём массив полей:

```php
use DigitalStars\SimpleSQL\Select;
$select = Select::create()->setFrom('table_name tn'); // Устанавливаем таблицу в From

$select->setSelect(['id', 'name', 'age']);
```

```sql
SELECT tn.id   AS tn_id,
       tn.name AS tn_name,
       tn.age  AS tn_age
FROM table_name tn
```

Передаём массив полей, часть из которых с алиасами, часть вычисляемые:

```php
$select->setSelect([
    'id',
    'name',
    'age_now' => 'tn.age',
    'age_plus_10' => 'tn.age + 10',
    'age_plus_20' => 'tn.age + 20'
]);
```

```sql
SELECT tn.id       AS tn_id,
       tn.name     AS tn_name,
       tn.age      AS age_now,
       tn.age + 10 AS age_plus_10,
       tn.age + 20 AS age_plus_20
FROM table_name tn
```

### addSelect(string $field, string $alias = null): self

Добавляет поле в список выборки.

- $field - имя поля или выражение.
- $alias - алиас поля. (не обязательный)

#### Примеры:

```php
use DigitalStars\SimpleSQL\Select;
$select = Select::create()->setFrom('table_name tn'); // Устанавливаем таблицу в From

$select->addSelect('id');
$select->addSelect('name');
$select->addSelect('age', 'age_now');
$select->addSelect('age + 10', 'age_plus_10');
$select->addSelect('age + 20', 'age_plus_20');
```

```sql
SELECT tn.id    AS tn_id,
       tn.name  AS tn_name,
       age      AS age_now,
       age + 10 AS age_plus_10,
       age + 20 AS age_plus_20
FROM table_name tn
```

### getSelect(): array

Возвращает список полей для выборки. В том же формате, что и передавался в setSelect.

### setFrom(string|Select|From $from = null, string $alias = null): self

Устанавливает таблицу для запроса.

Параметры:

- $from - имя таблицы (в формате с алиасом или без), объект Select (подзапрос) или объект From (созданный заранее).
- $alias - алиас таблицы. (не обязательный). Если не указан, то будет сгенерирован автоматически.

#### Примеры:

Пример 1:

```php
use DigitalStars\SimpleSQL\Select;
use DigitalStars\SimpleSQL\Components\From;
$select = Select::create()->setSelect(['id', 'name', 'age']);

$select->setFrom('table_name tn');
// Или
$select->setFrom('table_name tn', 'tn');
// Или
$from = From::create('table_name tn');
$select->setFrom($from);
// Или
$from = From::create('table_name', 'tn');
$select->setFrom($from);
// Или
$select->setFrom('table_name');
```

```sql
SELECT tn.id   AS tn_id,
       tn.name AS tn_name,
       tn.age  AS tn_age
FROM table_name tn 
```

Пример 2:

```php
use DigitalStars\SimpleSQL\Select;

$select_sub = Select::create()
    ->setSelect(['id', 'name', 'age'])
    ->setFrom('table_name tn');

$select = Select::create()
    ->setSelect(['tn_name', 'tn_age'])
    ->setFrom($select_sub, 'sub');
```

```sql
SELECT sub.tn_name AS sub_tn_name,
       sub.tn_age  AS sub_tn_age
FROM (SELECT tn.id   AS tn_id,
             tn.name AS tn_name,
             tn.age  AS tn_age
      FROM table_name tn) sub
```

### getFrom(): From

Возвращает объект From, который был установлен в запросе.

### getJoinList(): array

Возвращает список объектов Join, которые были установлены в запросе.

### addJoin(array|Join $join): self

Добавляет объект Join (или массив объектов), в список JOIN-ов.

### setJoin(array|Join $join = null): self

Устанавливает объект Join (или массив объектов), в список JOIN-ов.

### Примеры использования JOIN-ов:

Пример 1:
```php
use DigitalStars\SimpleSQL\Components\Join;

$select = Select::create()
    ->setSelect(['name', 'age', 't2.other_info', 't3.other_info'])
    ->setFrom('table_name_one', 'tno')
    ->addJoin(Join::inner('table_name_two t2', 'tno.id = t2.id'))
    ->addJoin(Join::left('table_name_three t3', 'tno.id = t3.id'));
```

```sql
SELECT tno.name      AS tno_name,
       tno.age       AS tno_age,
       t2.other_info AS t2_other_info,
       t3.other_info AS t3_other_info
FROM table_name_one tno
         INNER JOIN table_name_two t2 on (tno.id = t2.id)
         LEFT JOIN table_name_three t3 on (tno.id = t3.id)
```

Пример 2 (с подзапросом):
```php
$select1 = Select::create()
    ->setSelect(['id', 'name', 'age', 't2.other_info', 't3.other_info'])
    ->setFrom('table_name_one', 'tno')
    ->addJoin(Join::inner('table_name_two t2', 'tno.id = t2.id'))
    ->addJoin(Join::left('table_name_three t3', 'tno.id = t3.id'));

$select = Select::create()
    ->setSelect(["t.id", "inf.tno_name", 'sum' => 'inf.t2_other_info + IFNULL(inf.t3_other_info, 0)'])
    ->setFrom('main_table', 't')
    ->addJoin(Join::inner(From::create($select1, 'inf'), 'inf.tno_id = t.id'));
```

```sql
SELECT t.id                                             AS t_id,
       inf.tno_name                                     AS inf_tno_name,
       inf.t2_other_info + IFNULL(inf.t3_other_info, 0) AS sum
FROM main_table t
         INNER JOIN (SELECT tno.id        AS tno_id,
                            tno.name      AS tno_name,
                            tno.age       AS tno_age,
                            t2.other_info AS t2_other_info,
                            t3.other_info AS t3_other_info
                     FROM table_name_one tno
                              INNER JOIN table_name_two t2 on (tno.id = t2.id)
                              LEFT JOIN table_name_three t3 on (tno.id = t3.id)) inf on (inf.tno_id = t.id)
```

### getWhere(): Where

Возвращает объект Where, который был установлен в запросе.

### setWhere(Where $where = null): self

Устанавливает объект Where в запросе.

Пример:

```php
use DigitalStars\SimpleSQL\Select;
use DigitalStars\SimpleSQL\Components\Where;

$select = Select::create()
    ->setSelect(['id', 'name', 'age'])
    ->setFrom('table_name tn')
    ->setWhere(Where::create('age > ?i', 18)->w_and('name = ?s', 'John'));
```

```sql
SELECT tn.id   AS tn_id,
       tn.name AS tn_name,
       tn.age  AS tn_age
FROM table_name tn
WHERE (age > 18 AND name = 'John')
```

### getGroupBy(): array

Возвращает список полей для группировки.

### setGroupBy(array $group_by = null): self

Устанавливает список полей для группировки. Принимает массив алиасов или полей (как в setSelect).

### Пример:

```php
use DigitalStars\SimpleSQL\Select;

$select = Select::create()
    ->setSelect(['name', 'age', 'sum_salary' => 'SUM(salary)'])
    ->setFrom('table_name tn')
    ->setGroupBy(['name', 'age']);
```

```sql
SELECT tn.name     AS tn_name,
       tn.age      AS tn_age,
       SUM(salary) AS sum_salary
FROM table_name tn
GROUP BY tn.name, tn.age
```

### getHawing(): Where

Возвращает объект Where, который был установлен в запросе.

### setHawing(Where $hawing = null): self

Устанавливает объект Where в запросе.

Пример:

```php
use DigitalStars\SimpleSQL\Select;
use DigitalStars\SimpleSQL\Components\Where;

$select = Select::create()
    ->setSelect(['name', 'age', 'sum_salary' => 'SUM(salary)'])
    ->setFrom('table_name tn')
    ->setGroupBy(['name', 'age'])
    ->setHawing(Where::create('sum_salary > ?i', 1000));
```

```sql
SELECT tn.name     AS tn_name,
       tn.age      AS tn_age,
       SUM(salary) AS sum_salary
FROM table_name tn
GROUP BY tn.name, tn.age
HAVING (sum_salary > 1000)
```

### getOrderBy(): array

Возвращает список полей для сортировки в том формате, в котором они были установлены (setOrderBy).

### setOrderBy(array $order_by = null): self

Устанавливает список полей для сортировки. Ключ - это поле (как в методе setGroupBy), значение - это направление сортировки.

Пример:

```php
use DigitalStars\SimpleSQL\Select;

$select = Select::create()
    ->setSelect(['name', 'age', 'sum_salary' => 'SUM(salary)'])
    ->setFrom('table_name tn')
    ->setGroupBy(['name', 'age'])
    ->setOrderBy(['name' => 'ASC', 'age' => 'DESC']);
```

```sql
SELECT tn.name     AS tn_name,
       tn.age      AS tn_age,
       SUM(salary) AS sum_salary
FROM table_name tn
GROUP BY tn.name, tn.age
ORDER BY tn.name ASC, tn.age DESC
```

### getLimit(): ?int

Возвращает количество строк, которые будут выбраны. В случае, если не установлено, то возвращает null.

### setLimit(?int $limit = null): self

Устанавливает количество строк, которые будут выбраны.

### getShareMode(): ?string

Возвращает режим блокировки. В случае, если не установлено, то возвращает null.

### setShareModeUpdate(): self

Устанавливает режим FOR UPDATE.

### setShareModeShare(): self

Устанавливает режим FOR SHARE.

### setShareModeDefault(): self

Устанавливает режим блокировки по умолчанию.

### getSelectArray(): array

Собирает и возвращает массив, который содержит в себе все поля выборки в формате, который используется в запросе.

Ключ - это алиас поля, значение - это поле или выражение.

### getSelectFeels(): array

Возвращает массив алиасов полей выборки.

### getSql(): string

Возвращает строку запроса, которая была сгенерирована и все заполнители заменены на значения.

### getSqlRaw(): array

Метод используется для внутреннего взаимодействия внутри библиотеки. Но может быть полезен для отладки.

Возвращает массив:

1) 'sql' => string - строка запроса, которая была сгенерирована, но заполнители не были заменены на значения.
2) 'values' => array - массив значений для заполнителей.

## INSERT запросы

Класс Insert представляет из себя конструктор запросов INSERT.

### static create(): self

Статический метод, инициализация объекта Insert.

### setFields(array $sql = []): self

Устанавливает список полей для вставки. Принимает ассоциативный массив, где ключи - это поля, а значения - это заполнитель (или значение для вставки).

### addField(string $field, string|int|float $placeholder_or_raw_value = null): self

Добавляет поле в список полей для вставки.

Параметры:

- $field - имя поля.
- $placeholder_or_raw_value - заполнитель (или значение для вставки).

### getFields(): array

Возвращает список полей для вставки. (в том же формате, в котором они были установлены методом setFields).

### setValues(array|Select $values = []): self

Устанавливает список значений для вставки. Принимает массив значений или объект Select (если нужно вставить значения как из результата выполнения Select запроса).

> **Внимание!** Каждый элемент массива - это массив значений всех полей, под каждый заполнитель

### addValues(array $values = []): self

Добавляет значения для вставки.

### getValues(): array|Select

Возвращает список значений для вставки (или объект Select). В том же формате, в котором они были установлены методом setValues.

### setFrom(string|Select|From $from = null, string $alias = null): self

Устанавливает таблицу, в которую будет производиться вставка.

### getFrom(): From

Возвращает объект From, который был установлен в запросе.

### setIgnoreDuplicate(bool $is_ignore_duplicate = true): self

Устанавливает флаг IGNORE в INSERT.

### getIgnoreDuplicate(): bool

Возвращает true, если установлен флаг IGNORE.

### setFieldsUpdateOnDuplicate(array $fields = []): self

Устанавливает список полей, которые будут обновлены в случае, если запись уже существует.

### addFieldsUpdateOnDuplicate(array|string $fields): self

Добавляет поле в список полей, которые будут обновлены в случае, если запись уже существует.

### getFieldsUpdateOnDuplicate(): array

Возвращает список полей, которые будут обновлены в случае, если запись уже существует.

### getSql(): string

Возвращает строку запроса, которая была сгенерирована и все заполнители заменены на значения.

### getSqlRaw(): array

Метод используется для внутреннего взаимодействия внутри библиотеки. Но может быть полезен для отладки.

Возвращает массив:

1) 'sql' => string - строка запроса, которая была сгенерирована, но заполнители не были заменены на значения.
2) 'values' => array - массив значений для заполнителей.

### Примеры использования:

Пример 1:

```php
use DigitalStars\SimpleSQL\Insert;

$insert = Insert::create()
    ->setFields(['name' => '?s', 'age' => '?i'])
    ->addValues(['John', 25])
    ->addValues(['Mike', 30])
    ->setFrom('table_name')
    ->setFieldsUpdateOnDuplicate(['age']);

// Или

$insert = Insert::create()
    ->setFields(['name' => '?s', 'age' => '?i'])
    ->setValues([['John', 25], ['Mike', 30]])
    ->setFrom('table_name')
    ->setFieldsUpdateOnDuplicate(['age']);
```

```sql
INSERT INTO table_name (name, age)
VALUES ('John', 25),
       ('Mike', 30)
    ON DUPLICATE KEY UPDATE age = VALUES(age)
```

Пример 2 (значения из Select запроса):

```php
use DigitalStars\SimpleSQL\Insert;
use DigitalStars\SimpleSQL\Select;

$select = Select::create()
    ->setSelect(['name', 'age'])
    ->setFrom('table_name_2');

$insert = Insert::create()
    ->setFields(['name' => '?s', 'age' => '?i'])
    ->setValues($select)
    ->setFrom('table_name')
    ->setIgnoreDuplicate();
```

```sql
INSERT IGNORE INTO table_name (name, age)
SELECT tn2.name AS tn2_name, tn2.age AS tn2_age
FROM table_name_2 tn2
```

## UPDATE запросы

Класс Update представляет из себя конструктор запросов UPDATE.

### static create(): self

Статический метод, инициализация объекта Update.

### setSet(array $sql = [], array $values = []): self

Устанавливает список полей и их значений для обновления.

Параметры:

- $sql - ассоциативный массив, где ключи - это поля, а значения - это заполнитель (или значение для вставки без валидации).
- $values - массив значений, которые будут подставлены под заполнители

### addSet(string $field, string|int|float $placeholder_or_raw_value = null, $value = null): self

Добавляет поле и его значение для обновления.

Параметры:

- $field - имя поля.
- $placeholder_or_raw_value - заполнитель (или значение для вставки без валидации).
- $value - значение для заполнителя.

### getSet(): array

Возвращает список полей и их значений для обновления.

Возвращает ассоциативный массив, где ключи - это поля, а значения, следующий массив:

- `field` - имя поля.
- `placeholder` - заполнитель (или значение для вставки без валидации).
- `value` - значение для заполнителя.

### getSetSQLRaw(): array

Возвращает данные, которые были установлены для обновления. Используется для внутреннего взаимодействия внутри библиотеки. Но может быть полезен для отладки.

Возвращает массив:

1) 'sql' => SQL запрос, который был сгенерирован, но заполнители не были заменены на значения.
2) 'values' => массив значений для заполнителей.

### getFrom(): From

Возвращает объект From, который был установлен в запросе.

### setFrom(string|Select|From $from = null, string $alias = null): self

Устанавливает таблицу, в которой будет производиться обновление.

### getJoinList(): array

Возвращает список объектов Join, которые были установлены в запросе. (По аналоии с Select)

### setJoin(array|Join $join = null): self

Устанавливает объект Join (или массив объектов), в список JOIN-ов. (По аналоии с Select)

### addJoin(array|Join $join): self

Добавляет объект Join (или массив объектов), в список JOIN-ов. (По аналоии с Select)

### getWhere(): Where

Возвращает объект Where, который был установлен в запросе. Отвечает за условие WHERE.

### setWhere(Where $where = null): self

Устанавливает объект Where в запросе. Отвечает за условие WHERE.

### getLimit(): ?int

Возвращает количество строк, которые будут обновлены. В случае, если не установлено, то возвращает null.

### setLimit(?int $limit = null): self

Устанавливает количество строк, которые будут обновлены.

### getSql(): string

Возвращает строку запроса, которая была сгенерирована и все заполнители заменены на значения.

### getSqlRaw(): array

Метод используется для внутреннего взаимодействия внутри библиотеки. Но может быть полезен для отладки.

Возвращает массив:

1) 'sql' => string - строка запроса, которая была сгенерирована, но заполнители не были заменены на значения.
2) 'values' => array - массив значений для заполнителей.

### Примеры использования:

Пример 1:

```php
use DigitalStars\SimpleSQL\Update;

$update = Update::create()
    ->setSet(['name' => '?s', 'age' => '?i'], ['John', 25])
    ->setFrom('table_name')
    ->setWhere(Where::create('tn.id = ?i', 1));

// Или

$update = Update::create()
    ->addSet('name', '?s', 'John')
    ->addSet('age', '?i', 25)
    ->setFrom('table_name')
    ->setWhere(Where::create('tn.id = ?i', 1));
```

```sql
UPDATE table_name tn
SET tn.name = 'John',
    tn.age  = 25
WHERE (tn.id = 1)
```

Пример 2:

```php
use DigitalStars\SimpleSQL\Update;
use DigitalStars\SimpleSQL\Select;

$select = Select::create()
    ->setSelect(['name', 'age', 'id'])
    ->setFrom('table_name_2');

$update = Update::create()
    ->setSet(['name' => '?s', 'age' => '?i'], ['John', 25])
    ->setFrom('table_name')
    ->setWhere(Where::create('tn.id = ?i', 1))
    ->setJoin(Join::inner(From::create($select, 'tn2'), 'tn2.tn2_id = tn.id'));
```

```sql
UPDATE table_name tn
    INNER JOIN (SELECT tn2.name AS tn2_name,
                       tn2.age  AS tn2_age,
                       tn2.id   AS tn2_id
                FROM table_name_2 tn2) tn2 on (tn2.tn2_id = tn.id)
SET tn.name = 'John',
    tn.age  = 25
WHERE (tn.id = 1)
```
