<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantRegistrationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Tenant $tenant)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Registration in progress - BrewCloud')
            ->view('emails.tenant.registration-received');
    }
}
