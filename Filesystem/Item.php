<?php
/**
 * DesignLab Framework
 *
 * @copyright   2001-2013 DesignLab, LLC.
 * @author      Victor Yasinovsky <victor@designlab.ru>
 * @link        http://www.designlab.ru
 */



namespace DL\Filesystem;

use DL\Filesystem\Item\Exception as FSI_Exception;

/**
 * Абстрактный элемент файловой системы
 *
 * @package DL_Core
 * @subpackage Filesystem
 * @author Victor Yasinovsky
 */
abstract class Item {



    /**
     * Драйвер файловой системы
     *
     * @var \DL\Filesystem
     */
    protected $_fs = null;

    /**
     * Транзакции
     *
     * @var Transaction
     */
    protected $_transaction = null;

    /**
     * Корзина
     *
     * @var RecycleBin
     */
    protected $_recycle_bin = null;



    /**
     * Путь элементу
     *
     * @var string
     */
    protected $_path = null;

    /**
     * Старый путь элементу, когда он удален
     *
     * @var string
     */
    private $_previous_path = null;



    /**
     * Конструктор
     *
     * @param string $path Путь к элементу
     * @param bool $check Проверять существование
     * @throws Item\Exception
     */
    public function __construct($path, $check=true) {
        if ($check && !file_exists($path)) {
            throw new FSI_Exception(
                'File "' . $path . '" is not exists', 10
            );
        }
        // Назначим в каноническом виде
        $this->_path = realpath($path);
        $this->_fs = \DL\Filesystem::getInstance();
        $this->_transaction = $this->_fs->transaction();
        $this->_recycle_bin = $this->_fs->recycleBin();
    }



