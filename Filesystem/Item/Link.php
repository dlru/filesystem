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

/**
 * Представление ссылки
 *
 * @package DL_Core
 * @subpackage Filesystem
 * @author Victor Yasinovsky
 */
class Link extends Directory {



    /**
     * Конструктор
     *
     * @param string $path Путь к ссылке
     * @param bool $check Проверять существование
     * @throws Exception
     */
    public function __construct($path, $check=true) {
        parent::__construct($path, $check);
        // Дело в том, что лика у нас наследуеся от директории и там
        // будет развернута в ее настоящий путь - поэтому придется
        $this->_path = $path; // ... ее тут переназначить снова
        if (!$this->_fs->_is_link($this->_path)) {
            throw new Exception(
                'File "' . $this->_path . '" is not a link'
            );
        }
    }



    /**
     * Является ли элемент ссылкой?
     *
     * @return bool
     */
    public function isLink() {
        return true;
    }



    /**
     * Является ли элемент директорией?
     *
     * @return bool
     */
    public function isDir() {
        return false;
    }



    /**
     * Удаляет ссылку
     *
     * @throws Exception
     */
    protected function _delete() {
        if (!$this->_fs->_delete_link($this->path())) {
            // Здесь через $this->path() для проверки
            throw new Exception( // Не получилось удалить
                'Cannot delete link "' . $this->_path . '"'
            );
        }
        clearstatcache();
    }



}