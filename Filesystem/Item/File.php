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



namespace DL\Filesystem\Item;

use DL\Filesystem\Item;
use DL\Filesystem\Exception as FS_Exception;

/**
 * Представление файла
 *
 * @package DL_Core
 * @subpackage Filesystem
 * @author Victor Yasinovsky
 */
class File extends Item {



    /**
     * Конструктор
     *
     * @param string $path Путь к файлу
     * @param bool $check Проверять существование
     * @throws Exception
     */
    public function __construct($path, $check=true) {
        parent::__construct($path, $check);
        if (!is_file($this->_path)) {
            throw new Exception(
                'File "' . $this->_path . '" is not a regular file'
            );
        }
    }



    /**
     * Является ли элемент обычным файлом?
     *
     * @return bool
     */
    public function isFile() {
        return true;
    }



    /**
     * Удаляет файл
     *
     * @throws Exception
     */
    protected function _delete() {
        if (!@unlink($this->path())) {
            // Здесь через $this->path() для проверки
            throw new Exception( // Не получилось удалить
                'Cannot delete file "' . $this->_path . '"'
            );
        }
        clearstatcache();
    }



    /**
     * Возвращает содержимое
     *
     * @return string
     */
    public function read() {
        return file_get_contents($this->path());
    }



    /**
     * Пишет данные и возвращает число записанных байтов
     *
     * @param string $data Данные
     * @return int
     * @throws Exception
     */
    public function write($data) {
        try {
            $temp_id = null;
            if ($this->_transaction->in()) {
                // Здесь обращение через $this->path() для проверки
                $temp_id = $this->_recycle_bin->drop($this->path());
                $this->_transaction->log($this->_recycle_bin, 'restore', array($temp_id));
                $this->_fs->makeFile($this->_path);
            }
            // Теперь уже можно попробовать записать данные в наш файл
            $result = @file_put_contents($this->path(), $data, LOCK_EX);
            if (!is_int($result)) { // Произошла ошибка во время записи
                throw new Exception('An error occurred while writing data');
            }
        }
        catch (FS_Exception $e) {
            if (isset($temp_id)) { $this->_recycle_bin->restore($temp_id); }
            throw new Exception( // Не получилось записать данные
                'Cannot write data to file "' . $this->_path . '"', 0, $e
            );
        }
        clearstatcache();
        return $result;
    }



    /**
     * Дописывает данные в конец файла и возвращает число записанных байтов
     *
     * @param string $data Данные
     * @return int
     * @throws Exception
     */
    public function append($data) {
        try {
            $temp_id = null;
            if ($this->_transaction->in()) {
                $need_copy_data = $this->size() > 0;
                // Положим существующий уже сейчас файл в корзину
                // Здесь обращение через $this->path() для проверки
                $temp_id = $this->_recycle_bin->drop($this->path());
                $this->_transaction->log($this->_recycle_bin, 'restore', array($temp_id));
                $this->_fs->makeFile($this->_path); // Создадим новый пустой файл
                if ($need_copy_data) { // Файл был ненулевой - надо скопировать!
                    $source = $this->_recycle_bin->getResource($temp_id); // Источник
                    $destination = fopen($this->_path, 'w'); // Поток назначения
                    stream_copy_to_stream($source, $destination); // Копируем ...
                    fclose($source); fclose($destination); // Закроем дескрипторы
                }
            }
            // Теперь уже можно попробовать дописать данные в конец нашего файла
            $result = @file_put_contents($this->path(), $data, FILE_APPEND | LOCK_EX);
            if (!is_int($result)) { // Произошла ошибка во время записи
                throw new Exception('An error occurred while writing data');
            }
        }
        catch (FS_Exception $e) {
            if (isset($temp_id)) { $this->_recycle_bin->restore($temp_id); }
            throw new Exception( // Не получилось дописать данные
                'Cannot append data to file "' . $this->_path . '"', 0, $e
            );
        }
        clearstatcache();
        return $result;
    }



    /**
     * Возвращает размер файла
     *
     * @return int
     */
    public function size() {
        return filesize($this->path());
    }



}