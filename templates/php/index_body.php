<div class="row no-gutters" id="ModulePageTopRow">

    <div class="col-2 modules smoothwidth" id="ColumnModules">
		<div id="TopPluginIcon" class="pluginlogo_32">&nbsp;</div>
		<div class="nav flex-column">
		<?php foreach ( $aSummaryData as $nKey => $aSummary ) : ?>
			<a class="nav-link module <?php echo $aSummary[ 'active' ] ? 'active' : ''; ?>"
			   id="tab-<?php echo $aSummary[ 'slug' ]; ?>"
			   href="<?php echo $aSummary[ 'href' ]; ?>" role="tab">
				<?php if ( !$aSummary['enabled'] ) : ?>
					<div class="dashicons dashicons-warning"
						 title="This module is currently disabled"></div>
				<?php endif; ?>
				<div><?php echo $aSummary[ 'name' ]; ?></div>

			</a>
		<?php endforeach; ?>
		</div>
	</div>

    <div class="col" id="ColumnOptions">
		<?php
		if ( empty( $sFeatureInclude ) ) {
			$sFeatureInclude = 'feature-default';
		}
		include( $sBaseDirName.$sFeatureInclude );
		?>
	</div>
</div>