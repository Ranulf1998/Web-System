<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $ownerEmail,
        public string $loginUrl,
        public string $generatedPassword
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Your BrewCloud shop has been approved')
            ->view('emails.tenant.approved');
    }
}
