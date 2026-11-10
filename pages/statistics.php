
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

$translations = [

    "fr" => [
        "title" => "Mes statistiques",
        "description" => "Voici le résumé de tes performances.",
        "courses" => "Cours terminés",
        "lessons" => "Leçons terminées",
        "quizzes" => "Quiz réalisés",
        "average" => "Moyenne des quiz",
        "exercises" => "Exercices réalisés",
        "exercise_average" => "Moyenne des exercices",
        "recent" => "Derniers résultats",
        "quiz" => "Quiz",
        "exercise" => "Exercice",
        "score" => "Note",
        "empty" => "Tu n'as pas encore réalisé de quiz ou d'exercice.",
        "back" => "Retour au tableau de bord",
        "home" => "Accueil",
        "dashboard" => "Tableau de bord",
        "activity" => "Ton activité d'apprentissage",
        "success" => "Continue ainsi ! Chaque étape compte."
    ],

    "en" => [
        "title" => "My Statistics",
        "description" => "Here is a summary of your performance.",
        "courses" => "Completed courses",
        "lessons" => "Completed lessons",
        "quizzes" => "Quizzes completed",
        "average" => "Quiz average",
        "exercises" => "Exercises completed",
        "exercise_average" => "Exercise average",
        "recent" => "Recent results",
        "quiz" => "Quiz",
        "exercise" => "Exercise",
        "score" => "Score",
        "empty" => "You haven't completed any quizzes or exercises yet.",
        "back" => "Back to dashboard",
        "home" => "Home",
        "dashboard" => "Dashboard",
        "activity" => "Your learning activity",
        "success" => "Keep going! Every step counts."
    ],

    "ar" => [
        "title" => "إحصائياتي",
        "description" => "إليك ملخص أدائك.",
        "courses" => "الدورات المكتملة",
        "lessons" => "الدروس المكتملة",
        "quizzes" => "الاختبارات المنجزة",
        "average" => "متوسط نتائج الاختبارات",
        "exercises" => "التمارين المنجزة",
        "exercise_average" => "متوسط نتائج التمارين",
        "recent" => "أحدث النتائج",
        "quiz" => "اختبار",
        "exercise" => "تمرين",
        "score" => "النتيجة",
        "empty" => "لم تنجز أي اختبار أو تمرين بعد.",
        "back" => "العودة إلى لوحة التحكم",
        "home" => "الرئيسية",
        "dashboard" => "لوحة التحكم",
        "activity" => "نشاطك التعليمي",
        "success" => "واصل التعلم، فكل خطوة مهمة."
    ]

];

$t = $translations[$lang];

// ======================================
// 4. NOMBRE DE COURS TERMINÉS
// ======================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM user_progress
    WHERE user_id = :user_id
    AND status = 'completed'
");

$stmt->execute([
    "user_id" => $userId
]);

$completedCourses = (int) $stmt->fetchColumn();


// ======================================
// 5. NOMBRE DE LEÇONS TERMINÉES
// ======================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM lesson_progress
    WHERE user_id = :user_id
    AND is_completed = 1
");

$stmt->execute([
    "user_id" => $userId
]);

$completedLessons = (int) $stmt->fetchColumn();


// ======================================
// 6. NOMBRE DE QUIZ RÉALISÉS
// ======================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM quiz_results
    WHERE user_id = :user_id
");

$stmt->execute([
    "user_id" => $userId
]);

$totalQuizzes = (int) $stmt->fetchColumn();


// ======================================
// 7. MOYENNE DES QUIZ
// ======================================

$stmt = $pdo->prepare("
    SELECT AVG(score)
    FROM quiz_results
    WHERE user_id = :user_id
");

$stmt->execute([
    "user_id" => $userId
]);

$averageScore = $stmt->fetchColumn();

$averageScore = ($averageScore !== false && $averageScore !== null)
    ? round((float) $averageScore, 1)
    : 0;


// ======================================
// 8. NOMBRE D'EXERCICES RÉALISÉS
// ======================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM exercise_results
    WHERE user_id = :user_id
");

$stmt->execute([
    "user_id" => $userId
]);

$totalExercises = (int) $stmt->fetchColumn();


// ======================================
// 9. MOYENNE DES EXERCICES
// ======================================

$stmt = $pdo->prepare("
    SELECT AVG(score)
    FROM exercise_results
    WHERE user_id = :user_id
");

$stmt->execute([
    "user_id" => $userId
]);

$exerciseAverage = $stmt->fetchColumn();

$exerciseAverage = ($exerciseAverage !== false && $exerciseAverage !== null)
    ? round((float) $exerciseAverage, 1)
    : 0;


// ======================================
// 10. DERNIERS RÉSULTATS
// ======================================

$titleColumn = "title_" . $lang;

$sql = "

    SELECT
        q.$titleColumn AS result_title,
        qr.score,
        qr.completed_at,
        'quiz' AS result_type

    FROM quiz_results qr

    INNER JOIN quizzes q
        ON q.id = qr.quiz_id

    WHERE qr.user_id = :quiz_user_id


    UNION ALL


    SELECT
        e.$titleColumn AS result_title,
        er.score,
        er.completed_at,
        'exercise' AS result_type

    FROM exercise_results er

    INNER JOIN exercises e
        ON e.id = er.exercise_id

    WHERE er.user_id = :exercise_user_id


    ORDER BY completed_at DESC

    LIMIT 5

";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    "quiz_user_id" => $userId,
    "exercise_user_id" => $userId
]);

$recentResults = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ======================================
// 11. FONCTION DE SÉCURISATION
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
<html lang="<?= clean($lang) ?>"
      dir="<?= $direction ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= clean($t["title"]) ?> - Tajwid Academy
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>


<body class="statistics-page">


<!-- ======================================
     HEADER
