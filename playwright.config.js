// 端對端測試（Playwright）。網站以 APP_ENV=e2e 啟動（讀取 .env.e2e），使用獨立的 bairi_e2e 資料庫，
// 每次執行前重建資料。CI 直接以 PHP 啟動網站；本機沒有 PHP，改用 docker compose 的 e2e 服務。
import { defineConfig, devices } from '@playwright/test';

const PORT = 8001;
const inCi = !!process.env.CI;

export default defineConfig({
    testDir: 'e2e',
    forbidOnly: inCi,
    reporter: inCi ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL: `http://127.0.0.1:${PORT}`,
        trace: 'retain-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    globalSetup: inCi ? undefined : './e2e/docker-up.js',
    globalTeardown: inCi ? undefined : './e2e/docker-down.js',
    webServer: inCi
        ? {
              command: `php artisan migrate:fresh --seed --seeder=EndToEndSeeder --force && php artisan serve --host=127.0.0.1 --port=${PORT} --no-reload`,
              url: `http://127.0.0.1:${PORT}/up`,
              env: { APP_ENV: 'e2e' },
              timeout: 120_000,
          }
        : undefined,
});
