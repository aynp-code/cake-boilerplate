<?php
declare(strict_types=1);

namespace App\View\Helper;

use BootstrapUI\View\Helper\BreadcrumbsHelper as BootstrapUIBreadcrumbsHelper;

/**
 * Boilerplate root-fix:
 * - BootstrapUI の BreadcrumbsHelper は add() 経由だと breadcrumb-item を付けるが、
 *   addMany() 経由だと付かないケースがあるため補正する。
 */
class BreadcrumbsHelper extends BootstrapUIBreadcrumbsHelper
{
    /**
     * Cake\View\Helper\BreadcrumbsHelper::addMany(array $crumbs, array $options = [])
     * と互換のシグネチャにする（これが重要）
     *
     * @param array<int, array<string, mixed>> $crumbs
     * @param array<string, mixed> $options addMany全体に適用したい属性等（classなど）
     */
    public function addMany(array $crumbs, array $options = []): static
    {
        foreach ($crumbs as $crumb) {
            $title = $crumb['title'] ?? '';
            $url = $crumb['url'] ?? null;

            // 各crumb固有options と addMany全体options をマージ
            $crumbOptions = $crumb['options'] ?? [];
            $merged = $options + $crumbOptions; // 既に crumb側で指定があればそちら優先にしたいなら「$crumbOptions + $options」に変える

            // Bootstrap divider(/)復活のキー：breadcrumb-item を必ず付ける
            $merged['class'] = trim(($merged['class'] ?? '') . ' breadcrumb-item');

            // URLなし（現在地）なら active を付けておく（旧 add の出力に寄せる）
            if ($url === null) {
                $merged['class'] = trim($merged['class'] . ' active');
                $merged['aria-current'] ??= 'page';
            }

            $this->add($title, $url, $merged);
        }

        return $this;
    }
}
