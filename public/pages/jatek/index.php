<?php
session_start();

$game_id_valid = true;
$game_exists = true;
$game_started = false;
$authorized = false;

// get game_id from the query string
if (isset($_SERVER['QUERY_STRING']) && preg_match('/^[0-9]+$/', $_SERVER['QUERY_STRING'])) {
    $game_id = (int)$_SERVER['QUERY_STRING'];
} else {
    $game_id_valid = false;
}

if ($game_id_valid) {
    include '../../../backend/db.php';

    // get user session/cookie
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : null);

    // fetch game
    $stmt = $conn->prepare("SELECT created_by, game_started FROM games WHERE game_id = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($created_by, $game_started);
    $stmt->fetch();
    $stmt->close();

    // check if game exists
    if (!$created_by) {
        $game_exists = false;
    } else {
        $game_exists = true;
        $authorized = ($user_id === $created_by);
    }
}

if ($game_exists && $authorized) {
    if ($game_started) {
        include './game.php';
    } else {
        include './init.php';
    }
} else {
    die("Ez a játék nem elérhető. (Nem létezik / Nincs jogosultságod)");
}
?>