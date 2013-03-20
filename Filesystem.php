<?php
/**
 * DesignLab Framework
 *
 * @copyright   2001-2013 DesignLab, LLC.
 * @author      Victor Yasinovsky <victor@designlab.ru>
 * @link        http://www.designlab.ru
 */



namespace DL;

use DL\Filesystem\Item as FS_Item;
use DL\Filesystem\Item\File as FSI_File;
use DL\Filesystem\Item\Link as FSI_Link;
use DL\Filesystem\Item\Directory as FSI_Directory;
use DL\Filesystem\Exception as FS_Exception;

/**
 * Драйвер файловой системы
 *
 * @package DL_Core
 * @subpackage Filesystem
 * @author Victor Yasinovsky
 */
abstract class Filesystem {



    /**
     * Транзакции Файловой системы
     *
     * @var Filesystem\Transaction
     */
    private $_transaction = null;

    /**
     * Корзина для хранения удаленных файлов
     *
     * @var Filesystem\RecycleBin
     */
    private $_recycle_bin = null;



    /**
     * Конструктор
     */
    protected function __construct() {
        // Создадим корзинку и транзакции
        $this->_recycle_bin = new Filesystem\RecycleBin();
        $this->_transaction = new Filesystem\Transaction();
    }



    /**
     * Возвращает название драйвера для текущей ОС
     *
     * @throws Filesystem\Exception
     * @return string
     */
    private static function _get_driver_name() {
        switch (PHP_OS) {
            case 'Linux':
            case 'FreeBSD':
                return 'Linux';
            case 'WINNT':
                return 'Windows';
            default:
                throw new FS_Exception(
                    'Unknown operating system'
                );
        }
    }



    /**
     * Возвращает драйвер файловой системы
     *
     * @throws Filesystem\Exception
     * @return Filesystem
     */
    public static function getInstance() {
        static $instance = null; // Тут храним экземпляр
        if (is_null($instance)) { // Не создавали? Создадим!
            // Получим имя драйвера текущей операционной системы
            $classname = __CLASS__ . '\\' . self::_get_driver_name();
            if (!class_exists($classname)) {
                throw new FS_Exception( // По какой-то причине нет драйвера
                    'Filesystem driver for "'.PHP_OS.'" is not exists'
                );
            }
            // Создаем экземпляр драйвера
            $instance = new $classname();
        }
        // Возвращаем! :)
        return $instance;
    }



    /**
     * Запрещает клонирование объекта
     *
     * @throws Filesystem\Exception
     */
    final public function __clone() {
        throw new FS_Exception(
            'Cannot clone instance of "' . get_class($this) . '"'
        );
    }



    /**
     * Возвращает объект транзакций
     *
     * @return Filesystem\Transaction
     */
    public function transaction() {
        return $this->_transaction;
    }



    /**
     * Возвращает объект корзины
     *
     * @return Filesystem\RecycleBin
     */
    public function recycleBin() {
        return $this->_recycle_bin;
    }



    /**
     * Возвращает представление элемента
     *
     * @param string $path Путь
     * @throws Filesystem\Exception
     * @return Filesystem\Item
     */
    public function item($path) {
        if (!file_exists($path)) {
            throw new FS_Exception(
                'File "' . $path . '" is not exists'
            );
        }
        switch (true) {
            case is_file($path): return new FSI_File($path, false);
            // Весьма тонкий момент - ссылка у нас это тоже директория,
            // поэтому необходимо сначала проверить элемент "на ссылку"
            case $this->_is_link($path): return new FSI_Link($path, false);
            case is_dir($path): return new FSI_Directory($path, false);
            default:
                // Странная ситуация, но все же и ее
                throw new FS_Exception( // ... обработаем!
                    'Unknown file type "' . $path . '"'
                );
        }
    }



    /**
     * Возвращает представление файла
     *
     * @param string $path Путь
     * @return Filesystem\Item\File
     */
    public function file($path) {
        return new FSI_File($path);
    }



    /**
     * Возвращает представление директории
     *
     * @param string $path Путь
     * @return Filesystem\Item\Directory
     */
    public function dir($path) {
        return new FSI_Directory($path);
    }



    /**
     * Возвращает представление ссылки
     *
     * @param string $path Путь
     * @return Filesystem\Item\Link
     */
    public function link($path) {
        return new FSI_Link($path);
    }



    /**
     * Создает файл
     *
     * @param string $path Имя файла
     * @return bool
     */
    abstract protected function _make_file($path);



