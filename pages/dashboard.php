
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
$userName = $_SESSION["full_name"] ?? "Étudiant";

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
        "title" => "Mon tableau de bord",
        "welcome" => "Bienvenue",
        "description" => "Voici le résumé de ton apprentissage.",
        "total" => "Cours commencés",
        "completed" => "Cours terminés",
        "lessons" => "Leçons terminées",
        "quizzes" => "Quiz disponibles",
        "exercises" => "Exercices disponibles",
        "progress" => "Progression",
        "duration" => "Durée",
        "continue" => "Continuer le cours",
        "start" => "Commencer le cours",
        "empty" => "Tu n'as pas encore commencé de cours.",
        "courses" => "Mes cours",
        "back" => "Retour à l'accueil",
        "not_started" => "Non commencé",
        "in_progress" => "En cours",
        "done" => "Terminé",
        "results" => "Mes résultats",
        "favorites" => "Mes favoris",
        "statistics" => "Mes statistiques",
        "minutes" => "min"
    ],

    "en" => [
        "title" => "My Dashboard",
        "welcome" => "Welcome",
        "description" => "Here is your learning summary.",
        "total" => "Courses started",
        "completed" => "Courses completed",
        "lessons" => "Lessons completed",
        "quizzes" => "Available quizzes",
        "exercises" => "Available exercises",
        "progress" => "Progress",
        "duration" => "Duration",
        "continue" => "Continue course",
        "start" => "Start course",
        "empty" => "You haven't started any course yet.",
        "courses" => "My courses",
        "back" => "Back to home",
        "not_started" => "Not started",
        "in_progress" => "In progress",
        "done" => "Completed",
        "results" => "My results",
        "favorites" => "My favorites",
        "statistics" => "My statistics",
        "minutes" => "min"
    ],

    "ar" => [
        "title" => "لوحة التحكم الخاصة بي",
        "welcome" => "مرحباً",
        "description" => "إليك ملخص تقدمك في التعلم.",
        "total" => "الدورات التي بدأت",
        "completed" => "الدورات المكتملة",
        "lessons" => "الدروس المكتملة",
        "quizzes" => "الاختبارات المتاحة",
        "exercises" => "التمارين المتاحة",
        "progress" => "التقدم",
        "duration" => "المدة",
        "continue" => "متابعة الدورة",
        "start" => "ابدأ الدورة",
        "empty" => "لم تبدأ أي دورة بعد.",
        "courses" => "دوراتي",
        "back" => "العودة إلى الصفحة الرئيسية",
        "not_started" => "لم تبدأ",
        "in_progress" => "قيد التقدم",
        "done" => "مكتملة",
        "results" => "نتائجي",
        "favorites" => "مفضلتي",
        "statistics" => "إحصائياتي",
        "minutes" => "دقيقة"
    ]
];

$t = $texts[$lang];

// ======================================
// 4. RÉCUPÉRER LES COURS ET PROGRESSIONS
// ======================================

$sql = "SELECT
            c.id,
            c.title_$lang AS course_title,
            c.description_$lang AS course_description,
            c.duration_minutes,
            COALESCE(up.completed_lessons, 0) AS completed_lessons,
            COALESCE(up.progress_percentage, 0) AS progress_percentage,
            COALESCE(up.status, 'not_started') AS status

        FROM courses c

        LEFT JOIN user_progress up
            ON c.id = up.course_id
            AND up.user_id = :user_id

        ORDER BY c.id ASC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    "user_id" => $userId
]);

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================================
// 5. CALCUL DES STATISTIQUES DES COURS
// ======================================

$totalStarted = 0;
$totalCompleted = 0;
$totalLessons = 0;

foreach ($courses as $course) {

    if ($course["status"] !== "not_started") {
        $totalStarted++;
    }

    if ($course["status"] === "completed") {
        $totalCompleted++;
    }

    $totalLessons += (int) $course["completed_lessons"];
}

// ======================================
// 6. NOMBRE DE QUIZ ET D'EXERCICES
// ======================================

// Nombre total de quiz disponibles
$totalQuizzes = (int) $pdo
    ->query("SELECT COUNT(*) FROM quizzes")
    ->fetchColumn();

// Nombre total d'exercices disponibles
$totalExercises = (int) $pdo
    ->query("SELECT COUNT(*) FROM exercises")
    ->fetchColumn();

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"
      dir="<?= $direction ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($t["title"]) ?> - Tajwid Academy
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="dashboard-page">

<header class="main-header">

    <div class="header-top">

        <div class="brand">

            <h1>تَعَلَّمِ التَّجْوِيدَ</h1>

            <h2>Tajwid Academy</h2>

            <p>
                <?= htmlspecialchars($t["description"]) ?>
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
            <?= htmlspecialchars($t["back"]) ?>
        </a>

    </nav>

</header>

