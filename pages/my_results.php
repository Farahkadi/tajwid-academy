
<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

// ======================================
// 1. VÉRIFIER LA CONNEXION
// ======================================

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

// ======================================
// 2. GESTION DE LA LANGUE
// ======================================

$lang = $_GET["lang"] ?? $_SESSION["lang"] ?? "fr";

if (!in_array($lang, ["fr", "ar", "en"], true)) {
    $lang = "fr";
}

$_SESSION["lang"] = $lang;

$direction = ($lang === "ar") ? "rtl" : "ltr";

// ======================================
// 3. TRADUCTIONS
// ======================================

$texts = [

    "fr" => [
        "title" => "Mes résultats",
        "subtitle" => "Consulte l'historique de tes quiz et exercices.",
        "quiz" => "Quiz",
        "exercise" => "Exercice",
        "score" => "Note",
        "correct" => "Bonnes réponses",
        "date" => "Date",
        "empty" => "Tu n'as encore terminé aucun quiz ni exercice.",
        "back" => "Tableau de bord",
        "home" => "Accueil",
        "questions" => "questions",
        "label" => "MON APPRENTISSAGE"
    ],

    "en" => [
        "title" => "My Results",
        "subtitle" => "View your quiz and exercise history.",
        "quiz" => "Quiz",
        "exercise" => "Exercise",
        "score" => "Score",
        "correct" => "Correct answers",
        "date" => "Date",
        "empty" => "You haven't completed any quizzes or exercises yet.",
        "back" => "Dashboard",
        "home" => "Home",
        "questions" => "questions",
        "label" => "MY LEARNING"
    ],

    "ar" => [
        "title" => "نتائجي",
        "subtitle" => "اطلع على سجل اختباراتك وتمارينك.",
        "quiz" => "اختبار",
        "exercise" => "تمرين",
        "score" => "النتيجة",
        "correct" => "الإجابات الصحيحة",
        "date" => "التاريخ",
        "empty" => "لم تكمل أي اختبار أو تمرين بعد.",
        "back" => "لوحة التحكم",
        "home" => "الرئيسية",
        "questions" => "أسئلة",
        "label" => "مسيرتي التعليمية"
    ]

];

$t = $texts[$lang];

// ======================================
// 4. RÉCUPÉRER LES RÉSULTATS DES QUIZ
// ======================================

$titleColumn = "title_" . $lang;

try {

    $sql = "

        SELECT
            qr.id,
            'quiz' AS result_type,
            q.$titleColumn AS result_title,
            qr.score,
            qr.correct_answers,
            qr.total_questions,
            qr.completed_at

        FROM quiz_results qr

        INNER JOIN quizzes q
            ON q.id = qr.quiz_id

        WHERE qr.user_id = :quiz_user_id

        UNION ALL

        SELECT
            er.id,
            'exercise' AS result_type,
            e.$titleColumn AS result_title,
            er.score,
            er.correct_answers,
            er.total_questions,
            er.completed_at

        FROM exercise_results er

        INNER JOIN exercises e
            ON e.id = er.exercise_id

        WHERE er.user_id = :exercise_user_id

        ORDER BY completed_at DESC

    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        "quiz_user_id" => $userId,
        "exercise_user_id" => $userId
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Une erreur est survenue lors de la récupération des résultats.");

}

// ======================================
// 5. FONCTION DE SÉCURISATION
// ======================================

function clean($value) {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );

}

?>

<!DOCTYPE html>
<html lang="<?= clean($lang) ?>" dir="<?= $direction ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= clean($t["title"]) ?> - Tajwid Academy
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="results-page">

<!-- ======================================
     HEADER
====================================== -->

