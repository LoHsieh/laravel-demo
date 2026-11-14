// 本機執行端對端測試前，重新建立 docker compose 的 e2e 服務（重建資料庫後啟動網站），等到網站可以回應。
import { execSync } from 'node:child_process';

export default function () {
    execSync('docker compose --profile e2e up -d --force-recreate --wait e2e', { stdio: 'inherit' });
}
