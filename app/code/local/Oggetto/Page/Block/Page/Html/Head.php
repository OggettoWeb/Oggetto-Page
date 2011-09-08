<?php
/**
 * Oggetto Web extension for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade
 * the Oggetto Page module to newer versions in the future.
 * If you wish to customize the Oggetto Page module for your needs
 * please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Oggetto
 * @package    Oggetto_Page
 * @copyright  Copyright (C) 2011 Oggetto Web ltd (http://www.oggettoweb.com/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Html page block
 *
 * @category   Oggetto
 * @package    Oggetto_Page
 * @subpackage Block
 * @author     Valentin Sushkov <vsushkov@oggettoweb.com>
 */
class Oggetto_Page_Block_Page_Html_Head extends Mage_Page_Block_Html_Head
{
    const FORMAT_JS = '<script type="text/javascript" src="%s"></script>';
    const FORMAT_CSS = '<link rel="stylesheet" href="%s" media="all" />';

    /**
     * Get HEAD HTML with CSS/JS/RSS definitions
     * (actually it also renders other elements, TODO: fix it up or rename this method)
     *
     * @return string
     */
    public function getCssJsHtml()
    {
        // separate items by types
        $lines  = array();
        foreach ($this->_data['items'] as $item) {
            if (!is_null($item['cond']) && !$this->getData($item['cond']) || !isset($item['name'])) {
                continue;
            }
            $if     = !empty($item['if']) ? $item['if'] : '';
            $params = !empty($item['params']) ? $item['params'] : '';
            switch ($item['type']) {
                case 'js':        // js/*.js
                case 'skin_js':   // skin/*/*.js
                case 'js_css':    // js/*.css
                case 'skin_css':  // skin/*/*.css
                case 'absolute_js':
                case 'absolute_css':
                    $lines[$if][$item['type']][$params][$item['name']] = $item['name'];
                    break;
                default:
                    $this->_separateOtherHtmlHeadElements($lines, $if, $item['type'], $params, $item['name'], $item);
                    break;
            }
        }

        // prepare HTML
        $shouldMergeJs = Mage::getStoreConfigFlag('dev/js/merge_files');
        $shouldMergeCss = Mage::getStoreConfigFlag('dev/css/merge_css_files');
        $html   = '';
        foreach ($lines as $if => $items) {
            if (empty($items)) {
                continue;
            }
            if (!empty($if)) {
                $html .= '<!--[if '.$if.']>'."\n";
            }

            // static and skin css
            $html .= $this->_prepareStaticAndSkinElements(
                '<link rel="stylesheet" type="text/css" href="%s"%s />' . "\n",
                empty($items['js_css']) ? array() : $items['js_css'],
                empty($items['skin_css']) ? array() : $items['skin_css'],
                $shouldMergeCss ? array(Mage::getDesign(), 'getMergedCssUrl') : null
            );

            // static and skin javascripts
            $html .= $this->_prepareStaticAndSkinElements(
                '<script type="text/javascript" src="%s"%s></script>' . "\n",
                empty($items['js']) ? array() : $items['js'],
                empty($items['skin_js']) ? array() : $items['skin_js'],
                $shouldMergeJs ? array(Mage::getDesign(), 'getMergedJsUrl') : null
            );

            // other stuff
            if (!empty($items['other'])) {
                $html .= $this->_prepareOtherHtmlHeadElements($items['other']) . "\n";
            }

            if (!empty($items['absolute_js'])) {
                $html .= $this->_prepareAbsoluteJs($items['absolute_js']) . "\n";
            }

            if (!empty($items['absolute_css'])) {
                $html .= $this->_prepareAbsoluteCss($items['absolute_css']) . "\n";
            }

            if (!empty($if)) {
                $html .= '<![endif]-->'."\n";
            }
        }
        return $html;
    }

    /**
     * _prepareAbsoluteJs 
     * 
     * @param array $items 
     * @return strgin
     */
    protected function _prepareAbsoluteJs($items)
    {
        $rendered = '';
        $items = array_pop($items);
        foreach ($items as $item) {
            $rendered .= sprintf(Oggetto_Page_Block_Page_Html_Head::FORMAT_JS, $item) . "\n";
        }
        return $rendered;
    }

    /**
     * _prepareAbsoluteCss 
     * 
     * @param array $items 
     * @return string
     */
    protected function _prepareAbsoluteCss($items)
    {
        $rendered = '';
        $items = array_pop($items);
        foreach ($items as $item) {
            $rendered .= sprintf(Oggetto_Page_Block_Page_Html_Head::FORMAT_CSS, $item) . "\n";
        }
        return $rendered;
    }
}
