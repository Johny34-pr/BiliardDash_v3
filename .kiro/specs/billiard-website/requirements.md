# Requirements Document

## Introduction

Ez a dokumentum a Magyar Biliárd Weboldal követelményeit tartalmazza. A weboldal célja egy professzionális, modern megjelenésű és felhasználóbarát platform létrehozása, amely lehetővé teszi a biliárd közösség számára a hírek böngészését, fotógaléria megtekintését és versenyekre való egyszerű nevezést.

## Glossary

- **Weboldal**: A magyar biliárd közösség számára készült webes alkalmazás
- **Látogató**: A weboldal bármely felhasználója, aki böngészi az oldalt
- **Adminisztrátor**: A weboldal tartalomkezelésért felelős személy, aki híreket és képeket tölt fel
- **Nevező**: A versenyekre jelentkező felhasználó
- **Hírmodul**: A weboldal azon komponense, amely a hírek megjelenítéséért és kezeléséért felelős
- **Galéria**: A weboldal képgaléria funkciója, amely albumokba rendezve jeleníti meg a fotókat
- **Nevezési_Rendszer**: A versenyekre való online jelentkezést biztosító felület
- **Album**: A galéria egy logikai egysége, amely összetartozó képeket csoportosít

## Requirements

### Requirement 1: Hírek megjelenítése

**User Story:** Látogatóként szeretném böngészni a biliárd híreket, hogy naprakész legyek a közösség eseményeivel kapcsolatban.

#### Acceptance Criteria

1. THE Weboldal SHALL megjelenítse a híreket fordított időrendi sorrendben a főoldalon, hírenként feltüntetve a publikálás dátumát és a hír címét
2. WHEN egy Látogató megnyitja a főoldalt, THE Hírmodul SHALL betöltse és megjelenítse a legfeljebb 10 legfrissebb hírt, mindegyiket címmel, publikálási dátummal és maximum 200 karakter hosszúságú összefoglalóval
3. WHEN egy Látogató rákattint egy hír címére, THE Hírmodul SHALL megjelenítse a hír teljes tartalmát egy külön oldalon, feltüntetve a hír címét, publikálási dátumát és a teljes szöveget
4. IF egyetlen hír sem érhető el, THEN THE Hírmodul SHALL egy üzenetet jelenítsen meg, amely tájékoztatja a Látogatót, hogy jelenleg nincsenek hírek
5. IF egy hír nem tölthető be, THEN THE Hírmodul SHALL egy hibaüzenetet jelenítsen meg a Látogató számára, amely jelzi, hogy a tartalom átmenetileg nem elérhető

### Requirement 2: Hírek kezelése

**User Story:** Adminisztrátorként szeretnék híreket létrehozni és kezelni, hogy a közösséget informálhassam az aktuális eseményekről.

#### Acceptance Criteria

1. WHEN az Adminisztrátor új hírt hoz létre, THE Hírmodul SHALL mentse a hírt címmel (maximum 200 karakter, kötelező), tartalommal (kötelező) és publikálási dátummal
2. WHEN az Adminisztrátor szerkeszt egy hírt, THE Hírmodul SHALL frissítse a hír tartalmát és megőrizze az eredeti publikálási dátumot
3. WHEN az Adminisztrátor töröl egy hírt, THE Hírmodul SHALL megerősítő párbeszédablakot jelenítsen meg, és csak a megerősítés után távolítsa el a hírt a megjelenített hírek listájáról
4. THE Hírmodul SHALL lehetővé tegye félkövér, dőlt, felsorolás és hivatkozás formázási lehetőségek használatát a hír tartalmánál
5. IF az Adminisztrátor nem tölti ki a cím vagy tartalom mezőt, THEN THE Hírmodul SHALL hibaüzenetet jelenítsen meg és ne mentse a hírt

### Requirement 3: Képgaléria megjelenítése

**User Story:** Látogatóként szeretném megtekinteni a biliárd eseményekről készült fotókat, hogy visszanézhessem a versenyeket és rendezvényeket.

#### Acceptance Criteria

