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



namespace DL\Filesystem\Item\Directory;

/**
 * Итератор директории
 *
 * @package DL_Core
 * @subpackage Filesystem
 * @author Victor Yasinovsky
 */
class Iterator implements \Iterator {



    /**
     * Путь к директории
     *
     * @var string
     */
    private $_path = null;



    /**
     * Список файлов
     *
     * @var array
     */
    private $_files = array();

    /**
     * Текущая позиция
     *
     * @var int
     */
    private $_position = 0;



    /**
     * Конструктор
     *
     * @param string $path Путь
     */
    public function __construct($path) {
        $this->_path = $path; // Назначим
        if ($handle = @opendir($path)) {
            while ($file = readdir($handle)) {
                if ($file != '.' && $file != '..') {
                    $this->_files[] = $file;
                }
            }
            closedir($handle);
        }
    }



    /**
     * Возвращает итератор на первый элемент
     */
    public function rewind() {
        $this->_position = 0;
    }



    /**
     * Возвращает текущий элемент
     *
     * @return \DL\Filesystem\Item
     */
    public function current() {
        static $fs = null;
        if (is_null($fs)) { // Единожды повесим
            $fs = \DL\Filesystem::getInstance();
        }
        return $fs->item( // Создаем и возвращаем элемент требуемого типа
            $this->_path . DIRECTORY_SEPARATOR . $this->_files[$this->_position]
        );
    }



    /**
     * Возвращает ключ текущего элемента
     *
     * @return int
     */
    public function key() {
        return $this->_position;
    }



    /**
     * Переходит к следующему элементу
     */
    public function next() {
        ++$this->_position;
    }



    /**
     * Проверка корректности позиции
     *
     * @return bool
     */
    public function valid() {
        return array_key_exists(
            $this->_position, $this->_files
        );
    }



}