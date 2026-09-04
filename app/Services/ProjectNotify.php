<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\ClientTicket;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketDeadlineNotification;
use App\Support\SafeMail;

class ProjectNotify
{
    public static function created(ClientTicket $ticket): void
    {
        $order  = $ticket->order;
        $fs     = $order->frontSeller ?? $order->seller;
        $pm     = $order->ownerSeller;
        $admins = Admin::where('role', 'admin')->get();

        if ($fs) {
            SafeMail::notify($fs, new TicketCreatedNotification($ticket), 'ticket created', [
                'ticket_id' => $ticket->id,
            ]);
        }

        if ($pm && $pm->id !== ($fs->id ?? null)) {
            SafeMail::notify($pm, new TicketCreatedNotification($ticket), 'ticket created', [
                'ticket_id' => $ticket->id,
            ]);
        }

        SafeMail::notify($admins, new TicketCreatedNotification($ticket), 'ticket created', [
            'ticket_id' => $ticket->id,
        ]);
    }

    public static function deadline(ClientTicket $ticket, string $when): void
    {
        $fs = $ticket->order->frontSeller;
        $pm = $ticket->order->ownerSeller;
        $admins = Admin::where('role', 'admin')->get();

        if ($fs) {
            SafeMail::notify($fs, new TicketDeadlineNotification($ticket, $when), 'ticket deadline', [
                'ticket_id' => $ticket->id,
                'when'      => $when,
            ]);
        }

        if ($pm) {
            SafeMail::notify($pm, new TicketDeadlineNotification($ticket, $when), 'ticket deadline', [
                'ticket_id' => $ticket->id,
                'when'      => $when,
            ]);
        }

        SafeMail::notify($admins, new TicketDeadlineNotification($ticket, $when), 'ticket deadline', [
            'ticket_id' => $ticket->id,
            'when'      => $when,
        ]);
    }
}
