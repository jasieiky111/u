<?php
declare(strict_types=1);

final class InventoryService
{
    public function __construct(private PDO $db) {}

    public function create(int $userId, string $sku, string $name, float $price, float $stock): int
    {
        if ($sku === '' || $name === '' || $price < 0 || $stock < 0) throw new InvalidArgumentException('Datos de producto inválidos.');
        $q=$this->db->prepare('INSERT INTO products (sku,name,price_cup,stock) VALUES (?,?,?,?)');
        $q->execute([$sku,$name,round($price,2),$stock]);
        $id=(int)$this->db->lastInsertId();
        $this->audit($userId,'product.created',$id,['sku'=>$sku,'stock'=>$stock]);
        return $id;
    }

    public function adjust(int $userId, int $productId, float $delta): void
    {
        if ($delta == 0) throw new InvalidArgumentException('El ajuste no puede ser cero.');
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare('SELECT stock FROM products WHERE id=? AND active=1 FOR UPDATE');
            $q->execute([$productId]); $p=$q->fetch();
            if (!$p || (float)$p['stock'] + $delta < 0) throw new InvalidArgumentException('Producto inexistente o stock insuficiente.');
            $q=$this->db->prepare('UPDATE products SET stock=stock+? WHERE id=?'); $q->execute([$delta,$productId]);
            $this->audit($userId,'inventory.adjusted',$productId,['delta'=>$delta,'new_stock'=>(float)$p['stock']+$delta]);
            $this->db->commit();
        } catch(Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    private function audit(int $userId,string $action,int $id,array $details):void {
        $q=$this->db->prepare('INSERT INTO audit_log (user_id,action,entity_type,entity_id,details) VALUES (?,?,?,?,?)');
        $q->execute([$userId,$action,'product',$id,json_encode($details,JSON_UNESCAPED_UNICODE)]);
    }
}
