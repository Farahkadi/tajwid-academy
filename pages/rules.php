
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
// 2. RÉCUPÉRATION DES RÈGLES
// ======================================

$sql = "SELECT * FROM tajwid_rules ORDER BY id ASC";
$stmt = $pdo->query($sql);
$rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Colonnes selon la langue
$nameColumn = "name_" . $lang;
$descriptionColumn = "description_" . $lang;
$exampleColumn = "example_" . $lang;

// ======================================
// 3. TRADUCTIONS
// ======================================

$texts = [
    "fr" => [
        "title" => "Les règles du Tajwid",
        "subtitle" => "Découvre les principales règles de récitation du Saint Coran.",
        "example" => "Exemple",
        "category1" => "Nun Sakinah et Tanwin",
        "category2" => "Les règles du Madd",
        "empty" => "Aucune règle disponible pour le moment.",
        "home" => "Accueil",
        "label" => "APPRENDRE LE TAJWID"
    ],

    "ar" => [
        "title" => "أحكام التجويد",
        "subtitle" => "اكتشف أهم أحكام تلاوة القرآن الكريم.",
        "example" => "مثال",
        "category1" => "أحكام النون الساكنة والتنوين",
        "category2" => "أحكام المد",
        "empty" => "لا توجد أحكام متاحة حالياً.",
        "home" => "الرئيسية",
        "label" => "تَعَلَّمِ التَّجْوِيدَ"
    ],

    "en" => [
        "title" => "Tajwid Rules",
        "subtitle" => "Discover the main rules of Quranic recitation.",
        "example" => "Example",
        "category1" => "Noon Sakinah and Tanwin",
        "category2" => "Madd Rules",
        "empty" => "No rules are available at the moment.",
        "home" => "Home",
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

<body class="rules-page">

    <!-- HEADER -->
    <header class="main-header">

        <div class="header-top">

            <div class="brand">
                <h1>تَعَلَّمِ التَّجْوِيدَ</h1>
                <h2>Tajwid Academy</h2>
                <p>Learn the Quran, improve your recitation</p>
            </div>

            <div class="language-selector">
                <a href="rules.php?lang=fr"
                   class="<?= $lang === 'fr' ? 'active' : '' ?>">FR</a>

                <a href="rules.php?lang=ar"
                   class="<?= $lang === 'ar' ? 'active' : '' ?>">AR</a>

                <a href="rules.php?lang=en"
                   class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
            </div>

        </div>

        <nav class="main-nav">
            <a href="../index.php?lang=<?= urlencode($lang) ?>">
                <?= htmlspecialchars($t["home"]) ?>
            </a>
        </nav>

    </header>

    <!-- CONTENU PRINCIPAL -->
    <main class="rules-container">

        <div class="rules-heading">

            <span class="section-label">
                ✦ <?= htmlspecialchars($t["label"]) ?>
            </span>

            <h1>
                <?= htmlspecialchars($t["title"]) ?>
            </h1>

            <p>
                <?= htmlspecialchars($t["subtitle"]) ?>
            </p>

            <div class="heading-decoration">
                <span></span>
                ✧
                <span></span>
            </div>

        </div>

        <?php if (empty($rules)): ?>

            <div class="rules-empty">
                <?= htmlspecialchars($t["empty"]) ?>
            </div>

        <?php else: ?>

            <div class="rules-grid">

                <?php foreach ($rules as $index => $rule): ?>

                    <article class="rule-card">

                        <div class="rule-card-top">

                            <span class="rule-number">
                                <?= str_pad($index + 1, 2, "0", STR_PAD_LEFT) ?>
                            </span>

                            <span class="rule-decoration">✧</span>

                        </div>

                        <h2>
                            <?= htmlspecialchars($rule[$nameColumn] ?? "") ?>
                        </h2>

                        <p class="rule-description">
                            <?= htmlspecialchars($rule[$descriptionColumn] ?? "") ?>
                        </p>

                        <div class="rule-example">

                            <strong>
                                <span>✦</span>
                                <?= htmlspecialchars($t["example"]) ?>
                            </strong>

                            <p dir="auto">
                                <?= htmlspecialchars($rule[$exampleColumn] ?? "") ?>
                            </p>

                        </div>

                        <div class="rule-card-footer">

                            <span class="rule-category">
                                <?php
                                if ($rule["category"] === "Nun Sakinah et Tanwin") {
                                    echo htmlspecialchars($t["category1"]);
                                } else {
                                    echo htmlspecialchars($t["category2"]);
                                }
                                ?>
                            </span>

                        </div>

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
