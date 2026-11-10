
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

$lang = $_GET["lang"] ?? "fr";

if (!in_array($lang, ["fr", "ar", "en"], true)) {
    $lang = "fr";
}

$direction = ($lang === "ar") ? "rtl" : "ltr";

// ======================================
// 2. IDENTIFIANT DU COURS
// ======================================

$courseId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($courseId <= 0) {
    die("Cours introuvable.");
}

// Colonnes selon la langue
$titleColumn = "title_" . $lang;
$descriptionColumn = "description_" . $lang;
$ruleColumn = "name_" . $lang;

// ======================================
// 3. RÉCUPÉRATION DU COURS
// ======================================

$sql = "SELECT courses.*, tajwid_rules.$ruleColumn AS rule_name
        FROM courses
        LEFT JOIN tajwid_rules ON courses.rule_id = tajwid_rules.id
        WHERE courses.id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute(["id" => $courseId]);

$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Ce cours n'existe pas.");
}

// ======================================
// 4. RÉCUPÉRATION DES LEÇONS
// ======================================

$sql = "SELECT * FROM lessons
        WHERE course_id = :course_id
        ORDER BY position ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(["course_id" => $courseId]);

$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================================
// 5. TRADUCTIONS
// ======================================

$texts = [
    "fr" => [
        "lessons" => "Leçons du cours",
        "duration" => "Durée",
        "minutes" => "minutes",
        "start" => "Commencer la leçon",
        "back" => "Retour aux cours",
        "no_lessons" => "Aucune leçon disponible pour le moment.",
        "label" => "APPRENDRE À TON RYTHME",
        "lesson" => "LEÇON",
        "rule" => "Règle de Tajwid"
    ],

    "en" => [
        "lessons" => "Course lessons",
        "duration" => "Duration",
        "minutes" => "minutes",
        "start" => "Start lesson",
        "back" => "Back to courses",
        "no_lessons" => "No lessons available at the moment.",
        "label" => "LEARN AT YOUR OWN PACE",
        "lesson" => "LESSON",
        "rule" => "Tajwid Rule"
    ],

    "ar" => [
        "lessons" => "دروس الدورة",
        "duration" => "المدة",
        "minutes" => "دقائق",
        "start" => "ابدأ الدرس",
        "back" => "العودة إلى الدورات",
        "no_lessons" => "لا توجد دروس متاحة حالياً.",
        "label" => "تَعَلَّمْ بِخُطْوَاتٍ ثَابِتَةٍ",
        "lesson" => "الدرس",
        "rule" => "حكم التجويد"
    ]
];

$t = $texts[$lang];
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $direction ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($course[$titleColumn] ?? "") ?> - Tajwid Academy
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="course-detail-page">

    <!-- HEADER -->
    <header class="main-header">

        <div class="header-top">

            <div class="brand">
                <h1>تَعَلَّمِ التَّجْوِيدَ</h1>
                <h2>Tajwid Academy</h2>
                <p>Learn the Quran, improve your recitation</p>
            </div>

            <div class="language-selector">

                <a href="course.php?id=<?= $courseId ?>&lang=fr"
                   class="<?= $lang === 'fr' ? 'active' : '' ?>">FR</a>

                <a href="course.php?id=<?= $courseId ?>&lang=ar"
                   class="<?= $lang === 'ar' ? 'active' : '' ?>">AR</a>

                <a href="course.php?id=<?= $courseId ?>&lang=en"
                   class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>

            </div>

        </div>

        <nav class="main-nav">
            <a href="../index.php?lang=<?= urlencode($lang) ?>">
                <?= $lang === "ar" ? "الرئيسية" : ($lang === "fr" ? "Accueil" : "Home") ?>
            </a>

            <a href="courses.php?lang=<?= urlencode($lang) ?>">
                <?= htmlspecialchars($t["back"]) ?>
            </a>
        </nav>

    </header>

    <!-- MAIN CONTENT -->
    <main class="course-details">

        <a class="back-button"
           href="courses.php?lang=<?= urlencode($lang) ?>">
            <span>←</span>
            <?= htmlspecialchars($t["back"]) ?>
        </a>

        <!-- COURSE PRESENTATION -->
        <section class="course-header">

            <span class="section-label">
                ✦ <?= htmlspecialchars($t["label"]) ?>
            </span>

            <h1>
                <?= htmlspecialchars($course[$titleColumn] ?? "") ?>
            </h1>

            <p class="course-intro">
                <?= nl2br(htmlspecialchars($course[$descriptionColumn] ?? "")) ?>
            </p>

            <div class="course-meta">

                <div class="course-meta-item">
                    <span class="meta-icon">◷</span>

                    <div>
                        <small><?= htmlspecialchars($t["duration"]) ?></small>

                        <strong>
                            <?= (int) $course["duration_minutes"] ?>
                            <?= htmlspecialchars($t["minutes"]) ?>
                        </strong>
                    </div>
                </div>

                <?php if (!empty($course["rule_name"])): ?>

                    <div class="course-meta-item">
                        <span class="meta-icon">✦</span>

                        <div>
                            <small><?= htmlspecialchars($t["rule"]) ?></small>

                            <strong>
                                <?= htmlspecialchars($course["rule_name"]) ?>
                            </strong>
                        </div>
                    </div>

                <?php endif; ?>

            </div>

        </section>

        <!-- LESSONS -->
        <section class="course-lessons-section">

            <div class="lessons-heading">
                <span class="section-label">✦ TAJWID ACADEMY</span>

                <h2><?= htmlspecialchars($t["lessons"]) ?></h2>
            </div>

            <?php if (count($lessons) > 0): ?>

                <div class="lesson-list">

                    <?php foreach ($lessons as $index => $lesson): ?>

                        <article class="lesson-item">

                            <div class="lesson-number">
                                <?= str_pad(
                                    (int) $lesson["position"],
                                    2,
                                    "0",
                                    STR_PAD_LEFT
                                ) ?>
                            </div>

                            <div class="lesson-info">

                                <span class="lesson-label">
                                    <?= htmlspecialchars($t["lesson"]) ?>
                                    <?= $index + 1 ?>
                                </span>

                                <h3>
                                    <?= htmlspecialchars(
                                        $lesson["title_" . $lang] ?? ""
                                    ) ?>
                                </h3>

                            </div>

                            <a class="lesson-button"
                               href="lesson.php?id=<?= (int) $lesson["id"] ?>&lang=<?= urlencode($lang) ?>">

                                <?= htmlspecialchars($t["start"]) ?>

                                <span>←</span>

                            </a>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="lessons-empty">
                    <span>📖</span>
                    <p><?= htmlspecialchars($t["no_lessons"]) ?></p>
                </div>

            <?php endif; ?>

        </section>

    </main>

    <!-- FOOTER -->
    <footer class="main-footer">
        <p>© <?= date("Y") ?> Tajwid Academy — Learn with knowledge and confidence.</p>
    </footer>

</body>
</html>
