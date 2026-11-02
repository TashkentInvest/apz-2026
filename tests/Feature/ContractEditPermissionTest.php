<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Shartnomani tahrirlash huquqi rol orqali tekshiriladi (KAMCHILIKLAR C1).
 * Foydalanuvchilar bazaga yozilmaydi, shuning uchun testlar bazaga murojaat qilmaydi.
 */
class ContractEditPermissionTest extends TestCase
{
    private function user(string $role, string $email = 'user@example.test', string $name = 'Test'): User
    {
        $user = new User(['name' => $name, 'email' => $email, 'role' => $role, 'status' => 'approved']);
        $user->id = 999001;

        return $user;
    }

    public function test_any_admin_can_edit_regardless_of_email_and_name(): void
    {
        $this->assertTrue(Gate::forUser($this->user('admin', 'boshqa@toshkentinvest.uz', 'Operator'))->allows('edit-apz-contract'));
    }

    public function test_consumer_cannot_edit_even_with_old_hardcoded_email(): void
    {
        $this->assertFalse(Gate::forUser($this->user('consumer', 'superadmin@example.com', 'Administrator'))->allows('edit-apz-contract'));
    }

    /**
     * @dataProvider editRoutes
     */
    public function test_consumer_gets_403_on_edit_routes(string $uri): void
    {
        $this->actingAs($this->user('consumer'))
            ->post($uri)
            ->assertForbidden();
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
