<?php

namespace Alal\WaveClient\Auth;

use Alal\WaveClient\Contracts\SessionStore;
use Alal\WaveClient\Exceptions\AuthenticationException;
use Alal\WaveClient\Exceptions\OtpRequiredException;
use Alal\WaveClient\Http\WaveConnector;
use Alal\WaveClient\Http\WaveSession;

/**
 * Drives the three-step Wave Business login flow:
 *
 *   1. login($phone, $pin)      — startBusinessUserAuth → verifyPin
 *                                 throws OtpRequiredException when SMS is sent
 *   2. confirmSms($code)        — verifyAuthCode → session stored
 *
 * GraphQL mutations are taken verbatim from the captured browser requests.
 */
class Authenticator
{
    private const START_AUTH_MUTATION = <<<'GQL'
        mutation StartBusinessUserAuth_Mutation(
          $mobile: String!
        ) {
          startBusinessUserAuth(mobile: $mobile) {
            nextStep
          }
        }
        GQL;

    private const VERIFY_PIN_MUTATION = <<<'GQL'
        mutation VerifyPin_Mutation(
          $mobile: String!
          $pin: String!
          $deviceId: String!
        ) {
          login(mobile: $mobile, pin: $pin, deviceInfo: {deviceId: $deviceId, deviceModel: "biz", deviceName: "biz"}) {
            token {
              id
              mobile
              length
            }
          }
        }
        GQL;

    private const VERIFY_SMS_MUTATION = <<<'GQL'
        mutation VerifySMS_Mutation(
          $tokenId: String!
          $code: String!
          $pin: String!
        ) {
          verifyAuthCode(tokenId: $tokenId, code: $code, pin: $pin) {
            session {
              sId
              user {
                businessUser {
                  business {
                    wallet {
                      id
                    }
                    id
                  }
                  id
                }
                id
              }
              id
            }
          }
        }
        GQL;

    public function __construct(
        private readonly WaveConnector $connector,
        private readonly SessionStore $store,
        private readonly string $deviceId,
    ) {}

    /**
     * Step 1+2: submit phone number and PIN.
     *
     * Internally runs startBusinessUserAuth then login (verifyPin).
     * On success, stores the tokenId and throws OtpRequiredException
     * so the caller knows to collect the SMS code.
     *
     * @throws OtpRequiredException always on success — the caller must call confirmSms().
     * @throws AuthenticationException when the phone/PIN is rejected.
     */
    public function login(string $mobile, string $pin): void
    {
        $session = new WaveSession();
        $this->connector->setSession($session);

        // Step 1 — initiate auth (sends SMS or activates PIN flow).
        $this->connector->graphql(self::START_AUTH_MUTATION, [
            'mobile' => $mobile,
        ], authenticated: false);

        // Step 2 — verify PIN, receive tokenId.
        $data = $this->connector->graphql(self::VERIFY_PIN_MUTATION, [
            'mobile'   => $mobile,
            'pin'      => $pin,
            'deviceId' => $this->deviceId,
        ], authenticated: false);

        $tokenId = $data['login']['token']['id'] ?? null;

        if (!$tokenId) {
            throw new AuthenticationException('Wave did not return a token after PIN verification. Check your phone number and PIN.');
        }

        $session->setPendingVerification($tokenId, $pin);
        $this->store->put($session);

        throw new OtpRequiredException($tokenId, $mobile);
    }

    /**
     * Step 3: submit the SMS code received after login().
     *
     * On success, the authenticated session (sId + IDs) is persisted.
     *
     * @throws AuthenticationException when there is no pending verification or the code is wrong.
     */
    public function confirmSms(string $code): void
    {
        $session = $this->store->get();

        if (!$session || !$session->hasPendingVerification()) {
            throw new AuthenticationException(
                'No pending SMS verification. Call Wave::auth()->login() first.'
            );
        }

        $this->connector->setSession($session);

        $data = $this->connector->graphql(self::VERIFY_SMS_MUTATION, [
            'tokenId' => $session->tokenId(),
            'code'    => $code,
            'pin'     => $session->pendingPin(),
        ], authenticated: false);

        $authSession = $data['verifyAuthCode']['session'] ?? null;

        if (!$authSession || empty($authSession['sId'])) {
            throw new AuthenticationException('SMS verification failed. The code may be expired or incorrect.');
        }

        $businessUser = $authSession['user']['businessUser'] ?? [];
        $business     = $businessUser['business'] ?? [];

        $session->authenticate(
            sId:        $authSession['sId'],
            walletId:   $business['wallet']['id'] ?? null,
            businessId: $business['id'] ?? null,
            userId:     $authSession['user']['id'] ?? null,
        );

        $this->store->put($session);
    }

    /**
     * Clears the stored session, forcing a fresh login on next use.
     */
    public function logout(): void
    {
        $this->store->forget();
    }
}
