<?php

namespace App\Console\Commands;

use App\Models\SsoPartner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Onboards a new SSO partner (e.g. talkEHR) without any code changes: generates a random API key,
 * stores only its sha256 hash, and prints the raw key once — that's the value to hand the partner
 * out-of-band. It is never shown or logged again after this.
 */
#[Signature('sso:create-partner {name : A short slug identifying the partner, e.g. talkehr}')]
#[Description('Creates a new SSO partner and prints its one-time API key')]
class CreateSsoPartner extends Command
{
    public function handle(): int
    {
        $name = $this->argument('name');

        if (SsoPartner::where('name', $name)->exists()) {
            $this->components->error("A partner named \"{$name}\" already exists.");

            return self::FAILURE;
        }

        $apiKey = Str::random(64);

        SsoPartner::create([
            'name' => $name,
            'api_key_hash' => hash('sha256', $apiKey),
            'is_active' => true,
        ]);

        $this->components->info("SSO partner \"{$name}\" created.");
        $this->newLine();
        $this->line('API key (shown once — store it securely, it cannot be retrieved again):');
        $this->line("<fg=yellow>{$apiKey}</>");

        return self::SUCCESS;
    }
}