    /**
     * Создает файл и возвращает его представление
     *
     * @param string $path Имя файла
     * @param bool $forced Сильный режим
     * @throws Filesystem\Exception
     * @return Filesystem\Item\File
     */
    public function makeFile($path, $forced=false) {
        try {
            $temp_id = null;
            if (is_file($path)) {
                if ($forced) {
                    $temp_id = $this->_recycle_bin->drop($path); // Бросаем файл в корзину
                    $this->_transaction->log($this->_recycle_bin, 'restore', array($temp_id));
                }
                else { // В слабом режме, если файл уже есть - сообщим об этом!
                    throw new FS_Exception('File "' . $path . '" already exists');
                }
            }
            // Тут уже в любом случае файла нет, значит можно попытаться
            if (!$this->_make_file($path)) { // ... создать новый пустой файл
                throw new FS_Exception('Cannot make new file "' . $path . '"');
            }
        }
        catch (FS_Exception $e) {
            if (isset($temp_id)) { $this->_recycle_bin->restore($temp_id); }
            throw new FS_Exception('Cannot make file "' . $path . '"', 0, $e);
        }
        $item = new FSI_File($path, false);
        $this->_transaction->log($item, 'delete');
        return $item;
    }



    /**
     * Создает директорию
     *
     * @param string $path Имя директории
     * @return bool
     */
    abstract protected function _make_dir($path);



    /**
     * Создает директорию и возвращает ее представление
     *
     * @param string $path Имя директории
     * @param bool $forced Сильный режим
     * @throws Filesystem\Exception
     * @return Filesystem\Item\Directory
     */
    public function makeDir($path, $forced=false) {
        try {
            $temp_id = null;
            if (is_dir($path)) {
                if ($forced) {
                    $temp_id = $this->_recycle_bin->drop($path); // Бросаем директорию в корзину
                    $this->_transaction->log($this->_recycle_bin, 'restore', array($temp_id));
                }
                else { // В слабом режме, если директория уже есть - сообщим об этом!
                    throw new FS_Exception('Directory "' . $path . '" already exists');
                }
            }
            // Тут уже в любом случае директории нет, значит можно попытаться
            if (!$this->_make_dir($path)) { // ... создать новую пустую директорию
                throw new FS_Exception('Cannot make new directory "' . $path . '"');
            }
        }
        catch (FS_Exception $e) {
            if (isset($temp_id)) { $this->_recycle_bin->restore($temp_id); }
            throw new FS_Exception('Cannot make directory "' . $path . '"', 0, $e);
        }
        $item = new FSI_Directory($path, false);
        $this->_transaction->log($item, 'delete');
        return $item;
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
        return is_link($path);
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
        return @unlink($path);
    }



    /**
     * Проверяет тип цели
     *
     * @param mixed $item Представление цели
     * @throws Filesystem\Exception
     */
    private static function _cast_directory_item($item) {
        if (!is_object($item) || !($item instanceof FS_Item) || !$item->isDir()) {
            throw new FS_Exception('Invalid directory parameter type');
        }
    }



    /**
     * Создает ссылку и возвращает ее представление
     *
     * @param string $path Имя ссылки
     * @param Filesystem\Item\Directory $target Представление директории
     * @param bool $forced Сильный режим
     * @throws Filesystem\Exception
     * @return Filesystem\Item\Link
     */
    public function makeLink($path, $target, $forced=false) {
        try {
            $temp_id = $pointer = null;
            self::_cast_directory_item($target); // Цель должна быть директорией
            if (file_exists($path) && (is_dir($path) || $this->_is_link($path))) {
                if ($forced) {
                    $temp_id = $this->_recycle_bin->drop($path); // Бросаем ссылку в корзину
                    $this->_transaction->log($this->_recycle_bin, 'restore', array($temp_id));
                }
                else { // В слабом режме, если ссылка или директория уже
                    throw new FS_Exception( // ... есть - сообщим об этом
                        ($this->_is_link($path) ? 'Link' : 'Directory')
                            . ' "' . $path . '" already exists'
                    );
                }
            }
            $pointer = $target->path(); // Получим цель создаваемой ссылки
            // Тут уже в любом случае ссылки/директории нет, значит можно
            if (!@symlink($pointer, $path)) { // ... создать новую ссылку!
                throw new FS_Exception('Cannot make new link "' . $path . '"');
            }
        }
        catch (FS_Exception $e) {
            if (isset($temp_id)) { $this->_recycle_bin->restore($temp_id); }
            throw new FS_Exception(
                'Cannot make link "' . $path . '"' // Составим нормальный текст
                    . (isset($pointer) ? ' points to "' . $pointer . '"' : ''), 0, $e
            );
        }
        $item = new FSI_Link($path, false);
        $this->_transaction->log($item, 'delete');
        return $item;
    }



}