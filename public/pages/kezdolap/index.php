<?php include("../../assets/components/head.php");?>
<link rel="stylesheet" href="/kezdolap/index.css">

<div class="full-height-container">
    <div class="col-md-6 col-lg-4 text-center">
        <h1 class="display-5 mb-4">KAG Játék</h1>
        <form action="/kezdolap/create-game.php" method="post">
            <button type="submit" name="create_game" class="btn btn-primary btn-lg">Új Játék létrehozása</button>
        </form>
    </div>
</div>

<?php include("../../assets/components/footer.html"); ?>