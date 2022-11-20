<?php

namespace FluentCrm\App\Services\BlockRender;

use FluentCrm\Framework\Support\Arr;

class WooProduct
{
    private static $productsCache = [];

    public static function renderProduct($buttonHtml, $data)
    {
        if(!defined('WC_PLUGIN_FILE')) {
            return '';
        }

        $defaultAtts = [
            'productId'       => null,
            'showDescription' => true,
            'showPrice'       => true,
            'customImage'     => '',
            'backgroundColor' => '#fffeeb',
            'contentColor'    => '',
            'pricingColor'    => '',
            'template'        => 'left'
        ];
        $atts = wp_parse_args($data['attrs'], $defaultAtts);
        $productId = (int) $atts['productId'];

        if (!$productId) {
            return '';
        }

        if(isset(self::$productsCache[$productId])) {
            $product = self::$productsCache[$productId];
        } else {
            self::$productsCache[$productId] = wc_get_product($productId);
            $product = self::$productsCache[$productId];
        }

        if (!$product) {
            return '';
        }

        $tableStyle = 'border-radius: 5px;';

        if ($bgColor = Arr::get($atts, 'backgroundColor')) {
            $tableStyle .= 'background-color: ' . $bgColor . ';';
        }

        $contentColorStyle = '';
        if ($color = Arr::get($atts, 'contentColor')) {
            $tableStyle .= 'color: ' . $color . ';';
            $contentColorStyle = 'color: ' . $color . ';';
        }

        $pricingStyle = 'margin: 5px 0px; 10px;';
        if ($color = Arr::get($atts, 'pricingColor')) {
            $pricingStyle = 'color: ' . $color . ';';
        }

        $contentHtml = sprintf(
            '<h2 style="%1$s">%2$s</h2>',
            $contentColorStyle.'margin: 0;',
            wp_kses_post($product->get_title())
        );

        if (Arr::get($atts, 'showDescription')) {
            $contentHtml .= sprintf(
                '<div style="%1$s">%2$s</div>',
                $contentColorStyle.'margin-bottom: 5px;',
                wc_format_content(wp_kses_post($product->get_short_description() ? $product->get_short_description() : wc_trim_string($product->get_description(), 400)))
            );
        }

        if (Arr::get($atts, 'showPrice')) {
            $contentHtml .= sprintf(
                '<div style="%1$s">%2$s</div>',
                $pricingStyle,
                wp_kses_post($product->get_price_html())
            );
        }

        $image = '';

        $template = Arr::get($atts, 'template', 'left');

        if($template != 'none') {
            $image = self::getImage($product);
        }
        if (!$image) {
            $template = 'none';
        }

        $atts['template'] = $template;

        $contentStyle = '';
        if ($template == 'none' || $template == 'top') {
            $contentStyle = 'text-align: center;';
        }

        $imageTd = '';
        if($image) {
            if($template == 'top') {
                $contentHtml = '<img src="'.$image.'" width="auto" alt="'.$product->get_title().'" style="border: 0; height: auto; outline: none; text-decoration: none; max-width: 100%; -ms-interpolation-mode: bicubic; display: block;margin: 0 auto 15px;">'.$contentHtml;
            } else if($template == 'left') {
                $imageContent = '<img src="'.$image.'" width="auto" alt="'.$product->get_title().'" style="border: 0; height: auto; outline: none; text-decoration: none; max-width: 100%; -ms-interpolation-mode: bicubic; display: block;margin: 0 auto;">';
                $imageTd = self::getContentTd($imageContent, $template);
            }
        }

        $buttonHtml = '<div style="margin-top: 10px; margin-bottom: 10px;">'.$buttonHtml.'</div>';

        $contentHtml .= $buttonHtml;
        ob_start();
        ?>

        <table class="fce_row" border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;margin-bottom: 20px; margin-top: 20px;<?php echo $tableStyle; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><tbody><tr>
                <?php
                if($imageTd) {
                    echo $imageTd; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                echo self::getContentTd($contentHtml, $template, $contentStyle); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>
        </tr></tbody></table>
        <?php
        return ob_get_clean();
    }

    private static function getContentTd($contentHtml, $template, $extraStyle = '')
    {
        if(!defined('WC_PLUGIN_FILE')) {
            return '';
        }

        $width = '100';
        if($template == 'left') {
            $width = '50';
        }
        return '<td align="center" valign="middle" width="' . $width . '%" class="fce_column"><table border="0" cellpadding="10" cellspacing="0" width="100%"><tr><td class="fc_column_content" style="padding: 10px;'. $extraStyle.'">'.$contentHtml.'</td></tr></table></td>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Returns the main product image URL.
     *
     * @param \WC_Product $product Product object.
     * @param string $size Image size, defaults to 'full'.
     * @return string
     */
    public static function getImage($product, $size = 'full')
    {
        if(!defined('WC_PLUGIN_FILE')) {
            return '';
        }

        static $imageCache = [];

        if(isset($imageCache[$product->get_id()])) {
            return $imageCache[$product->get_id()];
        }

        $image = '';
        if ($product->get_image_id()) {
            $image = wp_get_attachment_image_url($product->get_image_id(), $size);
        } elseif ($product->get_parent_id()) {
            $parent_product = wc_get_product($product->get_parent_id());
            if ($parent_product) {
                $image = wp_get_attachment_image_url($parent_product->get_image_id(), $size);
            }
        }

        $imageCache[$product->get_id()] = $image;

        return $imageCache[$product->get_id()] ;
    }
}
