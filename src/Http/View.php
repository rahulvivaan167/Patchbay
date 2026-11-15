<?php

declare(strict_types=1);

namespace Patchbay\Http;

use RuntimeException;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'app'): string
    {
        $viewFile = base_path('views/' . str_replace('.', '/', $view) . '.php');
        if (!is_file($viewFile)) throw new RuntimeException('View not found: ' . $view);
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        if ($layout === '') return $content;
        $layoutFile = base_path('views/layouts/' . $layout . '.php');
        ob_start();
        require $layoutFile;
        return (string) ob_get_clean();
    }
}
