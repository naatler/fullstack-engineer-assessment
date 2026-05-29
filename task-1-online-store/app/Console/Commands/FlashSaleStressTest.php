<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Console\Command;

class FlashSaleStressTest extends Command
{
    protected $signature = 'flash-sale:stress-test
                            {productId=1}
                            {--requests=100}
                            {--stock=10}
                            {--quantity=1}
                            {--url=http://127.0.0.1:8000/api/orders}';

    protected $description = 'Run concurrent requests to test flash sale race condition handling';

    public function handle(): int
    {
        $productId = (int) $this->argument('productId');
        $totalRequests = (int) $this->option('requests');
        $initialStock = (int) $this->option('stock');
        $quantity = (int) $this->option('quantity');
        $url = $this->option('url');

        Order::query()->delete();
        OrderItem::query()->delete();

        $product = Product::findOrFail($productId);
        $product->update(['stock' => $initialStock]);

        $payload = json_encode([
            'items' => [
                [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ],
            ],
        ]);

        $multiHandle = curl_multi_init();
        $curlHandles = [];

        for ($i = 0; $i < $totalRequests; $i++) {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT => 30,
            ]);

            curl_multi_add_handle($multiHandle, $curl);
            $curlHandles[] = $curl;
        }

        do {
            $status = curl_multi_exec($multiHandle, $running);

            if ($running) {
                curl_multi_select($multiHandle);
            }
        } while ($running && $status === CURLM_OK);

        $successCount = 0;
        $failedCount = 0;

        foreach ($curlHandles as $curl) {
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($httpCode === 201) {
                $successCount++;
            } else {
                $failedCount++;
            }

            curl_multi_remove_handle($multiHandle, $curl);
            curl_close($curl);
        }

        curl_multi_close($multiHandle);

        $product->refresh();

        $this->info("Total requests: {$totalRequests}");
        $this->info("Success orders: {$successCount}");
        $this->info("Failed orders: {$failedCount}");
        $this->info("Final stock: {$product->stock}");

        if ($product->stock < 0) {
            $this->error('FAILED: Stock is negative.');
            return self::FAILURE;
        }

        if ($successCount > $initialStock) {
            $this->error('FAILED: More orders succeeded than available stock.');
            return self::FAILURE;
        }

        if ($product->stock === 0 && $successCount === $initialStock) {
            $this->info('PASSED: Race condition handled successfully.');
            return self::SUCCESS;
        }

        $this->warn('Test finished, but result needs manual review.');
        return self::SUCCESS;
    }
}