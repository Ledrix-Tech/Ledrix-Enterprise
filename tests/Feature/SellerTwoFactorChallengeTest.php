<?php

namespace Tests\Feature;

use App\Services\Security\TotpService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesPortalUsers;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

/**
 * F-28: Seller portal 2FA challenge after password login.
 *
 * @group seller
 * @group security
 */
class SellerTwoFactorChallengeTest extends TestCase
{
    use CreatesPortalUsers;
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->ensureSellerTwoFactorColumns();
        $this->mockTenantFeaturesEnabled();
        $this->mockCrmWorkspaceAccess();
    }

    public function test_seller_with_2fa_is_challenged_on_login(): void
    {
        $seller = $this->createSellerUser(null, [
            'email'    => 'seller-2fa-'.uniqid().'@example.com',
            'password' => 'password',
            'status'   => 'Active',
        ]);

        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $seller->forceFill([
            'two_factor_secret'         => $secret,
            'two_factor_recovery_codes' => json_encode($totp->hashRecoveryCodes(['AAAA-BBBB'])),
        ])->save();

        $this->post(route('seller.login.post'), [
            'email'    => $seller->email,
            'password' => 'password',
        ])->assertRedirect(route('seller.2fa.challenge'));

        $this->assertGuest('seller');
        $this->assertEquals($seller->id, session('seller_2fa_pending_id'));

        $code = $this->currentTotp($secret);
        $this->post(route('seller.2fa.challenge.post'), ['code' => $code])
            ->assertRedirect(route('seller.index.get'));

        $this->assertAuthenticatedAs($seller->fresh(), 'seller');
    }

    public function test_seller_without_2fa_logs_in_directly(): void
    {
        $seller = $this->createSellerUser(null, [
            'email'    => 'seller-plain-'.uniqid().'@example.com',
            'password' => 'password',
            'status'   => 'Active',
        ]);

        $this->post(route('seller.login.post'), [
            'email'    => $seller->email,
            'password' => 'password',
        ])->assertRedirect(route('seller.index.get'));

        $this->assertAuthenticatedAs($seller->fresh(), 'seller');
        $this->assertNull(session('seller_2fa_pending_id'));
    }

    private function currentTotp(string $secret): string
    {
        $service = app(TotpService::class);
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('at');
        $method->setAccessible(true);
        $slice = (int) floor(time() / 30);

        return $method->invoke($service, $secret, $slice);
    }

    private function ensureSellerTwoFactorColumns(): void
    {
        if (! Schema::hasColumn('sellers', 'two_factor_secret')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
            });
        }
    }
}
