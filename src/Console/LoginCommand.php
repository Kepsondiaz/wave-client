<?php

namespace Alal\WaveClient\Console;

use Alal\WaveClient\Exceptions\OtpRequiredException;
use Alal\WaveClient\WaveManager;
use Illuminate\Console\Command;

class LoginCommand extends Command
{
    protected $signature = 'wave:login
                            {--phone= : Phone number in E.164 format (uses WAVE_PHONE env if omitted)}
                            {--pin=   : 4-digit PIN (uses WAVE_PIN env if omitted)}';

    protected $description = 'Authenticate with the Wave Business dashboard (phone → PIN → SMS code) and cache the session';

    public function handle(WaveManager $wave): int
    {
        $phone = $this->option('phone') ?? config('wave-client.phone');
        $pin   = $this->option('pin')   ?? config('wave-client.pin');

        if (!$phone) {
            $phone = $this->ask('Phone number (E.164, e.g. +2250700000000)');
        }
        if (!$pin) {
            $pin = $this->secret('4-digit PIN');
        }

        if (!$phone || !$pin) {
            $this->error('Phone and PIN are required.');
            return self::FAILURE;
        }

        $this->info('Sending phone + PIN to Wave…');

        try {
            $wave->auth()->login($phone, $pin);
            // login() always throws OtpRequiredException on success.
            // If we reach here something unexpected happened.
            $this->error('Unexpected: no OTP exception thrown.');
            return self::FAILURE;
        } catch (OtpRequiredException $e) {
            $this->info("SMS code sent to {$e->mobile}.");
        }

        $code = $this->ask('Enter the SMS code');

        if (!$code) {
            $this->error('No SMS code entered.');
            return self::FAILURE;
        }

        $wave->auth()->confirmSms($code);
        $this->info('Authenticated. Session cached.');

        return self::SUCCESS;
    }
}
