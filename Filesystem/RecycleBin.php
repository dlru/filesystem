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

use DL\Filesystem\RecycleBin\Exception as FSR_Exception;

/**
 * Корзина для хранения удаленных файлов
 *
 * @package DL_Core
 * @subpackage Filesystem
 * @author Victor Yasinovsky
 */
class RecycleBin {



    /**
     * Путь к рабочей директории
     *
     * @var string
     */
    private $_folder = null;

    /**
     * Уникальный идентификатор процесса
     *
     * @var string
     */
    private $_process_uid = null;



    /**
     * Список элементов корзины
     *
     * @var array
     */
    private $_items = array();

    /**
     * Текущий идентификатор
     *
     * @var int
     */
    private $_last_item_id = 0;



    /**
     * Конструктор
     *
     * @param string $folder Путь к рабочей директории
     * @throws RecycleBin\Exception
     */
    public function __construct($folder=null) {
        if (is_null($folder)) { // Путь не передан?
            $folder = sys_get_temp_dir(); // Пробуем найти
        }
        if (!is_string($folder)) {
            throw new FSR_Exception(
                'Temporary folder parameter is not a string', 10
            );
        }
        // Проврерим существование и то, что это директория
        if (!file_exists($folder) || !is_dir($folder)) {
            throw new FSR_Exception(
                'Temporary folder "' . $folder . '" is not exists', 11
            );
        }
        // Директория должна быть доступна на запись
        if (!is_writable($folder)) {
            throw new FSR_Exception(
                'Temporary folder "' . $folder . '" is not writable', 12
            );
        }
        // Приведем к правильному формату
        $this->_folder = realpath($folder);
        $this->_process_uid = self::_get_process_uid();
    }



    /**
     * Деструктор
     */
    public function __destruct() {
        $this->purge(); // Очищаемся
    }



    /**
     * Возвращает уникальный идентификатор процесса
     *
     * @return string
     */
    private static function _get_process_uid() {
        // Штатным образом получим время и микровремя
        list($microtime, $time) = explode(' ', microtime());
        return $time . '-' . substr($microtime, 2);
    }



    /**
     * Возвращает следующий номер
     *
     * @return int
     */
    private function _get_next_id() {
         // Увеличим и вернем значение
        return ++$this->_last_item_id;
    }



    /**
     * Возвращает имя временного файла
     *
     * @param int $id Номер файла
     * @return string
     */
    private function _get_temp_name($id) {
        return $this->_folder . DIRECTORY_SEPARATOR
            . $this->_process_uid . '-' . $id;
    }


    /**
     * Перемещает файл в корзину и возвращает его номер
     *
     * @param string $file Путь к файлу
     * @throws RecycleBin\Exception
     * @return int
     */
    public function drop($file) {
        try {
            if (!is_string($file)) {
                throw new FSR_Exception(
                    'Filename is not a string', 33
                );
            }
            if (!file_exists($file)) {
                throw new FSR_Exception(
                    'File "' . $file . '" is not exists', 30
                );
            }
            // Файл есть, теперь можно попытаться переместить его ...
            $id = $this->_get_next_id(); // Следующий порядковый номер
            $temp_name = $this->_get_temp_name($id); // Временное имя
            if (@rename($file, $temp_name)) {
                $this->_items[$id] = $file;
                clearstatcache();
            }
            else {
                throw new FSR_Exception( // По непонятной причине не удалось ...
                    'Cannot rename "' . $file . '" to "' . $temp_name . '"', 31
                );
            }
        }
        catch (FSR_Exception $e) {
            throw new FSR_Exception(
                'Cannot drop file "' . $file . '"', 32, $e
            );
        }
        return $id;
    }



    /**
     * Проверяет номер файла
     *
     * @param int $id Номер файла
     * @throws RecycleBin\Exception
     */
    private function _cast_id($id) {
        if (!is_int($id)) {
            throw new FSR_Exception(
                'File identifier is not an integer', 40
            );
        }
        if (!array_key_exists($id, $this->_items)) {
            throw new FSR_Exception(
                'File identifier is not exists', 41
            );
        }
    }



    /**
     * Восстанавливает файл из корзины
     *
     * @param int $id Номер файла
     * @throws RecycleBin\Exception
     */
    public function restore($id) {
        try {
            // Исключения могут быть выброшены до определения
            $file = null; // ... имени и пути к файлу, поэтому так
            $this->_cast_id($id); // Сразу же проверим номер файла
            // Собственно тут начинается работа по восстановлению
            $file = $this->_items[$id]; // Имя файла (полный путь)
            $temp_name = $this->_get_temp_name($id); // Временное имя
            if (@rename($temp_name, $file)) {
                unset($this->_items[$id]);
                clearstatcache();
            }
            else {
                throw new FSR_Exception( // Если не получилось вернуть файл на место
                    'Cannot rename "' . $temp_name . '" to "' . $file . '"', 50
                );
            }
        }
        catch (FSR_Exception $e) {
            throw new FSR_Exception(
                'Cannot restore file' . (isset($file) ? ' "' . $file . '"' : ''), 51, $e
            );
        }
    }



    /**
     * Возвращает указатель на файл из корзины
     *
     * @param int $id Номер файла
     * @param string $mode Режим доступа
     * @throws RecycleBin\Exception
     * @return resource
     */
    public function getResource($id, $mode='r') {
        try {
            $this->_cast_id($id); // Проверим номер файла
            $file = $this->_get_temp_name($id); // Получим имя файла
            $mode = strval($mode); // Приведем режим файла к строке ...
            if (!$result = @fopen($file, $mode)) { // ... и пробуем открыть
                throw new FSR_Exception( // Сформируем нормальный текст ошибки
                    'Cannot open file "' . $file . '" with "' . $mode . '" mode'
                );
            }
        }
        catch (FSR_Exception $e) { // Ресурс не получился ...
            throw new FSR_Exception('Cannot get resource', 0, $e);
        }
        return $result;
    }



    /**
     * Удаляет файл, ссылку или директорию
     *
     * @param string $file Путь к файлу
     */
    private static function _delete($file) {
        static $fso = null; // Получим драйвер ФС
        if (is_null($fso)) { // Нужен для ссылок :(
            $fso = \DL\Filesystem::getInstance();
        }
        switch (true) {
            // C файлами и символическими ссылками просто
            case is_file($file): @unlink($file); break;
            case $fso->_is_link($file): $fso->_delete_link($file); break;
            case is_dir($file):
                // Директорию надо удалять, обойдя ее рекурсивно!
                $iterator = new \DirectoryIterator($file); // Итератор
                foreach ($iterator as $item) { // Пройдем по элементам
                    if (!$item->isDot()) { // Это не точки-ссылки? :)
                        self::_delete($item->getPathName());
                    }
                }
                // Удаяем опустевшую к тому моменту
                @rmdir($file); // ... директорию!
                break;
        }
    }



    /**
     * Очищает содержимое корзины
     */
    public function purge() {
        if (!empty($this->_items)) {
            // Пройдем по списку текущих временных файлов
            foreach (array_keys($this->_items) as $id) {
                // И удалим каждый из них индивидуально
                self::_delete($this->_get_temp_name($id));
            }
            // Сбросим состояние
            $this->_items = array();
            clearstatcache();
        }
    }



}