
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

// Colonnes selon la langue
$titleColumn = "title_" . $lang;
$descriptionColumn = "description_" . $lang;
$ruleColumn = "name_" . $lang;

// ======================================
// 2. RÉCUPÉRATION DES COURS
// ======================================

$sql = "SELECT
            courses.*,
            tajwid_rules.$ruleColumn AS rule_name
        FROM courses
        LEFT JOIN tajwid_rules
        ON courses.rule_id = tajwid_rules.id
        ORDER BY courses.id ASC";

$stmt = $pdo->query($sql);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================================
// 3. TRADUCTIONS
// ======================================

$texts = [
    "fr" => [
        "title" => "Nos cours de Tajwid",
        "description" => "Apprenez les règles de récitation du Coran étape par étape.",
        "duration" => "Durée",
        "rule" => "Règle",
        "beginner" => "Débutant",
        "intermediate" => "Intermédiaire",
        "advanced" => "Avancé",
        "no_rule" => "Cours général",
        "home" => "Accueil",
        "view" => "Voir le cours",
        "minutes" => "min",
        "empty" => "Aucun cours disponible pour le moment.",
        "label" => "APPRENDRE LE TAJWID"
    ],

    "ar" => [
        "title" => "دروس التجويد",
        "description" => "تعلم أحكام تلاوة القرآن الكريم خطوة بخطوة.",
        "duration" => "المدة",
        "rule" => "القاعدة",
        "beginner" => "مبتدئ",
        "intermediate" => "متوسط",
        "advanced" => "متقدم",
        "no_rule" => "درس عام",
        "home" => "الرئيسية",
        "view" => "عرض الدورة",
        "minutes" => "دقيقة",
        "empty" => "لا توجد دروس متاحة حالياً.",
        "label" => "تَعَلَّمِ التَّجْوِيدَ"
    ],

    "en" => [
        "title" => "Our Tajwid Courses",
        "description" => "Learn the rules of Quran recitation step by step.",
        "duration" => "Duration",
        "rule" => "Rule",
        "beginner" => "Beginner",
        "intermediate" => "Intermediate",
        "advanced" => "Advanced",
        "no_rule" => "General course",
        "home" => "Home",
        "view" => "View course",
        "minutes" => "min",
        "empty" => "No courses available at the moment.",
        "label" => "LEARN TAJWID"
    ]
];

$t = $texts[$lang];
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $direction ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($t["title"]) ?> - Tajwid Academy</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="courses-page">

    <!-- HEADER -->
    <header class="main-header">

        <div class="header-top">

            <div class="brand">
                <h1>تَعَلَّمِ التَّجْوِيدَ</h1>
                <h2>Tajwid Academy</h2>
                <p>Learn the Quran, improve your recitation</p>
            </div>

            <div class="language-selector">

                <a href="courses.php?lang=fr"
                   class="<?= $lang === 'fr' ? 'active' : '' ?>">FR</a>

                <a href="courses.php?lang=ar"
                   class="<?= $lang === 'ar' ? 'active' : '' ?>">AR</a>

                <a href="courses.php?lang=en"
                   class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>

            </div>

        </div>

        <nav class="main-nav">
            <a href="../index.php?lang=<?= urlencode($lang) ?>">
                <?= htmlspecialchars($t["home"]) ?>
            </a>
        </nav>

    </header>

    <!-- MAIN CONTENT -->
    <main class="courses-container">

        <div class="courses-heading">

            <span class="section-label">
                ✦ <?= htmlspecialchars($t["label"]) ?>
            </span>

            <h1><?= htmlspecialchars($t["title"]) ?></h1>

            <p><?= htmlspecialchars($t["description"]) ?></p>

            <div class="heading-decoration">
                <span></span>
                ✧
                <span></span>
            </div>

        </div>

        <?php if (empty($courses)): ?>

            <div class="courses-empty">
                <?= htmlspecialchars($t["empty"]) ?>
            </div>

        <?php else: ?>

            <div class="courses-grid">

                <?php foreach ($courses as $index => $course): ?>

                    <article class="course-card">

                        <div class="course-card-top">

                            <span class="course-number">
                                <?= str_pad($index + 1, 2, "0", STR_PAD_LEFT) ?>
                            </span>

                            <span class="course-icon">📖</span>

                        </div>

                        <h2>
                            <?= htmlspecialchars($course[$titleColumn] ?? "") ?>
                        </h2>

                        <p class="course-description">
                            <?= htmlspecialchars($course[$descriptionColumn] ?? "") ?>
                        </p>

                        <div class="course-level">
                            <?= htmlspecialchars(
                                $t[$course["level"]] ?? $t["beginner"]
                            ) ?>
                        </div>

                        <div class="course-info">

                            <div class="course-info-item">
                                <span class="info-icon">◷</span>

                                <div>
                                    <small><?= htmlspecialchars($t["duration"]) ?></small>
                                    <strong>
                                        <?= (int)$course["duration_minutes"] ?>
                                        <?= htmlspecialchars($t["minutes"]) ?>
                                    </strong>
                                </div>
                            </div>

                            <div class="course-info-item">
                                <span class="info-icon">✦</span>

                                <div>
                                    <small><?= htmlspecialchars($t["rule"]) ?></small>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $course["rule_name"] ?? $t["no_rule"]
                                        ) ?>
                                    </strong>
                                </div>
                            </div>

                        </div>

                        <a href="course.php?id=<?= (int)$course['id'] ?>&lang=<?= urlencode($lang) ?>"
                           class="course-button">

                            <?= htmlspecialchars($t["view"]) ?>

                            <span>←</span>

                        </a>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

    <!-- FOOTER -->
    <footer class="main-footer">
        <p>© <?= date("Y") ?> Tajwid Academy — Learn with knowledge and confidence.</p>
    </footer>

</body>
</html>
