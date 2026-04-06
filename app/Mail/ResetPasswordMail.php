<?php
// app/Mail/ResetPasswordMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public int $code;

    public function __construct(string $name, int $code)
    {
        $this->name = $name;
        $this->code = $code;
    }

    public function build(): self
    {
        return $this->subject('Password Reset Code')
            ->view('emails.reset_password');
    }
}