1. THE Galéria SHALL albumokba rendezve jelenítse meg a képeket, az albumokat létrehozási dátum szerinti fordított sorrendben listázva
2. WHEN egy Látogató megnyit egy Albumot, THE Galéria SHALL rácsos elrendezésben megjelenítse az Album összes képének bélyegképét
3. WHEN egy Látogató rákattint egy bélyegképre, THE Galéria SHALL teljes méretben megjelenítse a képet egy lightbox nézetben
4. WHILE a lightbox nézet aktív, THE Galéria SHALL lehetővé tegye a képek közötti navigálást előre és hátra nyilakkal, valamint az X gombbal vagy az Escape billentyűvel való bezárást
5. WHILE a lightbox nézet aktív és a Látogató az első képnél tartózkodik, THE Galéria SHALL letiltsa a hátra nyilat, és az utolsó képnél a előre nyilat
6. THE Galéria SHALL minden Albumnál megjelenítse az Album nevét, borítóképét és a benne lévő képek számát
7. IF egy kép nem tölthető be, THEN THE Galéria SHALL egy helyőrző képet jelenítsen meg hibajelzéssel

### Requirement 4: Galéria kezelése

**User Story:** Adminisztrátorként szeretnék albumokat létrehozni és képeket feltölteni, hogy a közösség megtekinthesse az eseményekről készült fotókat.

#### Acceptance Criteria

1. WHEN az Adminisztrátor új Albumot hoz létre, THE Galéria SHALL mentse az Albumot névvel (1-100 karakter) és létrehozási dátummal
2. WHEN az Adminisztrátor képeket tölt fel egy Albumba, THE Galéria SHALL fogadja a JPEG és PNG formátumú képfájlokat, maximum 10 MB méretig fájlonként
3. WHEN az Adminisztrátor képet tölt fel, THE Galéria SHALL automatikusan létrehozza a kép bélyegképét 200x200 pixel méretben
4. WHEN az Adminisztrátor töröl egy képet, THE Galéria SHALL eltávolítsa a képet és a hozzá tartozó bélyegképet az Albumból
5. IF a feltöltött fájl nem támogatott formátumú, THEN THE Galéria SHALL visszautasítsa a feltöltést és tájékoztassa az Adminisztrátort a támogatott formátumokról (JPEG, PNG)
6. IF a feltöltött fájl mérete meghaladja a 10 MB-ot, THEN THE Galéria SHALL visszautasítsa a feltöltést és tájékoztassa az Adminisztrátort a maximális megengedett fájlméretről
7. IF az Adminisztrátor üres vagy csak szóközökből álló Album nevet ad meg, THEN THE Galéria SHALL visszautasítsa az Album létrehozását és hibaüzenetet jelenítsen meg a név megadásának szükségességéről

### Requirement 5: Versenyekre való nevezés

**User Story:** Nevezőként szeretnék egyszerűen és gyorsan nevezni a biliárd versenyekre, hogy részt vehessek a megmérettetéseken.

#### Acceptance Criteria

1. THE Nevezési_Rendszer SHALL megjelenítse a nyitott nevezésű versenyeket listában a verseny nevével, nevezési határidővel és a verseny dátumával, a verseny dátuma szerinti növekvő sorrendben, ahol nyitott nevezésű az a verseny, amelynek nevezési határideje még nem járt le
2. WHEN egy Nevező kiválaszt egy versenyt, THE Nevezési_Rendszer SHALL megjelenítse a nevezési űrlapot a szükséges adatmezőkkel
3. WHEN egy Nevező kitölti és beküldi a nevezési űrlapot, THE Nevezési_Rendszer SHALL rögzítse a nevezést és a képernyőn visszaigazolást jelenítsen meg a verseny nevével és a beküldött adatok összefoglalójával
4. THE Nevezési_Rendszer SHALL a következő kötelező adatokat kérje a Nevezőtől: teljes név (maximum 100 karakter), e-mail cím (érvényes e-mail formátumban) és telefonszám
5. IF a Nevező nem tölt ki egy kötelező mezőt vagy az e-mail cím formátuma érvénytelen, THEN THE Nevezési_Rendszer SHALL az érintett mező mellett hibaüzenetet jelenítsen meg, amely jelzi a hiba okát
6. IF a nevezési határidő lejárt, THEN THE Nevezési_Rendszer SHALL megjelenítse hogy a nevezés lezárult és ne engedélyezze az űrlap beküldését
7. WHEN egy Nevező sikeresen nevez, THE Nevezési_Rendszer SHALL 5 percen belül visszaigazoló e-mailt küldjön a megadott e-mail címre, amely tartalmazza a verseny nevét, dátumát és a megadott nevezési adatokat
8. IF egy Nevező olyan versenyre próbál nevezni, amelyre már korábban regisztrált ugyanazzal az e-mail címmel, THEN THE Nevezési_Rendszer SHALL megjelenítse, hogy az adott e-mail címmel már történt nevezés erre a versenyre, és ne engedélyezze az ismételt nevezést

