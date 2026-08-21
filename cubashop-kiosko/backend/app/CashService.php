<?php
declare(strict_types=1);

final class CashService
{
    public function __construct(private PDO $db) {}

    public function open(int $workerId, float $opening): int
    {
        if ($opening < 0) throw new InvalidArgumentException('Monto inicial inválido.');
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare("SELECT id FROM cash_sessions WHERE worker_id = ? AND status = 'open' FOR UPDATE");
            $q->execute([$workerId]);
            if ($q->fetch()) throw new InvalidArgumentException('El trabajador ya tiene una caja abierta.');
            $q = $this->db->prepare('INSERT INTO cash_sessions (worker_id, opening_amount_cup) VALUES (?,?)');
            $q->execute([$workerId, round($opening,2)]);
            $id = (int)$this->db->lastInsertId();
            $this->audit($workerId, 'cash.opened', $id, ['opening_cup'=>round($opening,2)]);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function close(int $workerId, int $sessionId, float $closing): void
    {
        if ($closing < 0) throw new InvalidArgumentException('Monto de cierre inválido.');
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare("SELECT id FROM cash_sessions WHERE id = ? AND worker_id = ? AND status = 'open' FOR UPDATE");
            $q->execute([$sessionId,$workerId]);
            if (!$q->fetch()) throw new InvalidArgumentException('Caja no encontrada o ya cerrada.');
            $q = $this->db->prepare("UPDATE cash_sessions SET closing_amount_cup=?, status='closed', closed_at=CURRENT_TIMESTAMP WHERE id=?");
            $q->execute([round($closing,2),$sessionId]);
            $this->audit($workerId, 'cash.closed', $sessionId, ['closing_cup'=>round($closing,2)]);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function current(int $workerId): ?array
    {
        $q=$this->db->prepare("SELECT id,opening_amount_cup,status,opened_at FROM cash_sessions WHERE worker_id=? AND status='open' ORDER BY id DESC LIMIT 1");
        $q->execute([$workerId]); return $q->fetch() ?: null;
    }

    private function audit(int $userId,string $action,int $entityId,array $details):void {
        $q=$this->db->prepare('INSERT INTO audit_log (user_id,action,entity_type,entity_id,details) VALUES (?,?,?,?,?)');
        $q->execute([$userId,$action,'cash_session',$entityId,json_encode($details,JSON_UNESCAPED_UNICODE)]);
    }
}
