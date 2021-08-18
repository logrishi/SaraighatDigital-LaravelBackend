<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class PasswordResetNotification extends Notification
{
    use Queueable;

    public $token;
   
    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // $urlToResetForm = "http://192.168.43.26:80/saraighatDigital/public/api/login";
        // $urlToResetForm = "app://saraighatDigital";
        $urlToResetForm = "https://saraighatdigital.com/login";
        return (new MailMessage)
                    ->subject(Lang::get('Password Reset Notification'))
                ->line(Lang::get('Click on the button to reset the password'))
                ->action(Lang::get('Reset Password'), $urlToResetForm)
                ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.users.expire')]))
                ->line(Lang::get('If you did not request a password reset, no further action is required.'));
                // ->line(Lang::get('If you did not request a password reset, no further action is required. Token: ==>'. $this->token));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}