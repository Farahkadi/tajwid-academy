<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . "/../config/database.php";


// Langue sélectionnée
if (isset($_GET["lang"])) {
    $lang = $_GET["lang"];
    $_SESSION["lang"] = $lang;
} elseif (isset($_SESSION["lang"])) {
    $lang = $_SESSION["lang"];
} else {
    $lang = "fr";
}

if (!in_array($lang, ["fr", "ar", "en"])) {
    $lang = "fr";
}

if (!in_array($lang, ["fr", "ar", "en"])) {
    $lang = "fr";
}

// Traductions
$text = [
    "fr" => [
        "title" => "Mes quiz",
        "subtitle" => "Teste tes connaissances en Tajwid",
        "start" => "Commencer le quiz",
        "questions" => "Questions",
        "back" => "Retour à l'accueil",
        "error" => "Impossible de charger les quiz."
    ],
    "ar" => [
        "title" => "الاختبارات",
        "subtitle" => "اختبر معلوماتك في التجويد",
        "start" => "ابدأ الاختبار",
        "questions" => "أسئلة",
        "back" => "العودة إلى الصفحة الرئيسية",
        "error" => "تعذر تحميل الاختبارات."
    ],
    "en" => [
        "title" => "My Quizzes",
        "subtitle" => "Test your Tajwid knowledge",
        "start" => "Start quiz",
        "questions" => "Questions",
        "back" => "Back to home",
        "error" => "Unable to load quizzes."
    ]
];

$t = $text[$lang];

// Récupérer les quiz
try {
    $titleColumn = "title_" . $lang;
    $descriptionColumn = "description_" . $lang;

    $sql = "SELECT id, $titleColumn AS title,
                   $descriptionColumn AS description,
                   total_questions
            FROM quizzes
            ORDER BY id ASC";

    $stmt = $pdo->query($sql);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $quizzes = [];
    $error = true;
}
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

<body class="quizzes-page">

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

            <a href="quizzes.php?lang=fr"
               class="<?= $lang === 'fr' ? 'active' : '' ?>">
                FR
            </a>

            <a href="quizzes.php?lang=ar"
               class="<?= $lang === 'ar' ? 'active' : '' ?>">
                AR
            </a>

            <a href="quizzes.php?lang=en"
               class="<?= $lang === 'en' ? 'active' : '' ?>">
                EN
            </a>

        </div>

    </div>

    <nav class="main-nav">

    <!-- Retour à l'accueil -->
    <a href="../index.php?lang=<?= urlencode($lang) ?>">
        🏠 <?= htmlspecialchars($t["back"], ENT_QUOTES, 'UTF-8') ?>
    </a>

    <!-- Retour au tableau de bord -->
    <a href="dashboard.php?lang=<?= urlencode($lang) ?>">
        📊 <?= $lang === "ar"
            ? "لوحة التحكم"
            : ($lang === "en" ? "Dashboard" : "Tableau de bord") ?>
    </a>

</nav>

</header>

<!-- ======================================
     QUIZZES CONTENT
====================================== -->

<main class="container">

    <div class="heading">

        <span class="section-label">
            ✦ TAJWID ACADEMY
        </span>

        <h1>
            📝 <?= htmlspecialchars($t["title"], ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <p>
            <?= htmlspecialchars($t["subtitle"], ENT_QUOTES, 'UTF-8') ?>
        </p>

    </div>

    <?php if (empty($quizzes)): ?>

        <div class="empty">

            <?= isset($error)
                ? htmlspecialchars($t["error"], ENT_QUOTES, 'UTF-8')
                : ($lang === "ar"
                    ? "لا توجد اختبارات متاحة حاليا."
                    : ($lang === "en"
                        ? "No quizzes available."
                        : "Aucun quiz disponible.")) ?>

        </div>

    <?php else: ?>

        <div class="quiz-grid">

            <?php foreach ($quizzes as $index => $quiz): ?>

                <article class="quiz-card">

                    <div class="quiz-icon">
                        📖
                    </div>

                    <span class="quiz-number">

                        QUIZ <?= $index + 1 ?>

                    </span>

                    <h2>

                        <?= htmlspecialchars(
                            $quiz["title"],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </h2>

                    <p>

                        <?= htmlspecialchars(
                            $quiz["description"] ?? "",
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                    <div class="question-count">

                        <span>✦</span>

                        <?= (int)$quiz["total_questions"] ?>

                        <?= htmlspecialchars(
                            $t["questions"],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                    <a class="quiz-button"
                       href="take_quiz.php?id=<?= (int)$quiz["id"] ?>&lang=<?= urlencode($lang) ?>">

                        <?= htmlspecialchars(
                            $t["start"],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                        <span>←</span>

                    </a>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

</body>
</html>
