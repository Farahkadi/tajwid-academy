
<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . "/../config/database.php";

// ======================================
// 1. GESTION DE LA LANGUE
// ======================================

$lang = $_GET["lang"] ?? $_SESSION["lang"] ?? "fr";

if (!in_array($lang, ["fr", "ar", "en"], true)) {
    $lang = "fr";
}

$_SESSION["lang"] = $lang;

// ======================================
// 2. TRADUCTIONS DE L'INTERFACE
// ======================================

$texts = [
    "fr" => [
        "title" => "Exercices de Tajwid",
        "subtitle" => "Teste tes connaissances et améliore ta lecture.",
        "back" => "Retour au tableau de bord",
        "start" => "Commencer",
        "easy" => "Facile",
        "medium" => "Moyen",
        "hard" => "Difficile",
        "empty" => "Aucun exercice disponible pour le moment.",
        "error" => "Impossible de charger les exercices.",
        "exercise" => "Exercice",
        "level" => "Niveau"
    ],

    "en" => [
        "title" => "Tajwid Exercises",
        "subtitle" => "Test your knowledge and improve your recitation.",
        "back" => "Back to dashboard",
        "start" => "Start",
        "easy" => "Easy",
        "medium" => "Medium",
        "hard" => "Hard",
        "empty" => "No exercises available at the moment.",
        "error" => "Unable to load exercises.",
        "exercise" => "Exercise",
        "level" => "Level"
    ],

    "ar" => [
        "title" => "تمارين التجويد",
        "subtitle" => "اختبر معلوماتك وحسّن تلاوتك.",
        "back" => "العودة إلى لوحة التحكم",
        "start" => "ابدأ",
        "easy" => "سهل",
        "medium" => "متوسط",
        "hard" => "صعب",
        "empty" => "لا توجد تمارين متاحة حاليا.",
        "error" => "تعذر تحميل التمارين.",
        "exercise" => "التمرين",
        "level" => "المستوى"
    ]
];

$t = $texts[$lang];

// ======================================
// 3. اختيار أعمدة اللغة
// ======================================

$titleColumn = "title_" . $lang;
$descriptionColumn = "description_" . $lang;

// ======================================
// 4. RÉCUPÉRATION DES EXERCICES
// ======================================

$exercises = [];
$error = false;

try {

    $sql = "SELECT
                id,
                $titleColumn AS title,
                $descriptionColumn AS description,
                difficulty,
                rule_id
            FROM exercises
            ORDER BY id ASC";

    $stmt = $pdo->query($sql);

    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $error = true;

    // Pour consulter l'erreur pendant le développement :
    // error_log($e->getMessage());
}

// ======================================
// 5. TRADUCTION DES NIVEAUX
// ======================================

$difficulties = [
    "easy" => $t["easy"],
    "medium" => $t["medium"],
    "hard" => $t["hard"]
];

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"
      dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($t["title"], ENT_QUOTES, 'UTF-8') ?>
        - Tajwid Academy
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<!-- ======================================
     HEADER
====================================== -->

<header class="main-header">

    <div class="header-top">

        <div class="brand">

            <h1>تَعَلَّمِ التَّجْوِيدَ</h1>

            <h2>Tajwid Academy</h2>

            <p>
                <?= htmlspecialchars($t["title"], ENT_QUOTES, 'UTF-8') ?>
            </p>

        </div>

        <div class="language-selector">

            <a href="exercises.php?lang=fr">FR</a>

            <a href="exercises.php?lang=ar">AR</a>

            <a href="exercises.php?lang=en">EN</a>

        </div>

    </div>

    <nav class="main-nav">

        <a href="../index.php?lang=<?= urlencode($lang) ?>">
            <?= $lang === "ar" ? "الرئيسية" : ($lang === "en" ? "Home" : "Accueil") ?>
        </a>

        <a href="dashboard.php?lang=<?= urlencode($lang) ?>">
            <?= htmlspecialchars($t["back"], ENT_QUOTES, 'UTF-8') ?>
        </a>

    </nav>

</header>

<!-- ======================================
     EXERCISES CONTENT
====================================== -->

<main class="exercises-container">

    <div class="exercises-header">

        <span class="section-label">
            ✦ Tajwid Academy
        </span>

        <h1>
            📚 <?= htmlspecialchars($t["title"], ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <p>
            <?= htmlspecialchars($t["subtitle"], ENT_QUOTES, 'UTF-8') ?>
        </p>

    </div>

    <!-- AFFICHAGE DES ERREURS -->

    <?php if ($error): ?>

        <p class="empty-message">

            <?= htmlspecialchars($t["error"], ENT_QUOTES, 'UTF-8') ?>

        </p>

    <!-- AUCUN EXERCICE -->

    <?php elseif (empty($exercises)): ?>

        <p class="empty-message">

            <?= htmlspecialchars($t["empty"], ENT_QUOTES, 'UTF-8') ?>

        </p>

    <!-- LISTE DES EXERCICES -->

    <?php else: ?>

        <div class="exercises-grid">

            <?php foreach ($exercises as $index => $exercise): ?>

                <article class="exercise-card">

                    <div class="exercise-icon">
                        📖
                    </div>

                    <div class="exercise-number">

                        <?= htmlspecialchars($t["exercise"], ENT_QUOTES, 'UTF-8') ?>

                        <?= $index + 1 ?>

                    </div>

                    <h2>

                        <?= htmlspecialchars(
                            $exercise["title"] ?? "",
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </h2>

                    <p>

                        <?= htmlspecialchars(
                            $exercise["description"] ?? "",
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                    <div class="exercise-level">

                        <span>
                            <?= htmlspecialchars($t["level"], ENT_QUOTES, 'UTF-8') ?> :
                        </span>

                        <span class="difficulty">

                            <?= htmlspecialchars(
                                $difficulties[$exercise["difficulty"] ?? ""] 
                                ?? $exercise["difficulty"],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                    </div>

                    <a
                        class="start-button"
                        href="take_exercise.php?id=<?= (int)$exercise["id"] ?>&lang=<?= urlencode($lang) ?>"
                    >

                        <?= htmlspecialchars($t["start"], ENT_QUOTES, 'UTF-8') ?>

                        <span>←</span>

                    </a>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

</body>

</html>
