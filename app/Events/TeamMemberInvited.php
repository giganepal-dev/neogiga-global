<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamMemberInvited
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $member;
    public $invitation;

    public function __construct($member, $invitation)
    {
        $this->member = $member;
        $this->invitation = $invitation;
    }
}
