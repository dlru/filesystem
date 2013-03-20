<?php
/**
 * DesignLab Framework
 *
 * @copyright   2001-2013 DesignLab, LLC.
 * @author      Victor Yasinovsky <victor@designlab.ru>
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