<?php
/**
 * DesignLab Framework
 *
 * Copyright (c) 2001-2013, DesignLab, LLC. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *  -   Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *
 *  -   Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in
 *      the documentation and/or other materials provided with the
 *      distribution.
 *
 *  -   Neither the name of the DesignLab, LLC nor the names of its
 *      contributors may be used to endorse or promote products derived
 *      from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright   2001-2013 DesignLab, LLC.
 * @author      Victor Yasinovsky <victor@designlab.ru>
 * @license     http://opensource.org/licenses/BSD-3-Clause
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