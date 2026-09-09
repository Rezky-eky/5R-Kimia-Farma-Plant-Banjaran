import { test, expect } from '@playwright/test';

test('guest can open the login page', async ({ page }) => {
    await page.goto('/login');
    await expect(page).toHaveTitle(/Login/i);
    await expect(page.getByLabel(/NPP/i)).toBeVisible();
});

test('guest is redirected to login from the dashboard', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login/);
});

test('registration remains disabled', async ({ page }) => {
    const response = await page.goto('/register');
    expect(response?.status()).toBe(404);
});
