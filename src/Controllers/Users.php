<?php

namespace Controllers;


use Exception;
use http\Cookie;
use PDO;

class Users
{
    public PDO $pdo;

    public function __construct()
    {
        $pdo = new PDO('mysql:host=mysql;dbname=skill;charset=utf8', 'root', 'root');
    }


    public static function list(): array
    {
        global $pdo;

        $sth = $pdo->prepare("SELECT id, age, gender FROM Users");
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @throws Exception
     */
    public static function getById(): array
    {
        global $pdo;
        $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
        $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
        if ($id == null) throw new Exception('Неверный формат id');

        $sth = $pdo->prepare("SELECT id, age, gender FROM Users WHERE id = ?");
        $sth->execute([$id]);
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @throws Exception
     */
    public static function register(): string
    {
        global $pdo;

        try {
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $age = $_POST['age'] ?? 0;
            $gender = $_POST['gender'] ?? '';

            $sth = $pdo->prepare("INSERT INTO Users(email,password,age,gender) values (? ,?, ? ,?)");
            $sth->execute([$email, $password, $age, $gender]);
            return 'Регистрация прошла успешно';
        } catch (Exception $exception) {
            throw new $exception("Неверные данные");
        }
    }

    /**
     * @throws Exception
     */
    public static function login(): string
    {
        global $pdo;
        session_start();
        $sth = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
        $sth->execute([$_POST['email']]);
        $array = $sth->fetch(PDO::FETCH_ASSOC);
        if (password_verify($_POST['password'], $array['password'])) {
            $_SESSION['id'] = $array['id'];
            SetCookie('id', random_int(1000000000, 999999999999), time() + 86400);
            if ($array['is_admin']) {
                $_SESSION['admin'] = true;
            }
            return 'Добро пожаловать';
        } else {
            throw new Exception("Неверные данные входа");
        }
    }

    public static function logout(): string
    {
        session_start();
        try {
            session_destroy();
            setcookie("id", "", time() - 3600);
            return 'Вы успешно вышли';
        } catch (Exception) {
            throw new Exception("Вы не вошли в систему");
        }
    }

    public static function reset_password(): string
    {
        $to = $_GET['email'];
        $subject = 'Reset password message';
        $message = 'confirm password changes';
        $headers = 'From: georgi.tedeev@mail.ru' . "\r\n" .
            'Reply-To: webmaster@example.com' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        mail($to, $subject, $message, $headers);
        return 'Выслали письмо на электронную почту';
    }

    public static function update(): string
    {
        global $pdo;
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

        session_start();
        $age = $processedData['age'];
        $gender = $processedData['gender'];
        $id = $_SESSION['id'];
        $sth = $pdo->prepare("UPDATE Users SET age = ?, gender = ? WHERE id = ?");
        $sth->execute([$age, $gender, $id]);
        return 'Данные успешно обновлены';
    }

    /**
     * @throws Exception
     */
    public static function listForAdmin(): false|array
    {
        global $pdo;
        session_start();
        if ($_SESSION['admin']) {
            $sth = $pdo->prepare("SELECT * FROM Users");
            $sth->execute();
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } else {
            throw new Exception("Отказано в доступе");
        }

    }

    public static function getByIdAdmin(): array
    {
        global $pdo;

        session_start();
        if ($_SESSION['admin']) {
            $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
            $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
            if ($id == null) throw new Exception('Неверный формат id');

            $sth = $pdo->prepare("SELECT * FROM Users WHERE id = ?");
            $sth->execute([$id]);
            return $sth->fetch(PDO::FETCH_ASSOC);
        } else {
            throw new Exception("Отказано в доступе");
        }
    }

    public static function adminDelete(): string
    {
        global $pdo;

        session_start();
        if ($_SESSION['admin']) {
            $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
            $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
            if ($id == null) throw new Exception('Неверный формат id');

            $sth = $pdo->prepare("DELETE FROM Users WHERE id = ?");
            $sth->execute([$id]);
            $sth->fetch(PDO::FETCH_ASSOC);
            return 'Пользователь удален';
        } else {
            throw new Exception("Отказано в доступе");
        }
    }

    /**
     * @throws Exception
     */
    public static function adminUpdate(): string
    {
        global $pdo;
        session_start();
        if (!isset($_SESSION['admin'])) throw new Exception("Отказано в доступе");

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

        $age = $processedData['age'];
        $gender = $processedData['gender'];

        $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
        $id = is_numeric(end($pathPieces)) ? end($pathPieces) : null;
        if ($id == null) throw new Exception('Неверный формат id');

        $sth = $pdo->prepare("UPDATE Users SET age = ?, gender = ? WHERE id = ?");
        $sth->execute([$age, $gender, $id]);
        return 'Данные успешно обновлены';
    }

    public static function search()
    {
        global $pdo;

        $pathPieces = explode('/', $_SERVER['REQUEST_URI']);
        $email = end($pathPieces);

        $sth = $pdo->prepare("SELECT id, age, gender FROM Users WHERE email = ?");
        $sth->execute([$email]);
        return $sth->fetch(PDO::FETCH_ASSOC);
    }
}