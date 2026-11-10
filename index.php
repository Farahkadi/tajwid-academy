
<?php
session_start();

require_once "config/api.php";

// ======================================
// 1. VERIFICATION DE CONNEXION
// ======================================

$isLoggedIn = isset($_SESSION["user_id"]);
$userName = $_SESSION["full_name"] ?? "";

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
        "home" => "Accueil",
        "quran" => "Le Saint Coran",
        "rules" => "Règles de Tajwid",
        "courses" => "Nos cours",
        "login" => "Connexion",
        "register" => "Inscription",
        "welcome" => "Bienvenue",
        "dashboard" => "Mon tableau de bord",
        "logout" => "Déconnexion",
        "title" => "Bienvenue sur Tajwid Academy",
        "description" => "Apprends les règles de Tajwid, découvre les versets du Coran et améliore tes connaissances.",
        "discover" => "Découvrir le Coran",
        "daily" => "Verset du jour",
        "unavailable" => "Le verset du jour est momentanément indisponible.",
        "footer" => "Apprendre le Tajwid",
        "access_message" => "Pour accéder à notre plateforme, connecte-toi ou crée ton compte.",
        "account_title" => "Commence ton apprentissage du Tajwid",
        "account_description" => "Découvre le Saint Coran, apprends les règles de récitation et progresse grâce à nos cours et exercices.",
        "profile" => "Mon compte"
    ],

    "en" => [
        "home" => "Home",
        "quran" => "The Holy Quran",
        "rules" => "Tajwid Rules",
        "courses" => "Our Courses",
        "login" => "Login",
        "register" => "Register",
        "welcome" => "Welcome",
        "dashboard" => "My Dashboard",
        "logout" => "Logout",
        "title" => "Welcome to Tajwid Academy",
        "description" => "Learn Tajwid rules, discover Quranic verses and improve your knowledge.",
        "discover" => "Discover the Quran",
        "daily" => "Verse of the Day",
        "unavailable" => "The verse of the day is temporarily unavailable.",
        "footer" => "Learn Tajwid",
        "access_message" => "To access our platform, please log in or create an account.",
        "account_title" => "Start learning Tajwid",
        "account_description" => "Discover the Holy Quran, learn recitation rules and improve through our courses and exercises.",
        "profile" => "My account"
    ],

    "ar" => [
        "home" => "الرئيسية",
        "quran" => "القرآن الكريم",
        "rules" => "أحكام التجويد",
        "courses" => "دروسنا",
        "login" => "تسجيل الدخول",
        "register" => "إنشاء حساب",
        "welcome" => "مرحباً",
        "dashboard" => "لوحة التحكم",
        "logout" => "تسجيل الخروج",
        "title" => "مرحباً بك في أكاديمية التجويد",
        "description" => "تعلم أحكام التجويد واكتشف آيات القرآن الكريم وطوّر معارفك.",
        "discover" => "اكتشف القرآن الكريم",
        "daily" => "آية اليوم",
        "unavailable" => "آية اليوم غير متاحة مؤقتاً.",
        "footer" => "تعلم التجويد",
        "access_message" => "للوصول إلى منصتنا، يرجى تسجيل الدخول أو إنشاء حساب.",
        "account_title" => "ابدأ رحلة تعلم التجويد",
        "account_description" => "اكتشف القرآن الكريم وتعلم أحكام التلاوة وتقدم من خلال الدروس والتمارين.",
        "profile" => "حسابي"
    ]

];

$t = $texts[$lang];

// ======================================
// 4. FONCTION DE SECURITE
// ======================================

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// ======================================
// 5. PAGE PUBLIQUE POUR LES VISITEURS
// ======================================

if (!$isLoggedIn) {
?>

<!DOCTYPE html>
<html lang="<?= safeText($lang) ?>" dir="<?= $direction ?>">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tajwid Academy</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<header class="main-header">

    <div class="header-top">

        <div class="brand">

            <h1>تَعَلَّمِ التَّجْوِيدَ</h1>

            <h2>Tajwid Academy</h2>

            <p>Learn the Quran, improve your recitation</p>

        </div>

        <div class="language-selector">

            <a href="?lang=fr" class="<?= $lang === "fr" ? "active" : "" ?>">FR</a>

            <a href="?lang=ar" class="<?= $lang === "ar" ? "active" : "" ?>">AR</a>

            <a href="?lang=en" class="<?= $lang === "en" ? "active" : "" ?>">EN</a>

        </div>

    </div>

</header>


<main class="container public-home">

    <section class="welcome public-home-card">

        <h2><?= safeText($t["account_title"]) ?></h2>

        <p><?= safeText($t["account_description"]) ?></p>

        <p class="access-message">
            <?= safeText($t["access_message"]) ?>
        </p>

        <div class="public-home-actions">

            <a href="pages/login.php?lang=<?= safeText($lang) ?>" class="btn">

                <?= safeText($t["login"]) ?>

            </a>

            <a href="pages/register.php?lang=<?= safeText($lang) ?>" class="btn btn-register">

                <?= safeText($t["register"]) ?>

            </a>

        </div>

    </section>

</main>


<footer>

    <p>
        © <?= date("Y") ?> Tajwid Academy -
        <?= safeText($t["footer"]) ?>
    </p>

</footer>

</body>
</html>

<?php
exit;
}

