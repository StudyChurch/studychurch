<?php
/**
 * The Sidebar containing the main widget areas.
 *
 * @package sc
 */

$study_id = sc_get_study_id();
?>
<div id="secondary" class="widget-area medium-3 small-12 columns sidebar" role="complementary">

	<dl class="accordion" data-accordion>
		<dd class="accordion-navigation">
			<a href="#study-nav">Study Navigation</a>
			<div id="study-nav" class="content">
				<ul class="table-of-contents">
					<?php sc_study_index(); ?>
				</ul>
			</div>
		</dd>
	</dl>


	<?php do_action( 'sc_study_sidebar_after', $study_id ); ?>
</div><!-- #secondary large-3-->
