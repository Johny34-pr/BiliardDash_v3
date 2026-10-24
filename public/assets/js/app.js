/**
 * Okányi Biliárd Klub weboldal - Általános JavaScript
 * Hamburger menü kezelés, form validáció, törlés megerősítés
 */

document.addEventListener('DOMContentLoaded', function() {
    // === Hamburger menü toggle ===
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function() {
            const isHidden = mobileMenu.classList.contains('hidden');
            mobileMenu.classList.toggle('hidden');
            menuToggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });

        // Menü bezárása ha kívül kattintanak
        document.addEventListener('click', function(event) {
            if (!menuToggle.contains(event.target) && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.add('hidden');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Escape billentyűvel bezárás
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.add('hidden');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.focus();
            }
        });
    }

    // === Törlés megerősítő dialógusok (admin felület) ===
    // Globális kezelő: minden form[data-confirm] attribútummal rendelkező űrlapnál
    document.querySelectorAll('form[data-confirm]').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            var message = form.getAttribute('data-confirm') || 'Biztosan törölni szeretnéd?';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Globális kezelő: törlés gombok/linkek data-confirm attribútummal
    document.querySelectorAll('[data-confirm]:not(form)').forEach(function(element) {
        element.addEventListener('click', function(event) {
            var message = element.getAttribute('data-confirm') || 'Biztosan törölni szeretnéd?';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // === Fiókmenü (fejléc lenyíló) ===
    initAccountMenu();

    // === Fórum: emoji beszúrás és karakterszámláló ===
    initCommentForm();

    // === Jelszó mezők láthatóvá tétele ===
    initPasswordToggles();

    // === Nyomtatás gombok ===
    initPrintButtons();

    // === Cikk megosztása ===
    initShare();

    // === Kliens-oldali form validáció bekötése ===
    // Csak a data-validate attribútummal ellátott formokra
    document.querySelectorAll('form[data-validate]').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            var errors = validateForm(form);
            if (errors.length > 0) {
                event.preventDefault();
                displayFormErrors(form, errors);
            }
        });

        // Élő validáció: hibajelzés eltávolítása gépeléskor
        form.querySelectorAll('input, textarea, select').forEach(function(field) {
            field.addEventListener('input', function() {
                field.classList.remove('border-red-500');
                var errorEl = form.querySelector('[data-error-for="' + field.name + '"]');
                if (errorEl) {
                    errorEl.remove();
                }
            });
        });
    });
});

/**
 * Kliens-oldali form validáció (kiegészítő, szerver-oldali a fő)
 * @param {HTMLFormElement} form
 * @returns {Array} Hibák tömbje
 */
function validateForm(form) {
    var errors = [];
    var required = form.querySelectorAll('[required]');

    required.forEach(function(field) {
        if (!field.value.trim()) {
            errors.push({ field: field.name, message: 'Ez a mező kötelező' });
            field.classList.add('border-red-500');
        } else {
            field.classList.remove('border-red-500');
        }
    });

    // E-mail validáció
    var emailField = form.querySelector('[type="email"]');
    if (emailField && emailField.value && !emailField.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        errors.push({ field: emailField.name, message: 'Érvénytelen e-mail formátum' });
        emailField.classList.add('border-red-500');
    }

    // Maximális hossz validáció
    form.querySelectorAll('[maxlength]').forEach(function(field) {
        var maxLen = parseInt(field.getAttribute('maxlength'), 10);
        if (field.value.length > maxLen) {
            errors.push({ field: field.name, message: 'Maximum ' + maxLen + ' karakter engedélyezett' });
            field.classList.add('border-red-500');
        }
    });

    return errors;
}

/**
 * Form hibák megjelenítése a megfelelő mezők mellett
 * @param {HTMLFormElement} form
 * @param {Array} errors
 */
function displayFormErrors(form, errors) {
    // Előző hibaüzenetek eltávolítása
    form.querySelectorAll('.js-field-error').forEach(function(el) {
        el.remove();
    });

    errors.forEach(function(error) {
        var field = form.querySelector('[name="' + error.field + '"]');
        if (field) {
            var errorEl = document.createElement('p');
            errorEl.className = 'js-field-error text-red-600 text-sm mt-1';
            errorEl.setAttribute('data-error-for', error.field);
            errorEl.setAttribute('role', 'alert');
            errorEl.textContent = error.message;
            field.parentNode.insertBefore(errorEl, field.nextSibling);
        }
    });

    // Fókuszálás az első hibás mezőre
    if (errors.length > 0) {
        var firstField = form.querySelector('[name="' + errors[0].field + '"]');
        if (firstField) {
            firstField.focus();
        }
    }
}

/**
 * Fórum hozzászólás űrlap: emoji beszúrás és karakterszámláló.
 *
 * Az emoji a kurzor pozíciójába kerül, nem a szöveg végére. A számláló
 * a maxlength attribútumból veszi a felső korlátot, így nem kell külön
 * szinkronban tartani a szerveroldali szabállyal.
 */
