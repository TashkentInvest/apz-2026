<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Shartnomani tahrirlash alohida can_edit_contracts ruxsati bilan tekshiriladi (KAMCHILIKLAR C1).
 * Foydalanuvchilar bazaga yozilmaydi, shuning uchun testlar bazaga murojaat qilmaydi.
 */
class ContractEditPermissionTest extends TestCase
{
    private function user(string $role, bool $canEdit, string $email = 'user@example.test', string $name = 'Test'): User
    {
        $user = new User([
            'name' => $name, 'email' => $email, 'role' => $role,
            'status' => 'approved', 'can_edit_contracts' => $canEdit,
        ]);
        $user->id = 999001;

        return $user;
    }

    public function test_user_with_permission_can_edit_regardless_of_role_email_and_name(): void
    {
        $this->assertTrue(Gate::forUser($this->user('user', true, 'operator@toshkentinvest.uz', 'Operator'))->allows('edit-apz-contract'));
    }

    public function test_admin_role_alone_does_not_grant_editing(): void
    {
        // role SSO orqali keladi, u tahrirlash huquqini bermasligi kerak
        $this->assertFalse(Gate::forUser($this->user('admin', false))->allows('edit-apz-contract'));
    }

    public function test_old_hardcoded_email_alone_does_not_grant_editing(): void
    {
        $this->assertFalse(Gate::forUser($this->user('admin', false, 'superadmin@example.com', 'Administrator'))->allows('edit-apz-contract'));
    }

    /**
     * @dataProvider editRoutes
     */
    public function test_user_without_permission_gets_403_on_edit_routes(string $uri): void
    {
        foreach (['admin', 'consumer'] as $role) {
            $this->actingAs($this->user($role, false))
                ->post($uri)
                ->assertForbidden();
        }
    }

    public static function editRoutes(): array
    {
        return [
            'update' => ['/contracts/1'],
            'schedule' => ['/contracts/1/schedule'],
            'contract file' => ['/contracts/1/file/contract'],
            'other files' => ['/contracts/1/files/other'],
            'delete attachment' => ['/contract-attachments/1/delete'],
        ];
    }
}
