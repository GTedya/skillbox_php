<?php

namespace Controllers;

use Exception;
use PDO;

class File
{
    public static function list(): false|array
    {
        $dir = "files";
        return array_diff(scandir($dir), array('..', '.'));
    }

    /**
     * @throws Exception
     */
    public static function get(): false|array
    {
        $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
        $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
        if ($id == null) throw new Exception('Неверный формат id');
        $files = self::list();
        return stat('files/' . $files[$id]);
    }

    /**
     * @throws Exception
     */
    public static function add(): string
    {
        global $pdo;
        session_start();
        if (!isset($_SESSION['id'])) throw new Exception('Отказано в доступе');
        $name = $_POST['filename'];
        $file_handle = fopen("files/$name.txt", 'w');
        fwrite($file_handle, $_POST['text'] ?? '');
        fclose($file_handle);
        $sth = $pdo->prepare("INSERT INTO File_privacy(user_id,file,is_owner) values ( ?,?,?)");
        $sth->execute([$_SESSION['id'], $name . '.txt', true]);
        return 'Файл успешно создан';

    }

    public static function delete(): string
    {
        $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
        $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
        if ($id == null) throw new Exception('Неверный формат id');
        $files = self::list();
        $file = 'files/' . $files[$id];
        unlink($file);
        return 'Файл успешко удален';
    }

    public static function rename(): void
    {
        $rawData = file_get_contents("php://input");

        $blocks = explode('----------------------------', $rawData);
        array_shift($blocks);

        $processedData = array();

        foreach ($blocks as $block) {
            $pattern = '/Content-Disposition:\sform-data;\sname="([^"]+)"\r\n\r\n(.*)\r\n/';

            preg_match($pattern, $block, $matches);


            if (count($matches) === 3) {
                $fieldName = $matches[1];
                $fieldValue = $matches[2];

                $processedData[$fieldName] = trim($fieldValue);
            }
        }

        $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
        $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
        if ($id == null) throw new Exception('Неверный формат id');
        $files = self::list();
        $file = 'files/' . $files[$id];
        rename($file, 'files/' . $processedData['name'] . '.txt');
    }

    /**
     * @throws Exception
     */
    public static function makedir(): string
    {
        $dirName = 'files/' . $_POST['name'];
        if (!is_dir($dirName)) {
            mkdir($dirName);
            return 'Директория успешко создана';
        } else {
            throw new Exception('Данная директория уже существует');
        }
    }

    public static function renameDir(): string
    {
        $rawData = file_get_contents("php://input");

        $blocks = explode('----------------------------', $rawData);
        array_shift($blocks);

        $processedData = array();

        foreach ($blocks as $block) {
            $pattern = '/Content-Disposition:\sform-data;\sname="([^"]+)"\r\n\r\n(.*)\r\n/';

            preg_match($pattern, $block, $matches);


            if (count($matches) === 3) {
                $fieldName = $matches[1];
                $fieldValue = $matches[2];

                $processedData[$fieldName] = trim($fieldValue);
            }
        }
        $from = 'files/' . $processedData['from'];
        if (!is_dir($from)) throw new Exception('Данная директория не существует');
        $to = 'files/' . $processedData['to'];
        rename($from, $to);
        return 'Данные успешно обновлены';
    }

    private static function directories(): array
    {
        $dirs = [];
        $files = self::list();
        foreach ($files as $file) {
            if (is_dir('files/' . $file)) {
                $dirs [] = $file;
            }
        }
        return $dirs;
    }

    /**
     * @throws Exception
     */
    public static function getDirectories(): false|array
    {
        $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
        $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
        if ($id == null) throw new Exception('Неверный формат id');
        return scandir('files/' . self::directories()[$id]);
    }

    private static function rrmdir($dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") rrmdir($dir . "/" . $object); else unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    /**
     * @throws Exception
     */
    public static function deleteDir(): string
    {
        $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
        $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
        if ($id == null) throw new Exception('Неверный формат id');
        self::rrmdir('files/' . self::directories()[$id]);
        return 'Директория успешно удалена';
    }

    /**
     * @throws Exception
     */
    public static function getShare(): false|array
    {
        global $pdo;
        $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
        $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
        if ($id == null) throw new Exception('Неверный формат id');
        $files = self::list();
        $sth = $pdo->prepare("SELECT u.id FROM File_privacy f JOIN Users u ON f.user_id=u.ID WHERE file = ?");
        $sth->execute([$files[$id]]);

        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    public static function shareWith(): string
    {
        global $pdo;
        $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
        $userId = array_pop($pathPieces);
        $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
        if ($id == null) throw new Exception('Неверный формат id');
        $files = self::list();
        $sth = $pdo->prepare("INSERT INTO File_privacy(user_id,file,is_owner) values ( ?,?,?)");
        $sth->execute([$userId, $files[$id], 0]);
        $sth->fetch(PDO::FETCH_ASSOC);
        return 'Вы успешно поделились файлом';
    }

    public static function deleteShare(): string
    {
        global $pdo;
        $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
        $userId = array_pop($pathPieces);
        $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
        if ($id == null) throw new Exception('Неверный формат id');
        $files = self::list();
        $sth = $pdo->prepare("DELETE FROM File_privacy WHERE user_id = ? AND file = ?");
        $sth->execute([$userId, $files[$id]]);
        $sth->fetch(PDO::FETCH_ASSOC);
        return 'Пользователь больше не имеет прав доступа к файлу';

    }


}