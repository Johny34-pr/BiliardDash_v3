/**
 * Okányi Biliárd Klub - Blogszerkesztő (TinyMCE)
 *
 * A beállítást a szerkesztő elem `data-*` attribútumaiból veszi, így a
 * végpontok és korlátok PHP oldalon, egy helyen módosíthatók.
 *
 * Funkciók:
 *   - Kép beszúrása húzd-és-vidd módszerrel, vágólapról beillesztve,
 *     vagy fájlválasztóból (azonnali feltöltéssel)
 *   - Dokumentum csatolása hivatkozásként (PDF, DOCX, XLSX stb.)
 *   - Médiakönyvtár: korábbi feltöltések újra beszúrhatók
 *   - Belső hivatkozás-választó: aloldalak, versenyek nevezési űrlapja,
 *     hírek, albumok és fórum topikok listából
 */

(function () {
    'use strict';

    var textarea = document.getElementById('content');
    if (!textarea || typeof tinymce === 'undefined') {
        return;
    }

    var config = {
        imageUrl: textarea.getAttribute('data-upload-image'),
        documentUrl: textarea.getAttribute('data-upload-document'),
        libraryUrl: textarea.getAttribute('data-media-library'),
        linkListUrl: textarea.getAttribute('data-link-list'),
        maxImageMb: parseInt(textarea.getAttribute('data-max-image-mb'), 10) || 10,
        maxDocumentMb: parseInt(textarea.getAttribute('data-max-document-mb'), 10) || 20
    };

    /**
     * Fájl feltöltése a megadott végpontra.
     *
     * @returns {Promise<Object>} A kiszolgáló JSON válasza
     */
    function uploadFile(url, file) {
        var data = new FormData();
        data.append('file', file, file.name);

        return fetch(url, {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json()
                .catch(function () {
                    throw new Error('A kiszolgáló váratlan választ adott.');
                })
                .then(function (payload) {
                    if (!response.ok) {
                        throw new Error(payload.message || 'A feltöltés nem sikerült.');
                    }
                    return payload;
                });
        });
    }

    /**
     * Rejtett fájlválasztó megnyitása.
     *
     * @param {string} accept Az input accept attribútuma
     * @param {Function} onPick A kiválasztott fájlt megkapó függvény
     */
    function pickFile(accept, onPick) {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = accept;
        input.style.display = 'none';

        input.addEventListener('change', function () {
            if (input.files && input.files[0]) {
                onPick(input.files[0]);
            }
            input.remove();
        });

        document.body.appendChild(input);
        input.click();
    }

    /** Emberi méretjelölés */
    function formatSize(bytes) {
        if (bytes >= 1048576) {
            return (Math.round(bytes / 104857.6) / 10) + ' MB';
        }
        if (bytes >= 1024) {
            return Math.round(bytes / 1024) + ' kB';
        }
        return bytes + ' B';
    }

    /**
     * Médiakönyvtár párbeszédpanel: korábbi feltöltések beszúrása.
     *
     * A képek rácsban, a dokumentumok listában jelennek meg. A kiválasztott
     * elem a kurzor pozíciójába kerül: kép esetén <img>, dokumentumnál
     * letölthető hivatkozásként.
     */
    function openMediaLibrary(editor) {
        fetch(config.libraryUrl, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (payload) {
                var items = payload.items || [];

                if (items.length === 0) {
                    editor.windowManager.alert(
                        'Még nincs feltöltött média. Húzz be egy képet a szerkesztőbe, ' +
                        'vagy használd a Kép, illetve a Csatolmány gombot.'
                    );
                    return;
                }

                var images = items.filter(function (i) { return i.kind === 'image'; });
                var documents = items.filter(function (i) { return i.kind === 'document'; });

                var html = '<div class="mce-media-library">';

                if (images.length) {
                    html += '<p class="mce-media-title">Képek</p><div class="mce-media-grid">';
                    images.forEach(function (item) {
                        html += '<button type="button" class="mce-media-tile" ' +
                            'data-kind="image" data-url="' + item.url + '" ' +
                            'title="' + item.filename + ' &middot; ' + item.sizeLabel + '">' +
                            '<img src="' + item.url + '" alt="">' +
                            '</button>';
                    });
                    html += '</div>';
                }

                if (documents.length) {
                    html += '<p class="mce-media-title">Dokumentumok</p><div class="mce-media-list">';
                    documents.forEach(function (item) {
                        html += '<button type="button" class="mce-media-row" ' +
                            'data-kind="document" data-url="' + item.url + '" ' +
                            'data-name="' + item.filename + '">' +
                            '<span class="mce-media-name">' + item.filename + '</span>' +
                            '<span class="mce-media-size">' + item.sizeLabel + '</span>' +
                            '</button>';
                    });
                    html += '</div>';
                }

                html += '</div>';

                var dialog = editor.windowManager.open({
                    title: 'Médiakönyvtár',
                    size: 'large',
                    body: { type: 'panel', items: [{ type: 'htmlpanel', html: html }] },
                    buttons: [{ type: 'cancel', text: 'Mégse' }]
                });

                // A htmlpanel gombjaira az eseményt közvetlenül kötjük be
                setTimeout(function () {
                    document.querySelectorAll('.mce-media-tile, .mce-media-row').forEach(function (el) {
                        el.addEventListener('click', function () {
                            var url = el.getAttribute('data-url');

                            if (el.getAttribute('data-kind') === 'image') {
                                editor.insertContent('<img src="' + url + '" alt="">');
                            } else {
                                insertAttachment(editor, url, el.getAttribute('data-name'));
                            }

                            dialog.close();
                        });
                    });
                }, 50);
            })
            .catch(function () {
                editor.windowManager.alert('A médiakönyvtár nem tölthető be.');
            });
    }

    /**
     * Csatolmány beszúrása letölthető hivatkozásként.
     *
     * A `.attachment` osztályt a publikus oldal stílusa emeli ki, hogy
     * a csatolmány ne olvadjon össze a szöveg közbeni hivatkozásokkal.
     */
    function insertAttachment(editor, url, label) {
        var text = (label || 'Csatolmány').replace(/</g, '&lt;');

        editor.insertContent(
            '<p><a class="attachment" href="' + url + '" download>' + text + '</a></p>'
        );
    }

    tinymce.init({
        selector: '#content',
        language: 'hu_HU',
        menubar: false,
        statusbar: true,
        branding: false,
        elementpath: false,

        plugins: 'lists link image table charmap searchreplace visualblocks wordcount ' +
                 'autoresize autolink media codesample fullscreen quickbars preview',

        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | ' +
                 'link internalLink | image mediaLibrary attachFile | ' +
                 'blockquote table hr | alignleft aligncenter | removeformat fullscreen preview',

        block_formats: 'Bekezdés=p; Alcím=h2; Kisebb alcím=h3; Idézet=blockquote',

        /* Kijelölésre felugró rövid eszköztár - blogoláshoz kényelmes */
        quickbars_selection_toolbar: 'bold italic | h2 h3 | quicklink blockquote',
        quickbars_insert_toolbar: 'image mediaLibrary table hr',

        /* Ékezetek valódi UTF-8 karakterként mentődnek, ne entitásként */
        entity_encoding: 'raw',

        /* Magasság a tartalomhoz igazodik */
        min_height: 520,
        max_height: 1000,
        autoresize_bottom_margin: 24,

        /* ---------------- Képfeltöltés ---------------- */

        /* Húzd-és-vidd, illetve vágólapról beillesztett kép azonnal feltöltődik */
        automatic_uploads: true,
        paste_data_images: true,
        images_upload_url: config.imageUrl,
        images_upload_credentials: true,
        images_reuse_filename: false,

        /*
         * Saját feltöltéskezelő: a hibát a kiszolgáló üzenetével adja vissza,
         * így a szerkesztő nem általános hibát mutat.
         */
        images_upload_handler: function (blobInfo, progress) {
            return uploadFile(config.imageUrl, new File(
                [blobInfo.blob()],
                blobInfo.filename(),
                { type: blobInfo.blob().type }
            )).then(function (payload) {
                progress(100);
                return payload.location;
            });
        },

        /* Fájlválasztó a Kép és a Média párbeszédpanelen */
        file_picker_types: 'image media',
        file_picker_callback: function (callback, value, meta) {
            var isImage = meta.filetype === 'image';
            var accept = isImage
                ? 'image/jpeg,image/png,image/gif,image/webp'
                : 'video/mp4,video/webm,audio/mpeg';

            pickFile(accept, function (file) {
                uploadFile(config.imageUrl, file)
                    .then(function (payload) {
                        callback(payload.location, { alt: file.name });
                    })
                    .catch(function (error) {
                        tinymce.activeEditor.windowManager.alert(error.message);
                    });
            });
        },

        /* ---------------- Belső hivatkozások ---------------- */

        /*
         * A hivatkozás párbeszédpanel legördülőjét a kiszolgáló tölti fel:
         * aloldalak, versenyek nevezési űrlapja, hírek, albumok, topikok.
         */
        link_list: config.linkListUrl,
        link_title: false,
        link_default_target: null,
        /* Belső hivatkozásoknál relatív útvonal maradjon */
        relative_urls: false,
        remove_script_host: true,
        convert_urls: true,

        /* ---------------- Egyedi eszköztár gombok ---------------- */

        setup: function (editor) {
            /* Csatolmány feltöltése és beszúrása */
            editor.ui.registry.addButton('attachFile', {
                icon: 'new-document',
                tooltip: 'Csatolmány feltöltése (PDF, DOCX, XLSX…)',
                onAction: function () {
                    pickFile(
                        '.pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.txt,.csv',
                        function (file) {
                            uploadFile(config.documentUrl, file)
                                .then(function (payload) {
                                    var label = (payload.originalName || file.name) +
                                        ' (' + (payload.sizeLabel || formatSize(file.size)) + ')';
                                    insertAttachment(editor, payload.location, label);
                                })
                                .catch(function (error) {
                                    editor.windowManager.alert(error.message);
                                });
                        }
                    );
                }
            });

            /* Médiakönyvtár: korábbi feltöltések */
            editor.ui.registry.addButton('mediaLibrary', {
                icon: 'gallery',
                tooltip: 'Médiakönyvtár (korábbi feltöltések)',
                onAction: function () {
                    openMediaLibrary(editor);
                }
            });

            /* Belső hivatkozás gyors beszúrása a hivatkozás panelen keresztül */
            editor.ui.registry.addButton('internalLink', {
                icon: 'code-sample',
                tooltip: 'Belső hivatkozás (aloldal, verseny, hír)',
                onAction: function () {
                    editor.execCommand('mceLink');
                }
            });
        },

        /* ---------------- Megjelenés ---------------- */

        /* A szerkesztő tartalma a publikus .article-body stílust tükrözi */
        content_style: [
            "@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');",
            'body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; font-size: 17px;',
            '  line-height: 1.75; color: #524b40; margin: 1.25rem 1.5rem; padding: 0; }',
            'body > *:first-child { margin-top: 0; }',
            'h2, h3 { color: #0c2f22; font-weight: 600; line-height: 1.3; }',
            'h2 { font-size: 1.5rem; margin-top: 2em; }',
            'h3 { font-size: 1.25rem; margin-top: 2em; }',
            'a { color: #1a7a53; text-underline-offset: 3px; }',
            'strong { color: #1f1c18; }',
            'blockquote { padding-left: 1.25rem; border-left: 3px solid #eccb70;',
            '  font-style: italic; margin-left: 0; }',
            'ul, ol { padding-left: 1.5rem; }',
            'li::marker { color: #d99a26; }',
            'img { max-width: 100%; height: auto; border-radius: 0.875rem; }',
            'figure { margin: 1.5em 0; }',
            'figcaption { font-size: 0.875rem; color: #8c8272; text-align: center; margin-top: 0.5rem; }',
            'hr { border: 0; border-top: 1px solid #e9e5dd; margin: 2em 0; }',
            'table { border-collapse: collapse; width: 100%; }',
            'table td, table th { border: 1px solid #e9e5dd; padding: 0.5rem 0.75rem; }',
            /* Csatolmány kiemelése már a szerkesztőben is */
            'a.attachment { display: inline-flex; align-items: center; gap: 0.5rem;',
            '  padding: 0.6rem 0.9rem; border: 1px solid #d7d1c5; border-radius: 0.75rem;',
            '  background: #f5f3ef; color: #154d38; font-weight: 600; text-decoration: none; }',
            'a.attachment::before { content: "\\1F4C4"; }'
        ].join('\n'),

        /* A csatolmány osztálya és a download attribútum ne essen ki tisztításnál */
        extended_valid_elements: 'a[href|class|download|target|rel|title]',
        valid_children: '+body[style]'
    });
})();
