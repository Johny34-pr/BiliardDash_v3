<?php

declare(strict_types=1);

/**
 * Segédosztály feloldó
 * =============================================================================
 *
 * Egy osztálynévből (pl. "mt-4", "text-sand-500", "md:flex", "hover:bg-white/10")
 * előállítja a hozzá tartozó CSS deklarációkat. A tools/build-css.php ezt
 * hívja minden, a sablonokban megtalált osztálynévre.
 *
 * Amit nem ismer fel, arról null-t ad vissza - a generátor ezeket kiírja,
 * így egy elgépelt vagy még nem támogatott osztály nem marad csendben
 * stílus nélkül.
 *
 * A kimenet nem karakterre egyezik a korábbi, JavaScript alapú eszköz
 * kimenetével, de a hatása megegyezik. Ahol egyszerűbb formát választottunk
 * (pl. tömör szín a rgb()+változó helyett), azt megjegyzés jelzi.
 */
final class UtilityResolver
{
    /** @var array<string, mixed> */
    private array $tokens;

    /** @var array<string, array<string, string>> Egyszerű, minta nélküli osztályok */
    private array $statics;

    /**
     * @param array<string, mixed> $tokens A tools/css/tokens.php tartalma
     */
    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
        $this->statics = $this->buildStatics();
    }

    /**
     * Egy segédosztály feloldása.
     *
     * @return array{suffix:string, decls:array<string,string>}|null
     *         A suffix a szelektorhoz fűzött rész (pl. a space-y gyerek
     *         szelektora), a decls a CSS deklarációk.
     */
    public function resolve(string $utility): ?array
    {
        // A "group" csak jelölő a szülőn, nincs saját stílusa
        if ($utility === 'group') {
            return ['suffix' => '', 'decls' => []];
        }

        if (isset($this->statics[$utility])) {
            return ['suffix' => '', 'decls' => $this->statics[$utility]];
        }

        foreach ($this->patternResolvers() as $resolver) {
            $result = $resolver($utility);

            if ($result !== null) {
                return isset($result['decls']) ? $result : ['suffix' => '', 'decls' => $result];
            }
        }

        return null;
    }

    // =========================================================================
    // Minta nélküli osztályok
    // =========================================================================

    /**
     * @return array<string, array<string, string>>
     */
    private function buildStatics(): array
    {
        $s = [
            // Megjelenítés
            'block' => ['display' => 'block'],
            'inline-block' => ['display' => 'inline-block'],
            'inline' => ['display' => 'inline'],
            'flex' => ['display' => 'flex'],
            'inline-flex' => ['display' => 'inline-flex'],
            'grid' => ['display' => 'grid'],
            'inline-grid' => ['display' => 'inline-grid'],
            'contents' => ['display' => 'contents'],
            'table' => ['display' => 'table'],
            'table-cell' => ['display' => 'table-cell'],
            'hidden' => ['display' => 'none'],

            // Elhelyezés
            'static' => ['position' => 'static'],
            'fixed' => ['position' => 'fixed'],
            'absolute' => ['position' => 'absolute'],
            'relative' => ['position' => 'relative'],
            'sticky' => ['position' => 'sticky'],
            'isolate' => ['isolation' => 'isolate'],

            // Flexbox
            'flex-row' => ['flex-direction' => 'row'],
            'flex-row-reverse' => ['flex-direction' => 'row-reverse'],
            'flex-col' => ['flex-direction' => 'column'],
            'flex-col-reverse' => ['flex-direction' => 'column-reverse'],
            'flex-wrap' => ['flex-wrap' => 'wrap'],
            'flex-wrap-reverse' => ['flex-wrap' => 'wrap-reverse'],
            'flex-nowrap' => ['flex-wrap' => 'nowrap'],
            'flex-1' => ['flex' => '1 1 0%'],
            'flex-auto' => ['flex' => '1 1 auto'],
            'flex-initial' => ['flex' => '0 1 auto'],
            'flex-none' => ['flex' => 'none'],
            'shrink' => ['flex-shrink' => '1'],
            'shrink-0' => ['flex-shrink' => '0'],
            'grow' => ['flex-grow' => '1'],
            'grow-0' => ['flex-grow' => '0'],

            // Igazítás
            'items-start' => ['align-items' => 'flex-start'],
            'items-end' => ['align-items' => 'flex-end'],
            'items-center' => ['align-items' => 'center'],
            'items-baseline' => ['align-items' => 'baseline'],
            'items-stretch' => ['align-items' => 'stretch'],
            'justify-start' => ['justify-content' => 'flex-start'],
            'justify-end' => ['justify-content' => 'flex-end'],
            'justify-center' => ['justify-content' => 'center'],
            'justify-between' => ['justify-content' => 'space-between'],
            'justify-around' => ['justify-content' => 'space-around'],
            'justify-evenly' => ['justify-content' => 'space-evenly'],
            'self-auto' => ['align-self' => 'auto'],
            'self-start' => ['align-self' => 'flex-start'],
            'self-end' => ['align-self' => 'flex-end'],
            'self-center' => ['align-self' => 'center'],
            'self-stretch' => ['align-self' => 'stretch'],
            'place-items-center' => ['place-items' => 'center'],
            'place-items-start' => ['place-items' => 'start'],
            'place-items-end' => ['place-items' => 'end'],
            'place-content-center' => ['place-content' => 'center'],

            // Méret kulcsszavak
            'w-full' => ['width' => '100%'],
            'w-auto' => ['width' => 'auto'],
            'w-fit' => ['width' => 'fit-content'],
            'w-min' => ['width' => 'min-content'],
            'w-max' => ['width' => 'max-content'],
            'w-screen' => ['width' => '100vw'],
            'h-full' => ['height' => '100%'],
            'h-auto' => ['height' => 'auto'],
            'h-fit' => ['height' => 'fit-content'],
            'h-min' => ['height' => 'min-content'],
            'h-max' => ['height' => 'max-content'],
            'h-screen' => ['height' => '100vh'],
            'min-h-0' => ['min-height' => '0px'],
            'min-h-full' => ['min-height' => '100%'],
            'min-h-screen' => ['min-height' => '100vh'],
            'min-w-0' => ['min-width' => '0px'],
            'min-w-full' => ['min-width' => '100%'],
            'min-w-min' => ['min-width' => 'min-content'],
            'min-w-max' => ['min-width' => 'max-content'],
            'min-w-fit' => ['min-width' => 'fit-content'],
            'max-h-full' => ['max-height' => '100%'],
            'max-h-screen' => ['max-height' => '100vh'],

            // Automatikus margók
            'm-auto' => ['margin' => 'auto'],
            'mx-auto' => ['margin-left' => 'auto', 'margin-right' => 'auto'],
            'my-auto' => ['margin-top' => 'auto', 'margin-bottom' => 'auto'],
            'mt-auto' => ['margin-top' => 'auto'],
            'mb-auto' => ['margin-bottom' => 'auto'],
            'ml-auto' => ['margin-left' => 'auto'],
            'mr-auto' => ['margin-right' => 'auto'],

            // Szöveg
            'text-left' => ['text-align' => 'left'],
            'text-center' => ['text-align' => 'center'],
            'text-right' => ['text-align' => 'right'],
            'text-justify' => ['text-align' => 'justify'],
            'uppercase' => ['text-transform' => 'uppercase'],
            'lowercase' => ['text-transform' => 'lowercase'],
            'capitalize' => ['text-transform' => 'capitalize'],
            'normal-case' => ['text-transform' => 'none'],
            'underline' => ['text-decoration-line' => 'underline'],
            'line-through' => ['text-decoration-line' => 'line-through'],
            'no-underline' => ['text-decoration-line' => 'none'],
            'italic' => ['font-style' => 'italic'],
            'not-italic' => ['font-style' => 'normal'],
            'antialiased' => [
                '-webkit-font-smoothing' => 'antialiased',
                '-moz-osx-font-smoothing' => 'grayscale',
            ],
            'tabular-nums' => [
                '--tw-numeric-spacing' => 'tabular-nums',
                'font-variant-numeric' =>
                    'var(--tw-ordinal) var(--tw-slashed-zero) var(--tw-numeric-figure)'
                    . ' var(--tw-numeric-spacing) var(--tw-numeric-fraction)',
            ],
            'truncate' => [
                'overflow' => 'hidden',
                'text-overflow' => 'ellipsis',
                'white-space' => 'nowrap',
            ],
            'whitespace-normal' => ['white-space' => 'normal'],
            'whitespace-nowrap' => ['white-space' => 'nowrap'],
            'whitespace-pre' => ['white-space' => 'pre'],
            'whitespace-pre-line' => ['white-space' => 'pre-line'],
            'whitespace-pre-wrap' => ['white-space' => 'pre-wrap'],
            'break-words' => ['overflow-wrap' => 'break-word'],
            'break-all' => ['word-break' => 'break-all'],

            // Túlcsordulás
            'overflow-auto' => ['overflow' => 'auto'],
            'overflow-hidden' => ['overflow' => 'hidden'],
            'overflow-visible' => ['overflow' => 'visible'],
            'overflow-scroll' => ['overflow' => 'scroll'],
            'overflow-x-auto' => ['overflow-x' => 'auto'],
            'overflow-y-auto' => ['overflow-y' => 'auto'],
            'overflow-x-hidden' => ['overflow-x' => 'hidden'],
            'overflow-y-hidden' => ['overflow-y' => 'hidden'],

            // Kép illesztés
            'object-contain' => ['-o-object-fit' => 'contain', 'object-fit' => 'contain'],
            'object-cover' => ['-o-object-fit' => 'cover', 'object-fit' => 'cover'],
            'object-fill' => ['-o-object-fit' => 'fill', 'object-fit' => 'fill'],
            'object-none' => ['-o-object-fit' => 'none', 'object-fit' => 'none'],
            'object-center' => ['-o-object-position' => 'center', 'object-position' => 'center'],

            // Egyéb
            'sr-only' => [
                'position' => 'absolute',
                'width' => '1px',
                'height' => '1px',
                'padding' => '0',
                'margin' => '-1px',
                'overflow' => 'hidden',
                'clip' => 'rect(0,0,0,0)',
                'white-space' => 'nowrap',
                'border-width' => '0',
            ],
            'not-sr-only' => [
                'position' => 'static',
                'width' => 'auto',
                'height' => 'auto',
                'padding' => '0',
                'margin' => '0',
                'overflow' => 'visible',
                'clip' => 'auto',
                'white-space' => 'normal',
            ],
            'pointer-events-none' => ['pointer-events' => 'none'],
            'pointer-events-auto' => ['pointer-events' => 'auto'],
            'cursor-pointer' => ['cursor' => 'pointer'],
            'cursor-default' => ['cursor' => 'default'],
            'cursor-not-allowed' => ['cursor' => 'not-allowed'],
            'resize' => ['resize' => 'both'],
            'resize-none' => ['resize' => 'none'],
            'resize-x' => ['resize' => 'horizontal'],
            'resize-y' => ['resize' => 'vertical'],
            'appearance-none' => ['-webkit-appearance' => 'none', 'appearance' => 'none'],
            'align-middle' => ['vertical-align' => 'middle'],
            'align-top' => ['vertical-align' => 'top'],
            'align-bottom' => ['vertical-align' => 'bottom'],

            // Szegély stílus
            'border-solid' => ['border-style' => 'solid'],
            'border-dashed' => ['border-style' => 'dashed'],
            'border-dotted' => ['border-style' => 'dotted'],
            'border-none' => ['border-style' => 'none'],

            // Átmenetek
            'transition-none' => ['transition-property' => 'none'],
            'transition-all' => [
                'transition-property' => 'all',
                'transition-timing-function' => 'cubic-bezier(.4,0,.2,1)',
                'transition-duration' => '.15s',
            ],
            'transition' => [
                'transition-property' =>
                    'color,background-color,border-color,text-decoration-color,fill,stroke,'
                    . 'opacity,box-shadow,transform,filter,-webkit-backdrop-filter,backdrop-filter',
                'transition-timing-function' => 'cubic-bezier(.4,0,.2,1)',
                'transition-duration' => '.15s',
            ],
            'transition-colors' => [
                'transition-property' =>
                    'color,background-color,border-color,text-decoration-color,fill,stroke',
                'transition-timing-function' => 'cubic-bezier(.4,0,.2,1)',
                'transition-duration' => '.15s',
            ],
            'transition-opacity' => [
                'transition-property' => 'opacity',
                'transition-timing-function' => 'cubic-bezier(.4,0,.2,1)',
                'transition-duration' => '.15s',
            ],
            'transition-transform' => [
                'transition-property' => 'transform',
                'transition-timing-function' => 'cubic-bezier(.4,0,.2,1)',
                'transition-duration' => '.15s',
            ],
            'transition-shadow' => [
                'transition-property' => 'box-shadow',
                'transition-timing-function' => 'cubic-bezier(.4,0,.2,1)',
                'transition-duration' => '.15s',
            ],

            // Gyűrű belső irányba
            'ring-inset' => ['--tw-ring-inset' => 'inset'],
        ];

        // Tartalomsáv: középre igazítva, fix belső margóval, töréspontonként
        // növő felső korláttal. A töréspontokat a generátor teszi médiába.
        $s['container'] = [
            'width' => '100%',
            'margin-right' => 'auto',
            'margin-left' => 'auto',
            'padding-right' => '1rem',
            'padding-left' => '1rem',
        ];

        // Színátmenet irányok
        $directions = [
            't' => 'to top', 'tr' => 'to top right', 'r' => 'to right',
            'br' => 'to bottom right', 'b' => 'to bottom', 'bl' => 'to bottom left',
            'l' => 'to left', 'tl' => 'to top left',
        ];

        foreach ($directions as $short => $value) {
            $s['bg-gradient-to-' . $short] = [
                'background-image' => 'linear-gradient(' . $value . ',var(--tw-gradient-stops))',
            ];
        }

        // Betűtípus családok
        foreach ($this->tokens['fontFamily'] as $name => $stack) {
            $s['font-' . $name] = ['font-family' => $stack];
        }

        return $s;
    }

    // =========================================================================
    // Mintaalapú feloldók
    // =========================================================================

    /**
     * @return array<callable(string):(array|null)>
     */
    private function patternResolvers(): array
    {
        return [
            fn(string $u) => $this->resolveSpacing($u),
            fn(string $u) => $this->resolveSizing($u),
            fn(string $u) => $this->resolveInset($u),
            fn(string $u) => $this->resolveGapAndSpace($u),
            fn(string $u) => $this->resolveTypography($u),
            fn(string $u) => $this->resolveColorUtilities($u),
            fn(string $u) => $this->resolveBorders($u),
            fn(string $u) => $this->resolveEffects($u),
            fn(string $u) => $this->resolveGrid($u),
            fn(string $u) => $this->resolveTransforms($u),
            fn(string $u) => $this->resolveMisc($u),
        ];
    }

    /**
     * Belső és külső margók: p-4, px-6, -mt-2, my-auto stb.
     */
    private function resolveSpacing(string $utility): ?array
    {
        if (preg_match('/^(-?)(p|m)(x|y|t|r|b|l)?-(.+)$/', $utility, $m) !== 1) {
            return null;
        }

        [, $negative, $type, $side, $value] = $m;

        $size = $this->spacingValue($value);

        if ($size === null) {
            return null;
        }

        if ($negative === '-') {
            $size = $this->negate($size);
        }

        $property = $type === 'p' ? 'padding' : 'margin';

        return match ($side) {
            '' => [$property => $size],
            'x' => [$property . '-left' => $size, $property . '-right' => $size],
            'y' => [$property . '-top' => $size, $property . '-bottom' => $size],
            't' => [$property . '-top' => $size],
            'r' => [$property . '-right' => $size],
            'b' => [$property . '-bottom' => $size],
            'l' => [$property . '-left' => $size],
            default => null,
        };
    }

    /**
     * Méretek: w-6, h-full, max-w-reading, min-h-[80vh], w-1/2 stb.
     */
    private function resolveSizing(string $utility): ?array
    {
        $map = [
            'max-w' => ['max-width', 'maxWidth'],
            'max-h' => ['max-height', null],
            'min-w' => ['min-width', null],
            'min-h' => ['min-height', null],
            'w' => ['width', null],
            'h' => ['height', null],
            'size' => ['size', null],
            'basis' => ['flex-basis', null],
        ];

        foreach ($map as $prefix => [$property, $tokenKey]) {
            if (!str_starts_with($utility, $prefix . '-')) {
                continue;
            }

            $value = substr($utility, strlen($prefix) + 1);

            // Saját skála (jelenleg csak a max-width)
            if ($tokenKey !== null && isset($this->tokens[$tokenKey][$value])) {
                return [$property => $this->tokens[$tokenKey][$value]];
            }

            $resolved = $this->spacingValue($value) ?? $this->fractionValue($value);

            if ($resolved === null) {
                continue;
            }

            if ($property === 'size') {
                return ['width' => $resolved, 'height' => $resolved];
            }

            return [$property => $resolved];
        }

        return null;
    }

    /**
     * Pozicionálás: top-0, -left-2, inset-0, inset-x-0 stb.
     */
    private function resolveInset(string $utility): ?array
    {
        if (preg_match('/^(-?)(inset-x|inset-y|inset|top|right|bottom|left)-(.+)$/', $utility, $m) !== 1) {
            return null;
        }

        [, $negative, $side, $value] = $m;

        $size = $this->spacingValue($value) ?? $this->fractionValue($value);

        if ($size === null) {
            return null;
        }

        if ($negative === '-') {
            $size = $this->negate($size);
        }

        return match ($side) {
            'inset' => ['inset' => $size],
            'inset-x' => ['left' => $size, 'right' => $size],
            'inset-y' => ['top' => $size, 'bottom' => $size],
            default => [$side => $size],
        };
    }

    /**
     * Rések és elemek közti távolság: gap-4, gap-x-6, space-y-3, divide-y.
     *
     * A space-* és divide-* a szomszédos gyerekekre hat, ezért szelektor
     * utótagot is ad vissza.
     */
    private function resolveGapAndSpace(string $utility): ?array
    {
        // Rések
        if (preg_match('/^gap(-x|-y)?-(.+)$/', $utility, $m) === 1) {
            $size = $this->spacingValue($m[2]);

            if ($size === null) {
                return null;
            }

            return match ($m[1]) {
                '' => ['gap' => $size],
                '-x' => ['-moz-column-gap' => $size, 'column-gap' => $size],
                '-y' => ['row-gap' => $size],
                default => null,
            };
        }

        // Elemek közti távolság
        if (preg_match('/^(-?)space-(x|y)-(.+)$/', $utility, $m) === 1) {
            $size = $this->spacingValue($m[3]);

            if ($size === null) {
                return null;
            }

            if ($m[1] === '-') {
                $size = $this->negate($size);
            }

            $axis = $m[2];
            $reverse = '--tw-space-' . $axis . '-reverse';

            $decls = $axis === 'y'
                ? [
                    $reverse => '0',
                    'margin-top' => 'calc(' . $size . '*(1 - var(' . $reverse . ')))',
                    'margin-bottom' => 'calc(' . $size . '*var(' . $reverse . '))',
                ]
                : [
                    $reverse => '0',
                    'margin-right' => 'calc(' . $size . '*var(' . $reverse . '))',
                    'margin-left' => 'calc(' . $size . '*(1 - var(' . $reverse . ')))',
                ];

            return ['suffix' => '>:not([hidden])~:not([hidden])', 'decls' => $decls];
        }

        // Elválasztó vonalak a gyerekek között
        if (preg_match('/^divide-(x|y)(-reverse|-(\d+))?$/', $utility, $m) === 1) {
            $axis = $m[1];
            $reverse = '--tw-divide-' . $axis . '-reverse';

            if (($m[2] ?? '') === '-reverse') {
                return ['suffix' => '>:not([hidden])~:not([hidden])', 'decls' => [$reverse => '1']];
            }

            $width = isset($m[3]) && $m[3] !== '' ? $m[3] . 'px' : '1px';

            $decls = $axis === 'y'
                ? [
                    $reverse => '0',
                    'border-top-width' => 'calc(' . $width . '*(1 - var(' . $reverse . ')))',
                    'border-bottom-width' => 'calc(' . $width . '*var(' . $reverse . '))',
                ]
                : [
                    $reverse => '0',
                    'border-right-width' => 'calc(' . $width . '*var(' . $reverse . '))',
                    'border-left-width' => 'calc(' . $width . '*(1 - var(' . $reverse . ')))',
                ];

            return ['suffix' => '>:not([hidden])~:not([hidden])', 'decls' => $decls];
        }

        // Elválasztó vonal színe
        if (str_starts_with($utility, 'divide-')) {
            $color = $this->colorValue(substr($utility, 7));

            if ($color !== null) {
                return [
                    'suffix' => '>:not([hidden])~:not([hidden])',
                    'decls' => ['border-color' => $color],
                ];
            }
        }

        return null;
    }

    /**
     * Tipográfia: text-sm, font-semibold, leading-snug, tracking-wider.
     */
    private function resolveTypography(string $utility): ?array
    {
        // Betűméret (a szín feloldása előtt, mert a text- előtag közös)
        if (preg_match('/^text-(.+)$/', $utility, $m) === 1) {
            $value = $m[1];

            if (isset($this->tokens['fontSize'][$value])) {
                [$size, $height] = $this->tokens['fontSize'][$value];
                return ['font-size' => $size, 'line-height' => $height];
            }

            $arbitrary = $this->arbitraryValue($value);

            // A szögletes zárójeles érték lehet méret vagy szín is
            if ($arbitrary !== null && !str_starts_with($arbitrary, '#')) {
                return ['font-size' => $arbitrary];
            }
        }

        if (preg_match('/^font-(.+)$/', $utility, $m) === 1
            && isset($this->tokens['fontWeight'][$m[1]])) {
            return ['font-weight' => $this->tokens['fontWeight'][$m[1]]];
        }

        if (preg_match('/^leading-(.+)$/', $utility, $m) === 1) {
            $value = $this->tokens['lineHeight'][$m[1]] ?? $this->arbitraryValue($m[1]);

            if ($value !== null) {
                return ['line-height' => $value];
            }
        }

        if (preg_match('/^tracking-(.+)$/', $utility, $m) === 1) {
            $value = $this->tokens['letterSpacing'][$m[1]] ?? $this->arbitraryValue($m[1]);

            if ($value !== null) {
                return ['letter-spacing' => $value];
            }
        }

        if (preg_match('/^underline-offset-(.+)$/', $utility, $m) === 1) {
            $value = $this->arbitraryValue($m[1]) ?? ($m[1] === 'auto' ? 'auto' : $m[1] . 'px');
            return ['text-underline-offset' => $value];
        }

        if (preg_match('/^decoration-(\d+)$/', $utility, $m) === 1) {
            return ['text-decoration-thickness' => $m[1] . 'px'];
        }

        return null;
    }

    /**
     * Színek: bg-sand-100, text-white, ring-black/5, from-billiard-gold-300.
     */
    private function resolveColorUtilities(string $utility): ?array
    {
        $map = [
            'bg' => 'background-color',
            'text' => 'color',
            'ring' => '--tw-ring-color',
            'fill' => 'fill',
            'stroke' => 'stroke',
            'decoration' => 'text-decoration-color',
            'caret' => 'caret-color',
            'accent' => 'accent-color',
        ];

        foreach ($map as $prefix => $property) {
            if (!str_starts_with($utility, $prefix . '-')) {
                continue;
            }

            $color = $this->colorValue(substr($utility, strlen($prefix) + 1));

            if ($color !== null) {
                return [$property => $color];
            }
        }

        // Színátmenet megállói
        if (preg_match('/^(from|via|to)-(.+)$/', $utility, $m) === 1) {
            $color = $this->colorValue($m[2]);

            if ($color === null) {
                return null;
            }

            $transparent = $this->transparentVersion($m[2]);

            return match ($m[1]) {
                'from' => [
                    '--tw-gradient-from' => $color . ' var(--tw-gradient-from-position)',
                    '--tw-gradient-to' => $transparent . ' var(--tw-gradient-to-position)',
                    '--tw-gradient-stops' => 'var(--tw-gradient-from),var(--tw-gradient-to)',
                ],
                'via' => [
                    '--tw-gradient-to' => $transparent . ' var(--tw-gradient-to-position)',
                    '--tw-gradient-stops' =>
                        'var(--tw-gradient-from),' . $color
                        . ' var(--tw-gradient-via-position),var(--tw-gradient-to)',
                ],
                'to' => ['--tw-gradient-to' => $color . ' var(--tw-gradient-to-position)'],
            };
        }

        return null;
    }

    /**
     * Szegélyek és lekerekítés: border, border-2, border-t, rounded-xl,
     * border-sand-200, rounded-l-lg.
     */
    private function resolveBorders(string $utility): ?array
    {
        // Lekerekítés
        if (preg_match('/^rounded(?:-(t|r|b|l|tl|tr|br|bl))?(?:-(.+))?$/', $utility, $m) === 1) {
            $side = $m[1] ?? '';
            $sizeKey = ($m[2] ?? '') !== '' ? $m[2] : 'DEFAULT';
            $value = $this->tokens['borderRadius'][$sizeKey] ?? $this->arbitraryValue($sizeKey);

            if ($value === null) {
                return null;
            }

            $corners = [
                '' => ['border-radius'],
                't' => ['border-top-left-radius', 'border-top-right-radius'],
                'r' => ['border-top-right-radius', 'border-bottom-right-radius'],
                'b' => ['border-bottom-right-radius', 'border-bottom-left-radius'],
                'l' => ['border-top-left-radius', 'border-bottom-left-radius'],
                'tl' => ['border-top-left-radius'],
                'tr' => ['border-top-right-radius'],
                'br' => ['border-bottom-right-radius'],
                'bl' => ['border-bottom-left-radius'],
            ];

            $decls = [];
            foreach ($corners[$side] as $property) {
                $decls[$property] = $value;
            }

            return $decls;
        }

        // Szegély szélessége és színe
        if (str_starts_with($utility, 'border')) {
            $sides = [
                '' => ['border'],
                't' => ['border-top'],
                'r' => ['border-right'],
                'b' => ['border-bottom'],
                'l' => ['border-left'],
                'x' => ['border-left', 'border-right'],
                'y' => ['border-top', 'border-bottom'],
            ];

            $rest = substr($utility, strlen('border'));
            $rest = $rest === '' ? '' : ltrim($rest, '-');

            /*
             * Két értelmezést próbálunk, ebben a sorrendben:
             *
             *   1. oldal megadása nélkül, pl. border-red-500
             *   2. oldallal, pl. border-t-2
             *
             * A sorrend számít: a "border-red-500" első betűje egybeesik az
             * "r" (jobb oldal) rövidítéssel, ezért oldalként értelmezve
             * "ed-500" maradna, ami semmit nem jelent. Előbb tehát a teljes
             * maradékot próbáljuk feloldani.
             */
            $candidates = [['', $rest]];

            if (preg_match('/^(t|r|b|l|x|y)(?:-(.*))?$/', $rest, $m) === 1) {
                $candidates[] = [$m[1], $m[2] ?? ''];
            }

            foreach ($candidates as [$side, $value]) {
                $widthKey = $value === '' ? 'DEFAULT' : $value;

                if (isset($this->tokens['borderWidth'][$widthKey])) {
                    $decls = [];
                    foreach ($sides[$side] as $property) {
                        $decls[$property . '-width'] = $this->tokens['borderWidth'][$widthKey];
                    }
                    return $decls;
                }

                if ($value !== '') {
                    $color = $this->colorValue($value);

                    if ($color !== null) {
                        $decls = [];
                        foreach ($sides[$side] as $property) {
                            $decls[$property . '-color'] = $color;
                        }
                        return $decls;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Árnyék, áttetszőség, gyűrű, elmosás.
     */
    private function resolveEffects(string $utility): ?array
    {
        // Árnyék
        if (preg_match('/^shadow(?:-(.+))?$/', $utility, $m) === 1) {
            $key = ($m[1] ?? '') !== '' ? $m[1] : 'DEFAULT';

            if (isset($this->tokens['boxShadow'][$key])) {
                [$shadow, $colored] = $this->tokens['boxShadow'][$key];

                return [
                    '--tw-shadow' => $shadow,
                    '--tw-shadow-colored' => $colored,
                    'box-shadow' =>
                        'var(--tw-ring-offset-shadow,0 0 #0000),var(--tw-ring-shadow,0 0 #0000),'
                        . 'var(--tw-shadow)',
                ];
            }
        }

        // Áttetszőség
        if (preg_match('/^opacity-(\d+)$/', $utility, $m) === 1) {
            return ['opacity' => $this->decimal((int) $m[1] / 100)];
        }

        // Gyűrű szélessége
        if (preg_match('/^ring(?:-(\d+))?$/', $utility, $m) === 1) {
            $width = ($m[1] ?? '') !== '' ? $m[1] . 'px' : '3px';

            return [
                '--tw-ring-offset-shadow' =>
                    'var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color)',
                '--tw-ring-shadow' =>
                    'var(--tw-ring-inset) 0 0 0 calc(' . $width
                    . ' + var(--tw-ring-offset-width)) var(--tw-ring-color)',
                'box-shadow' =>
                    'var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow,0 0 #0000)',
            ];
        }

        // Gyűrű eltolása
        if (preg_match('/^ring-offset-(\d+)$/', $utility, $m) === 1) {
            return ['--tw-ring-offset-width' => $m[1] . 'px'];
        }

        // Elmosás
        if (preg_match('/^blur(?:-(.+))?$/', $utility, $m) === 1) {
            $key = ($m[1] ?? '') !== '' ? $m[1] : 'DEFAULT';

            if (isset($this->tokens['blur'][$key])) {
                $value = $this->tokens['blur'][$key];

                return [
                    '--tw-blur' => $value === '' ? ' ' : 'blur(' . $value . ')',
                    'filter' =>
                        'var(--tw-blur) var(--tw-brightness) var(--tw-contrast) var(--tw-grayscale)'
                        . ' var(--tw-hue-rotate) var(--tw-invert) var(--tw-saturate) var(--tw-sepia)'
                        . ' var(--tw-drop-shadow)',
                ];
            }
        }

        // Háttér elmosása (üveghatás)
        if (preg_match('/^backdrop-blur(?:-(.+))?$/', $utility, $m) === 1) {
            $key = ($m[1] ?? '') !== '' ? $m[1] : 'DEFAULT';

            if (isset($this->tokens['blur'][$key])) {
                $value = $this->tokens['blur'][$key];
                $filter =
                    'var(--tw-backdrop-blur) var(--tw-backdrop-brightness) var(--tw-backdrop-contrast)'
                    . ' var(--tw-backdrop-grayscale) var(--tw-backdrop-hue-rotate)'
                    . ' var(--tw-backdrop-invert) var(--tw-backdrop-opacity)'
                    . ' var(--tw-backdrop-saturate) var(--tw-backdrop-sepia)';

                return [
                    '--tw-backdrop-blur' => $value === '' ? ' ' : 'blur(' . $value . ')',
                    '-webkit-backdrop-filter' => $filter,
                    'backdrop-filter' => $filter,
                ];
            }
        }

        return null;
    }

    /**
     * Rács: grid-cols-3, col-span-2, grid-rows-2, row-span-2.
     */
    private function resolveGrid(string $utility): ?array
    {
        if (preg_match('/^grid-(cols|rows)-(\d+)$/', $utility, $m) === 1) {
            $property = $m[1] === 'cols' ? 'grid-template-columns' : 'grid-template-rows';
            return [$property => 'repeat(' . $m[2] . ',minmax(0,1fr))'];
        }

        if (preg_match('/^(col|row)-span-(\d+)$/', $utility, $m) === 1) {
            $property = $m[1] === 'col' ? 'grid-column' : 'grid-row';
            return [$property => 'span ' . $m[2] . '/span ' . $m[2]];
        }

        if (preg_match('/^(col|row)-span-full$/', $utility, $m) === 1) {
            $property = $m[1] === 'col' ? 'grid-column' : 'grid-row';
            return [$property => '1/-1'];
        }

        if (preg_match('/^order-(\d+)$/', $utility, $m) === 1) {
            return ['order' => $m[1]];
        }

        if (preg_match('/^z-(\d+)$/', $utility, $m) === 1) {
            return ['z-index' => $m[1]];
        }

        if (preg_match('/^z-\[(.+)\]$/', $utility, $m) === 1) {
            return ['z-index' => $m[1]];
        }

        return null;
    }

    /**
     * Transzformációk és időzítés: scale-105, rotate-45, duration-300, ease-out.
     */
    private function resolveTransforms(string $utility): ?array
    {
        $transform =
            'translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate))'
            . ' skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y))'
            . ' scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))';

        if (preg_match('/^scale-(\d+)$/', $utility, $m) === 1) {
            $value = $this->decimal((int) $m[1] / 100);
            return [
                '--tw-scale-x' => $value,
                '--tw-scale-y' => $value,
                'transform' => $transform,
            ];
        }

        if (preg_match('/^scale-\[(.+)\]$/', $utility, $m) === 1) {
            return [
                '--tw-scale-x' => $m[1],
                '--tw-scale-y' => $m[1],
                'transform' => $transform,
            ];
        }

        if (preg_match('/^(-?)rotate-(\d+)$/', $utility, $m) === 1) {
            return [
                '--tw-rotate' => $m[1] . $m[2] . 'deg',
                'transform' => $transform,
            ];
        }

        if (preg_match('/^(-?)translate-(x|y)-(.+)$/', $utility, $m) === 1) {
            $size = $this->spacingValue($m[3]) ?? $this->fractionValue($m[3]);

            if ($size === null) {
                return null;
            }

            if ($m[1] === '-') {
                $size = $this->negate($size);
            }

            return [
                '--tw-translate-' . $m[2] => $size,
                'transform' => $transform,
            ];
        }

        if (preg_match('/^duration-(\d+)$/', $utility, $m) === 1) {
            return ['transition-duration' => $this->milliseconds((int) $m[1])];
        }

        if (preg_match('/^delay-(\d+)$/', $utility, $m) === 1) {
            return ['transition-delay' => $this->milliseconds((int) $m[1])];
        }

        if (preg_match('/^ease-(.+)$/', $utility, $m) === 1
            && isset($this->tokens['transitionTimingFunction'][$m[1]])) {
            return ['transition-timing-function' => $this->tokens['transitionTimingFunction'][$m[1]]];
        }

        return null;
    }

    /**
     * Vegyes: aspect-square, aspect-[4/3], scroll-mt-24.
     */
    private function resolveMisc(string $utility): ?array
    {
        if ($utility === 'aspect-square') {
            return ['aspect-ratio' => '1/1'];
        }

        if ($utility === 'aspect-video') {
            return ['aspect-ratio' => '16/9'];
        }

        if ($utility === 'aspect-auto') {
            return ['aspect-ratio' => 'auto'];
        }

        if (preg_match('/^aspect-\[(.+)\]$/', $utility, $m) === 1) {
            return ['aspect-ratio' => $m[1]];
        }

        if (preg_match('/^scroll-m(t|b|l|r|x|y)?-(.+)$/', $utility, $m) === 1) {
            $size = $this->spacingValue($m[2]);

            if ($size === null) {
                return null;
            }

            return match ($m[1] ?? '') {
                '' => ['scroll-margin' => $size],
                't' => ['scroll-margin-top' => $size],
                'b' => ['scroll-margin-bottom' => $size],
                'l' => ['scroll-margin-left' => $size],
                'r' => ['scroll-margin-right' => $size],
                'x' => ['scroll-margin-left' => $size, 'scroll-margin-right' => $size],
                'y' => ['scroll-margin-top' => $size, 'scroll-margin-bottom' => $size],
                default => null,
            };
        }

        return null;
    }

    // =========================================================================
    // Értékfeloldó segédek
    // =========================================================================

    /**
     * Méret feloldása a skáláról vagy szögletes zárójelből.
     */
    private function spacingValue(string $value): ?string
    {
        if (isset($this->tokens['spacing'][$value])) {
            return $this->tokens['spacing'][$value];
        }

        if ($value === 'full') {
            return '100%';
        }

        return $this->arbitraryValue($value);
    }

    /**
     * Törtérték: w-1/2 → 50%.
     */
    private function fractionValue(string $value): ?string
    {
        if (preg_match('#^(\d+)/(\d+)$#', $value, $m) !== 1) {
            return null;
        }

        $percent = ((int) $m[1] / (int) $m[2]) * 100;

        return $this->decimal(round($percent, 6)) . '%';
    }

    /**
     * Szögletes zárójeles, kézzel megadott érték: mt-[1.85rem] → 1.85rem.
     *
     * Az aláhúzás szóközzé alakul, ahogyan a jelölésben szokás.
     */
    private function arbitraryValue(string $value): ?string
    {
        if (preg_match('/^\[(.+)\]$/', $value, $m) !== 1) {
            return null;
        }

        return str_replace('_', ' ', $m[1]);
    }

    /**
     * Szín feloldása, opcionális áttetszőséggel.
     *
     * Példák: white, sand-200, black/5, billiard-gold-400/30, [#1877F2]/10
     *
     * Megjegyzés: a tömör színt közvetlenül írjuk ki, nem az rgb()+CSS
     * változó formában. Az eredmény megjelenésben azonos; a különálló
     * bg-opacity-* jelölést a projekt nem használja, helyette a /N alakot.
     */
    private function colorValue(string $token): ?string
    {
        $alpha = null;

        if (preg_match('#^(.+)/(\d+)$#', $token, $m) === 1) {
            $token = $m[1];
            $alpha = (int) $m[2] / 100;
        }

        $hex = $this->arbitraryValue($token);

        if ($hex === null) {
            $hex = $this->lookupColor($token);
        }

        if ($hex === null) {
            return null;
        }

        if ($alpha === null) {
            return $hex;
        }

        $rgb = $this->hexToRgb($hex);

        if ($rgb === null) {
            return $hex;
        }

        return 'rgba(' . implode(',', $rgb) . ',' . $this->decimal($alpha) . ')';
    }

    /**
     * Színnév feloldása a tokenekből.
     */
    private function lookupColor(string $token): ?string
    {
        $colors = $this->tokens['colors'];

        if (isset($colors[$token]) && is_string($colors[$token])) {
            return $colors[$token];
        }

        // Skálázott szín: <név>-<árnyalat>
        if (preg_match('/^(.+)-(\d+)$/', $token, $m) === 1
            && isset($colors[$m[1]][$m[2]])) {
            return $colors[$m[1]][$m[2]];
        }

        return null;
    }

    /**
     * Egy szín teljesen átlátszó változata a színátmenetekhez.
     */
    private function transparentVersion(string $token): string
    {
        $base = preg_replace('#/\d+$#', '', $token) ?? $token;
        $hex = $this->arbitraryValue($base) ?? $this->lookupColor($base);

        if ($hex === null || $hex === 'transparent') {
            return 'transparent';
        }

        $rgb = $this->hexToRgb($hex);

        return $rgb === null ? 'transparent' : 'rgba(' . implode(',', $rgb) . ',0)';
    }

    /**
     * Hexadecimális szín átalakítása RGB komponensekre.
     *
     * @return array{0:int,1:int,2:int}|null
     */
    private function hexToRgb(string $hex): ?array
    {
        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $hex, $m) !== 1) {
            return null;
        }

        $value = $m[1];

        if (strlen($value) === 3) {
            $value = $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
        }

        return [
            (int) hexdec(substr($value, 0, 2)),
            (int) hexdec(substr($value, 2, 2)),
            (int) hexdec(substr($value, 4, 2)),
        ];
    }

    /**
     * Méret előjelének megfordítása.
     */
    private function negate(string $size): string
    {
        if ($size === '0px' || $size === '0') {
            return $size;
        }

        if (str_starts_with($size, '-')) {
            return substr($size, 1);
        }

        // A vezető nulla elhagyható: a -.25rem rövidebb és azonos jelentésű
        return '-' . preg_replace('/^0\./', '.', $size);
    }

    /**
     * Szám tömör CSS alakja: a vezető nulla és a záró nullák elmaradnak.
     */
    private function decimal(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');

        if ($text === '' || $text === '-') {
            return '0';
        }

        // A .5 rövidebb, mint a 0.5, és a böngészők ugyanúgy értik
        return preg_replace('/^(-?)0\./', '$1.', $text) ?? $text;
    }

    /**
     * Időtartam másodpercben, tömör alakban: 300 → .3s
     */
    private function milliseconds(int $value): string
    {
        return $this->decimal($value / 1000) . 's';
    }
}