// ======================================
// 6. VERSER DU JOUR POUR LES UTILISATEURS CONNECTES
// ======================================

$dailyResult = callUmmahAPI("/api/quran/random");

$dailyAyah = [];
$dailySurah = [];

if (is_array($dailyResult) && !empty($dailyResult["success"])) {

    $dailyAyah = $dailyResult["data"]["verse"] ?? [];

    $dailySurah = $dailyResult["data"]["surah"] ?? [];

}

// ======================================
// 7. TRADUCTION DU VERSET
// ======================================

$translation = "";

if ($lang === "fr") {

    $translation = $dailyAyah["translations"]["french"] ?? "";

} elseif ($lang === "en") {

    $translation = $dailyAyah["translations"]["sahih_international"] ?? "";

}

?>


<!DOCTYPE html>
<html lang="<?= safeText($lang) ?>" dir="<?= $direction ?>">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tajwid Academy</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>


<body>


<header class="main-header">

    <!-- IDENTITE DU SITE -->

    <div class="header-top">

        <div class="brand">

            <h1>تَعَلَّمِ التَّجْوِيدَ</h1>

            <h2>Tajwid Academy</h2>

            <p>Learn the Quran, improve your recitation</p>

        </div>


        <div class="language-selector">

            <a href="?lang=fr" class="<?= $lang === "fr" ? "active" : "" ?>">FR</a>

            <a href="?lang=ar" class="<?= $lang === "ar" ? "active" : "" ?>">AR</a>

            <a href="?lang=en" class="<?= $lang === "en" ? "active" : "" ?>">EN</a>

        </div>

    </div>


    <!-- NAVIGATION -->

    <nav class="main-nav">

        <a href="index.php?lang=<?= safeText($lang) ?>">
            <?= safeText($t["home"]) ?>
        </a>

        <a href="pages/quran.php?lang=<?= safeText($lang) ?>">
            <?= safeText($t["quran"]) ?>
        </a>

        <a href="pages/rules.php?lang=<?= safeText($lang) ?>">
            <?= safeText($t["rules"]) ?>
        </a>

        <a href="pages/courses.php?lang=<?= safeText($lang) ?>">
            <?= safeText($t["courses"]) ?>
        </a>

        <a href="pages/quizzes.php?lang=<?= safeText($lang) ?>">
            <?= $lang === "fr" ? "Quiz" : ($lang === "ar" ? "الاختبارات" : "Quizzes") ?>
        </a>

        <a href="pages/exercises.php?lang=<?= safeText($lang) ?>">
            <?= $lang === "fr" ? "Exercices" : ($lang === "ar" ? "التمارين" : "Exercises") ?>
        </a>

        <a href="pages/dashboard.php?lang=<?= safeText($lang) ?>">
            <?= safeText($t["dashboard"]) ?>
        </a>

        <a href="pages/logout.php">
            <?= safeText($t["logout"]) ?>
        </a>

    </nav>


    <!-- UTILISATEUR CONNECTE -->

    <div class="logged-user-bar">

        <span class="user-icon">✦</span>

        <span class="user-label">
            <?= safeText($t["welcome"]) ?>
        </span>

        <strong class="user-name">
            <?= safeText($userName) ?>
        </strong>

    </div>


</header>


<main class="container">


    <!-- PRESENTATION -->

    <section class="welcome">

        <h2><?= safeText($t["title"]) ?></h2>

        <p><?= safeText($t["description"]) ?></p>

        <a href="pages/quran.php?lang=<?= safeText($lang) ?>" class="btn">

            <?= safeText($t["discover"]) ?>

        </a>

    </section>


    <!-- VERSET DU JOUR -->

    <section class="daily-section">

        <h2>📖 <?= safeText($t["daily"]) ?></h2>


        <?php if (!empty($dailyAyah["arabic"])): ?>

            <p class="daily-verse" dir="rtl">

                <?= safeText($dailyAyah["arabic"]) ?>

            </p>


            <?php if ($translation !== ""): ?>

                <p class="daily-translation">

                    <?= safeText($translation) ?>

                </p>

            <?php endif; ?>


            <p class="verse-reference">

                <?= safeText($dailySurah["name_arabic"] ?? "") ?>

                - <?= safeText($dailySurah["name_english"] ?? "") ?>

                (<?= (int)($dailySurah["number"] ?? 0) ?> :

                <?= (int)($dailyAyah["ayah"] ?? 0) ?>)

            </p>


        <?php else: ?>

            <p><?= safeText($t["unavailable"]) ?></p>

        <?php endif; ?>


    </section>


</main>


<footer>

    <p>
        © <?= date("Y") ?> Tajwid Academy -
        <?= safeText($t["footer"]) ?>
    </p>

</footer>


</body>
</html>
