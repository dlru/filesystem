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
 * Драйвер файловой системы UNIX/Linux
 *
 * @package DL_Core
 * @subpackage Filesystem
 * @author Victor Yasinovsky
 */
class Linux extends \DL\Filesystem {



    /**
     * Битмаска прав для файлов
     *
     * @var int
     */
    private $_mask_file = null;

    /**
     * Битмаска прав для директорий
     *
     * @var int
     */
    private $_mask_dir = null;



    /**
     * Значение старой umask
     *
     * @var int
     */
    private $_old_umask = null;



    /**
     * Конструктор
     *
     * @todo Сделать, забор масок из конфига
     */
    protected function __construct() {
        $need_save_old_umask = false;
        // Возможно нас просят другую маску файлов
        if (defined('_MF') && is_numeric(_MF)) {
            $this->_mask_file = octdec(_MF);
            $need_save_old_umask = true;
        }
        // Возможно нас просят другую маску директорий
        if (defined('_MD') && is_numeric(_MD)) {
            $this->_mask_dir = octdec(_MD);
            $need_save_old_umask = true;
        }
        if ($need_save_old_umask) {
            // Сохраним старое значение
            $this->_old_umask = umask(0);
        }
        parent::__construct();
    }



    /**
     * Деструктор
     */
    public function __destruct() {
        if (isset($this->_old_umask)) {
            umask($this->_old_umask);
        }
    }



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
            if (isset($this->_mask_file)) {
                // Поправим маску прав на доступ
                @chmod($path, $this->_mask_file);
            }
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
        return @mkdir(
            $path,
            isset($this->_mask_dir)
                ? $this->_mask_dir
                : 0777
        );
    }



}