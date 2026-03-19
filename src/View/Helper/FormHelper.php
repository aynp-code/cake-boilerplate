<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\ViewAuthorization;
use BootstrapUI\View\Helper\FormHelper as CoreFormHelper;

class FormHelper extends CoreFormHelper
{
    private ViewAuthorization $authz;

    /**
     * Initialization hook method.
     *
     * @param array<string, mixed> $config The configuration array.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->authz = new ViewAuthorization();
    }

    /**
     * Creates a link with a form that submits via POST, checking permissions before rendering.
     *
     * @param array<mixed>|string $title The content to be wrapped by <a> tags.
     * @param array<mixed>|string|null $url Cake-relative URL or array of URL parameters.
     * @param array<string, mixed> $options Array of options and HTML attributes.
     * @return string
     */
    public function postLink(array|string $title, array|string|null $url = null, array $options = []): string
    {
        if (!$this->authz->canUrl($this->getView()->getRequest(), $url)) {
            return '';
        }

        $titleStr = is_string($title) ? $title : '';

        return parent::postLink($titleStr, $url, $options);
    }
}