    /**
     * Является ли элемент обычным файлом?
     *
     * @return bool
     */
    public function isFile() {
        return false;
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
     * Является ли элемент ссылкой?
     *
     * @return bool
     */
    public function isLink() {
        return false;
    }



    /**
     * Удаляет файл, директорию или ссылку
     *
     * @throws Item\Exception
     */
    abstract protected function _delete();



    /**
     * Удаляет элемент
     *
     * @throws Item\Exception
     */
    public function delete() {
        try {
            if ($this->_transaction->in()) {
                // Здесь обращение через $this->path() для проверки
                $temp_id = $this->_recycle_bin->drop($this->path());
                $this->_transaction->log($this->_recycle_bin, 'restore', array($temp_id));
                // Тут весьма тонкий момент - после того как элемент окажется в
                // корзине - его надо "заморозить", но что если его восстановят?
                // ... для этой разморозки сделан $this->restore()
                $this->_previous_path = $this->_path;
                $this->_transaction->log($this, 'restore');
            }
            else {
                $this->_delete();
            }
        }
        catch (Exception $e) {
            throw new FSI_Exception( // Не получилось удалить ...
                'Cannot delete "' . $this->_path . '"', 20, $e
            );
        }
        // Очистим этот путь
        $this->_path = null;
    }



    /**
     * Восстанавливает элемент после удаления
     *
     * @throws Item\Exception
     */
    public function restore() {
        if (isset($this->_path) || is_null($this->_previous_path)) {
            throw new FSI_Exception('Can not restore item', 30);
        }
        $this->_path = $this->_previous_path;
        $this->_previous_path = null;
    }



    /**
     * Проверяет тип цели
     *
     * @param mixed $item Представление цели
     * @throws Item\Exception
     */
    private static function _cast_directory_item($item) {
        if (!is_object($item) || !($item instanceof Item) || !$item->isDir()) {
            throw new FSI_Exception('Invalid directory parameter type', 40);
        }
    }



    /**
     * Копирует файл или ссылку
     *
     * @param string $destination Цель
     * @return bool
     */
    protected function _copy($destination) {
        return @copy($this->_path, $destination);
    }



    /**
     * Копирует элемент и возвращает его представление
     *
     * @param Item\Directory $target Представление директории
     * @param bool $forced Сильный режим
     * @throws Item\Exception
     * @return Item
     */
    public function copy($target, $forced=false) {
        try {
            $temp_id = $destination = null;
            self::_cast_directory_item($target); // Цель должна быть директорией
            $destination = $target->path() . DIRECTORY_SEPARATOR . $this->baseName();
            if ($this->path() == $destination) { // Здесь через $this->path() для проверки
                throw new FSI_Exception('Cannot copy "' . $this->_path . '" to itself', 50);
            }
            if (file_exists($destination)) {
                if ($forced == false) { // В слабом режме, если элемент уже есть - все плохо!
                    throw new FSI_Exception('File "' . $destination . '" already exists', 51);
                }
                $temp_id = $this->_recycle_bin->drop($destination);
                $this->_transaction->log($this->_recycle_bin, 'restore', array($temp_id));
            }
            if (!$this->_copy($destination)) {
                throw new FSI_Exception( // Если по каким-то причинам не получилось...
                    'Cannot copy file "' . $this->_path . '" to "' . $destination . '"', 52
                );
            }
        }
        catch (Exception $e) {
            if (isset($temp_id)) { $this->_recycle_bin->restore($temp_id); }
            throw new FSI_Exception( // Составим нормальный текст, понятный
                'Cannot copy "' . $this->_path . '"' // ... для любого человека! :)
                    . (isset($destination) ? ' to "' . $target->path() . '"' : ''), 53, $e
            );
        }
        $item = $this->_fs->item($destination);
        $this->_transaction->log($item, 'delete');
        return $item;
    }



    /**
     * Перемещает элемент и возвращает его представление
     *
     * @param Item\Directory $target Представление директории
     * @param bool $forced Сильный режим
     * @throws Item\Exception
     * @return Item
     */
    public function move($target, $forced=false) {
        try {
            $temp_id = $destination = null;
            self::_cast_directory_item($target); // Цель должна быть директорией
            $destination = $target->path() . DIRECTORY_SEPARATOR . $this->baseName();
            if ($this->path() == $destination) { // Здесь через $this->path() для проверки
                throw new FSI_Exception('Cannot move "' . $this->_path . '" to itself', 60);
            }
            if (file_exists($destination)) {
                if ($forced == false) { // В слабом режме, если элемент уже есть - все плохо!
                    throw new FSI_Exception('File "' . $destination . '" already exists', 61);
                }
                $temp_id = $this->_recycle_bin->drop($destination);
                $this->_transaction->log($this->_recycle_bin, 'restore', array($temp_id));
            }
            if (!@rename($this->_path, $destination)) {
                throw new FSI_Exception( // Если по каким-то причинам не получилось...
                    'Cannot move file "' . $this->_path . '" to "' . $destination . '"', 62
                );
            }
        }
        catch (Exception $e) {
            if (isset($temp_id)) { $this->_recycle_bin->restore($temp_id); }
            throw new FSI_Exception(
                'Cannot move "' . $this->_path . '"'
                    . (isset($destination) ? ' to "' . $target->path() . '"' : ''), 63, $e
            );
        }
        $parent = $this->parent(); // Текущий родитель - директория
        $this->_path = $destination; // Назначаем новый путь к файлу
        $this->_transaction->log($this, 'move', array($parent));
        return $this;
    }



    /**
     * Возвращает путь к элементу
     *
     * @throws Item\Exception
     * @return string
     */
    public function path() {
        if (is_null($this->_path)) { // Проверим наличие
            throw new FSI_Exception('Item is not exists', 70);
        }
        return $this->_path;
    }



    /**
     * Возвращает представление родительской директории
     *
     * @return Item\Directory
     */
    public function parent() {
        return $this->_fs->dir(
            pathinfo($this->_path, PATHINFO_DIRNAME)
        );
    }



    /**
     * Возвращает имя элемента (без расширения)
     *
     * @return string
     */
    public function fileName() {
        return pathinfo($this->_path, PATHINFO_FILENAME);
    }



    /**
     * Возвращает имя и расширение элемента
     *
     * @return string
     */
    public function baseName() {
        return pathinfo($this->_path, PATHINFO_BASENAME);
    }



    /**
     * Возвращает расширение элемента
     *
     * @return null|string
     */
    public function extension() {
        $result = pathinfo($this->_path, PATHINFO_EXTENSION);
        return empty($result) ? null : $result;
    }



}
