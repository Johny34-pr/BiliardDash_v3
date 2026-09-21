/**
 * Lightbox osztály - Galéria képnézegetés
 *
 * Funkciók:
 * - Teljes méretű kép megjelenítés overlay-ben
 * - Navigáció: következő/előző gombok
 * - Billentyűzet kezelés: Escape (bezárás), ArrowLeft (előző), ArrowRight (következő)
 * - Mobil swipe gesztus (balra/jobbra)
 * - Első képnél hátra nyíl letiltva, utolsó képnél előre nyíl letiltva
 * - Kép betöltési hiba: placeholder
 */
class Lightbox {
    constructor(images) {
        this.images = images;
        this.currentIndex = 0;
        this.overlay = null;
        this.imgElement = null;
        this.prevButton = null;
        this.nextButton = null;
        this.touchStartX = 0;
        this.touchEndX = 0;
        this.isOpen = false;

        this.handleKeydown = this.handleKeydown.bind(this);
        this.handleTouchStart = this.handleTouchStart.bind(this);
        this.handleTouchEnd = this.handleTouchEnd.bind(this);
    }

    /**
     * Lightbox megnyitása adott index-ű képpel
     */
    open(index) {
        if (this.images.length === 0) return;

        this.currentIndex = Math.max(0, Math.min(index, this.images.length - 1));
        this.isOpen = true;

        this.createOverlay();
        this.showImage();
        this.updateNavButtons();

        document.addEventListener('keydown', this.handleKeydown);
        document.body.style.overflow = 'hidden';
    }

    /**
     * Lightbox bezárása
     */
    close() {
        if (!this.isOpen) return;

        this.isOpen = false;
        document.removeEventListener('keydown', this.handleKeydown);
        document.body.style.overflow = '';

        if (this.overlay && this.overlay.parentNode) {
            this.overlay.parentNode.removeChild(this.overlay);
        }

        this.overlay = null;
        this.imgElement = null;
        this.prevButton = null;
        this.nextButton = null;
    }

    /**
     * Következő kép megjelenítése (utolsónál letiltva)
     */
    next() {
        if (this.currentIndex >= this.images.length - 1) return;
        this.currentIndex++;
        this.showImage();
        this.updateNavButtons();
    }

    /**
     * Előző kép megjelenítése (elsőnél letiltva)
     */
    prev() {
        if (this.currentIndex <= 0) return;
        this.currentIndex--;
        this.showImage();
        this.updateNavButtons();
    }

    /**
     * Billentyűzet kezelés
     */
    handleKeydown(e) {
        switch (e.key) {
            case 'Escape':
                this.close();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                this.prev();
                break;
            case 'ArrowRight':
                e.preventDefault();
                this.next();
                break;
        }
    }

    /**
     * Mobil swipe gesztus feldolgozása
     */
    handleSwipe(startX, endX) {
        const threshold = 50;
        const diff = startX - endX;

        if (Math.abs(diff) < threshold) return;

        if (diff > 0) {
            // Balra húzás → következő kép
            this.next();
        } else {
            // Jobbra húzás → előző kép
            this.prev();
        }
    }

    /**
     * Navigációs gombok állapotának frissítése
     *
     * Egyetlen kép esetén a nyilak és a számláló el is tűnnek: egy képnél
     * nincs mire lépni, a letiltott nyíl és az "1 / 1" felirat pedig csak
     * zavarná a nézőt.
     */
    updateNavButtons() {
        const single = this.images.length <= 1;

        if (single) {
            if (this.prevButton) this.prevButton.style.display = 'none';
            if (this.nextButton) this.nextButton.style.display = 'none';

            const singleCounter = this.overlay ? this.overlay.querySelector('[data-lightbox-counter]') : null;
            if (singleCounter) singleCounter.style.display = 'none';

            return;
        }

        if (this.prevButton) {
            const isFirst = this.currentIndex <= 0;
            this.prevButton.disabled = isFirst;
            this.prevButton.style.opacity = isFirst ? '0.3' : '1';
            this.prevButton.style.cursor = isFirst ? 'default' : 'pointer';
            this.prevButton.setAttribute('aria-disabled', isFirst.toString());
        }

        if (this.nextButton) {
            const isLast = this.currentIndex >= this.images.length - 1;
            this.nextButton.disabled = isLast;
            this.nextButton.style.opacity = isLast ? '0.3' : '1';
            this.nextButton.style.cursor = isLast ? 'default' : 'pointer';
            this.nextButton.setAttribute('aria-disabled', isLast.toString());
        }

        // Számláló frissítése
        const counter = this.overlay ? this.overlay.querySelector('[data-lightbox-counter]') : null;
        if (counter) {
            counter.textContent = `${this.currentIndex + 1} / ${this.images.length}`;
        }
    }

