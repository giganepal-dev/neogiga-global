<?php
namespace App\Services\Marketing;
class TransactionalEmailService { public function __construct(private EmailQueueService $queue) {} public function queue(string $email, string $subject, string $body, array $meta=[]): int { return $this->queue->queue($email,$subject,$body,'transactional',$meta); } }
