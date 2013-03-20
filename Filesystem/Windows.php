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

/**
 * Драйвер файловой системы Microsoft Windows
 *
 * @package DL_Core
 * @subpackage Filesystem
 * @author Victor Yasinovsky
 */
class Windows extends \DL\Filesystem {



    /**
     * Создает файл
     *
     * @param string $path Имя файла
     * @return bool
     */
    protected function _make_file($path) {
        $result = false; // Пока не ясно
        if ($handle = @fopen($path, 'w')) {
            $result = fclose($handle);
        }
        return $result;
    }



    /**
     * Создает директорию
     *
     * @param string $path Имя директории
     * @return bool
     */
    protected function _make_dir($path) {
        return @mkdir($path);
    }



    /**
     * Является ли файл символической ссылкой (Приватный метод)
     *
     * Внимание! Этот метод объявлен публичным по техническим причинам
     * и его использование снаружи, как части интерфейса, недопустимо!
     *
     * @param string $path Путь
     * @access private
     * @return bool
     */
    public function _is_link($path) {
        return is_dir($path) && readlink($path) != $path;
    }



    /**
     * Удаляет символическую ссылку (Приватный метод)
     *
     * Внимание! Этот метод объявлен публичным по техническим причинам
     * и его использование снаружи, как части интерфейса, недопустимо!
     *
     * @param string $path Путь
     * @access private
     * @return bool
     */
    public function _delete_link($path) {
        return @rmdir($path);
    }



}