function initCommentForm() {
    var textarea = document.getElementById('body');
    if (!textarea) {
        return;
    }

    var counter = document.getElementById('body-counter');
    var maxLength = parseInt(textarea.getAttribute('maxlength'), 10) || 0;

    function updateCounter() {
        if (!counter || !maxLength) {
            return;
        }
        var length = textarea.value.length;
        counter.textContent = length + ' / ' + maxLength;
        // Közeledés a korláthoz: figyelmeztető szín
        counter.classList.toggle('text-red-600', length >= maxLength);
    }

    /** Szöveg beszúrása a kurzor pozíciójába, a visszavonás megőrzésével */
    function insertAtCursor(text) {
        textarea.focus();

        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;

        // Ha a beszúrás átlépné a korlátot, nem csinálunk semmit
        if (maxLength && textarea.value.length - (end - start) + text.length > maxLength) {
            return;
        }

        // Ahol támogatott, az execCommand megőrzi a Ctrl+Z előzményt
        var inserted = false;
        if (document.queryCommandSupported && document.queryCommandSupported('insertText')) {
            inserted = document.execCommand('insertText', false, text);
        }

        if (!inserted) {
            var value = textarea.value;
            textarea.value = value.slice(0, start) + text + value.slice(end);
            textarea.selectionStart = textarea.selectionEnd = start + text.length;
        }

        updateCounter();
    }

    document.querySelectorAll('.js-emoji').forEach(function (button) {
        button.addEventListener('click', function () {
            insertAtCursor(button.getAttribute('data-emoji') || '');
        });
    });

    textarea.addEventListener('input', updateCounter);
    updateCounter();
}

/**
 * Fiókmenü a fejlécben.
 *
 * Egyetlen lenyíló panel kezeli a látogatói fiókot és a szervezői
 * hozzáférést, külön megnevezett szakaszokban. A menü kívülre kattintásra
 * és Escape-re bezárul, a fókusz pedig visszakerül a nyitó gombra.
 */
function initAccountMenu() {
    var toggle = document.getElementById('account-toggle');
    var panel = document.getElementById('account-panel');

    if (!toggle || !panel) {
        return;
    }

    function isOpen() {
        return !panel.classList.contains('hidden');
    }

    function open() {
        panel.classList.remove('hidden');
        toggle.setAttribute('aria-expanded', 'true');
    }

    function close(returnFocus) {
        panel.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
        if (returnFocus) {
            toggle.focus();
        }
    }

    toggle.addEventListener('click', function (event) {
        event.stopPropagation();
        if (isOpen()) {
            close(false);
        } else {
            open();
        }
    });

    // Kívülre kattintás bezárja
    document.addEventListener('click', function (event) {
        if (isOpen() && !panel.contains(event.target) && !toggle.contains(event.target)) {
            close(false);
        }
    });

    // Escape bezárja, és visszaadja a fókuszt a gombra
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isOpen()) {
            close(true);
        }
    });

    // A panelből kifelé tabolva is záruljon be
    panel.addEventListener('focusout', function (event) {
        if (!panel.contains(event.relatedTarget) && event.relatedTarget !== toggle) {
            close(false);
        }
    });
}

/**
 * Jelszó mezők láthatóvá tétele.
 *
 * MINDEN type="password" mezőhöz gombot tesz, a jelölés módosítása nélkül:
 * a nézetekben nem kell egyetlen sort sem átírni, és egy később felvett
 * jelszómező is automatikusan megkapja.
 *
 * Miért a mezőbe helyezett gomb: a mezővel egy helyen van, tehát nem kell a
 * szemnek máshova ugrani, és mobilon is marad 44 képpontos érintési
 * célterület.
 *
 * A gomb type="button", különben elküldené az űrlapot. Az állapotot
 * aria-pressed jelzi, az aria-label pedig mindkét állapotban megnevezi a
 * műveletet.
 */
