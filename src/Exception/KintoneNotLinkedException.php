<?php
declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * kintone 未連携例外
 *
 * makeClient() で連携トークンが取得できない場合にスローする。
 * コントローラ側でこの例外だけ escape => false で Flash 表示することで、
 * 連携ページへのリンクを含む HTML メッセージを安全に表示できる。
 *
 * 通常の RuntimeException（kintone API エラー等）とは区別されるため、
 * エスケープ有無の判断を例外の型で明示的に行える。
 */
class KintoneNotLinkedException extends RuntimeException
{
}
