
<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once "../config/api.php";

// Langue sélectionnée
$lang = $_GET["lang"] ?? "fr";

if (!in_array($lang, ["fr", "ar", "en"], true)) {
    $lang = "fr";
}

// Traductions
$texts = [
    "fr" => [
        "title" => "Le Saint Coran",
        "heading" => "Les 114 sourates du Coran",
        "home" => "Accueil",
        "verses" => "versets",
        "empty" => "Aucune sourate reçue.",
        "error" => "Erreur lors du chargement des sourates."
    ],

    "en" => [
        "title" => "The Holy Quran",
        "heading" => "The 114 Surahs of the Quran",
        "home" => "Home",
        "verses" => "verses",
        "empty" => "No Surahs received.",
        "error" => "Error while loading the Surahs."
    ],

    "ar" => [
        "title" => "القرآن الكريم",
        "heading" => "سور القرآن الكريم الـ 114",
        "home" => "الرئيسية",
        "verses" => "آية",
        "empty" => "لم يتم العثور على أي سورة.",
        "error" => "حدث خطأ أثناء تحميل السور."
    ]
];

$t = $texts[$lang];

$direction = ($lang === "ar") ? "rtl" : "ltr";

// Récupérer les sourates depuis UmmahAPI
$result = callUmmahAPI("/api/quran/surahs");

$surahs = [];

if (is_array($result) && !empty($result["success"])) {

    $data = $result["data"] ?? [];

    if (isset($data["surahs"])) {
        $surahs = $data["surahs"];

    } elseif (isset($data["data"]["surahs"])) {
        $surahs = $data["data"]["surahs"];

    } elseif (is_array($data) && array_is_list($data)) {
        $surahs = $data;
    }
}

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

?>

<!DOCTYPE html>
<html lang="<?= safe($lang) ?>" dir="<?= $direction ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= safe($t["title"]) ?> - Tajwid Academy</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .quran-languages {
            text-align: center;
            margin: 20px;
        }

        .quran-languages a {
            margin: 0 10px;
            color: #176b50;
            text-decoration: none;
            font-weight: bold;
        }

        .quran-heading {
            text-align: center;
            margin: 30px 0;
        }

        .surah-card {
            text-align: center;
        }

        html[dir="rtl"] .surah-card {
            direction: rtl;
        }
    </style>

</head>

<body>

<header class="main-header">

    <div class="header-top">

        <div class="brand">
            <h1>تَعَلَّمِ التَّجْوِيدَ</h1>
            <h2>Tajwid Academy</h2>
            <p><?= safe($t["title"]) ?></p>
        </div>

        <div class="language-selector">
            <a href="?lang=fr">FR</a>
            <a href="?lang=ar">AR</a>
            <a href="?lang=en">EN</a>
        </div>

    </div>

    <nav class="main-nav">

        <a href="../index.php?lang=<?= safe($lang) ?>">
            <?= safe($t["home"]) ?>
        </a>

    </nav>

</header>

<main class="container">

    <h2 class="quran-heading">
        <?= safe($t["heading"]) ?>
    </h2>

    <?php if (!is_array($result) || empty($result["success"])): ?>

        <p class="error">
            <?= safe($result["error"] ?? $t["error"]) ?>
        </p>

    <?php elseif (empty($surahs)): ?>

        <p><?= safe($t["empty"]) ?></p>

    <?php else: ?>

        <div class="surah-grid">

            <?php foreach ($surahs as $surah): ?>

                <?php

                $number = $surah["number"]
                    ?? $surah["id"]
                    ?? null;

                $arabicName = $surah["name_arabic"]
                    ?? $surah["name_ar"]
                    ?? $surah["name"]
                    ?? "سورة";

                $englishName = $surah["name_english"]
                    ?? $surah["english_name"]
                    ?? $surah["englishName"]
                    ?? "";

                $frenchName = $surah["name_translation"]
                    ?? $surah["name_french"]
                    ?? $englishName;

                $verses = $surah["verses_count"]
                    ?? $surah["ayah_count"]
                    ?? $surah["number_of_ayahs"]
                    ?? "";

                if ($lang === "ar") {
                    $displayName = $arabicName;
                } elseif ($lang === "en") {
                    $displayName = $englishName ?: $arabicName;
                } else {
                    $displayName = $frenchName ?: $arabicName;
                }

                ?>

                <?php if ($number !== null): ?>

                    <a class="surah-card"
                       href="surah.php?number=<?= (int)$number ?>&lang=<?= safe($lang) ?>">

                        <span class="surah-number">
                            <?= (int)$number ?>
                        </span>

                        <h3><?= safe($displayName) ?></h3>

                        <?php if ($lang !== "ar" && $englishName !== ""): ?>

                            <p><?= safe($englishName) ?></p>

                        <?php endif; ?>

                        <small>
                            <?= safe($verses) ?>
                            <?= safe($t["verses"]) ?>
                        </small>

                    </a>

                <?php endif; ?>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

</body>
</html>
