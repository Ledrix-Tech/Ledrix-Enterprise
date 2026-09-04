<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendClientAccountCRMLink extends Notification
{

    public $client;
    public $password;
    public $loginUrl;

    public function __construct($client, $password, $loginUrl)
    {
        $this->client = $client;
        $this->password = $password;
        $this->loginUrl = $loginUrl;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your CRM Portal Access')
            ->view('emails.client-account-access', [
                'client' => $this->client,
                'password' => $this->password,
                'loginUrl' => $this->loginUrl,
            ]);
    }
}
