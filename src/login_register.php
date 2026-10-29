<?php

session_start();
require_once '../config.php';
require_once __DIR__ . '/oauth_common.php';

if (isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $plateNumber = normalizePlateNumber($_POST['plate_number'] ?? '');
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = 'Please enter a valid email address.';
        $_SESSION['activate_form'] = 'register';
        header('Location: ./signup/index.php');
        exit();
    }

    if ($plateNumber === null) {
        $_SESSION['register_error'] = 'Please enter a valid plate number.';
        $_SESSION['activate_form'] = 'register';
        header('Location: ./signup/index.php');
        exit();
    }

    if (!in_array($role, ['student', 'employee', 'guest'], true)) {
        $_SESSION['register_error'] = 'Please select a valid account type.';
        $_SESSION['activate_form'] = 'register';
        header("Location: ./signup/index.php");
        exit();
    }

    $checkEmail = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $checkEmail->bind_param('s', $email);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        $_SESSION['register_error'] = "Email is already registered!";
        $_SESSION['activate_form'] = 'register';
    } else {
        $insertUser = $conn->prepare(
            'INSERT INTO users (name, email, plate_number, password, role) VALUES (?, ?, ?, ?, ?)'
        );
        $insertUser->bind_param('sssss', $name, $email, $plateNumber, $password, $role);
        $insertUser->execute();
        $_SESSION['register_success'] = true;
    }

    header("Location: ./signup/index.php");
    exit();
}

if (isset($_POST['login'])){

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $plateNumber = normalizePlateNumber($_POST['plate_number'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['login_error'] = 'Please enter a valid email address.';
        $_SESSION['activate_form'] = 'login';
        header('Location: ./login/index.php');
        exit();
    }

    $lookupUser = $conn->prepare('SELECT * FROM users WHERE email = ?');
    $lookupUser->bind_param('s', $email);
    $lookupUser->execute();
    $result = $lookupUser->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if ($plateNumber === null) {
                    $_SESSION['login_error'] = 'Please enter a valid plate number.';
                    $_SESSION['activate_form'] = 'login';
                    header('Location: ./login/index.php');
                    exit();
                }

                $updatePlate = $conn->prepare('UPDATE users SET plate_number = ? WHERE id = ?');
                $updatePlate->bind_param('si', $plateNumber, $user['id']);
                $updatePlate->execute();

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                
                if ($user['role'] === 'admin') {
                    $_SESSION['is_admin'] = true;
                    header("Location: ./admin_page.php");
                } else {
                    $_SESSION['is_admin'] = false;
                    header("Location: ./user_page.php");
                }
                exit();
            }
        }
 $_SESSION['login_error'] = 'Incorrect email or password!';
 $_SESSION['activate_form'] = 'login';}
 header("Location: ./login/index.php");
 exit();
?>