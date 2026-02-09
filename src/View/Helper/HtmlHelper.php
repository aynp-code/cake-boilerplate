<?php
declare(strict_types=1);

namespace App\View\Helper;

use BootstrapUI\View\Helper\HtmlHelper as CoreHtmlHelper;
use App\Service\ViewAuthorization;

class HtmlHelper extends CoreHtmlHelper
{
    private ViewAuthorization $authz;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->authz = new ViewAuthorization();
    }

    public function link($title, $url = null, array $options = []): string
    {
        if (!$this->authz->canUrl($this->getView()->getRequest(), $url)) {
            return '';
        }
        return parent::link($title, $url, $options);
    }
}
