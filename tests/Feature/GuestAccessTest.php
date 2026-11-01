<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tizimga kirmagan foydalanuvchi (mehmon) uchun asosiy marshrutlar.
 * Bu testlar bazaga murojaat qilmaydi.
 */
class GuestAccessTest extends TestCase
{
    public function test_root_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_renders_password_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('login.password'), false);
    }

    /**
     * @dataProvider protectedRoutes
     */
    public function test_protected_pages_require_login(string $uri): void
    {
        $this->get($uri)->assertRedirect(route('login'));
    }

    public static function protectedRoutes(): array
    {
        return [
            'home' => ['/home'],
            'dashboard' => ['/dashboard'],
            'debts' => ['/debts'],
            'contract' => ['/contracts/1'],
            'admin' => ['/admin'],
        ];
    }
}
