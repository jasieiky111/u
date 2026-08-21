<?php
declare(strict_types=1);

final class KioskoService
{
    public function __construct(private PDO $db) {}

    public function login(string $username, string $password): ?array
    {
        $s = $this->db->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ? AND active = 1 LIMIT 1');
        $s->execute([$username]);
        $u = $s->fetch();
        if (!$u || !password_verify($password, $u['password_hash'])) return null;
        unset($u['password_hash']);
        return $u;
    }

    public function products(): array
    {
        return $this->db->query('SELECT id, sku, name, price_cup, stock, active FROM products WHERE active = 1 ORDER BY name')->fetchAll();
    }

    public function createSale(int $workerId, array $items, string $payment, ?string $reference = null): int
    {
        if (!in_array($payment, ['efectivo', 'transfermovil'], true)) throw new InvalidArgumentException('Método de pago inválido.');
        if ($payment === 'transfermovil' && trim((string)$reference) === '') throw new InvalidArgumentException('Referencia de Transfermóvil requerida.');
        if (!$items) throw new InvalidArgumentException('La venta requiere productos.');

        $this->db->beginTransaction();
        try {
            $total = 0.0;
            $lines = [];
            foreach ($items as $item) {
                $id = (int)($item['product_id'] ?? 0);
                $qty = (float)($item['quantity'] ?? 0);
                if ($id < 1 || $qty <= 0) throw new InvalidArgumentException('Producto o cantidad inválidos.');
                $s = $this->db->prepare('SELECT id, price_cup, stock FROM products WHERE id = ? AND active = 1 FOR UPDATE');
                $s->execute([$id]);
                $p = $s->fetch();
                if (!$p || (float)$p['stock'] < $qty) throw new InvalidArgumentException('Stock insuficiente.');
                $subtotal = round((float)$p['price_cup'] * $qty, 2);
                $total += $subtotal;
                $lines[] = [$id, $qty, (float)$p['price_cup'], $subtotal];
            }

            $s = $this->db->prepare('INSERT INTO sales (worker_id,total_cup,payment_method,transfer_reference) VALUES (?,?,?,?)');
            $s->execute([$workerId, round($total, 2), $payment, $reference]);
            $saleId = (int)$this->db->lastInsertId();

            $line = $this->db->prepare('INSERT INTO sale_items (sale_id,product_id,quantity,unit_price_cup,subtotal_cup) VALUES (?,?,?,?,?)');
            $stock = $this->db->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($lines as [$productId, $qty, $unit, $subtotal]) {
                $line->execute([$saleId, $productId, $qty, $unit, $subtotal]);
                $stock->execute([$qty, $productId]);
            }

            $audit = $this->db->prepare('INSERT INTO audit_log (user_id,action,entity_type,entity_id,details) VALUES (?,?,?,?,?)');
            $audit->execute([$workerId, 'sale.created', 'sale', $saleId, json_encode(['total_cup' => round($total, 2), 'payment' => $payment], JSON_UNESCAPED_UNICODE)]);
            $this->db->commit();
            return $saleId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
