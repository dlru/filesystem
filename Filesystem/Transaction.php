<?php
/**
 * DesignLab Framework
 *
 * @copyright   2001-2013 DesignLab, LLC.
 * @author      Victor Yasinovsky <victor@designlab.ru>
 * @link        http://www.designlab.ru
 */



namespace DL\Filesystem;

use DL\Filesystem\Transaction\Exception as FST_Exception;

/**
 * Транзакции файловой системы
 *
 * @package DL_Core
 * @subpackage Filesystem
 * @author Victor Yasinovsky
 */
class Transaction {



    /**
     * Флаг нахождения в транзакции
     *
     * @var bool
     */
    private $_state_in = false;

    /**
     * Журнал операций для отката
     *
     * @var array
     */
    private $_journal = array();



    /**
     * Начинает транзакцию
     *
     * @throws Transaction\Exception
     */
    public function begin() {
        if ($this->_state_in) { // Нельзя начать если уже начата
            throw new FST_Exception('Transaction already started', 10);
        }
        $this->_state_in = true;
    }



    /**
     * Принимает транзакцию
     *
     * @throws Transaction\Exception
     */
    public function commit() {
        if (!$this->_state_in) { // Нельзя принять не начатую
            throw new FST_Exception('Transaction was not started', 20);
        }
        // Важно! Объект транзакций стартует в конструкторе драйвера ФС,
        // но "commit()" будет вызван гарантировано после создания драйвера,
        // поэтому, для того чтобы получить драйвер - мы в праве сделать так:
        static $fs = null;
        if (is_null($fs)) {
            $fs = \DL\Filesystem::getInstance();
        }
        $this->_state_in = false;
        $this->_journal = array();
        $fs->recycleBin()->purge();
    }



    /**
     * Откатывает транзакцию
     *
     * @throws Transaction\Exception
     */
    public function rollback() {
        if (!$this->_state_in) { // Нельзя откатить не начатую
            throw new FST_Exception('Transaction was not started', 30);
        }
        // Отключим, чтобы не срабатывали логи транзакций из методов
        $this->_state_in = false; // ... драйвера во время откатов :)
        // Выполним все записи из лога событий в обратном порядке!
        $num_of_records = count($this->_journal);
        for ($i = $num_of_records - 1; $i >= 0; $i--) {
            list($object, $method, $arguments) = $this->_journal[$i];
            call_user_func_array( // Вызываем метод на драйвере ФС
                array($object, $method), $arguments
            );
        }
        $this->_journal = array();
    }



    /**
     * Записывает событие
     *
     * @param object $object Объект
     * @param string $method Имя метода
     * @param array $arguments Аргуметы вызова
     */
    public function log($object, $method, $arguments=array()) {
        if ($this->_state_in) { // Мы находимся в транзакции?
            $this->_journal[] = array( // Записываем в журнал
                $object, $method, $arguments
            );
        }
    }



    /**
     * Возвращает состояние нахождения в транзакции
     *
     * @return bool
     */
    public function in() {
        return $this->_state_in;
    }



}