<?php
/**
 * Single Card Template
 *
 * @package IBD_Vertretungen
 */

if (!defined('ABSPATH')) {
    exit;
}

// $v contains the vertretung data
$has_coordinates = !empty($v['coordinates']);
$country_count = count($v['all_countries']);
?>

<div class="ibd-card"
     data-id="<?php echo esc_attr($v['id']); ?>"
     data-lat="<?php echo esc_attr($v['coordinates']['lat'] ?? ''); ?>"
     data-lng="<?php echo esc_attr($v['coordinates']['lng'] ?? ''); ?>">

    <div class="ibd-card-header">
        <?php if (!empty($v['logo'])): ?>
            <div class="ibd-card-logo">
                <img src="<?php echo esc_url($v['logo']); ?>"
                     alt="<?php echo esc_attr($v['title']); ?>"
                     loading="lazy">
            </div>
        <?php endif; ?>

        <div class="ibd-card-title-wrap">
            <h4 class="ibd-card-title"><?php echo esc_html($v['title']); ?></h4>

            <div class="ibd-card-address">
                <?php if (!empty($v['address']['street'])): ?>
                    <span class="ibd-address-street"><?php echo esc_html($v['address']['street']); ?></span>
                <?php endif; ?>
                <?php if (!empty($v['address']['zip']) || !empty($v['address']['city'])): ?>
                    <span class="ibd-address-city">
                        <?php echo esc_html(trim($v['address']['zip'] . ' ' . $v['address']['city'])); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($v['address']['country'])): ?>
                    <span class="ibd-address-country"><?php echo esc_html($v['address']['country']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ibd-card-body">
        <?php if (!empty($v['website']['url'])): ?>
            <a href="<?php echo esc_url($v['website']['url']); ?>"
               class="ibd-card-website"
               target="<?php echo esc_attr($v['website']['target'] ?? '_blank'); ?>"
               rel="noopener noreferrer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
                <?php echo esc_html($v['website']['title'] ?: __('Website', 'ibd-vertretungen')); ?>
            </a>
        <?php endif; ?>

        <?php if (!empty($v['additional_info'])): ?>
            <div class="ibd-card-info">
                <p><?php echo nl2br(esc_html($v['additional_info'])); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($v['contacts_by_country'])): ?>
            <div class="ibd-card-countries">
                <?php foreach ($v['contacts_by_country'] as $group_index => $group): ?>
                    <?php if (!empty($group['countries'])): ?>
                        <div class="ibd-country-accordion" data-group="<?php echo esc_attr($group_index); ?>">
                            <button type="button" class="ibd-accordion-trigger" aria-expanded="false">
                                <span class="ibd-accordion-title">
                                    <?php
                                    $country_names = array_map(function($c) { return $c['name']; }, $group['countries']);
                                    echo esc_html(implode(', ', $country_names));
                                    ?>
                                    <?php if (count($group['contacts']) > 0): ?>
                                        <span class="ibd-contact-count">(<?php echo count($group['contacts']); ?>)</span>
                                    <?php endif; ?>
                                </span>
                                <svg class="ibd-accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>

                            <div class="ibd-accordion-content">
                                <?php if (!empty($group['contacts'])): ?>
                                    <?php foreach ($group['contacts'] as $contact_index => $contact): ?>
                                        <div class="ibd-contact">
                                            <?php if (!empty($contact['name'])): ?>
                                                <div class="ibd-contact-name"><?php echo esc_html($contact['name']); ?></div>
                                            <?php endif; ?>

                                            <div class="ibd-contact-details">
                                                <?php if (!empty($contact['email'])): ?>
                                                    <a href="mailto:<?php echo esc_attr($contact['email']); ?>" class="ibd-contact-email">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                            <polyline points="22,6 12,13 2,6"></polyline>
                                                        </svg>
                                                        <?php echo esc_html($contact['email']); ?>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if (!empty($contact['phone'])): ?>
                                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^+0-9]/', '', $contact['phone'])); ?>" class="ibd-contact-phone">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                                        </svg>
                                                        <?php echo esc_html($contact['phone']); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>

                                            <div class="ibd-contact-actions">
                                                <a href="<?php echo esc_url(IBD_VCard::get_download_url($v['id'], $group_index, $contact_index)); ?>"
                                                   class="ibd-btn-icon"
                                                   title="<?php esc_attr_e('Als Kontakt speichern', 'ibd-vertretungen'); ?>">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                        <polyline points="7 10 12 15 17 10"></polyline>
                                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="ibd-card-footer">
        <a href="<?php echo esc_url(IBD_PDF_Export::get_download_url($v['id'])); ?>"
           class="ibd-btn ibd-btn-small ibd-btn-outline"
           target="_blank">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
            <?php _e('PDF', 'ibd-vertretungen'); ?>
        </a>

        <a href="<?php echo esc_url(IBD_VCard::get_download_url($v['id'])); ?>"
           class="ibd-btn ibd-btn-small ibd-btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <?php _e('vCard', 'ibd-vertretungen'); ?>
        </a>

        <?php if ($has_coordinates): ?>
            <button type="button" class="ibd-btn ibd-btn-small ibd-btn-primary ibd-show-on-map">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <?php _e('Karte', 'ibd-vertretungen'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>
