// 本機端對端測試結束後停止 e2e 服務。
import { execSync } from 'node:child_process';

export default function () {
    execSync('docker compose --profile e2e stop e2e', { stdio: 'inherit' });
}
