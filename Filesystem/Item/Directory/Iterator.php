<?php
/**
 * DesignLab Framework
 *
 * @copyright   2001-2012 DesignLab, LLC.
 * @author      Victor Yasinovsky <victor@designlab.ru>
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