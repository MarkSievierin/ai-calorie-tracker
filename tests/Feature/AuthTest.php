<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthcheck_needs_no_token(): void
    {
        $this->get('/up')->assertOk()->assertJson(['ok' => true]);
    }

    public function test_api_rejects_missing_and_wrong_token(): void
    {
        $this->getJson('/api/day')->assertStatus(401)->assertExactJson(['error' => 'unauthorized']);
        $this->getJson('/api/day?key=wrong')->assertStatus(401);
        $this->getJson('/api/day', ['Authorization' => 'Bearer wrong'])->assertStatus(401);
    }

    public function test_token_is_accepted_from_query_header_and_cookie(): void
    {
        $this->getJson('/api/day?key=test-token')->assertOk();
        $this->getJson('/api/day', ['Authorization' => 'Bearer test-token'])->assertOk();
        $this->getJson('/api/day', ['X-Api-Key' => 'test-token'])->assertOk();
        $this->getJson('/api/day', ['X-Api-Key' => 'wrong'])->assertStatus(401);
        $this->withCredentials()->withUnencryptedCookie('kkal_token', 'test-token')->getJson('/api/day')->assertOk();
    }

    public function test_login_sets_http_only_cookie(): void
    {
        $this->postJson('/api/login', ['token' => 'nope'])->assertStatus(401);
        $res = $this->postJson('/api/login', ['token' => 'test-token'])->assertOk();
        $cookie = collect($res->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === 'kkal_token');
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('test-token', $cookie->getValue());
    }

    public function test_failed_attempts_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/day?key=bad')->assertStatus(401);
        }
        $this->getJson('/api/day?key=bad')->assertStatus(429);
        // Even the right token waits until the window passes.
        $this->getJson('/api/day?key=test-token')->assertStatus(429);
    }

    public function test_empty_configured_token_closes_everything(): void
    {
        config(['kkal.token' => '']);
        $this->getJson('/api/day?key=')->assertStatus(401);
    }

    public function test_mcp_requires_token(): void
    {
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])->assertStatus(401);
    }

    public function test_mcp_accepts_token_in_headers(): void
    {
        $ping = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'];
        $accept = ['Accept' => 'application/json, text/event-stream'];
        $this->postJson('/mcp', $ping, $accept + ['Authorization' => 'Bearer test-token'])->assertOk();
        $this->postJson('/mcp', $ping, $accept + ['X-Api-Key' => 'test-token'])->assertOk();
    }
}