<main class="dashboard-container">

    <!-- ================================== -->
    <!-- BIENVENUE -->
    <!-- ================================== -->

    <section class="dashboard-header">

        <span class="dashboard-label">
            ✦ TAJWID ACADEMY
        </span>

        <h1>
            <?= htmlspecialchars($t["welcome"]) ?>,
            <?= htmlspecialchars($userName) ?> !
        </h1>

        <p>
            <?= htmlspecialchars($t["description"]) ?>
        </p>

    </section>


    <!-- ================================== -->
    <!-- STATISTIQUES -->
    <!-- ================================== -->

    <section class="dashboard-stats">

        <!-- Cours commencés -->
        <article class="stat-card">

            <div class="stat-icon">📚</div>

            <p>
                <?= htmlspecialchars($t["total"]) ?>
            </p>

            <h2><?= $totalStarted ?></h2>

        </article>


        <!-- Cours terminés -->
        <article class="stat-card">

            <div class="stat-icon">🏆</div>

            <p>
                <?= htmlspecialchars($t["completed"]) ?>
            </p>

            <h2><?= $totalCompleted ?></h2>

        </article>


        <!-- Leçons terminées -->
        <article class="stat-card">

            <div class="stat-icon">📖</div>

            <p>
                <?= htmlspecialchars($t["lessons"]) ?>
            </p>

            <h2><?= $totalLessons ?></h2>

        </article>


        <!-- Quiz disponibles -->
        <article class="stat-card">

            <div class="stat-icon">📝</div>

            <p>
                <?= htmlspecialchars($t["quizzes"]) ?>
            </p>

            <h2><?= $totalQuizzes ?></h2>

        </article>


        <!-- Exercices disponibles -->
        <article class="stat-card">

            <div class="stat-icon">✍️</div>

            <p>
                <?= htmlspecialchars($t["exercises"]) ?>
            </p>

            <h2><?= $totalExercises ?></h2>

        </article>

    </section>


    <!-- ================================== -->
    <!-- ACCÈS RAPIDES -->
    <!-- ================================== -->

    <section class="dashboard-quick-links">

        <a href="my_results.php?lang=<?= urlencode($lang) ?>"
           class="quick-link">

            <span>📊</span>

            <?= htmlspecialchars($t["results"]) ?>

        </a>


        <a href="statistics.php?lang=<?= urlencode($lang) ?>"
           class="quick-link">

            <span>📈</span>

            <?= htmlspecialchars($t["statistics"]) ?>

        </a>


        <a href="favorites.php?lang=<?= urlencode($lang) ?>"
           class="quick-link">

            <span>❤️</span>

            <?= htmlspecialchars($t["favorites"]) ?>

        </a>

    </section>


    <!-- ================================== -->
    <!-- MES COURS -->
    <!-- ================================== -->

    <section class="dashboard-courses-section">

        <div class="dashboard-section-heading">

            <span class="section-label">
                ✦ MY LEARNING
            </span>

            <h2>
                <?= htmlspecialchars($t["courses"]) ?>
            </h2>

        </div>


        <?php if (empty($courses)): ?>

            <div class="empty-message">

                <div class="empty-icon">📚</div>

                <p>
                    <?= htmlspecialchars($t["empty"]) ?>
                </p>

                <a href="courses.php?lang=<?= urlencode($lang) ?>"
                   class="course-button">

                    <?= htmlspecialchars($t["start"]) ?>

                </a>

            </div>


        <?php else: ?>

            <div class="dashboard-courses-grid">

                <?php foreach ($courses as $course): ?>

                    <?php

                    $percentage = min(
                        100,
                        max(0, (float) $course["progress_percentage"])
                    );

                    $statusText = match ($course["status"]) {

                        "completed" => $t["done"],

                        "in_progress" => $t["in_progress"],

                        default => $t["not_started"]

                    };

                    ?>

                    <article class="dashboard-course">

                        <div class="course-card-top">

                            <span class="course-card-icon">
                                📘
                            </span>

                            <span class="course-status">

                                <?= htmlspecialchars($statusText) ?>

                            </span>

                        </div>


                        <h3>

                            <?= htmlspecialchars(
                                $course["course_title"] ?? ""
                            ) ?>

                        </h3>


                        <p class="course-description">

                            <?= htmlspecialchars(
                                $course["course_description"] ?? ""
                            ) ?>

                        </p>


                        <p class="course-duration">

                            ⏱️
                            <?= htmlspecialchars($t["duration"]) ?> :

                            <?= (int) $course["duration_minutes"] ?>

                            <?= htmlspecialchars($t["minutes"]) ?>

                        </p>


                        <div class="course-progress-info">

                            <span>
                                <?= htmlspecialchars($t["progress"]) ?>
                            </span>

                            <strong>
                                <?= number_format($percentage, 0) ?>%
                            </strong>

                        </div>


                        <div class="progress-bar">

                            <div class="progress-fill"
                                 style="width: <?= $percentage ?>%;">

                            </div>

                        </div>


                        <a class="course-button"
                           href="course.php?id=<?= (int) $course["id"] ?>&lang=<?= urlencode($lang) ?>">

                            <?= $course["status"] === "not_started"
                                ? htmlspecialchars($t["start"])
                                : htmlspecialchars($t["continue"]) ?>

                            <span>→</span>

                        </a>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>


<!-- ================================== -->
<!-- FOOTER -->
<!-- ================================== -->

<footer class="main-footer">

    <p>
        © <?= date("Y") ?> Tajwid Academy
    </p>

</footer>

</body>
</html>
