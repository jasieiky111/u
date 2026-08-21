<?php
declare(strict_types=1);

final class ReportService
{
    public function __construct(private PDO $db) {}

    public function salesSummary(?string $from, ?string $to): array
    {
        $where = ['s.status = "completed"']; $params=[];
        if ($from) { $where[]='s.created_at >= ?'; $params[]=$from.' 00:00:00'; }
        if ($to) { $where[]='s.created_at <= ?'; $params[]=$to.' 23:59:59'; }
        $sql='SELECT s.worker_id,u.username,COUNT(*) sales_count,COALESCE(SUM(s.total_cup),0) total_cup,SUM(s.payment_method="efectivo") cash_sales,SUM(s.payment_method="transfermovil") transfer_sales FROM sales s JOIN users u ON u.id=s.worker_id WHERE '.implode(' AND ',$where).' GROUP BY s.worker_id,u.username ORDER BY total_cup DESC';
        $q=$this->db->prepare($sql); $q->execute($params); return $q->fetchAll();
    }

    public function audit(int $limit=100): array
    {
        $limit=max(1,min($limit,500));
        return $this->db->query("SELECT a.id,a.user_id,u.username,a.action,a.entity_type,a.entity_id,a.details,a.created_at FROM audit_log a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT {$limit}")->fetchAll();
    }
}
