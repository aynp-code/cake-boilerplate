<?php
declare(strict_types=1);

namespace App\View\Helper;

use BootstrapUI\View\Helper\FormHelper as CoreFormHelper;
use App\Service\ViewAuthorization;

class FormHelper extends CoreFormHelper
{
    private ViewAuthorization $authz;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->authz = new ViewAuthorization();
    }

    public function postLink($title, $url = null, array $options = []): string
    {
        if (!$this->authz->canUrl($this->getView()->getRequest(), $url)) {
            return '';
        }
        return parent::postLink($title, $url, $options);
    }
}
