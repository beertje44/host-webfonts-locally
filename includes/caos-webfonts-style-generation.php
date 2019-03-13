<?php
/**
 * @package  : CAOS for Webfonts
 * @author   : Daan van den Bergh
 * @copyright: (c) 2019 Daan van den Bergh
 * @url      : https://daan.dev
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="">
    <h3><?php _e('Generate Stylesheet'); ?></h3>
    <p class="description">
        <?php _e('Search for fonts using a comma-separated list (e.g. Open Sans,Roboto,Poppins) and click \'Search\'.', 'host-webfonts-local'); ?>
    </p>
    <div class="hwl-search-box">
        <input type="text" name="search-field"
               id="search-field" class="form-input-tip ui-autocomplete-input" placeholder="<?php _e('Search... (e.g. Roboto,Open Sans)', 'host-webfonts-local'); ?>"/>
        <input type="button" onclick="hwlClickSearch()" name="search-btn"
               id="search-btn" class="button button-primary button-hero" value="<?php _e('Search', 'host-webfonts-local'); ?>"/>
    </div>
    <table>
        <tr id="row" valign="top">
            <th align="left" colspan="3"><?php _e('Available subsets', 'host-webfonts-local'); ?></th>
        </tr>
        <tbody id="hwl-subsets">
        <?php
        $subsetFonts = hwlGetSubsets();
        ?>
        <?php if ($subsetFonts): ?>
            <?php foreach ($subsetFonts as $subsetFont): ?>
                <?php
                $availableSubsets = explode(',', $subsetFont->available_subsets);
                $selectedSubsets  = explode(',', $subsetFont->selected_subsets);
                ?>
                <tr valign="top" id="<?php echo $subsetFont->subset_font; ?>">
                    <td>
                        <label>
                            <input readonly type="text" class="hwl-subset-font-family" value="<?php echo $subsetFont->subset_family; ?>"/>
                        </label>
                    </td>
                    <?php foreach ($availableSubsets as $availableSubset): ?>
                        <td>
                            <label>
                                <?php $checked = in_array($availableSubset, $selectedSubsets) ? 'checked="checked"' : ''; ?>
                                <input name="<?php echo $subsetFont->subset_font; ?>" value="<?php echo $availableSubset; ?>" type="checkbox" onclick="hwlGenerateSearchQuery('<?php echo $subsetFont->subset_font; ?>')" <?php echo $checked; ?>/>
                                <?php echo $availableSubset; ?>
                            </label>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <table>
        <tr valign="top">
            <th align="left" colspan="3">Available fonts</th>
        </tr>
    </table>
    <table align="left" id="hwl-results">
        <?php
        $savedFonts = hwlGetTotalFonts();
        ?>
        <?php if ($savedFonts && $subsetFonts): ?>
            <?php foreach ($subsetFonts as $subsetFont): ?>
                <tbody id="hwl-section-<?php echo $subsetFont->subset_font; ?>">
                <?php
                $fonts = hwlGetFontsByFamily($subsetFont->subset_family);
                ?>
                <?php foreach ($fonts as $font):
                    $fontId = $font->font_id;
                    $arrayPath = "caos_webfonts_array][$fontId]";
                    ?>
                    <tr id="row-<?php echo $fontId; ?>" valign="top">
                        <td>
                            <input readonly type="text" value="<?php echo $font->font_family; ?>" name="<?php echo $arrayPath; ?>[font-family]"/>
                        </td>
                        <td>
                            <input readonly type="text" value="<?php echo $font->font_style; ?>" name="<?php echo $arrayPath; ?>[font-style]"/>
                        </td>
                        <td>
                            <input readonly type="text" value="<?php echo $font->font_weight; ?>" name="<?php echo $arrayPath; ?>[font-weight]"/>
                        </td>
                        <td>
                            <input type="hidden" value="<?php echo $fontId; ?>" name="<?php echo $arrayPath; ?>[id]"/>
                            <input type="hidden" value="<?php echo $font->url_ttf; ?>" name="<?php echo $arrayPath; ?>[url][ttf]"/>
                            <input type="hidden" value="<?php echo $font->url_woff; ?>" name="<?php echo $arrayPath; ?>[url][woff]"/>
                            <input type="hidden" value="<?php echo $font->url_woff2; ?>" name="<?php echo $arrayPath; ?>[url][woff2]"/>
                            <input type="hidden" value="<?php echo $font->url_eot; ?>" name="<?php echo $arrayPath; ?>[url][eot]"/>
                            <div class="hwl-remove">
                                <a onclick="hwlRemoveRow('row-<?php echo $fontId; ?>')">
                                    <small>remove</small>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            <?php endforeach; ?>
        <?php endif; ?>
        <tbody id="hwl-warning">
        <tr class="loading" style="display: none;">
            <td colspan="3" align="center">
                <span class="spinner"></span>
            </td>
        </tr>
        <tr class="error" style="display: none;">
            <td colspan="3" align="center">No fonts available.</td>
        </tr>
        </tbody>
    </table>

    <table>
        <tbody>
        <tr valign="center" align="center">
            <td>
                <input type="button" onclick="hwlDownloadFonts()" name="save-btn"
                       id="save-btn" class="button-primary" value="Download Fonts"/>
            </td>
            <td>
                <input type="button" onclick="hwlGenerateStylesheet()" name="generate-btn"
                       id="generate-btn" class="button-secondary" value="Generate Stylesheet"/>
            </td>
            <td>
                <a onclick="hwlCleanQueue()" name="clean-btn"
                   id="clean-btn" class="button-cancel">Clean Queue</a>
            </td>
            <td>
                <a onclick="hwlEmptyDir()" name="empty-btn"
                   id="empty-btn" class="button-cancel">Empty Cache Directory</a>
            </td>
            <td width="20%"></td>
        </tr>
        <tr valign="center">
            <?php
            $downloaded = hwlGetDownloadStatus()['downloaded'];
            $total      = hwlGetDownloadStatus()['total'];
            $width      = $downloaded && $total ? (100 / $total) * $downloaded : 0;
            ?>
            <td colspan="5">
                <div class="caos-status-total-bar" style="">
                    <div id="caos-status-progress-bar" style="width: <?php echo $width; ?>%;">
                        <span class="caos-status-progress-percentage"><?php echo $width . '%'; ?></span>
                    </div>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>
