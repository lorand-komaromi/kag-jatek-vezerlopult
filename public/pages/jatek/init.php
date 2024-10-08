<?php
session_start();

$game_id_valid = true;
$game_exists = true;
$authorized = false;

// check if the game ID is valid from the query string
if (isset($_SERVER['QUERY_STRING']) && preg_match('/^[0-9]+$/', $_SERVER['QUERY_STRING'])) {
    $game_id = (int)$_SERVER['QUERY_STRING'];
} else {
    $game_id_valid = false;
}

if ($game_id_valid) {
    include '../../../backend/db.php';

    if (!isset($conn) || $conn === null) {
        die("Database connection not established.");
    }

    // get user session/cookie
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : null);

    $stmt = $conn->prepare("SELECT created_by FROM games WHERE game_id = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($created_by);
    $stmt->fetch();
    $stmt->close();

    // check if the game exists and the user is authorized
    if (!$created_by) {
        $game_exists = false;
    } else {
        $game_exists = true;
        $authorized = ($user_id === $created_by);
    }

    if ($authorized && isset($_POST['start_game'])) {
        $stmt = $conn->prepare("UPDATE games SET game_started = TRUE WHERE game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->close();

        header("Location: /jatek/?$game_id");
        exit();
    }

    // add countries
    if ($authorized && isset($_POST['add_country'])) {
        $country_name = filter_var($_POST['country_name'], FILTER_SANITIZE_STRING);
        $political_type = filter_var($_POST['political_type'], FILTER_SANITIZE_STRING);
        $continent = filter_var($_POST['continent'], FILTER_SANITIZE_STRING);

        $revenue = filter_var($_POST['revenue'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999999]]);
        $production = filter_var($_POST['production'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999999]]);
        $research_points = filter_var($_POST['research_points'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999999]]);
        $diplomacy_points = filter_var($_POST['diplomacy_points'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999999]]);
        $military_points = filter_var($_POST['military_points'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999999]]);
        $banks = filter_var($_POST['banks'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999999]]);
        $factories = filter_var($_POST['factories'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999999]]);
        $universities = filter_var($_POST['universities'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999999]]);
        $barracks = filter_var($_POST['barracks'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999999]]);

        $stmt = $conn->prepare("INSERT INTO countries (game_id, country_name, political_type, continent, revenue, production, research_points, diplomacy_points, military_points, banks, factories, universities, barracks) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssiiiiiiiii", 
            $game_id, 
            $country_name, 
            $political_type, 
            $continent, 
            $revenue, 
            $production, 
            $research_points, 
            $diplomacy_points, 
            $military_points, 
            $banks, 
            $factories, 
            $universities, 
            $barracks
        );
        $stmt->execute();
        $stmt->close();
    }

    // fetch countries
    $countries = [];
    if ($game_exists) {
        $stmt = $conn->prepare("SELECT id, country_name, political_type, continent, revenue, production, research_points, diplomacy_points, military_points, banks, factories, universities, barracks 
                                FROM countries WHERE game_id = ?");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $countries[] = $row;
        }
        $stmt->close();
    }

    if ($authorized && isset($_POST['remove_country'])) {
        $stmt = $conn->prepare("DELETE FROM countries WHERE id = ? AND game_id = ?");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("ii", $_POST['country_id'], $game_id);
        $stmt->execute();
        $stmt->close();

        header("Location: /jatek/?" . htmlspecialchars($game_id, ENT_QUOTES, 'UTF-8'));
        exit();
    }

    if ($authorized && isset($_POST['delete_game'])) {
        $stmt = $conn->prepare("DELETE FROM countries WHERE game_id = ?");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM games WHERE game_id = ?");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->close();

        header("Location: /kezdolap/");
        exit();
    }
}
?>

<?php include("../../assets/components/head.php"); ?>

<?php if ($authorized): ?>
    <div class="container">
        <form class="sgamenav py-3 d-flex justify-content-between align-items-center w-100" action="/jatek/?<?= htmlspecialchars($game_id, ENT_QUOTES, 'UTF-8') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit" name="delete_game" class="btn btn-outline-danger" onclick="return confirm('Biztosan törlöd a játékot?');">Játék törlése</button>
            <p class="text-secondary mb-0 fw-light px-2">#<?= htmlspecialchars($game_id, ENT_QUOTES, 'UTF-8') ?></p>
            <button type="submit" name="start_game" class="btn btn-success">Játék indítása</button>
        </form>
    </div>
<?php endif; ?>

