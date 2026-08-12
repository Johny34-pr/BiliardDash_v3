/**
 * Magyar Biliárd Weboldal - Általános JavaScript
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
