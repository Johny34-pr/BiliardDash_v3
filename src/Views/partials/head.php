<?php
/**
 * Közös <head> tartalom - Magyar Biliárd Weboldal
 *
 * Egy helyen definiálja a design tokeneket (színek, tipográfia, árnyékok),
 * hogy a fő layout, az admin layout és a hibaoldalak konzisztensek legyenek.
 *
 * @var string|null $pageTitle Oldal címe
 * @var string|null $metaDescription Opcionális leírás
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0c2f22">
<meta name="description" content="<?= e($metaDescription ?? 'A magyar biliárd közösség hírei, fotógalériája és online versenynevezés.') ?>">
<title><?= e($pageTitle ?? 'Magyar Biliárd') ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    /* Biliárdposztó zöld - hűvös, mély, telt tónusskála */
                    'billiard-green': {
                        50:  '#f0faf5',
                        100: '#daf3e5',
                        200: '#b7e6cd',
                        300: '#86d1ac',
                        400: '#4eb586',
                        500: '#299868',
                        600: '#1a7a53',
                        700: '#166145',
                        800: '#154d38',
                        900: '#0c2f22',
                        950: '#061a13',
                    },
                    /* Meleg sárgaréz arany - akcentus szín */
                    'billiard-gold': {
                        50:  '#fdfaef',
                        100: '#faf1d3',
                        200: '#f4e0a4',
                        300: '#eccb70',
                        400: '#e5b544',
                        500: '#d99a26',
                        600: '#bf761d',
                        700: '#9e561b',
                        800: '#81441c',
                        900: '#6b391a',
                    },
                    /* Meleg semleges alapszínek - kevésbé steril, mint a szürke */
                    'sand': {
                        50:  '#fbfaf8',
                        100: '#f5f3ef',
                        200: '#e9e5dd',
                        300: '#d7d1c5',
                        400: '#b3aa9a',
                        500: '#8c8272',
                        600: '#6b6254',
                        700: '#524b40',
                        800: '#38332c',
                        900: '#1f1c18',
                    },
                },
                fontFamily: {
                    sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Arial', 'sans-serif'],
                },
                letterSpacing: {
                    tightest: '-0.035em',
                },
                boxShadow: {
                    'soft':  '0 1px 2px rgba(6,26,19,.04), 0 4px 16px -6px rgba(6,26,19,.08)',
                    'lift':  '0 2px 4px rgba(6,26,19,.04), 0 16px 32px -12px rgba(6,26,19,.16)',
                    'inset-line': 'inset 0 1px 0 rgba(255,255,255,.06)',
                },
                borderRadius: {
                    '4xl': '1.75rem',
                },
                maxWidth: {
                    'reading': '44rem',
                },
                transitionTimingFunction: {
                    'out-soft': 'cubic-bezier(0.22, 1, 0.36, 1)',
                },
            }
        }
    }
</script>

<link rel="stylesheet" href="/assets/css/app.css">
