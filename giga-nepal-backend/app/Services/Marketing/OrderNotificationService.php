<?php
namespace App\Services\Marketing;
class OrderNotificationService { public function __construct(private TransactionalEmailService $email) {} public function orderStatus(string $email, string $orderNumber, string $status): int { return $this->email->queue($email, "Order {$orderNumber} update", "Your order {$orderNumber} is now {$status}.", ['order_number'=>$orderNumber,'status'=>$status]); } }
