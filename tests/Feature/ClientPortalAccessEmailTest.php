<?php

namespace Tests\Feature;

use App\Mail\ClientPortalAccessMail;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesPortalUsers;
use Tests\TestCase;

class ClientPortalAccessEmailTest extends TestCase
{
    use CreatesPortalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockTenantFeaturesEnabled();
        $this->mockCrmWorkspaceAccess();
    }

    public function test_setting_client_password_emails_portal_access_immediately(): void
    {
        Mail::fake();

        $admin = $this->createAdmin(['role' => 'admin']);
        $client = Client::factory()->create([
            'tenant_id' => 1,
            'email'     => 'client.portal@example.com',
            'name'      => 'Alex Client',
            'meta'      => ['portal_access' => false],
        ]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.clients.get'))
            ->post(route('client.account-access'), [
                'client_id' => $client->id,
                'password'  => 'secret99',
            ])
            ->assertRedirect(route('admin.clients.get'))
            ->assertSessionHas('success', 'Portal access saved and emailed to client.portal@example.com.');

        $client->refresh();
        $this->assertTrue($client->hasPortalAccess());
        $this->assertTrue(Hash::check('secret99', $client->password));

        Mail::assertSent(ClientPortalAccessMail::class, function (ClientPortalAccessMail $mail) {
            return $mail->hasTo('client.portal@example.com')
                && $mail->clientName === 'Alex Client'
                && $mail->password === 'secret99'
                && $mail->loginUrl === route('client.login.get');
        });
    }

    public function test_invalid_client_email_still_saves_access_without_sending(): void
    {
        Mail::fake();

        $admin = $this->createAdmin(['role' => 'admin']);
        $client = Client::factory()->create([
            'tenant_id' => 1,
            'email'     => 'not-an-email',
            'meta'      => [],
        ]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.clients.get'))
            ->post(route('client.account-access'), [
                'client_id' => $client->id,
                'password'  => 'secret99',
            ])
            ->assertRedirect(route('admin.clients.get'))
            ->assertSessionHas('success');

        $this->assertTrue($client->fresh()->hasPortalAccess());
        Mail::assertNothingSent();
    }
}