====================================== -->

<header class="main-header">

    <div class="header-top">

        <div class="brand">

            <h1>تَعَلَّمِ التَّجْوِيدَ</h1>

            <h2>Tajwid Academy</h2>

            <p>
                <?= clean($t["description"]) ?>
            </p>

        </div>


        <div class="language-selector">

            <a href="statistics.php?lang=fr"
               class="<?= $lang === 'fr' ? 'active' : '' ?>">
                FR
            </a>

            <a href="statistics.php?lang=ar"
               class="<?= $lang === 'ar' ? 'active' : '' ?>">
                AR
            </a>

            <a href="statistics.php?lang=en"
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

            📊 <?= clean($t["dashboard"]) ?>

        </a>

    </nav>

</header>


<!-- ======================================
     CONTENU PRINCIPAL
====================================== -->

<main class="statistics-container">


    <!-- TITRE -->

    <section class="statistics-heading">

        <span class="statistics-label">
            TAJWID ACADEMY
        </span>

        <h1>
            <?= clean($t["title"]) ?>
        </h1>

        <p>
            <?= clean($t["description"]) ?>
        </p>

        <div class="statistics-decoration">
            ✦
        </div>

    </section>


    <!-- ==================================
         CARTES DES STATISTIQUES
    =================================== -->

    <section class="statistics-grid">


        <!-- COURS -->

        <div class="stat-card courses-stat">

            <div class="stat-icon">
                📚
            </div>

            <p>
                <?= clean($t["courses"]) ?>
            </p>

            <h2>
                <?= $completedCourses ?>
            </h2>

            <span class="stat-decoration">
                ✦
            </span>

        </div>


        <!-- LEÇONS -->

        <div class="stat-card lessons-stat">

            <div class="stat-icon">
                📖
            </div>

            <p>
                <?= clean($t["lessons"]) ?>
            </p>

            <h2>
                <?= $completedLessons ?>
            </h2>

            <span class="stat-decoration">
                ✦
            </span>

        </div>


        <!-- QUIZ RÉALISÉS -->

        <div class="stat-card quizzes-stat">

            <div class="stat-icon">
                📝
            </div>

            <p>
                <?= clean($t["quizzes"]) ?>
            </p>

            <h2>
                <?= $totalQuizzes ?>
            </h2>

            <span class="stat-decoration">
                ✦
            </span>

        </div>


        <!-- MOYENNE QUIZ -->

        <div class="stat-card average-stat">

            <div class="stat-icon">
                🏆
            </div>

            <p>
                <?= clean($t["average"]) ?>
            </p>

            <h2>
                <?= number_format($averageScore, 1) ?>%
            </h2>

            <span class="stat-decoration">
                ✦
            </span>

        </div>


        <!-- EXERCICES RÉALISÉS -->

        <div class="stat-card lessons-stat">

            <div class="stat-icon">
                ✏️
            </div>

            <p>
                <?= clean($t["exercises"]) ?>
            </p>

            <h2>
                <?= $totalExercises ?>
            </h2>

            <span class="stat-decoration">
                ✦
            </span>

        </div>


        <!-- MOYENNE EXERCICES -->

        <div class="stat-card average-stat">

            <div class="stat-icon">
                🌟
            </div>

            <p>
                <?= clean($t["exercise_average"]) ?>
            </p>

            <h2>
                <?= number_format($exerciseAverage, 1) ?>%
            </h2>

            <span class="stat-decoration">
                ✦
            </span>

        </div>


    </section>


    <!-- ==================================
         DERNIERS RÉSULTATS
    =================================== -->

    <section class="recent-section">

        <div class="section-heading">

            <div>

                <span class="statistics-label">

                    <?= clean($t["activity"]) ?>

                </span>

                <h2>

                    <?= clean($t["recent"]) ?>

                </h2>

            </div>

            <span class="section-icon">
                ✧
            </span>

        </div>


        <?php if (empty($recentResults)): ?>


            <div class="statistics-empty">

                <div class="empty-icon">
                    📘
                </div>

                <p>
                    <?= clean($t["empty"]) ?>
                </p>

            </div>


        <?php else: ?>


            <div class="results-list">


                <?php foreach ($recentResults as $index => $result): ?>

                    <?php

                        $isQuiz = ($result["result_type"] === "quiz");

                        $typeLabel = $isQuiz
                            ? $t["quiz"]
                            : $t["exercise"];

                        $icon = $isQuiz ? "📝" : "📚";

                    ?>


                    <article class="statistics-result">


                        <div class="result-icon">

                            <?= $icon ?>

                        </div>


                        <div class="result-information">

                            <span class="result-number">

                                <?= clean($typeLabel) ?>

                            </span>

                            <h3>

                                <?= clean($result["result_title"]) ?>

                            </h3>

                            <p>

                                <?= date(
                                    "d/m/Y H:i",
                                    strtotime($result["completed_at"])
                                ) ?>

                            </p>

                        </div>


                        <div class="result-score">

                            <span>

                                <?= clean($t["score"]) ?>

                            </span>

                            <strong>

                                <?= number_format(
                                    (float) $result["score"],
                                    1
                                ) ?>%

                            </strong>

                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </section>


    <!-- MESSAGE -->

    <div class="statistics-message">

        ✨ <?= clean($t["success"]) ?>

    </div>


    <!-- RETOUR -->

    <div class="statistics-back">

        <a href="dashboard.php?lang=<?= urlencode($lang) ?>">

            ← <?= clean($t["back"]) ?>

        </a>

    </div>


</main>


<!-- ======================================
     FOOTER
====================================== -->

<footer class="main-footer">

    <p>
        © <?= date("Y") ?> Tajwid Academy — Learn with knowledge and serenity.
    </p>

</footer>


</body>
</html>
