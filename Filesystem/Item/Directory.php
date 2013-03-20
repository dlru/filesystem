<?php
/**
 * DesignLab Framework
 *
 * @copyright   2001-2013 DesignLab, LLC.
 * @author      Victor Yasinovsky <victor@designlab.ru>
 * @link        http://www.designlab.ru
 */



namespace DL\Filesystem\Item;

use DL\Filesystem\Item as FS_Item;
use DL\Filesystem\Item\Directory\Iterator as FSID_Iterator;

/**
 * Представление директории
 *
 * @package DL_Core
 * @subpackage Filesystem
 * @author Victor Yasinovsky
 */
class Directory extends FS_Item implements \IteratorAggregate {



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
     * @return FSID_Iterator
     * @todo Придумать механизм кэширования
     */
    public function getIterator() {
        // Здесь через $this->path() для проверки
        return new FSID_Iterator($this->path());
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