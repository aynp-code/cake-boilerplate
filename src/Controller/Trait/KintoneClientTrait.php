<?php
declare(strict_types=1);

namespace App\Controller\Trait;

use App\Exception\KintoneNotLinkedException;
use App\Service\CybozuOAuthService;
use App\Service\KintoneApiClientInterface;
use RuntimeException;

/**
 * kintone クライアント生成 Trait
 *
 * kintone 連携コントローラで共通して使う makeClient() を提供する。
 * 新しい kintone アプリのコントローラを作る場合は use するだけでよい。
 *
 * ## 使い方
 *
 *   class SampleKintoneController extends AppController
 *   {
 *       use KintoneClientTrait;
 *
 *       public function index(CybozuOAuthService $cybozuService): void
 *       {
 *           $client = $this->makeClient($cybozuService);
 *           ...
 *       }
 *   }
 */
trait KintoneClientTrait
{
    /**
     * kintone クライアントを生成する。
     *
     * 未連携の場合は KintoneNotLinkedException をスローする。
     * この例外はコントローラ側で escape => false で Flash 表示し、連携ページへ誘導する。
     *
     * @throws KintoneNotLinkedException  未連携の場合
     * @throws RuntimeException           その他のエラー
     */
    protected function makeClient(CybozuOAuthService $cybozuService): KintoneApiClientInterface
    {
        $userId = (string)$this->Authentication->getIdentity()->getIdentifier();

        try {
            return $cybozuService->makeKintoneClient($userId);
        } catch (RuntimeException) {
            throw new KintoneNotLinkedException(
                'kintone と連携されていません。' .
                '<a href="/auth/cybozu/connect">こちら</a>から連携してください。'
            );
        }
    }
}