### Requirement 6: Versenyek kezelése

**User Story:** Adminisztrátorként szeretnék versenyeket létrehozni és a nevezéseket kezelni, hogy szervezetten bonyolíthassam le az eseményeket.

#### Acceptance Criteria

1. WHEN az Adminisztrátor új versenyt hoz létre, THE Nevezési_Rendszer SHALL rögzítse a versenyt névvel (maximum 100 karakter), dátummal, helyszínnel (maximum 200 karakter) és nevezési határidővel
2. WHEN az Adminisztrátor megtekinti egy verseny nevezéseit, THE Nevezési_Rendszer SHALL listázza az összes Nevezőt nevükkel, e-mail címükkel és telefonszámukkal
3. WHEN az Adminisztrátor exportálni kívánja a nevezési listát, THE Nevezési_Rendszer SHALL CSV formátumú fájlként biztosítsa a listát letöltésre
4. THE Nevezési_Rendszer SHALL megjelenítse az aktuális nevezők számát minden versenynél
5. IF az Adminisztrátor nem tölt ki egy kötelező mezőt a verseny létrehozásakor (név, dátum, helyszín, nevezési határidő), THEN THE Nevezési_Rendszer SHALL jelezze a hiányzó mező kitöltésének szükségességét és ne mentse a versenyt
6. WHEN az Adminisztrátor szerkeszt egy meglévő versenyt, THE Nevezési_Rendszer SHALL frissítse a verseny adatait és megőrizze a meglévő nevezéseket
7. WHEN az Adminisztrátor töröl egy versenyt, THE Nevezési_Rendszer SHALL eltávolítsa a versenyt és az összes hozzá tartozó nevezést a rendszerből

### Requirement 7: Reszponzív megjelenés

**User Story:** Látogatóként szeretném mobilon és tableten is kényelmesen használni a weboldalt, hogy bárhonnan elérhessem a tartalmakat.

#### Acceptance Criteria

1. THE Weboldal SHALL reszponzív elrendezést alkalmazzon három törésponttal: mobil (768px alatt), tablet (768px–1024px) és asztali (1024px felett), ahol az elrendezés elemei a rendelkezésre álló szélességhez igazodnak átfedés és vízszintes görgetés nélkül
2. WHILE a Weboldal mobil nézetben (768px alatti szélességen) jelenik meg, THE Weboldal SHALL hamburger menüt alkalmazzon a navigációhoz, amely megérintésre kinyitja a teljes navigációs menüt, és újbóli megérintésre bezárja azt
3. WHILE a lightbox nézet aktív és a képernyő szélessége 768px alatt van, THE Galéria SHALL balra és jobbra húzás (swipe) gesztussal biztosítsa a képek közötti navigálást
4. WHILE a Weboldal mobil nézetben (768px alatti szélességen) jelenik meg, THE Nevezési_Rendszer SHALL legalább 44x44 pixel méretű érintési célterülettel rendelkező beviteli mezőket és gombokat jelenítsen meg, és az űrlap mezői egymás alatt, egyoszlopos elrendezésben jelenjenek meg
5. THE Weboldal SHALL minden interaktív elemen (gombok, linkek, beviteli mezők) legalább 44x44 pixel érintési célterületet biztosítson érintőképernyős eszközökön

### Requirement 8: Modern megjelenés és navigáció

**User Story:** Látogatóként szeretnék egy professzionális megjelenésű, könnyen navigálható weboldalt használni, amely tükrözi a biliárd sport eleganciáját.

#### Acceptance Criteria

1. THE Weboldal SHALL egyértelmű navigációs menüt biztosítson a Hírek, Galéria és Nevezés oldalak eléréséhez, ahol minden menüpont egy kattintással elérhető
2. THE Weboldal SHALL egységes vizuális stílust alkalmazzon minden oldalon, konzisztens színvilággal (sötétzöld és arany színösszeállítás) és tipográfiával
3. WHEN egy Látogató bármely oldalon tartózkodik, THE Weboldal SHALL egyértelműen jelezze az aktuális oldal pozícióját a navigációban vizuálisan megkülönböztetett stílussal (eltérő háttérszín vagy aláhúzás)
4. THE Weboldal SHALL 3 másodpercen belül betöltse a főoldalt átlagos sávszélességű (10 Mbps) kapcsolaton, optimalizált képek és erőforrások alkalmazásával
