<table class="form-table wpwoof-addfeed-top">
    <tr class="addfeed-top-field">
        <th class="addfeed-top-label addfeed-bigger">Feed's Name:</th>
        <td class="addfeed-top-value">
            <input type="text" id="idFeedName" name="feed_name" value="<?php echo isset($wpwoof_values['feed_name']) ? $wpwoof_values['feed_name'] : ''; ?>" />
            <?php if( !empty($wpwoofeed_oldname) ) { ?>
                <input type="hidden" name="old_feed_name" value="<?php echo $wpwoofeed_oldname; ?>" style="display:none" />
            <?php } ?>
        </td>
    </tr>
    <tr class="addfeed-top-field">
        <th class="addfeed-top-label addfeed-bigger">Feed's Type:</th>
        <td class="addfeed-top-value">
            <?php if (isset($_GET['edit'])) {
                switch($wpwoof_values['feed_type']){
                    case "google": echo "Google Merchant";break;
                    case "adsensecustom": echo "Google Adwords Remarketing Custom";break;
                    case "pinterest": echo "Pinterest";break;
                    case "tiktok": echo "TikTok";break;
                    case "googleReviews": echo "Reviews for Google Merchant";break;
                    default : echo "Facebook Product Catalog";
                }
                echo '<input id="ID-feed_type" type="hidden" name="feed_type" value="'.$wpwoof_values['feed_type'].'" style="display:none" />';
            } else { ?>
            <select id="ID-feed_type" name="feed_type" onchange="jQuery.fn.toggleFeedField(this.value);">
                <option <?php if(isset($wpwoof_values['feed_type'])) { selected( "facebook", $wpwoof_values['feed_type'], true); } ?> value="facebook">Facebook Product Catalog</option>
                <option <?php if(isset($wpwoof_values['feed_type'])) { selected( "google", $wpwoof_values['feed_type'], true); } ?> value="google">Google Merchant</option>
                <option <?php if(isset($wpwoof_values['feed_type'])) { selected( "adsensecustom", $wpwoof_values['feed_type'], true); } ?> value="adsensecustom">Google Adwords Remarketing Custom</option>
                <option <?php if(isset($wpwoof_values['feed_type'])) { selected( "pinterest", $wpwoof_values['feed_type'], true); } ?> value="pinterest">Pinterest</option>
                <option <?php if(isset($wpwoof_values['feed_type'])) { selected( "tiktok", $wpwoof_values['feed_type'], true); } ?> value="tiktok">TikTok</option>
                <option <?php if(isset($wpwoof_values['feed_type'])) { selected( "googleReviews", $wpwoof_values['feed_type'], true); } ?> value="googleReviews">Reviews for Google Merchant</option>
            </select>
            <?php } ?>
        </td>
    </tr>
    <tr class="addfeed-top-field">
        <th class="addfeed-top-label addfeed-bigger">Regenerate Feed:</th>
        <td class="addfeed-top-value">
            <select name="feed_interval">
                <?php 
                $current_interval = isset($wpwoof_values['feed_interval'])?$wpwoof_values['feed_interval']:0;
                $intervals = array(
                    '0'         => 'Global settings',
                    '3600'      => 'Hourly',
                    '86400'     => 'Daily',
                    '43200'     => 'Twice daily',
                    '604800'    => 'Weekly'
                );
                foreach($intervals as $interval => $interval_name) {
                    echo '<option '.selected($interval,$current_interval,false).' value="'.$interval.'">'.$interval_name.'</option>';
                }
                ?>
            </select>
        </td>
    </tr>
    <tr class="addfeed-top-field">
        <th class="addfeed-top-label addfeed-bigger">Start regeneration from:</th>
        <td class="addfeed-top-value">
            <input type="time" name="feed_schedule_from" value="<?=isset($wpwoof_values['feed_schedule_from']) ? $wpwoof_values['feed_schedule_from'] : ''; ?>"> 
        </td>
    </tr>
</table>