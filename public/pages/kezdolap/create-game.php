<?php
session_start();

include '../../../backend/db.php';

// set simple user_id for authorization
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = bin2hex(random_bytes(8));
}

setcookie("user_id", $_SESSION['user_id'], time() + (86400 * 3), "/"); // 86400s = 1d

if (isset($_POST['create_game'])) {
    $created_by = $_SESSION['user_id'];

    do {
        // new game_id
        $game_id = rand(100000, 999999);

        // check if game_id exists
        $stmt = $conn->prepare("SELECT game_id FROM games WHERE game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->store_result();
        $id_exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($id_exists); // continue the generation until its unique

    // insert into database
    $stmt = $conn->prepare("INSERT INTO games (game_id, kor, aktiv, created_by) VALUES (?, 0, TRUE, ?)");
    $stmt->bind_param("is", $game_id, $created_by);

    if ($stmt->execute()) {
        // generate csrf if doesnt exist
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        header("Location: /jatek/?" . $game_id);
        exit();
    } else {
        echo "Hiba a játék létrehozásában: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>