    /**
     * Overlay DOM struktúra létrehozása
     */
    createOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'lightbox-overlay';
        this.overlay.setAttribute('role', 'dialog');
        this.overlay.setAttribute('aria-modal', 'true');
        this.overlay.setAttribute('aria-label', 'Képnéző');
        this.overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.92);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        `;

        // Bezárás kattintás az overlay-re (kép kívüli terület)
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) {
                this.close();
            }
        });

        // Bezárás gomb
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.setAttribute('aria-label', 'Bezárás');
        closeButton.style.cssText = `
            position: absolute;
            top: 16px;
            right: 16px;
            background: none;
            border: none;
            color: white;
            font-size: 32px;
            cursor: pointer;
            z-index: 10001;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        `;
        closeButton.innerHTML = '&times;';
        closeButton.addEventListener('click', () => this.close());
        closeButton.addEventListener('mouseenter', () => {
            closeButton.style.backgroundColor = 'rgba(255,255,255,0.1)';
        });
        closeButton.addEventListener('mouseleave', () => {
            closeButton.style.backgroundColor = 'transparent';
        });

        // Kép konténer
        const imgContainer = document.createElement('div');
        imgContainer.style.cssText = `
            max-width: 90vw;
            max-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        `;

        // Kép elem
        this.imgElement = document.createElement('img');
        this.imgElement.style.cssText = `
            max-width: 90vw;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 4px;
        `;
        this.imgElement.addEventListener('click', (e) => e.stopPropagation());

        imgContainer.appendChild(this.imgElement);

        // Előző gomb
        this.prevButton = document.createElement('button');
        this.prevButton.type = 'button';
        this.prevButton.setAttribute('aria-label', 'Előző kép');
        this.prevButton.style.cssText = `
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            z-index: 10001;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s, opacity 0.2s;
        `;
        this.prevButton.innerHTML = '&#10094;';
        this.prevButton.addEventListener('click', (e) => {
            e.stopPropagation();
            this.prev();
        });

        // Következő gomb
        this.nextButton = document.createElement('button');
        this.nextButton.type = 'button';
        this.nextButton.setAttribute('aria-label', 'Következő kép');
        this.nextButton.style.cssText = `
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            z-index: 10001;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s, opacity 0.2s;
        `;
        this.nextButton.innerHTML = '&#10095;';
        this.nextButton.addEventListener('click', (e) => {
            e.stopPropagation();
            this.next();
        });

        // Számláló
        const counter = document.createElement('div');
        counter.setAttribute('data-lightbox-counter', '');
        counter.style.cssText = `
            color: white;
            margin-top: 12px;
            font-size: 14px;
            opacity: 0.8;
        `;

        this.overlay.appendChild(closeButton);
        this.overlay.appendChild(this.prevButton);
        this.overlay.appendChild(imgContainer);
        this.overlay.appendChild(this.nextButton);
        this.overlay.appendChild(counter);

        // Touch események mobil swipe-hoz
        this.overlay.addEventListener('touchstart', this.handleTouchStart, { passive: true });
        this.overlay.addEventListener('touchend', this.handleTouchEnd, { passive: true });

        document.body.appendChild(this.overlay);
    }

    /**
     * Aktuális kép megjelenítése
     */
    showImage() {
        if (!this.imgElement) return;

        const image = this.images[this.currentIndex];
        this.imgElement.alt = image.alt || '';
        this.imgElement.src = image.full;

        // Kép betöltési hiba kezelés
        this.imgElement.onerror = () => {
            this.imgElement.onerror = null;
            this.imgElement.src = '/assets/images/placeholder.svg';
        };
    }

    /**
     * Touch start esemény
     */
    handleTouchStart(e) {
        this.touchStartX = e.changedTouches[0].screenX;
    }

    /**
     * Touch end esemény - swipe feldolgozás
     */
    handleTouchEnd(e) {
        this.touchEndX = e.changedTouches[0].screenX;
        this.handleSwipe(this.touchStartX, this.touchEndX);
    }
}

// Galéria inicializálás DOM betöltődés után
document.addEventListener('DOMContentLoaded', function () {
    const images = window.galleryImages || [];
    if (images.length === 0) return;

    const lightbox = new Lightbox(images);

    // Bélyegkép kattintás kezelés
    const grid = document.getElementById('gallery-grid');
    if (grid) {
        grid.addEventListener('click', function (e) {
            const button = e.target.closest('[data-lightbox-index]');
            if (button) {
                const index = parseInt(button.getAttribute('data-lightbox-index'), 10);
                lightbox.open(index);
            }
        });
    }
});