<header class="main-header">

    <div class="header-top">

        <div class="brand">

            <h1>تَعَلَّمِ التَّجْوِيدَ</h1>

            <h2>Tajwid Academy</h2>

            <p>
                <?= clean($t["subtitle"]) ?>
            </p>

        </div>

        <div class="language-selector">

            <a href="?lang=fr"
               class="<?= $lang === 'fr' ? 'active' : '' ?>">
                FR
            </a>

            <a href="?lang=ar"
               class="<?= $lang === 'ar' ? 'active' : '' ?>">
                AR
            </a>

            <a href="?lang=en"
               class="<?= $lang === 'en' ? 'active' : '' ?>">
                EN
            </a>

        </div>

    </div>

    <nav class="main-nav">

        <a href="../index.php?lang=<?= urlencode($lang) ?>">

            🏠 <?= clean($t["home"]) ?>

        </a>

        <a href="dashboard.php?lang=<?= urlencode($lang) ?>">

            📊 <?= clean($t["back"]) ?>

        </a>

    </nav>

</header>


<!-- ======================================
     CONTENU PRINCIPAL
====================================== -->

<main class="results-container">

    <section class="results-heading">

        <span class="section-label">

            ✦ <?= clean($t["label"]) ?>

        </span>

        <h1>

            🏆 <?= clean($t["title"]) ?>

        </h1>

        <p>

            <?= clean($t["subtitle"]) ?>

        </p>

    </section>


    <?php if (empty($results)): ?>

        <!-- Aucun résultat -->

        <div class="results-empty">

            <div class="empty-icon">📚</div>

            <h2>
                <?= clean($t["title"]) ?>
            </h2>

            <p>
                <?= clean($t["empty"]) ?>
            </p>

        </div>


    <?php else: ?>

        <!-- Liste des résultats -->

        <div class="results-list">

            <?php foreach ($results as $index => $result): ?>

                <?php

                    $score = (float) $result["score"];

                    $percentage = min(100, max(0, $score));

                    $isQuiz = ($result["result_type"] === "quiz");

                    $typeLabel = $isQuiz
                        ? $t["quiz"]
                        : $t["exercise"];

                    $icon = $isQuiz ? "📝" : "📚";

                ?>


                <article class="result-card">

                    <!-- En-tête du résultat -->

                    <div class="result-card-header">

                        <div class="result-icon">

                            <?= $icon ?>

                        </div>

                        <div class="result-title">

                            <span class="result-number">

                                <?= clean($typeLabel) ?>
                                #<?= $index + 1 ?>

                            </span>

                            <h2>

                                <?= clean($result["result_title"]) ?>

                            </h2>

                        </div>

                    </div>


                    <!-- Score -->

                    <div class="result-score-area">

                        <span>

                            <?= clean($t["score"]) ?>

                        </span>

                        <strong class="result-score">

                            <?= number_format($score, 0) ?>%

                        </strong>

                    </div>


                    <!-- Barre de progression -->

                    <div class="result-progress">

                        <div
                            class="result-progress-fill"
                            style="width: <?= $percentage ?>%;"
                        >

                        </div>

                    </div>


                    <!-- Détails -->

                    <div class="result-details">

                        <!-- Bonnes réponses -->

                        <div class="result-detail">

                            <span class="detail-icon">

                                ✓

                            </span>

                            <div>

                                <small>

                                    <?= clean($t["correct"]) ?>

                                </small>

                                <strong>

                                    <?= (int) $result["correct_answers"] ?>

                                    /

                                    <?= (int) $result["total_questions"] ?>

                                </strong>

                            </div>

                        </div>


                        <!-- Date -->

                        <div class="result-detail">

                            <span class="detail-icon">

                                📅

                            </span>

                            <div>

                                <small>

                                    <?= clean($t["date"]) ?>

                                </small>

                                <strong>

                                    <?= date(
                                        "d/m/Y H:i",
                                        strtotime($result["completed_at"])
                                    ) ?>

                                </strong>

                            </div>

                        </div>

                    </div>

                </article>


            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>


<!-- ======================================
     FOOTER
====================================== -->

<footer class="main-footer">

    <p>
        © <?= date("Y") ?> Tajwid Academy
    </p>

</footer>

</body>
</html>
