<?php
session_start();

include '../../backend/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['advance_round'])) {
    $stmt = $conn->prepare("UPDATE games SET kor = kor + 1 WHERE game_id = ?");
    $stmt->bind_param("i", $_POST['game_id']);
    $stmt->execute();
    $stmt->close();

    header("Location: /jatek/?{$_POST['game_id']}");
    exit();
}

// fetch current round number
$stmt = $conn->prepare("SELECT kor FROM games WHERE game_id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$stmt->bind_result($current_round);
$stmt->fetch();
$stmt->close();

$is_odd_round = $current_round % 2 === 1;

// fetch countries
$stmt = $conn->prepare("SELECT id, country_name, revenue, production, research_points, diplomacy_points, military_points, banks, factories, universities, barracks FROM countries WHERE game_id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$countries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<?php include("../../assets/components/head.php"); ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Játék (Kör: <?= $current_round ?>)</h1>
        <a href="#" class="btn btn-info btn-sm">Játékszabályok</a>
    </div>
    
    <div class="mt-4">
        <p><strong>Jelenlegi kör:</strong> <?= $is_odd_round ? 'Páratlan (Politika, Tudomány)' : 'Páros (Akció)' ?></p>
    </div>

    <div class="row">
        <?php foreach ($countries as $country): ?>
            <div class="col-md-6 mb-4">
                <div class="card border border-1">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?= htmlspecialchars($country['country_name']) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="d-flex align-items-center gap-1"><span class="fs-6">Bevétel:</span> <strong class="fs-5 border-bottom border-dark-subtle border-1 px-2"><?= $country['revenue'] ?></strong></p>
                                <p class="d-flex align-items-center gap-1"><span class="fs-6">Termelés:</span> <strong class="fs-5 border-bottom border-dark-subtle border-1 px-2"><?= $country['production'] ?></strong></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="d-flex align-items-center gap-1"><span class="fs-6">Kutatási Pont:</span> <strong class="fs-5 border-bottom border-dark-subtle border-1 px-2"><?= $country['research_points'] ?></strong></p>
                                <p class="d-flex align-items-center gap-1"><span class="fs-6">Diplomáciai Pont:</span> <strong class="fs-5 border-bottom border-dark-subtle border-1 px-2"><?= $country['diplomacy_points'] ?></strong></p>
                                <p class="d-flex align-items-center gap-1"><span class="fs-6">Katonai Pont:</span> <strong class="fs-5 border-bottom border-dark-subtle border-1 px-2"><?= $country['military_points'] ?></strong></p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="d-flex align-items-center gap-1"><span class="fs-6">Bankok:</span> <strong class="fs-5 border-bottom border-dark-subtle border-1 px-2"><?= $country['banks'] ?></strong></p>
                                <p class="d-flex align-items-center gap-1"><span class="fs-6">Gyárak:</span> <strong class="fs-5 border-bottom border-dark-subtle border-1 px-2"><?= $country['factories'] ?></strong></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="d-flex align-items-center gap-1"><span class="fs-6">Egyetemek:</span> <strong class="fs-5 border-bottom border-dark-subtle border-1 px-2"><?= $country['universities'] ?></strong></p>
                                <p class="d-flex align-items-center gap-1"><span class="fs-6">Laktanyák:</span> <strong class="fs-5 border-bottom border-dark-subtle border-1 px-2"><?= $country['barracks'] ?></strong></p>
                            </div>
                        </div>

                        <div class="mt-3">
                            <h6>Elérhető Akciók:</h6>
                            <form method="POST" action="/jatek/?<?= htmlspecialchars($game_id, ENT_QUOTES, 'UTF-8') ?>" class="d-flex flex-md-wrap gap-2">
                                <?php if ($is_odd_round): ?>
                                    <!-- odd -->
                                    <button type="submit" name="buy_science" class="btn btn-sm btn-outline-primary">Tudományos Kutatás Vásárlása</button>
                                    <button type="submit" name="plan_diplomacy" class="btn btn-sm btn-outline-secondary">Politikai Tervezés</button>
                                    <button type="submit" name="military_plan" class="btn btn-sm btn-outline-danger">Katonai Tervezés</button>
                                <?php else: ?>
                                    <!-- even -->
                                    <button type="submit" name="buy_building" class="btn btn-sm btn-outline-success">Épület Vásárlása</button>
                                    <button type="submit" name="un_vote" class="btn btn-sm btn-outline-warning">ENSZ Szavazás</button>
                                    <button type="submit" name="declare_war" class="btn btn-sm btn-outline-danger">Háború Indítása</button>
                                <?php endif; ?>
                                <input type="hidden" name="country_id" value="<?= $country['id'] ?>">
                                <input type="hidden" name="game_id" value="<?= $game_id ?>">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-5">
        <form method="POST" action="">
            <input type="hidden" name="game_id" value="<?= $game_id ?>">
            <button type="submit" name="advance_round" class="btn btn-lg btn-success">Következő Kör</button>
        </form>
    </div>
</div>

<?php include("../../assets/components/footer.html"); ?>