<div class="container mt-5">
    <div class="text-center">
        <?php if ($authorized): ?>
            <p class="lead mb-0">Államok hozzáadása</p>

            <div class="d-flex justify-content-center">
                <form action="/jatek/?<?= htmlspecialchars($game_id, ENT_QUOTES, 'UTF-8') ?>" method="POST" class="mt-4 w-100 text-start border border-secondary-subtle border-1 rounded py-3 px-4 needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="row">
                        <div class="col-md-4 d-flex flex-column gap-2">
                            <p class="fs-6 fw-light">Állam</p>
                            <div class="form-group">
                                <label>Állam neve</label>
                                <input autocomplete="off" type="text" name="country_name" class="form-control" required>
                                <div class="invalid-feedback">Kérjük, adjon meg egy államnevet.</div>
                            </div>
                            <div class="form-group">
                                <label for="political_type">Politikai Típus</label>
                                <select name="political_type" id="political_type" class="form-control" required>
                                    <option value="">Nincs megadva</option>
                                    <option value="Törzsi falu politikája">Törzsi falu</option>
                                    <option value="Arisztokratikus köztársaság politikája">Arisztokratikus köztársaság</option>
                                    <option value="Türannisz politikája">Türannisz</option>
                                    <option value="Kalmár köztársaság politikája">Kalmár köztársaság</option>
                                    <option value="Modern demokrácia politikája">Modern demokrácia</option>
                                    <option value="Kommunista diktatúra politikája">Kommunista diktatúra</option>
                                </select>
                                <div class="invalid-feedback">Kérjük, válasszon egy politikai típust.</div>
                            </div>
                            <div class="form-group">
                                <label>Kontinens</label>
                                <input autocomplete="off" type="text" name="continent" class="form-control" required>
                                <div class="invalid-feedback">Kérjük, adjon meg egy kontinenst.</div>
                            </div>
                        </div>

                        <div class="col-md-4 d-flex flex-column gap-2">
                            <p class="fs-6 fw-light">Erőforrások</p>
                            <div class="form-group">
                                <label>Bevétel</label>
                                <input autocomplete="off" type="number" name="revenue" class="form-control" required min="0" max="999999" value="0">
                                <div class="invalid-feedback">A bevételnek 0 és 999999 között kell lennie.</div>
                            </div>
                            <div class="form-group">
                                <label>Termelés</label>
                                <input autocomplete="off" type="number" name="production" class="form-control" required min="0" max="999999" value="0">
                                <div class="invalid-feedback">A termelésnek 0 és 999999 között kell lennie.</div>
                            </div>
                            <div class="form-group">
                                <label>Kutatási pont</label>
                                <input autocomplete="off" type="number" name="research_points" class="form-control" required min="0" max="999999" value="0">
                                <div class="invalid-feedback">A kutatási pontoknak 0 és 999999 között kell lennie.</div>
                            </div>
                            <div class="form-group">
                                <label>Diplomáciai pont</label>
                                <input autocomplete="off" type="number" name="diplomacy_points" class="form-control" required min="0" max="999999" value="0">
                                <div class="invalid-feedback">A diplomáciai pontoknak 0 és 999999 között kell lennie.</div>
                            </div>
                            <div class="form-group">
                                <label>Katonai pont</label>
                                <input autocomplete="off" type="number" name="military_points" class="form-control" required min="0" max="999999" value="0">
                                <div class="invalid-feedback">A katonai pontoknak 0 és 999999 között kell lennie.</div>
                            </div>
                        </div>

                        <div class="col-md-4 d-flex flex-column gap-2">
                            <p class="fs-6 fw-light">Épületek</p>
                            <div class="form-group">
                                <label>Bankok</label>
                                <input autocomplete="off" type="number" name="banks" class="form-control" required min="0" max="999999" value="0">
                                <div class="invalid-feedback">A bankok számának 0 és 999999 között kell lennie.</div>
                            </div>
                            <div class="form-group">
                                <label>Gyárak</label>
                                <input autocomplete="off" type="number" name="factories" class="form-control" required min="0" max="999999" value="0">
                                <div class="invalid-feedback">A gyárak számának 0 és 999999 között kell lennie.</div>
                            </div>
                            <div class="form-group">
                                <label>Egyetemek</label>
                                <input autocomplete="off" type="number" name="universities" class="form-control" required min="0" max="999999" value="0">
                                <div class="invalid-feedback">Az egyetemek számának 0 és 999999 között kell lennie.</div>
                            </div>
                            <div class="form-group">
                                <label>Laktanyák</label>
                                <input autocomplete="off" type="number" name="barracks" class="form-control" required min="0" max="999999" value="0">
                                <div class="invalid-feedback">A laktanyák számának 0 és 999999 között kell lennie.</div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_country" class="btn btn-primary mt-3">Állam hozzáadása</button>
                </form>
            </div>

            <?php if (!empty($countries)): ?>
                <h3 class="lead mb-0 mt-4">Hozzáadott államok</h3>
                <div class="d-flex justify-content-center">
                    <div class="accordion mt-3 w-100" id="countryAccordion">
                            <?php foreach ($countries as $index => $country): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?= $index ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                                        <?= htmlspecialchars($country['country_name'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($country['political_type'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($country['continent'], ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </h2>
                                <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#countryAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p class="fs-6 fw-light">Állam</p>
                                                <ul class="list-group text-start">
                                                    <li class="list-group-item"><strong>Állam neve:</strong> <span><?= htmlspecialchars($country['country_name'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                    <li class="list-group-item"><strong>Politikai típus:</strong> <span><?= htmlspecialchars($country['political_type'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                    <li class="list-group-item"><strong>Kontinens:</strong> <span><?= htmlspecialchars($country['continent'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                </ul>
                                            </div>

                                            <div class="col-md-4">
                                                <p class="fs-6 fw-light">Erőforrások</p>
                                                <ul class="list-group text-start">
                                                    <li class="list-group-item"><strong>Bevétel:</strong> <span><?= htmlspecialchars($country['revenue'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                    <li class="list-group-item"><strong>Termelés:</strong> <span><?= htmlspecialchars($country['production'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                    <li class="list-group-item"><strong>Kutatási pont:</strong> <span><?= htmlspecialchars($country['research_points'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                    <li class="list-group-item"><strong>Diplomáciai pont:</strong> <span><?= htmlspecialchars($country['diplomacy_points'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                    <li class="list-group-item"><strong>Katonai pont:</strong> <span><?= htmlspecialchars($country['military_points'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                </ul>
                                            </div>

                                            <div class="col-md-4">
                                                <p class="fs-6 fw-light">Épületek</p>
                                                <ul class="list-group text-start">
                                                    <li class="list-group-item"><strong>Bankok:</strong> <span><?= htmlspecialchars($country['banks'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                    <li class="list-group-item"><strong>Gyárak:</strong> <span><?= htmlspecialchars($country['factories'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                    <li class="list-group-item"><strong>Egyetemek:</strong> <span><?= htmlspecialchars($country['universities'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                    <li class="list-group-item"><strong>Laktanyák:</strong> <span><?= htmlspecialchars($country['barracks'], ENT_QUOTES, 'UTF-8') ?></span></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <form action="/jatek/?<?= htmlspecialchars($game_id, ENT_QUOTES, 'UTF-8') ?>" method="POST" class="mt-3 text-start">
                                            <input type="hidden" name="country_id" value="<?= htmlspecialchars($country['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" name="remove_country" class="btn btn-outline-danger">Állam törlése</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('political_type').addEventListener('change', function() {
    let politicalType = this.value;

    let values = {
        "Törzsi falu": { revenue: 0, production: 1, research: 1, diplomacy: 1, military: 1, banks: 1, factories: 1, universities: 1, barracks: 1 },
        "Arisztokratikus köztársaság": { revenue: 1, production: 2, research: 2, diplomacy: 2, military: 1, banks: 2, factories: 2, universities: 2, barracks: 2 },
        "Türannisz": { revenue: 2, production: 2, research: 2, diplomacy: 1, military: 2, banks: 1, factories: 1, universities: 1, barracks: 1 },
        "Kalmár köztársaság": { revenue: 3, production: 1, research: 2, diplomacy: 1, military: 1, banks: 3, factories: 1, universities: 1, barracks: 1 },
        "Modern demokrácia": { revenue: 3, production: 2, research: 3, diplomacy: 3, military: 1, banks: 2, factories: 2, universities: 3, barracks: 2 },
        "Kommunista diktatúra": { revenue: 2, production: 3, research: 1, diplomacy: 1, military: 3, banks: 1, factories: 3, universities: 1, barracks: 3 }
    };

    let selectedValues = values[politicalType] || {};

    // Update form fields dynamically
    document.querySelector('input[name="revenue"]').value = selectedValues.revenue || 0;
    document.querySelector('input[name="production"]').value = selectedValues.production || 0;
    document.querySelector('input[name="research_points"]').value = selectedValues.research || 0;
    document.querySelector('input[name="diplomacy_points"]').value = selectedValues.diplomacy || 0;
    document.querySelector('input[name="military_points"]').value = selectedValues.military || 0;
    document.querySelector('input[name="banks"]').value = selectedValues.banks || 0;
    document.querySelector('input[name="factories"]').value = selectedValues.factories || 0;
    document.querySelector('input[name="universities"]').value = selectedValues.universities || 0;
    document.querySelector('input[name="barracks"]').value = selectedValues.barracks || 0;
});
</script>
<script src="/jatek/form-validation.js"></script>

<?php include("../../assets/components/footer.html"); ?>
