<?php
/**
 * DesignLab Framework
 *
 * @copyright   2001-2013 DesignLab, LLC.
 * @author      Victor Yasinovsky <victor@designlab.ru>
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