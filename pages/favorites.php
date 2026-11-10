
<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/api.php";

// ======================================
// 1. VERIFIER LA CONNEXION
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

// Traduction API selon la langue
$apiTranslation = ($lang === "en")
    ? "sahih_international"
    : "french";

// ======================================
// 3. TRADUCTIONS
// ======================================

$texts = [
    "fr" => [
        "title" => "Mes favoris",
        "subtitle" => "Retrouve ici les versets que tu as enregistrés.",
        "empty" => "Vous n'avez pas encore de favoris.",
        "back" => "Tableau de bord",
        "remove" => "Retirer des favoris",
        "surah" => "Sourate",
        "verse" => "Verset",
        "discover" => "Découvrir le Coran",
        "unavailable" => "Verset indisponible pour le moment.",
        "home" => "Accueil",
        "saved" => "Tes versets préférés",
        "count" => "verset(s) enregistré(s)"
    ],

    "en" => [
        "title" => "My Favorites",
        "subtitle" => "Find the verses you have saved here.",
        "empty" => "You don't have any favorites yet.",
        "back" => "Dashboard",
        "remove" => "Remove from favorites",
        "surah" => "Surah",
        "verse" => "Verse",
        "discover" => "Explore the Quran",
        "unavailable" => "Verse currently unavailable.",
        "home" => "Home",
        "saved" => "Your favorite verses",
        "count" => "saved verse(s)"
    ],

    "ar" => [
        "title" => "مفضلتي",
        "subtitle" => "ستجد هنا الآيات التي قمت بحفظها.",
        "empty" => "لا توجد آيات في المفضلة.",
        "back" => "لوحة التحكم",
        "remove" => "إزالة من المفضلة",
        "surah" => "السورة",
        "verse" => "الآية",
        "discover" => "اكتشف القرآن الكريم",
        "unavailable" => "الآية غير متاحة حالياً.",
        "home" => "الرئيسية",
        "saved" => "آياتك المفضلة",
        "count" => "آية محفوظة"
    ]
];

$t = $texts[$lang];

// ======================================
// 4. PROTECTION CSRF
// ======================================

if (empty($_SESSION["csrf_favorites"])) {
    $_SESSION["csrf_favorites"] = bin2hex(random_bytes(32));
}

// ======================================
// 5. SUPPRIMER UN FAVORI
// ======================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals(
            $_SESSION["csrf_favorites"],
            $_POST["csrf_token"]
        )
    ) {
        die("Requête invalide.");
    }

    $favoriteId = (int) ($_POST["favorite_id"] ?? 0);

    $stmt = $pdo->prepare(
        "DELETE FROM favorites
         WHERE id = ? AND user_id = ?"
    );

    $stmt->execute([$favoriteId, $userId]);

    header("Location: favorites.php?lang=" . urlencode($lang));
    exit;
}

// ======================================
// 6. RECUPERER LES FAVORIS
// ======================================