function initPasswordToggles() {
    var fields = document.querySelectorAll('input[type="password"]');

    if (!fields.length) {
        return;
    }

    var iconShow = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" '
        + 'viewBox="0 0 24 24" aria-hidden="true">'
        + '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639'
        + 'C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639'
        + 'C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>'
        + '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'
        + '</svg>';

    var iconHide = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" '
        + 'viewBox="0 0 24 24" aria-hidden="true">'
        + '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12'
        + 'C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5'
        + 'c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228'
        + 'l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242'
        + 'L9.88 9.88"/></svg>';

    fields.forEach(function (field) {
        // Egy mezőt csak egyszer szerelünk fel
        if (field.getAttribute('data-password-toggle') === 'ready') {
            return;
        }

        field.setAttribute('data-password-toggle', 'ready');

        // Relatív box, hogy a gombot a mező jobb szélére lehessen tenni
        var wrapper = document.createElement('div');
        wrapper.className = 'relative';

        field.parentNode.insertBefore(wrapper, field);
        wrapper.appendChild(field);

        // Hely a gombnak, hogy a beírt szöveg ne fusson alá
        field.classList.add('pr-12');

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'absolute inset-y-0 right-0 grid place-items-center w-12 '
            + 'text-sand-500 hover:text-billiard-green-700 transition-colors';

        function render(visible) {
            button.innerHTML = visible ? iconHide : iconShow;
            button.setAttribute('aria-pressed', visible ? 'true' : 'false');
            button.setAttribute('aria-label', visible ? 'Jelszó elrejtése' : 'Jelszó megjelenítése');
            button.setAttribute('title', visible ? 'Jelszó elrejtése' : 'Jelszó megjelenítése');
        }

        render(false);

        button.addEventListener('click', function () {
            var visible = field.type === 'text';

            field.type = visible ? 'password' : 'text';
            render(!visible);

            // A fókusz maradjon a mezőn, a kurzor a szöveg végén, hogy a
            // gépelés megszakítás nélkül folytatható legyen
            field.focus();

            if (field.setSelectionRange) {
                try {
                    var end = field.value.length;
                    field.setSelectionRange(end, end);
                } catch (error) {
                    // Egyes böngészők a type váltás után tiltják - nem gond
                }
            }
        });

        wrapper.appendChild(button);
    });
}

/**
 * Nyomtatás gombok.
 *
 * A .js-print osztályú gomb a böngésző nyomtatási párbeszédét nyitja. A
 * megjelenést a stíluslap @media print szakasza rendezi: a fejléc, a menü és
 * a gombok nem kerülnek papírra.
 */
function initPrintButtons() {
    document.querySelectorAll('.js-print').forEach(function (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    });
}

/**
 * Tartalom megosztása.
 *
 * Három út, csökkenő sorrendben:
 *   1. a rendszer saját megosztó panelje (navigator.share), ha van - mobilon
 *      ez a megszokott, és minden telepített alkalmazást felajánl;
 *   2. hivatkozás másolása a vágólapra;
 *   3. a közösségi hivatkozások, amelyek sima <a> elemek, tehát JavaScript
 *      nélkül is működnek.
 *
 * A natív megosztás gombja a jelölésben rejtve van, és csak akkor jelenik
 * meg, ha a böngésző támogatja - különben egy működésképtelen gomb látszana.
 */
function initShare() {
    var root = document.querySelector('[data-share]');

    if (!root) {
        return;
    }

    var url = root.getAttribute('data-share-url') || window.location.href;
    var title = root.getAttribute('data-share-title') || document.title;

    // 1. Natív megosztás
    var nativeButton = root.querySelector('.js-share-native');

    if (nativeButton && navigator.share) {
        nativeButton.classList.remove('hidden');
        nativeButton.addEventListener('click', function () {
            navigator.share({ title: title, url: url }).catch(function () {
                // A felhasználó megszakította - nincs mit tenni
            });
        });
    }

    // 2. Vágólapra másolás
    var copyButton = root.querySelector('.js-share-copy');

    if (!copyButton) {
        return;
    }

    var feedback = root.querySelector('.js-share-feedback');
    var resetTimer = null;

    function showFeedback(message) {
        if (!feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.classList.remove('hidden');

        if (resetTimer) {
            window.clearTimeout(resetTimer);
        }

        resetTimer = window.setTimeout(function () {
            feedback.classList.add('hidden');
        }, 2500);
    }

    /** Tartalék másolás, ha a Clipboard API nem érhető el (pl. sima http-n) */
    function fallbackCopy(text) {
        var helper = document.createElement('textarea');
        helper.value = text;
        helper.setAttribute('readonly', 'readonly');
        // Képernyőn kívülre, hogy ne ugrassa meg az oldalt
        helper.className = 'sr-only';
        document.body.appendChild(helper);
        helper.select();

        var copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (error) {
            copied = false;
        }

        document.body.removeChild(helper);

        return copied;
    }

    copyButton.addEventListener('click', function () {
        // A Clipboard API csak biztonságos környezetben (https vagy
        // localhost) elérhető, ezért kell a tartalék
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(
                function () {
                    showFeedback('A hivatkozás a vágólapra került.');
                },
                function () {
                    showFeedback(
                        fallbackCopy(url)
                            ? 'A hivatkozás a vágólapra került.'
                            : 'A másolás nem sikerült, jelöld ki a címsort.'
                    );
                }
            );

            return;
        }

        showFeedback(
            fallbackCopy(url)
                ? 'A hivatkozás a vágólapra került.'
                : 'A másolás nem sikerült, jelöld ki a címsort.'
        );
    });
}
