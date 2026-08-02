<?php
/**
 * TinyMCE rich text szerkesztő - közös inicializálás
 *
 * A hír létrehozó és szerkesztő nézet is ezt tölti be, így a szerkesztő
 * beállításai (nyelv, eszköztár, tipográfia) egy helyen módosíthatók.
 *
 * A szerkesztő tartalmi stílusa szándékosan megegyezik a publikus oldal
 * .article-body stílusával, hogy a szerkesztés közbeni kép megfeleljen a
 * végleges megjelenésnek.
 *
 * Requirement 2.4: félkövér, dőlt, felsorolás és hivatkozás formázás.
 */
$tinymceApiKey = 'dg1pun9r315oclucr8p9k2vbr2diads6ekliyw49ihzw0450';
?>
<script src="https://cdn.tiny.cloud/1/<?= e($tinymceApiKey) ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#content',
        language: 'hu_HU',
        menubar: false,
        statusbar: false,
        branding: false,
        height: 460,
        plugins: 'lists link autolink table charmap searchreplace visualblocks wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link blockquote | removeformat',
        block_formats: 'Bekezdés=p; Alcím=h2; Kisebb alcím=h3',
        // A szerkesztő felületének illesztése az oldal design tokenjeihez
        content_style: `
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
            body {
                font-family: Inter, ui-sans-serif, system-ui, sans-serif;
                font-size: 17px;
                line-height: 1.75;
                color: #524b40;
                max-width: 44rem;
                margin: 1.25rem auto;
                padding: 0 1rem;
            }
            h2, h3 { color: #0c2f22; font-weight: 600; line-height: 1.3; }
            h2 { font-size: 1.5rem; margin-top: 2em; }
            h3 { font-size: 1.25rem; margin-top: 2em; }
            a { color: #1a7a53; text-underline-offset: 3px; }
            strong { color: #1f1c18; }
            blockquote {
                padding-left: 1.25rem;
                border-left: 3px solid #eccb70;
                font-style: italic;
                margin-left: 0;
            }
            ul, ol { padding-left: 1.5rem; }
            li::marker { color: #d99a26; }
        `
    });
</script>
