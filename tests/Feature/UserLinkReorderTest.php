<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLinkReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reorder_route_is_not_captured_as_link_id(): void
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'P@ssw0rd!',
            'email_verified_at' => now(),
        ]);

        $link1 = UserLink::create([
            'user_id' => $user->id,
            'title' => 'Link 1',
            'url' => 'https://example1.com',
            'sort_order' => 1,
        ]);
        $link2 = UserLink::create([
            'user_id' => $user->id,
            'title' => 'Link 2',
            'url' => 'https://example2.com',
            'sort_order' => 2,
        ]);

        // This should hit the reorder endpoint, NOT the {link} update endpoint
        $response = $this->actingAs($user)->putJson('/api/user/links/reorder', [
            'ids' => [$link2->id, $link1->id],
        ]);

        // Should not be a 404 (which would happen if 'reorder' was captured as {link})
        $this->assertNotEquals(404, $response->getStatusCode());
        // Should not be a 405 Method Not Allowed either
        $this->assertNotEquals(405, $response->getStatusCode());
    }

    public function test_link_crud_operations(): void
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'P@ssw0rd!',
            'email_verified_at' => now(),
        ]);

        // Create
        $response = $this->actingAs($user)->postJson('/api/user/links', [
            'title' => 'My Link',
            'url' => 'https://mylink.com',
        ]);
        $response->assertStatus(201);

        // List
        $response = $this->actingAs($user)->getJson('/api/user/links');
        $response->assertOk();
        $response->assertJsonCount(1);

        $linkId = $response->json('0.id');

        // Update
        $response = $this->actingAs($user)->putJson("/api/user/links/{$linkId}", [
            'title' => 'Updated Link',
            'url' => 'https://updated.com',
        ]);
        $response->assertOk();

        // Delete
        $response = $this->actingAs($user)->deleteJson("/api/user/links/{$linkId}");
        $response->assertOk();
    }
}
