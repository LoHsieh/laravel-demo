<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Ordering\RecordPaymentResult;
use App\Payment\InvalidPaymentNotification;
use App\Payment\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * 綠界呼叫的網址。不經過 CSRF 驗證，改以檢查碼確認內容來自綠界。
 */
class EcpayController extends Controller
{
    /**
     * 付款結果通知（ReturnURL），由綠界伺服器送來。處理完成回應 1|OK；
     * 其他回應會讓綠界在 5～15 分鐘後重送（當天共 4 次）。
     */
    public function notify(Request $request, PaymentGateway $gateway, RecordPaymentResult $recordPaymentResult): Response
    {
        try {
            $recordPaymentResult($gateway->parseNotification($request->post()));
        } catch (InvalidPaymentNotification $e) {
            Log::warning('拒絕綠界付款通知：'.$e->getMessage(), ['MerchantTradeNo' => $request->post('MerchantTradeNo')]);

            return response('0|'.$e->getMessage(), 400)->header('Content-Type', 'text/plain');
        }

        return response('1|OK')->header('Content-Type', 'text/plain');
    }

    /**
     * 付款後顧客的瀏覽器被導回（OrderResultURL）。只用來找出訂單並轉到付款結果頁；
     * 付款結果以付款通知為準，這裡不改變訂單。
     */
    public function result(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        try {
            $notification = $gateway->parseNotification($request->post());
        } catch (InvalidPaymentNotification) {
            abort(404);
        }

        $payment = Payment::where('merchant_trade_no', $notification->merchantTradeNo)->firstOrFail();

        // 303：讓瀏覽器以 GET 開啟結果頁，重新整理時不會再次送出表單
        return redirect()->route('payments.show', $payment->order, 303);
    }
}
