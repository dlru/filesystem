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

/**
 * Представление директории
 *
 * @package DL_Core
 * @subpackage Filesystem
 * @author Victor Yasinovsky
 */
class Directory extends Item implements \IteratorAggregate {



    /**
     * Конструктор
     *
     * @param string $path Путь к директории
     * @param bool $check Проверять существование
     * @throws Exception
     */
    public function __construct($path, $check=true) {
        parent::__construct($path, $check);
        if (!is_dir($this->_path)) {
            throw new Exception(
                'File "' . $this->_path . '" is not a directory'
            );
        }
    }



    /**
     * Является ли элемент директорией?
     *
     * @return bool
     */
    public function isDir() {
        return true;
    }



    /**
     * Возвращает итератор диреториии
     *
     * @return Directory\Iterator|\Traversable
     * @todo Придумать механизм кэширования
     */
    public function getIterator() {
        // Здесь через $this->path() для проверки
        return new Directory\Iterator($this->path());
    }



    /**
     * Удаляет директорию
     *
     * @throws Exception
     */
    protected function _delete() {
        // Пройдем по содержимому директории
        foreach ($this->getIterator() as $item) {
            $item->delete(); // ... Красиво!?
        }
        // Теперь удаляем ее саму
        if (!@rmdir($this->_path)) {
            throw new Exception( // Не получилось удалить
                'Cannot delete directory "' . $this->_path . '"'
            );
        }
        clearstatcache();
    }



    /**
     * Копирует директорию
     *
     * @param string $destination Цель
     * @return bool
     */
    protected function _copy($destination) {
        $target = $this->_fs->makeDir($destination);
        // Пройдем по содержимому директории
        foreach ($this->getIterator() as $item) {
            $item->copy($target); // ... Красиво!?
        }
        return true;
    }



    public function children() {
        $result = array();
        foreach ($this->getIterator() as $item) {
            $result[] = $item;
        }
        return $result;
    }



}