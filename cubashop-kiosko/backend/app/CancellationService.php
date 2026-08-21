<?php
declare(strict_types=1);

final class CancellationService
{
    public function __construct(private PDO $db) {}

    public function void(int $actorId, int $saleId): void
    {
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare("SELECT id,worker_id,total_cup,status FROM sales WHERE id=? FOR UPDATE");
            $q->execute([$saleId]); $sale=$q->fetch();
            if (!$sale || $sale['status'] !== 'completed') throw new InvalidArgumentException('Venta inexistente o ya anulada.');

            $q=$this->db->prepare('SELECT product_id,quantity FROM sale_items WHERE sale_id=?');
            $q->execute([$saleId]); $items=$q->fetchAll();
            $stock=$this->db->prepare('UPDATE products SET stock=stock+? WHERE id=?');
            foreach ($items as $item) $stock->execute([(float)$item['quantity'],(int)$item['product_id']]);

            $q=$this->db->prepare("UPDATE sales SET status='voided',voided_at=CURRENT_TIMESTAMP WHERE id=?");
            $q->execute([$saleId]);
            $audit=$this->db->prepare('INSERT INTO audit_log (user_id,action,entity_type,entity_id,details) VALUES (?,?,?,?,?)');
            $audit->execute([$actorId,'sale.voided','sale',$saleId,json_encode(['worker_id'=>(int)$sale['worker_id'],'total_cup'=>(float)$sale['total_cup'],'restored_items'=>count($items)],JSON_UNESCAPED_UNICODE)]);
            $this->db->commit();
        } catch(Throwable $e) { $this->db->rollBack(); throw $e; }
    }
}
