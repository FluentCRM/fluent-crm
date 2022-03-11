<?php

namespace FluentCrm\Framework\Database\Query;

use Closure;
use DateTime;
use Exception;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Database\Query\Builder;
use FluentCrm\Framework\Database\Query\Grammar;
use FluentCrm\Framework\Database\Query\Processor;
use FluentCrm\Framework\Database\QueryException;
use FluentCrm\Framework\Database\Query\Expression;
use FluentCrm\Framework\Database\ConnectionInterface;

class WPDBConnection implements ConnectionInterface
{
    protected $wpdb = null;

    /**
     * Count of active transactions
     *
     * @var int
     */
    protected $transactionCount = 0;

    /**
     * The database connection configuration options.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Construct the Connection object
     */
    public function __construct($wpdb, $config)
    {
        $this->wpdb = $wpdb;
        $this->config = $config;
        $this->wpdb->show_errors(false);
    }

    public function getWPDB()
    {
        return $this->wpdb;
    }

    /**
     * Get the database connection name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getConfig('name');
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param  string $table
     *
     * @return \FluentCrm\Framework\Database\Query\Builder
     */
    public function table($table)
    {
        $processor = $this->getPostProcessor();

        $query = new Builder($this, $this->getQueryGrammar(), $processor);

        return $query->from($table);
    }

    /**
     * Get a new raw query expression.
     *
     * @param  mixed $value
     *
     * @return \FluentCrm\Framework\Database\Query\Expression
     */
    public function raw($value)
    {
        return new Expression($value);
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param  string $query
     * @param  array $bindings
     * @param  bool $useReadPdo
     * @throws QueryException
     *
     * @return mixed
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $query = $this->bindParams($query, $bindings);

        $result = $this->wpdb->get_row($query);

        if ($result === false || $this->wpdb->last_error) {
            throw new QueryException($query, $bindings, new Exception($this->wpdb->last_error));
        }

        return $result;
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     * @param  bool $useReadPdo
     * @throws QueryException
     *
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        $query = $this->bindParams($query, $bindings);

        $result = $this->wpdb->get_results($query);

        if ($result === false || $this->wpdb->last_error) {
            throw new QueryException($query, $bindings, new Exception($this->wpdb->last_error));
        }

        return $result;
    }

    /**
     * A hacky way to emulate bind parameters into SQL query
     *
     * @param $query
     * @param $bindings
     *
     * @return mixed
     */
    protected function bindParams($query, $bindings, $update = false)
    {
        $query = str_replace('"', '`', $query);

        $bindings = $this->prepareBindings($bindings);

        if (!$bindings) {
            return $query;
        }

        $bindings = array_map(function ($replace) {

            if (is_string($replace)) {
                $replace = "'" . esc_sql($replace) . "'";
            } elseif ($replace === null) {
                $replace = "null";
            }

            return $replace;

        }, $bindings);

        $query = str_replace(array('%', '?'), array('%%', '%s'), $query);

        $query = vsprintf($query, $bindings);

        return $query;
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     *
     * @return bool
     */
    public function insert($query, $bindings = array())
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Run an update statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     *
     * @return int
     */
    public function update($query, $bindings = array())
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     *
     * @return int
     */
    public function delete($query, $bindings = array())
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string $query
     * @param  array $bindings
     *
     * @return mixed
     */
    public function statement($query, $bindings = array())
    {
        $newQuery = $this->bindParams($query, $bindings, true);

        $result = $this->unprepared($newQuery);

        if ($result === false || $this->wpdb->last_error) {
            throw new QueryException($newQuery, $bindings, new Exception($this->wpdb->last_error));
        }

        return $result;
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string $query
     * @param  array $bindings
     *
     * @return int
     */
    public function affectingStatement($query, $bindings = array())
    {
        $newQuery = $this->bindParams($query, $bindings, true);

        $result = $this->wpdb->query($newQuery);

        if ($result === false || $this->wpdb->last_error) {
            throw new QueryException($newQuery, $bindings, new Exception($this->wpdb->last_error));
        }

        return intval($result);
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param  string $query
     *
     * @return bool
     */
    public function unprepared($query)
    {
        return $this->wpdb->query($query);
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array $bindings
     *
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {

            // Micro-optimization: check for scalar values before instances
            if (is_bool($value)) {
                $bindings[$key] = intval($value);
            } elseif (is_scalar($value)) {
                continue;
            } elseif ($value instanceof DateTime) {
                // We need to transform all instances of the DateTime class into an actual
                // date string. Each query grammar maintains its own date string format
                // so we'll just ask the grammar for the format to get from the date.
                $bindings[$key] = $value->format($grammar->getDateFormat());
            }
        }

        return $bindings;
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @param  Closure $callback
     * @param  int  $attempts
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        $this->beginTransaction();
        try {
            $data = $callback();
            $this->commit();
            return $data;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $transaction = $this->unprepared("START TRANSACTION;");

        if (false !== $transaction) {
            $this->transactionCount++;
        }
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit()
    {
        if ($this->transactionCount < 1) {
            return;
        }

        $transaction = $this->unprepared("COMMIT;");

        if (false !== $transaction) {
            $this->transactionCount--;
        }
    }

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack()
    {
        if ($this->transactionCount < 1) {
            return;
        }

        $transaction = $this->unprepared("ROLLBACK;");

        if ($transaction !== false) {
            $this->transactionCount--;
        }
    }

    /**
     * Get the number of active transactions.
     *
     * @return int
     */
    public function transactionLevel()
    {
        return $this->transactionCount;
    }

    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param  Closure $callback
     *
     * @return array
     */
    public function pretend(Closure $callback)
    {
        // ...
    }

    public function getPostProcessor()
    {
        return new Processor;
    }

    public function getQueryGrammar()
    {
        $grammar = new MySqlGrammar;

        $grammar->setTablePrefix($this->wpdb->prefix);

        return $grammar;
    }

    /**
     * Return self as PDO
     *
     * @return \FluentCrm\Framework\Database\Query\WPDBConnection
     */
    public function getPdo()
    {
        return $this;
    }

    /**
     * Return the last insert id
     *
     * @param  string $args
     *
     * @return int
     */
    public function lastInsertId($args)
    {
        return $this->wpdb->insert_id;
    }

    /**
     * Get an option from the configuration options.
     *
     * @param  string|null  $option
     * @return mixed
     */
    public function getConfig($option)
    {
        return Arr::get($this->config, $option);
    }
}
