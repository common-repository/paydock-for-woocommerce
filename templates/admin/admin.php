<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly ?>
<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
	<?php foreach ( $tabs as $key => $value ) : ?>
		<a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=<?php echo esc_attr( $key ); ?>"
			class="nav-tab <?php echo $value['active'] ? 'nav-tab-active' : ''; ?>">
			<?php echo esc_html( $value['label'] ); ?>
		</a>
	<?php endforeach; ?>
</nav>
<?php if ( 'paydock_log' !== $this->currentSection ) : ?>
	<?php $templateService->settingService->parentGenerateSettingsHtml( $form_fields, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  --  the following require is safe it is not a user input. ?>
<?php else : ?>
	<table
		class="wp-list-table widefat fixed striped table-view-list orders wc-orders-list-table wc-orders-list-table-shop_order">
		<thead>
			<tr>
				<th scope="col" class="manage-column column-order_status sorted
			<?php if ( ! empty( $records['order'] ) && ( 'asc' == $records['order'] ) && ( 'id' == $records['orderBy'] ) ) : ?>
			  asc
			  <?php else : ?>
			  desc
			 <?php endif; ?>
			">
					<a href="
					<?php
					echo esc_url( add_query_arg( [ 
						'orderBy' => 'id',
						'order' => 'desc' == $records['order'] ? 'asc' : 'desc'
					] ) );
					?>
					">
						<span>ID</span>
						<span class="sorting-indicators">
							<span class="sorting-indicator asc" aria-hidden="true"></span>
							<span class="sorting-indicator desc" aria-hidden="true"></span>
						</span>
					</a>
				</th>
				<th scope="col" class="manage-column column-order_status sorted
			<?php if ( ! empty( $records['order'] ) && ( 'asc' == $records['order'] ) && ( 'id' == $records['orderBy'] ) ) : ?>
			  asc
			  <?php else : ?>
			  desc
			 <?php endif; ?>
			">
					<a href="
					<?php
					echo esc_url( add_query_arg( [ 
						'orderBy' => 'created_at',
						'order' => 'desc' == $records['order'] ? 'asc' : 'desc'
					] ) );
					?>
					">
						<span>Date</span>
						<span class="sorting-indicators">
							<span class="sorting-indicator asc" aria-hidden="true"></span>
							<span class="sorting-indicator desc" aria-hidden="true"></span>
						</span>
					</a>
				</th>
				<th scope="col" class="manage-column column-order_status sorted
			<?php if ( ! empty( $records['order'] ) && ( 'asc' == $records['order'] ) && ( 'id' == $records['orderBy'] ) ) : ?>
			  asc
			  <?php else : ?>
			  desc
			 <?php endif; ?>
			">
					<a href="
					<?php
					echo esc_url( add_query_arg( [ 
						'orderBy' => 'operation',
						'order' => 'desc' == $records['order'] ? 'asc' : 'desc'
					] ) );
					?>
					">
						<span>Operation</span>
						<span class="sorting-indicators">
							<span class="sorting-indicator asc" aria-hidden="true"></span>
							<span class="sorting-indicator desc" aria-hidden="true"></span>
						</span>
					</a>
				</th>
				<th scope="col" class="manage-column column-order_status sorted
			<?php if ( ! empty( $records['order'] ) && ( 'asc' == $records['order'] ) && ( 'id' == $records['orderBy'] ) ) : ?>
			  asc
			  <?php else : ?>
			  desc
			 <?php endif; ?>
			">
					<a href="
					<?php
					echo esc_url( add_query_arg( [ 
						'orderBy' => 'status',
						'order' => 'desc' == $records['order'] ? 'asc' : 'desc'
					] ) );
					?>
					">
						<span>Status</span>
						<span class="sorting-indicators">
							<span class="sorting-indicator asc" aria-hidden="true"></span>
							<span class="sorting-indicator desc" aria-hidden="true"></span>
						</span>
					</a>
				</th>
				<th scope="col" class="manage-column column-order_status">Message</th>
			</tr>
		</thead>
		<tbody id="the-list" data-wp-lists="list:order">
			<?php if ( empty( $records['data'] ) ) : ?>
				<tr class="no-items">
					<td class="colspanchange" colspan="3">No items found.</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $records['data'] as $record ) : ?>
					<tr>
						<td>
							<?php echo esc_html( $record->id ); ?>
						</td>
						<td class="order_date column-order_date">
							<?php echo esc_html( $record->created_at ); ?>
						</td>
						<td>
							<?php echo esc_html( $record->operation ); ?>
						</td>
						<td>
							<mark
								<?php if ( 1 == $record->type ) : ?>
									class="order-status status-processing tips"
								<?php elseif ( 2 == $record->type ) : ?>
									class="order-status status-on-hold tips"
								<?php else : ?>
									class="order-status status-pending tips"
								<?php endif; ?>
								>
								<span><?php echo esc_html( $record->status ); ?></span>
							</mark>
						</td>
						<td>
							<?php echo esc_html( $record->message ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<th scope="col" class="manage-column column-order_status sorted
			<?php if ( ! empty( $records['order'] ) && ( 'asc' == $records['order'] ) && ( 'id' == $records['orderBy'] ) ) : ?>
			  asc
			  <?php else : ?>
			  desc
			 <?php endif; ?>
			">
					<a href="
					<?php
					echo esc_url( add_query_arg( [ 
						'orderBy' => 'id',
						'order' => 'desc' == $records['order'] ? 'asc' : 'desc'
					] ) );
					?>
					">
						<span>ID</span>
						<span class="sorting-indicators">
							<span class="sorting-indicator asc" aria-hidden="true"></span>
							<span class="sorting-indicator desc" aria-hidden="true"></span>
						</span>
					</a>
				</th>
				<th scope="col" class="manage-column column-order_status sorted
			<?php if ( ! empty( $records['order'] ) && ( 'asc' == $records['order'] ) && ( 'id' == $records['orderBy'] ) ) : ?>
			  asc
			  <?php else : ?>
			  desc
			 <?php endif; ?>
			">
					<a href="
					<?php
					echo esc_url( add_query_arg( [ 
						'orderBy' => 'created_at',
						'order' => 'desc' == $records['order'] ? 'asc' : 'desc'
					] ) );
					?>
					">
						<span>Date</span>
						<span class="sorting-indicators">
							<span class="sorting-indicator asc" aria-hidden="true"></span>
							<span class="sorting-indicator desc" aria-hidden="true"></span>
						</span>
					</a>
				</th>
				<th scope="col" class="manage-column column-order_status sorted
			<?php if ( ! empty( $records['order'] ) && ( 'asc' == $records['order'] ) && ( 'id' == $records['orderBy'] ) ) : ?>
			  asc
			  <?php else : ?>
			  desc
			 <?php endif; ?>
			">
					<a href="
					<?php
					echo esc_url( add_query_arg( [ 
						'orderBy' => 'operation',
						'order' => 'desc' == $records['order'] ? 'asc' : 'desc'
					] ) );
					?>
					">
						<span>Operation</span>
						<span class="sorting-indicators">
							<span class="sorting-indicator asc" aria-hidden="true"></span>
							<span class="sorting-indicator desc" aria-hidden="true"></span>
						</span>
					</a>
				</th>
				<th scope="col" class="manage-column column-order_status sorted
			<?php if ( ! empty( $records['order'] ) && ( 'asc' == $records['order'] ) && ( 'id' == $records['orderBy'] ) ) : ?>
			  asc
			  <?php else : ?>
			  desc
			 <?php endif; ?>
			">
					<a href="
					<?php
					echo esc_url( add_query_arg( [ 
						'orderBy' => 'status',
						'order' => 'desc' == $records['order'] ? 'asc' : 'desc'
					] ) );
					?>
					">
						<span>Status</span>
						<span class="sorting-indicators">
							<span class="sorting-indicator asc" aria-hidden="true"></span>
							<span class="sorting-indicator desc" aria-hidden="true"></span>
						</span>
					</a>
				</th>
				<th scope="col" class="manage-column column-order_status">Message</th>
			</tr>
		</tfoot>
	</table>
	<div class="tablenav bottom">
		<div class="alignleft actions bulkactions">
		</div>
		<div class="alignleft actions"></div>
		<div class="tablenav-pages">
			<span class="displaying-num">
				<?php echo 'From ' . esc_html( $records['from'] ) . ' to ' . esc_html( $records['to'] ) . ' of ' . esc_html( $records['count'] ); ?>
				records
			</span>
			<span class="pagination-links">
				<?php if ( $records['current'] <= 1 ) : ?>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
				<?php else : ?>
					<a class="next-page button" href="<?php echo esc_url( add_query_arg( [ 'page_number' => 1 ] ) ); ?>">
						<span class="screen-reader-text">First page</span>
						<span aria-hidden="true">«</span>
					</a>
					<a class="last-page button"
						href="<?php echo esc_url( add_query_arg( [ 'page_number' => $records['current'] - 1 ] ) ); ?>">
						<span class="screen-reader-text">Prev page</span>
						<span aria-hidden="true">‹</span>
					</a>
				<?php endif; ?>
				<span class="screen-reader-text">Current Page</span>
				<span id="table-paging" class="paging-input">
					<span class="tablenav-paging-text">
						<?php echo esc_html( $records['current'] ); ?> of <span
							class="total-pages"><?php echo esc_html( $records['last_page'] ); ?></span>
					</span>
				</span>
				<?php if ( $records['current'] >= $records['last_page'] ) : ?>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
					<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
				<?php else : ?>
					<a class="next-page button"
						href="<?php echo esc_url( add_query_arg( [ 'page_number' => $records['current'] + 1 ] ) ); ?>">
						<span class="screen-reader-text">Next page</span>
						<span aria-hidden="true">›</span>
					</a>
					<a class="last-page button"
						href="<?php echo esc_url( add_query_arg( [ 'page_number' => $records['last_page'] ] ) ); ?>">
						<span class="screen-reader-text">Last page</span>
						<span aria-hidden="true">»</span>
					</a <?php endif; ?> </span>
		</div>
		<br class="clear">
	</div>
	<?php for ( $i = 1; $i <= $records['last_page']; $i++ ) : ?>

	<?php endfor; ?>
<?php endif; ?>
