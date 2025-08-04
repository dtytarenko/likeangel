<?php if (!defined('AIOWPS_PREMIUM_PATH')) die('No direct access allowed'); ?>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<h2><?php _e('Smart 404 stats', 'all-in-one-wp-security-and-firewall-premium');?></h2>
		<div id="smart_404_dashboard_widget_content">
			<script type="text/javascript">
				google.charts.load('current', {'packages':['bar','corechart']});
			</script>
			<div class="aiowps_dashboard_box_large">
				<div class="postbox">
					<h3 class="hndle"><label for="title"><?php _e($line_chart_widget_title, 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
					<div class="inside">
						<?php
						if (empty($last_x_days_404_data)) {
							echo '<div class="aio_yellow_box"><p>'.__('No data to display yet', 'all-in-one-wp-security-and-firewall-premium').'</p></div>';
						} else {
						?>
							<script type="text/javascript">
								google.charts.setOnLoadCallback(drawChartLine);
								function drawChartLine() {
									var data = google.visualization.arrayToDataTable([
										<?php echo $line_chart_data; ?>
									]);

									var options = {
										title: 'Top 5 countries - 404 events',
										height: '300'
									};

									var chart = new google.visualization.LineChart(document.getElementById('line_404_chart_div'));

									chart.draw(data, options);
								}
							</script>
							<div id='line_404_chart_div'></div>

						<?php
						}
						?>

					</div></div>
			</div>
			<div class="aiowps_dashboard_box_large">
				<div class="postbox">
					<h3 class="hndle"><label for="title"><?php _e('Last 10 days - 404 events by IP', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
					<div class="inside">
						<?php
						if (empty($last_x_days_404_data)) {
							echo '<div class="aio_yellow_box"><p>'.__('No data to display yet', 'all-in-one-wp-security-and-firewall-premium').'</p></div>';
						} else {
							$pt_src_chart_data3 = "['IP address', '404 count'],";
							foreach ($ip_top_10_count as $key => $value) {
								$pt_src_chart_data3 .= "['" . $key . "', " . $value . "],";
							}

							?>
							<script type="text/javascript">
								google.charts.setOnLoadCallback(drawChart);
								function drawChart() {
									var data = google.visualization.arrayToDataTable([
										<?php echo $pt_src_chart_data3; ?>
									]);

									var options = {
										height: '300',
										width: '550',
										backgroundColor: 'F6F6F6',
										colors: ['#990099']
									};

									var chart = new google.charts.Bar(document.getElementById('count_top_404_ip_chart_div'));
									chart.draw(data, options);
								}
							</script>
							<div id='count_top_404_ip_chart_div'></div>
						<?php
						}
						?>
					</div></div>
			</div>
			<div class="aiowps_dashboard_box_large">
				<div class="postbox">
			<h3 class="hndle"><label for="title"><?php _e('All time top 5 - 404 events by country', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
			<div class="inside">
				<?php
				if (empty($country_404_count)) {
					echo '<div class="aio_yellow_box"><p>'.__('No data to display yet', 'all-in-one-wp-security-and-firewall-premium').'</p></div>';
				} else {
					$pt_src_chart_data = "['Country', '404 count'],";
					foreach ($country_404_count as $key => $value) {
						$pt_src_chart_data .= "['" . $key . "', " . $value . "],";
					}
					?>
					<script type="text/javascript">
						//google.charts.load('current', {'packages':['bar']});
						google.charts.setOnLoadCallback(drawChart);
						function drawChart() {
							var data = google.visualization.arrayToDataTable([
								<?php echo $pt_src_chart_data; ?>
							]);

							var options = {
								height: '300',
								width: '550',
								backgroundColor: 'F6F6F6'
							};

							var chart = new google.charts.Bar(document.getElementById('count_404_chart_div'));
							chart.draw(data, options);
						}
					</script>
					<div id='count_404_chart_div'></div>
				<?php
				}
				?>
			</div></div>
			</div>
			<div class="aiowps_dashboard_box_large">
				<div class="postbox">
				<h3 class="hndle"><label for="title"><?php _e('All time top 10 - number of IPs blocked by country', 'all-in-one-wp-security-and-firewall-premium'); ?></label></h3>
				<div class="inside">
					<?php
					if (empty($data_blocked)) {
						echo '<div class="aio_yellow_box"><p>'.__('No data to display yet', 'all-in-one-wp-security-and-firewall-premium').'</p></div>';
					} else {
						$top10_blocked_ip_count = array_slice($blocked_ip_count, 0, 10, true); // get top 10
						$pt_src_chart_data2 = "['Country', '# blocked IPs'],";
						foreach ($top10_blocked_ip_count as $key => $value) {
							$pt_src_chart_data2 .= "['" . $key . "', " . $value . "],";
						}

						?>
						<script type="text/javascript">
							google.charts.setOnLoadCallback(drawChartBlocked);
							function drawChartBlocked() {
								var data = google.visualization.arrayToDataTable([
									<?php echo $pt_src_chart_data2; ?>
								]);

								var options = {
									height: '300',
									width: '550',
									backgroundColor: 'F6F6F6',
									colors: ['#dc3912']
								};

								var chart = new google.charts.Bar(document.getElementById('ips_blocked_chart_div'));
								chart.draw(data, options);
							}
						</script>
						<div id='ips_blocked_chart_div'></div>
					<?php
					}
					?>
				</div></div>
			</div>
		</div>
