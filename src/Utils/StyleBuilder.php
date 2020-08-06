<?php

declare(strict_types=1);

namespace Paneon\VueToTwig\Utils;

use DOMElement;
use Exception;
use Ramsey\Uuid\Uuid;
use ScssPhp\ScssPhp\Compiler as ScssCompiler;

class StyleBuilder
{
    public const STYLE_NO = 0;
    public const STYLE_SCOPED = 1;
    public const STYLE = 2;
    public const STYLE_ALL = 3;

    /**
     * @var int
     */
    private $outputType;

    /**
     * @var ScssCompiler|null
     */
    private $scssCompiler;

    /**
     * @var bool
     */
    private $hasScoped;

    /**
     * @var string|null
     */
    private $scopedAttribute;

    /**
     * StyleBuilder constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->outputType = self::STYLE_ALL;
        $this->scssCompiler = null;
        $this->hasScoped = false;
        $this->scopedAttribute = 'data-v-' . substr(md5(Uuid::uuid4()->toString()), 0, 8);
    }

    public function setOutputType(int $outputType): void
    {
        $this->outputType = $outputType;
    }

    public function compile(?DOMElement $styleElement): ?string
    {
        if (!$styleElement instanceof DOMElement
            || ($styleElement->hasAttribute('scoped') && !($this->outputType & self::STYLE_SCOPED))
            || (!$styleElement->hasAttribute('scoped') && !($this->outputType & self::STYLE))) {
            return null;
        }

        $style = $styleElement->textContent;

        if ($styleElement->hasAttribute('lang') && $styleElement->getAttribute('lang') === 'scss') {
            if ($this->scssCompiler === null) {
                $this->scssCompiler = new ScssCompiler();
            }
            $style = $this->scssCompiler->compile($style);
        }

        if ($styleElement->hasAttribute('scoped')) {
            $this->hasScoped = true;
            $style = preg_replace('/((?:^|[^},]*?)\S+)(\s*[{,])/i', '$1[' . $this->scopedAttribute . ']$2', $style);
        }

        return '<style>' . $style . '</style>';
    }

    public function hasScoped(): ?bool
    {
        return $this->hasScoped;
    }

    public function getScopedAttribute(): string
    {
        return $this->scopedAttribute;
    }
}