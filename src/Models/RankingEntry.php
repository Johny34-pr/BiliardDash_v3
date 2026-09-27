<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Ranglista pontszámok versenyenként.
 *
 * A LEGFONTOSABB tervezési döntés: a ranglista NEM tárolt összeg, hanem
 * ezekből a sorokból SZÁMOLT eredmény. Nincs olyan oszlop, ami egy játékos
 * összpontszámát tartalmazná.
 *
 * Miért: egy tárolt összeg elcsúszhat a részletektől - ha egy pontszám
 * módosul, törlődik, vagy egy verseny eltűnik, az összeget is frissíteni
 * kellene, és egy kimaradt frissítés után a ranglista csendben hibás lenne.
 * Számolt eredménynél ez nem fordulhat elő: minden megjelenítés a friss
 * pontokból áll össze, tehát a szerkesztés azonnal és mindenhol érvényesül.
 *
 * A játékost a NEVE azonosítja, nem a fiókja: a klub versenyein fiók nélküli
 * játékos is indul, és a pontjai ugyanúgy beleszámítanak a szezonba. A
 * user_id csak kapcsolat, ha van fiók.
 */
class RankingEntry
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Egy verseny pontszámai, a legtöbb ponttal az élen.
     *
     * @return array<array{id:string, competition_id:string, user_id:?string, player_name:string, points:int, place:?int}>
     */
    public function findByCompetition(string $competitionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, competition_id, user_id, player_name, points, place
             FROM ranking_entries
             WHERE competition_id = :competition_id
             ORDER BY points DESC, player_name ASC'
        );
        $stmt->execute([':competition_id' => $competitionId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array{id:string, competition_id:string, user_id:?string, player_name:string, points:int, place:?int}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, competition_id, user_id, player_name, points, place
             FROM ranking_entries
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * Egy szezon összes pontszáma, versenyenként és játékosonként.
     *
     * Ebből áll össze a nyilvános táblázat: minden sor egy pontszám, a
     * hozzá tartozó verseny adataival. A csoportosítást és az összesítést a
     * szolgáltatás végzi PHP-ben, mert így egyetlen lekérdezésből készül el
     * a teljes táblázat - versenyenkénti külön kérés nélkül.
     *
     * A szezon nélküli versenyek szándékosan kimaradnak: azok nem tartoznak
     * egyetlen pontversenyhez sem.
     *
     * @return array<array{competition_id:string, competition_name:string, competition_date:string, player_name:string, user_id:?string, points:int, place:?int}>
     */
    public function findBySeason(string $seasonId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.competition_id,
                    c.name AS competition_name,
                    c.date AS competition_date,
                    r.player_name,
                    r.user_id,
                    r.points,
                    r.place
             FROM ranking_entries r
             INNER JOIN competitions c ON c.id = r.competition_id
             WHERE c.season_id = :season_id
             ORDER BY c.date ASC, r.points DESC, r.player_name ASC'
        );
        $stmt->execute([':season_id' => $seasonId]);

        return $stmt->fetchAll();
    }

    /**
     * Van-e már pontszám ezen a néven ebben a versenyben (a megadotton kívül).
     *
     * A (competition_id, player_name) páron egyediségi kulcs áll; az
     * ütközést a mentés előtt szeretnénk jelezni, nem adatbázis-hibaként.
     */
    public function playerExists(string $competitionId, string $playerName, ?string $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM ranking_entries
                WHERE competition_id = :competition_id AND player_name = :player_name';
        $params = [
            ':competition_id' => $competitionId,
            ':player_name' => $playerName,
        ];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params[':except_id'] = $exceptId;
        }

        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }

    public function create(
        string $id,
        string $competitionId,
        string $playerName,
        int $points,
        ?int $place = null,
        ?string $userId = null
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO ranking_entries (id, competition_id, user_id, player_name, points, place)
             VALUES (:id, :competition_id, :user_id, :player_name, :points, :place)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':competition_id' => $competitionId,
            ':user_id' => $userId,
            ':player_name' => $playerName,
            ':points' => $points,
            ':place' => $place,
        ]);
    }

    public function update(string $id, string $playerName, int $points, ?int $place): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ranking_entries
             SET player_name = :player_name, points = :points, place = :place
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':player_name' => $playerName,
            ':points' => $points,
            ':place' => $place,
        ]);
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ranking_entries WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Egy szezonban pontot szerzett játékosok száma.
     *
     * A nyilvános oldal fejléce ebből tudja, érdemes-e megjeleníteni a
     * táblázatot, vagy üres állapotot kell mutatni.
     */
    public function countPlayersInSeason(string $seasonId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(DISTINCT r.player_name)
             FROM ranking_entries r
             INNER JOIN competitions c ON c.id = r.competition_id
             WHERE c.season_id = :season_id'
        );
        $stmt->execute([':season_id' => $seasonId]);

        return (int) $stmt->fetchColumn();
    }
}
