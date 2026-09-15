<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DropdownApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_without_permissions_can_access_dropdown_endpoints(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('dropdown-test')->plainTextToken;

        foreach ([
            '/v1/dropdowns/stakeholders',
            '/v1/dropdowns/stakeholder-types',
            '/v1/dropdowns/fee-types',
            '/v1/dropdowns/roles',
        ] as $endpoint) {
            $this->withToken($token)->getJson($endpoint)->assertOk();
        }
    }

    public function test_dropdown_endpoints_require_authentication(): void
    {
        foreach ([
            '/v1/dropdowns/stakeholders',
            '/v1/dropdowns/stakeholder-types',
            '/v1/dropdowns/fee-types',
            '/v1/dropdowns/roles',
        ] as $endpoint) {
            $this->getJson($endpoint)->assertUnauthorized();
        }
    }

    public function test_dropdown_role_response_does_not_include_permissions(): void
    {
        Role::create([
            'name' => 'dropdown-test-role',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $token = $user->createToken('dropdown-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/v1/dropdowns/roles')
            ->assertOk()
            ->assertJsonStructure([
                [
                    'id',
                    'name',
                    'guard_name',
                    'created_at',
                ],
            ])
            ->assertJsonMissingPath('0.permissions');
    }
}