$stmt = $pdo->prepare(
    "SELECT *
     FROM favorites
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->execute([$userId]);

$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================================
// 7. RECUPERER LES VERSETS DE L'API
// ======================================

$versesBySurah = [];

foreach ($favorites as $favorite) {

    $surahNumber = (int) $favorite["surah_number"];

    // Ne récupérer chaque sourate qu'une seule fois
    if (!isset($versesBySurah[$surahNumber])) {

        $result = callUmmahAPI(
            "/api/quran/surah/" . $surahNumber .
            "?script=uthmani&translation=" . $apiTranslation
        );

        if ($result && !empty($result["success"])) {

            $versesBySurah[$surahNumber] =
                $result["data"]["verses"] ?? [];

        } else {

            $versesBySurah[$surahNumber] = [];
        }
    }
}

// ======================================
// 8. FONCTION DE SECURITE
// ======================================

function safeText($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="<?= safeText($lang) ?>" dir="<?= $direction ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= safeText($t["title"]) ?> - Tajwid Academy
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="favorites-page">

<!-- ======================================
     HEADER
====================================== -->

<header class="main-header">

    <div class="header-top">

        <div class="brand">

            <h1>تَعَلَّمِ التَّجْوِيدَ</h1>

            <h2>Tajwid Academy</h2>

            <p><?= safeText($t["subtitle"]) ?></p>

        </div>

        <div class="language-selector">

            <a href="favorites.php?lang=fr"
               class="<?= $lang === 'fr' ? 'active' : '' ?>">
                FR
            </a>

            <a href="favorites.php?lang=ar"
               class="<?= $lang === 'ar' ? 'active' : '' ?>">
                AR
            </a>

            <a href="favorites.php?lang=en"
               class="<?= $lang === 'en' ? 'active' : '' ?>">
                EN
            </a>

        </div>

    </div>

    <nav class="main-nav">

        <a href="../index.php?lang=<?= urlencode($lang) ?>">
            🏠 <?= safeText($t["home"]) ?>
        </a>

        <a href="dashboard.php?lang=<?= urlencode($lang) ?>">
            📊 <?= safeText($t["back"]) ?>
        </a>

    </nav>

</header>

<!-- ======================================
     CONTENU PRINCIPAL
====================================== -->

<main class="favorites-container">

    <section class="favorites-heading">

        <span class="favorites-label">
            TAJWID ACADEMY
        </span>

        <h1>
            ❤️ <?= safeText($t["title"]) ?>
        </h1>

        <p>
            <?= safeText($t["subtitle"]) ?>
        </p>

        <div class="favorites-decoration">♡</div>

    </section>

    <!-- Titre et nombre de favoris -->

    <div class="favorites-section-heading">

        <h2>
            ✧ <?= safeText($t["saved"]) ?>
        </h2>

        <span class="favorites-count">

            <?= count($favorites) ?>

            <?= safeText($t["count"]) ?>

        </span>

    </div>

    <!-- ======================================
         SI AUCUN FAVORI
    ====================================== -->

    <?php if (empty($favorites)): ?>

        <section class="favorites-empty">

            <div class="empty-favorite-icon">
                🤍
            </div>

            <h2>
                <?= safeText($t["empty"]) ?>
            </h2>

            <a href="quran.php?lang=<?= urlencode($lang) ?>"
               class="discover-button">

                📖 <?= safeText($t["discover"]) ?>

            </a>

        </section>

    <?php else: ?>

        <!-- ======================================
             AFFICHER LES FAVORIS
        ====================================== -->

        <div class="favorites-list">

            <?php foreach ($favorites as $favorite): ?>

                <?php

                $surahNumber = (int) $favorite["surah_number"];

                $ayahNumber = (int) $favorite["ayah_number"];

                $verseData = null;

                foreach (
                    $versesBySurah[$surahNumber] ?? []
                    as $verse
                ) {

                    if (
                        (int) ($verse["ayah"] ?? 0) === $ayahNumber
                    ) {

                        $verseData = $verse;

                        break;
                    }
                }

                ?>

                <article class="favorite-card">

                    <div class="favorite-card-heading">

                        <div class="favorite-number">

                            <?= $surahNumber ?> : <?= $ayahNumber ?>

                        </div>

                        <span class="favorite-heart">
                            ♥
                        </span>

                    </div>

                    <div class="favorite-reference">

                        <span>
                            <?= safeText($t["surah"]) ?>
                            <?= $surahNumber ?>
                        </span>

                        <span class="reference-separator">
                            •
                        </span>

                        <span>
                            <?= safeText($t["verse"]) ?>
                            <?= $ayahNumber ?>
                        </span>

                    </div>

                    <!-- Verset arabe -->

                    <?php if ($verseData): ?>

                        <div class="favorite-arabic">

                            <?= safeText($verseData["arabic"] ?? "") ?>

                        </div>

                        <!-- Traduction française ou anglaise -->

                        <?php if (
                            $lang !== "ar" &&
                            !empty($verseData["translation"])
                        ): ?>

                            <div class="favorite-translation">

                                <?= safeText($verseData["translation"]) ?>

                            </div>

                        <?php endif; ?>

                        <!-- Translittération si disponible -->

                        <?php if (
                            !empty($verseData["transliteration"])
                        ): ?>

                            <div class="favorite-transliteration">

                                <?= safeText($verseData["transliteration"]) ?>

                            </div>

                        <?php endif; ?>

                    <?php else: ?>

                        <p class="favorite-unavailable">

                            <?= safeText($t["unavailable"]) ?>

                        </p>

                    <?php endif; ?>

                    <!-- Supprimer le favori -->

                    <div class="favorite-actions">

                        <form method="POST">

                            <input
                                type="hidden"
                                name="favorite_id"
                                value="<?= (int) $favorite["id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= safeText($_SESSION["csrf_favorites"]) ?>"
                            >

                            <button
                                type="submit"
                                class="remove-button"
                            >

                                <span>♥</span>

                                <?= safeText($t["remove"]) ?>

                            </button>

                        </form>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <!-- Retour au tableau de bord -->

    <div class="favorites-back">

        <a href="dashboard.php?lang=<?= urlencode($lang) ?>">

            ← <?= safeText($t["back"]) ?>

        </a>

    </div>

</main>

<!-- ======================================
     FOOTER
====================================== -->

<footer class="main-footer">

    <p>
        © <?= date("Y") ?> Tajwid Academy
        — Learn with knowledge and serenity.
    </p>

</footer>

</body>
</html>
